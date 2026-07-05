<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Models\UnitOfMeasure;
use App\Services\DocumentSequenceService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    public function index(): View
    {
        return view('purchase.purchase_orders.index', [
            'records' => PurchaseOrder::with(['supplier', 'purchaseRequest'])->latest('id')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return $this->formView(new PurchaseOrder([
            'order_date' => now()->toDateString(),
            'status' => 'draft',
        ]));
    }

    public function createFromPr(PurchaseRequest $purchaseRequest): View
    {
        abort_if($purchaseRequest->status !== 'approved', 422, 'Only approved purchase requests can be converted to purchase orders.');

        $purchaseRequest->load(['lines.item', 'lines.unit']);
        $record = new PurchaseOrder([
            'purchase_request_id' => $purchaseRequest->id,
            'order_date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $record->setRelation('lines', $purchaseRequest->lines
            ->filter(fn ($line): bool => filled($line->item_id)
                && filled($line->unit_id)
                && (float) $line->quantity - (float) $line->converted_quantity > 0)
            ->values()
            ->map(function ($line) {
                $remainingQuantity = max(0, (float) $line->quantity - (float) $line->converted_quantity);

                return new \App\Models\PurchaseOrderLine([
                    'purchase_request_line_id' => $line->id,
                    'item_id' => $line->item_id,
                    'description' => $line->description ?: $line->item?->name,
                    'quantity' => $remainingQuantity,
                    'received_quantity' => 0,
                    'remaining_quantity' => $remainingQuantity,
                    'unit_id' => $line->unit_id,
                    'unit_price' => 0,
                    'tax_percent' => 0,
                    'subtotal' => 0,
                ]);
            }));

        return $this->formView($record, $purchaseRequest);
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $record = DB::transaction(function () use ($request): PurchaseOrder {
                $data = $this->validated($request);
                $lines = $data['lines'];
                unset($data['lines']);

                if (! blank($data['purchase_request_id'] ?? null)) {
                    $purchaseRequest = PurchaseRequest::with('lines')->lockForUpdate()->findOrFail($data['purchase_request_id']);
                    abort_if($purchaseRequest->status !== 'approved', 422, 'Only approved purchase requests can be converted to purchase orders.');
                }

                $totals = $this->totals($lines);
                $record = PurchaseOrder::create($data + [
                    'number' => app(DocumentSequenceService::class)->generate('PURCHASE_ORDER'),
                    'status' => 'draft',
                    'subtotal' => $totals['subtotal'],
                    'tax_total' => $totals['tax_total'],
                    'grand_total' => $totals['grand_total'],
                ]);

                $this->syncLines($record, $lines);

                return $record;
            });
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() === '23000') {
                return back()
                    ->withInput()
                    ->withErrors(['number' => 'Nomor dokumen sudah digunakan. Silakan ulangi proses.']);
            }

            throw $exception;
        }

        return redirect()->route('purchase-orders.show', $record)->with('status', 'Purchase order dibuat.');
    }

    public function show(PurchaseOrder $record): View
    {
        return view('purchase.purchase_orders.show', [
            'record' => $record->load(['supplier', 'purchaseRequest', 'lines.item', 'lines.unit', 'lines.purchaseRequestLine', 'approvalLogs.user']),
        ]);
    }

    public function edit(PurchaseOrder $record): View
    {
        abort_if($record->status !== 'draft', 422, 'Only draft purchase orders can be edited.');

        return $this->formView($record->load('lines'));
    }

    public function update(Request $request, PurchaseOrder $record): RedirectResponse
    {
        abort_if($record->status !== 'draft', 422, 'Only draft purchase orders can be edited.');

        DB::transaction(function () use ($request, $record): void {
            $data = $this->validated($request);
            $lines = $data['lines'];
            unset($data['lines']);

            $totals = $this->totals($lines);
            $record->update($data + $totals);
            $this->syncLines($record, $lines);
        });

        return redirect()->route('purchase-orders.show', $record)->with('status', 'Purchase order diperbarui.');
    }

    public function destroy(PurchaseOrder $record): RedirectResponse
    {
        abort_if($record->status !== 'draft', 422, 'Only draft purchase orders can be deleted.');
        $record->delete();

        return redirect()->route('purchase-orders.index')->with('status', 'Purchase order dihapus.');
    }

    public function submit(PurchaseOrder $record): RedirectResponse
    {
        abort_if($record->status !== 'draft', 422, 'Only draft purchase orders can be submitted.');
        $this->setStatus($record, 'submitted', 'submit');

        return back()->with('status', 'Purchase order submitted.');
    }

    public function approve(PurchaseOrder $record): RedirectResponse
    {
        abort_if($record->status !== 'submitted', 422, 'Only submitted purchase orders can be approved.');
        $this->setStatus($record, 'approved', 'approve');

        return back()->with('status', 'Purchase order approved.');
    }

    public function reject(PurchaseOrder $record): RedirectResponse
    {
        abort_if($record->status !== 'submitted', 422, 'Only submitted purchase orders can be rejected.');
        $this->setStatus($record, 'cancelled', 'reject');

        return back()->with('status', 'Purchase order rejected.');
    }

    public function cancel(PurchaseOrder $record): RedirectResponse
    {
        abort_if(! in_array($record->status, ['draft', 'submitted', 'approved'], true), 422, 'Purchase order cannot be cancelled.');
        $this->setStatus($record, 'cancelled', 'cancel');

        return back()->with('status', 'Purchase order cancelled.');
    }

    private function formView(PurchaseOrder $record, ?PurchaseRequest $purchaseRequest = null): View
    {
        return view('purchase.purchase_orders.'.($record->exists ? 'edit' : 'create'), [
            'record' => $record,
            'purchaseRequest' => $purchaseRequest ?: $record->purchaseRequest,
            'items' => Item::with('unitOfMeasure')->where('is_active', true)->orderBy('name')->get(),
            'units' => UnitOfMeasure::where('is_active', true)->orderBy('name')->get(),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    private function validated(Request $request): array
    {
        $request->merge([
            'lines' => $this->validLines($request->input('lines', [])),
        ]);

        if (count($request->input('lines', [])) === 0) {
            throw ValidationException::withMessages([
                'lines' => 'At least one valid item line is required.',
            ]);
        }

        return $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'purchase_request_id' => ['nullable', 'exists:purchase_requests,id'],
            'order_date' => ['required', 'date'],
            'expected_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.purchase_request_line_id' => ['nullable', 'exists:purchase_request_lines,id'],
            'lines.*.item_id' => ['required', 'exists:items,id'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_id' => ['required', 'exists:units_of_measure,id'],
            'lines.*.unit_price' => ['required', 'numeric', 'gte:0'],
            'lines.*.tax_percent' => ['nullable', 'numeric', 'gte:0'],
        ]);
    }

    private function validLines(mixed $lines): array
    {
        if (! is_array($lines)) {
            return [];
        }

        return collect($lines)
            ->filter(fn ($line): bool => is_array($line)
                && filled($line['item_id'] ?? null)
                && (float) ($line['quantity'] ?? 0) > 0)
            ->values()
            ->all();
    }

    private function syncLines(PurchaseOrder $record, array $lines): void
    {
        foreach ($record->lines()->whereNotNull('purchase_request_line_id')->get() as $oldLine) {
            $oldLine->purchaseRequestLine?->decrement('converted_quantity', $oldLine->quantity);
        }

        $record->lines()->delete();

        foreach ($lines as $line) {
            if (blank($line['item_id'] ?? null) || (float) ($line['quantity'] ?? 0) <= 0) {
                continue;
            }

            if (! blank($line['purchase_request_line_id'] ?? null)) {
                $requestLine = \App\Models\PurchaseRequestLine::lockForUpdate()->findOrFail($line['purchase_request_line_id']);
                $available = (float) $requestLine->quantity - (float) $requestLine->converted_quantity;

                if ((float) $line['quantity'] > $available) {
                    throw ValidationException::withMessages([
                        'lines' => 'PO quantity cannot exceed remaining purchase request quantity.',
                    ]);
                }

                $requestLine->increment('converted_quantity', $line['quantity']);
            }

            $subtotal = (float) $line['quantity'] * (float) $line['unit_price'];

            $record->lines()->create([
                'purchase_request_line_id' => $line['purchase_request_line_id'] ?? null,
                'item_id' => $line['item_id'],
                'description' => $line['description'] ?? null,
                'quantity' => $line['quantity'],
                'received_quantity' => 0,
                'remaining_quantity' => $line['quantity'],
                'unit_id' => $line['unit_id'],
                'unit_price' => $line['unit_price'],
                'tax_percent' => $line['tax_percent'] ?? 0,
                'subtotal' => $subtotal,
            ]);
        }
    }

    private function totals(array $lines): array
    {
        $subtotal = 0;
        $taxTotal = 0;

        foreach ($lines as $line) {
            $lineSubtotal = (float) ($line['quantity'] ?? 0) * (float) ($line['unit_price'] ?? 0);
            $subtotal += $lineSubtotal;
            $taxTotal += $lineSubtotal * ((float) ($line['tax_percent'] ?? 0) / 100);
        }

        return [
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'grand_total' => $subtotal + $taxTotal,
        ];
    }

    private function setStatus(PurchaseOrder $record, string $status, string $action): void
    {
        DB::transaction(function () use ($record, $status, $action): void {
            $record->update(['status' => $status]);
            $record->approvalLogs()->create([
                'action' => $action,
                'user_id' => Auth::id(),
            ]);
        });
    }
}
