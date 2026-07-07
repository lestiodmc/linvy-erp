<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
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
    public function index(Request $request): View
    {
        $filters = $this->indexFilters($request);
        $branches = $this->accessibleBranches();
        $branchIds = $branches->pluck('id');

        $records = PurchaseOrder::with(['supplier', 'purchaseRequest', 'branch'])
            ->when(! Auth::user()?->isSuperAdmin(), fn ($query) => $query->where(function ($branchQuery) use ($branchIds): void {
                $branchQuery->whereNull('branch_id')->orWhereIn('branch_id', $branchIds);
            }))
            ->when(filled($filters['keyword'] ?? null), function ($query) use ($filters): void {
                $keyword = $filters['keyword'];

                $query->where(function ($search) use ($keyword): void {
                    $search->where('number', 'like', '%'.$keyword.'%')
                        ->orWhereHas('supplier', fn ($supplier) => $supplier->where('name', 'like', '%'.$keyword.'%'))
                        ->orWhereHas('purchaseRequest', fn ($purchaseRequest) => $purchaseRequest->where('number', 'like', '%'.$keyword.'%'));
                });
            })
            ->when(filled($filters['date_from'] ?? null), fn ($query) => $query->whereDate('order_date', '>=', $filters['date_from']))
            ->when(filled($filters['date_to'] ?? null), fn ($query) => $query->whereDate('order_date', '<=', $filters['date_to']))
            ->when(filled($filters['status'] ?? null), fn ($query) => $query->where('status', $filters['status']))
            ->when(filled($filters['branch_id'] ?? null), fn ($query) => $query->where('branch_id', $filters['branch_id']))
            ->when(filled($filters['supplier_id'] ?? null), fn ($query) => $query->where('supplier_id', $filters['supplier_id']))
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('purchase.purchase_orders.index', [
            'records' => $records,
            'filters' => $filters,
            'statuses' => PurchaseOrder::STATUSES,
            'branches' => $branches,
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }

    public function create(): View
    {
        return $this->formView(new PurchaseOrder([
            'order_date' => now()->toDateString(),
            'status' => PurchaseOrder::STATUS_DRAFT,
        ]));
    }

    public function createFromPr(PurchaseRequest $purchaseRequest): View
    {
        abort_if($purchaseRequest->status !== PurchaseRequest::STATUS_APPROVED, 422, 'Only approved purchase requests can be converted to purchase orders.');

        $purchaseRequest->load(['lines.item', 'lines.unit']);
        $record = new PurchaseOrder([
            'purchase_request_id' => $purchaseRequest->id,
            'company_id' => $purchaseRequest->company_id,
            'branch_id' => $purchaseRequest->branch_id,
            'order_date' => now()->toDateString(),
            'status' => PurchaseOrder::STATUS_DRAFT,
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
                    abort_if($purchaseRequest->status !== PurchaseRequest::STATUS_APPROVED, 422, 'Only approved purchase requests can be converted to purchase orders.');
                    $data['company_id'] = $purchaseRequest->company_id;
                    $data['branch_id'] = $purchaseRequest->branch_id;
                } else {
                    $branch = $this->currentBranch();
                    $data['company_id'] = $branch?->company_id;
                    $data['branch_id'] = $branch?->id;
                }

                $totals = $this->totals($lines);
                $record = PurchaseOrder::create($data + [
                    'number' => app(DocumentSequenceService::class)->generate('PURCHASE_ORDER', $data['company_id'], $data['branch_id']),
                    'status' => PurchaseOrder::STATUS_DRAFT,
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
        abort_if($record->status !== PurchaseOrder::STATUS_DRAFT, 422, 'Only draft purchase orders can be edited.');

        return $this->formView($record->load('lines'));
    }

    public function update(Request $request, PurchaseOrder $record): RedirectResponse
    {
        abort_if($record->status !== PurchaseOrder::STATUS_DRAFT, 422, 'Only draft purchase orders can be edited.');

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
        abort_if($record->status !== PurchaseOrder::STATUS_DRAFT, 422, 'Only draft purchase orders can be deleted.');
        $record->delete();

        return redirect()->route('purchase-orders.index')->with('status', 'Purchase order dihapus.');
    }

    public function submit(PurchaseOrder $record): RedirectResponse
    {
        abort_if($record->status !== PurchaseOrder::STATUS_DRAFT, 422, 'Only draft purchase orders can be submitted.');
        $this->setStatus($record, PurchaseOrder::STATUS_SUBMITTED, 'submit');

        return back()->with('status', 'Purchase order submitted.');
    }

    public function approve(PurchaseOrder $record): RedirectResponse
    {
        abort_if($record->status !== PurchaseOrder::STATUS_SUBMITTED, 422, 'Only submitted purchase orders can be approved.');
        $this->setStatus($record, PurchaseOrder::STATUS_APPROVED, 'approve');

        return back()->with('status', 'Purchase order approved.');
    }

    public function reject(PurchaseOrder $record): RedirectResponse
    {
        abort_if($record->status !== PurchaseOrder::STATUS_SUBMITTED, 422, 'Only submitted purchase orders can be rejected.');
        $this->setStatus($record, PurchaseOrder::STATUS_CANCELLED, 'reject');

        return back()->with('status', 'Purchase order rejected.');
    }

    public function cancel(PurchaseOrder $record): RedirectResponse
    {
        abort_if(! in_array($record->status, [PurchaseOrder::STATUS_DRAFT, PurchaseOrder::STATUS_SUBMITTED, PurchaseOrder::STATUS_APPROVED], true), 422, 'Purchase order cannot be cancelled.');
        $this->setStatus($record, PurchaseOrder::STATUS_CANCELLED, 'cancel');

        return back()->with('status', 'Purchase order cancelled.');
    }

    private function formView(PurchaseOrder $record, ?PurchaseRequest $purchaseRequest = null): View
    {
        return view('purchase.purchase_orders.'.($record->exists ? 'edit' : 'create'), [
            'record' => $record,
            'purchaseRequest' => $purchaseRequest ?: $record->purchaseRequest,
            'selectedItems' => $this->selectedItemOptions($record),
            'units' => UnitOfMeasure::where('is_active', true)->orderBy('name')->get(),
            'selectedSupplier' => $this->selectedSupplierOption($record),
        ]);
    }

    private function selectedItemOptions(PurchaseOrder $record): array
    {
        $lines = session()->getOldInput('lines', $record->lines->toArray());
        $ids = collect($lines)->pluck('item_id')->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return Item::with(['unitOfMeasure:id,code,name', 'purchaseUnit:id,code,name', 'baseUnit:id,code,name'])
            ->whereIn('id', $ids)
            ->get(['id', 'sku', 'name', 'unit_of_measure_id', 'base_unit_id', 'purchase_unit_id'])
            ->mapWithKeys(function (Item $item): array {
                $unit = $item->purchaseUnit ?: ($item->unitOfMeasure ?: $item->baseUnit);

                return [
                    $item->id => [
                        'text' => trim(($item->sku ? $item->sku.' - ' : '').$item->name),
                        'unit_id' => $unit?->id,
                    ],
                ];
            })
            ->all();
    }

    private function selectedSupplierOption(PurchaseOrder $record): array
    {
        $supplierId = session()->getOldInput('supplier_id', $record->supplier_id);

        if (! $supplierId) {
            return [];
        }

        $supplier = Supplier::find($supplierId);

        if (! $supplier) {
            return [];
        }

        return [
            'id' => $supplier->id,
            'text' => trim(($supplier->code ? $supplier->code.' - ' : '').$supplier->name),
        ];
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
            $record = PurchaseOrder::whereKey($record->id)->lockForUpdate()->firstOrFail();
            $record->update(['status' => $status]);
            $record->approvalLogs()->create([
                'action' => $action,
                'user_id' => Auth::id(),
            ]);
        });
    }

    private function currentBranch(): ?Branch
    {
        return Branch::where('is_active', true)->orderBy('id')->first();
    }

    private function indexFilters(Request $request): array
    {
        $filters = $request->only(['keyword', 'date_from', 'date_to', 'status', 'branch_id', 'supplier_id']);

        if (! $request->has('date_from')) {
            $filters['date_from'] = now()->startOfMonth()->toDateString();
        }

        if (! $request->has('date_to')) {
            $filters['date_to'] = now()->toDateString();
        }

        return $filters;
    }

    private function accessibleBranches(): \Illuminate\Support\Collection
    {
        $query = Branch::where('is_active', true)->orderBy('name');

        if (! Auth::user()?->isSuperAdmin()) {
            $query->whereHas('users', fn ($branchQuery) => $branchQuery->whereKey(Auth::id()));
        }

        return $query->get();
    }
}
