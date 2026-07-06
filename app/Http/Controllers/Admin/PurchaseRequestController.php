<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Item;
use App\Models\PurchaseRequest;
use App\Models\UnitOfMeasure;
use App\Services\DocumentSequenceService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PurchaseRequestController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->indexFilters($request);

        $records = PurchaseRequest::with(['requester', 'branch'])
            ->when(filled($filters['keyword'] ?? null), function ($query) use ($filters): void {
                $keyword = $filters['keyword'];

                $query->where(function ($search) use ($keyword): void {
                    $search->where('number', 'like', '%'.$keyword.'%')
                        ->orWhere('department', 'like', '%'.$keyword.'%')
                        ->orWhereHas('requester', fn ($user) => $user->where('name', 'like', '%'.$keyword.'%'));
                });
            })
            ->when(filled($filters['date_from'] ?? null), fn ($query) => $query->whereDate('request_date', '>=', $filters['date_from']))
            ->when(filled($filters['date_to'] ?? null), fn ($query) => $query->whereDate('request_date', '<=', $filters['date_to']))
            ->when(filled($filters['status'] ?? null), fn ($query) => $query->where('status', $filters['status']))
            ->orderByDesc('request_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('purchase.purchase_requests.index', [
            'records' => $records,
            'filters' => $filters,
            'statuses' => ['draft', 'submitted', 'approved', 'rejected', 'closed', 'cancelled'],
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
        try {
            $record = DB::transaction(function () use ($request): PurchaseRequest {
                $data = $this->validated($request);
                $lines = $data['lines'];
                unset($data['lines']);
                $branch = $this->currentBranch();
                $data['company_id'] = $branch?->company_id;
                $data['branch_id'] = $branch?->id;
                $data['number'] = app(DocumentSequenceService::class)->generate('PURCHASE_REQUEST', $data['company_id'], $data['branch_id']);
                $data['status'] = 'draft';
                $data['requested_by'] = Auth::id();

                $record = PurchaseRequest::create($data);
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
            'selectedItems' => $this->selectedItemOptions($record),
            'units' => UnitOfMeasure::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    private function selectedItemOptions(PurchaseRequest $record): array
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
            $record = PurchaseRequest::whereKey($record->id)->lockForUpdate()->firstOrFail();
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
        $filters = $request->only(['keyword', 'date_from', 'date_to', 'status']);

        if (! $request->has('date_from')) {
            $filters['date_from'] = now()->startOfMonth()->toDateString();
        }

        if (! $request->has('date_to')) {
            $filters['date_to'] = now()->toDateString();
        }

        return $filters;
    }
}
