<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\PurchaseRequest;
use App\Models\UnitOfMeasure;
use App\Services\DocumentNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PurchaseRequestController extends Controller
{
    public function index(): View
    {
        return view('purchase.purchase_requests.index', [
            'records' => PurchaseRequest::with(['requester'])->latest('id')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return $this->formView(new PurchaseRequest([
            'request_date' => now()->toDateString(),
            'requested_by' => Auth::id(),
            'status' => 'draft',
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $record = DB::transaction(function () use ($request): PurchaseRequest {
            $data = $this->validated($request);
            $lines = $data['lines'];
            unset($data['lines']);
            $data['number'] = app(DocumentNumberService::class)->generate('PR');
            $data['status'] = 'draft';
            $data['requested_by'] = Auth::id();

            $record = PurchaseRequest::create($data);
            $this->syncLines($record, $lines);

            return $record;
        });

        return redirect()->route('purchase-requests.show', $record)->with('status', 'Purchase request dibuat.');
    }

    public function show(PurchaseRequest $record): View
    {
        return view('purchase.purchase_requests.show', [
            'record' => $record->load(['requester', 'lines.item', 'lines.unit', 'approvalLogs.user']),
        ]);
    }

    public function edit(PurchaseRequest $record): View
    {
        abort_if($record->status !== 'draft', 422, 'Only draft purchase requests can be edited.');

        return $this->formView($record->load('lines'));
    }

    public function update(Request $request, PurchaseRequest $record): RedirectResponse
    {
        abort_if($record->status !== 'draft', 422, 'Only draft purchase requests can be edited.');

        DB::transaction(function () use ($request, $record): void {
            $data = $this->validated($request);
            $lines = $data['lines'];
            unset($data['lines']);

            $record->update($data);
            $this->syncLines($record, $lines);
        });

        return redirect()->route('purchase-requests.show', $record)->with('status', 'Purchase request diperbarui.');
    }

    public function destroy(PurchaseRequest $record): RedirectResponse
    {
        abort_if($record->status !== 'draft', 422, 'Only draft purchase requests can be deleted.');
        $record->delete();

        return redirect()->route('purchase-requests.index')->with('status', 'Purchase request dihapus.');
    }

    public function submit(PurchaseRequest $record): RedirectResponse
    {
        abort_if($record->status !== 'draft', 422, 'Only draft purchase requests can be submitted.');

        $this->setStatus($record, 'submitted', 'submit');

        return back()->with('status', 'Purchase request submitted.');
    }

    public function approve(PurchaseRequest $record): RedirectResponse
    {
        abort_if($record->status !== 'submitted', 422, 'Only submitted purchase requests can be approved.');

        $this->setStatus($record, 'approved', 'approve');

        return back()->with('status', 'Purchase request approved.');
    }

    public function reject(PurchaseRequest $record): RedirectResponse
    {
        abort_if($record->status !== 'submitted', 422, 'Only submitted purchase requests can be rejected.');

        $this->setStatus($record, 'rejected', 'reject');

        return back()->with('status', 'Purchase request rejected.');
    }

    public function cancel(PurchaseRequest $record): RedirectResponse
    {
        abort_if(! in_array($record->status, ['draft', 'submitted', 'approved'], true), 422, 'Purchase request cannot be cancelled.');

        $this->setStatus($record, 'cancelled', 'cancel');

        return back()->with('status', 'Purchase request cancelled.');
    }

    public function close(PurchaseRequest $record): RedirectResponse
    {
        abort_if($record->status !== 'approved', 422, 'Only approved purchase requests can be closed.');

        $this->setStatus($record, 'closed', 'close');

        return back()->with('status', 'Purchase request closed.');
    }

    private function formView(PurchaseRequest $record): View
    {
        return view('purchase.purchase_requests.'.($record->exists ? 'edit' : 'create'), [
            'record' => $record,
            'items' => Item::with('unitOfMeasure')->where('is_active', true)->orderBy('name')->get(),
            'units' => UnitOfMeasure::where('is_active', true)->orderBy('name')->get(),
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
            'request_date' => ['required', 'date'],
            'department' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'exists:items,id'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_id' => ['required', 'exists:units_of_measure,id'],
            'lines.*.required_date' => ['nullable', 'date'],
            'lines.*.notes' => ['nullable', 'string'],
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

    private function syncLines(PurchaseRequest $record, array $lines): void
    {
        $record->lines()->delete();

        foreach ($lines as $line) {
            if (blank($line['item_id'] ?? null) || (float) ($line['quantity'] ?? 0) <= 0) {
                continue;
            }

            $record->lines()->create([
                'item_id' => $line['item_id'],
                'description' => $line['description'] ?? null,
                'quantity' => $line['quantity'],
                'unit_id' => $line['unit_id'],
                'required_date' => $line['required_date'] ?? null,
                'notes' => $line['notes'] ?? null,
                'converted_quantity' => 0,
            ]);
        }
    }

    private function setStatus(PurchaseRequest $record, string $status, string $action): void
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
