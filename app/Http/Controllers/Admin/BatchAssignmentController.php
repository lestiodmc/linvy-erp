<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BatchAssignmentRequest;
use App\Models\BatchAssignment;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Inventory\StockBalance;
use App\Models\Inventory\StockBatchBalance;
use App\Models\Item;
use App\Models\Warehouse;
use App\Services\Inventory\BatchAssignmentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BatchAssignmentController extends Controller
{
    public function __construct(private readonly BatchAssignmentService $service) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['keyword', 'company_id', 'branch_id', 'warehouse_id', 'status']);
        $accessibleBranches = $this->accessibleBranches();
        $companyIds = $accessibleBranches->pluck('company_id')->filter()->unique();
        $companyId = filled($filters['company_id'] ?? null) && $companyIds->contains((int) $filters['company_id']) ? (int) $filters['company_id'] : null;
        $branchId = filled($filters['branch_id'] ?? null) && $accessibleBranches->contains(fn (Branch $branch) => (int) $branch->id === (int) $filters['branch_id'] && (! $companyId || (int) $branch->company_id === $companyId)) ? (int) $filters['branch_id'] : null;
        $warehouse = filled($filters['warehouse_id'] ?? null) ? $this->accessibleWarehouse((int) $filters['warehouse_id']) : null;
        if ($warehouse && (($companyId && (int) ($warehouse->company_id ?: $warehouse->branch?->company_id) !== $companyId) || ($branchId && (int) $warehouse->branch_id !== $branchId))) $warehouse = null;
        $filters['company_id'] = $companyId;
        $filters['branch_id'] = $branchId;
        $filters['warehouse_id'] = $warehouse?->id;
        $records = BatchAssignment::query()->with(['branch', 'warehouse', 'createdBy', 'lines.item'])->withCount('lines')
            ->whereIn('branch_id', $accessibleBranches->pluck('id'))
            ->whereHas('warehouse', fn (Builder $query) => $query->whereIn('branch_id', $accessibleBranches->pluck('id')))
            ->when($companyId, fn (Builder $query) => $query->where('company_id', $companyId))
            ->when(filled($filters['keyword'] ?? null), fn (Builder $query) => $query->where(fn (Builder $search) => $search->where('number', 'like', '%'.$filters['keyword'].'%')->orWhereHas('lines.item', fn (Builder $item) => $item->where('sku', 'like', '%'.$filters['keyword'].'%')->orWhere('name', 'like', '%'.$filters['keyword'].'%'))))
            ->when(filled($filters['branch_id'] ?? null), fn (Builder $query) => $query->where('branch_id', $filters['branch_id']))
            ->when(filled($filters['warehouse_id'] ?? null), fn (Builder $query) => $query->where('warehouse_id', $filters['warehouse_id']))
            ->when(filled($filters['status'] ?? null), fn (Builder $query) => $query->where('status', $filters['status']))
            ->orderByDesc('assignment_date')->orderByDesc('id')->paginate(15)->withQueryString();

        return view('inventory.batch_assignments.index', ['records' => $records, 'filters' => $filters, 'companies' => Company::query()->whereIn('id', $companyIds)->orderBy('name')->get(), 'branches' => $companyId ? $accessibleBranches->where('company_id', $companyId)->values() : $accessibleBranches, 'warehouses' => $branchId ? $this->warehouses($branchId, $companyId) : ($companyId ? $this->warehouses(null, $companyId) : $this->warehouses()), 'statuses' => BatchAssignment::STATUSES]);
    }

    public function create(): View
    {
        return $this->form(new BatchAssignment(['assignment_date' => now()->toDateString(), 'status' => BatchAssignment::STATUS_DRAFT]));
    }

    public function store(BatchAssignmentRequest $request): RedirectResponse
    {
        $data = $request->validated(); $this->validateAccess($data); $this->validateDraftLines($data); $record = $this->service->create($data);
        if (($data['action'] ?? 'draft') === 'post') $this->service->post($record);
        return redirect()->route('batch-assignments.show', $record)->with('status', 'Batch assignment saved.');
    }

    public function show(BatchAssignment $batchAssignment): View
    {
        $this->ensureAccess($batchAssignment); $batchAssignment->load(['company', 'branch', 'warehouse', 'createdBy', 'postedBy', 'lines.item.baseUnit', 'lines.unit']);
        return view('inventory.batch_assignments.show', ['record' => $batchAssignment, 'preview' => $this->preview($batchAssignment)]);
    }

    public function edit(BatchAssignment $batchAssignment): View
    {
        $this->ensureAccess($batchAssignment); abort_unless($batchAssignment->isDraft(), 422, 'Only draft assignments can be edited.');
        return $this->form($batchAssignment->load('lines.item'));
    }

    public function update(BatchAssignmentRequest $request, BatchAssignment $batchAssignment): RedirectResponse
    {
        $this->ensureAccess($batchAssignment); $data = $request->validated(); $this->validateAccess($data); $this->validateDraftLines($data); $record = $this->service->update($batchAssignment, $data);
        if (($data['action'] ?? 'draft') === 'post') $this->service->post($record);
        return redirect()->route('batch-assignments.show', $record)->with('status', 'Batch assignment updated.');
    }

    public function post(BatchAssignment $batchAssignment): RedirectResponse { $this->ensureAccess($batchAssignment); $this->service->post($batchAssignment); return back()->with('status', 'Batch assignment posted.'); }
    public function cancel(BatchAssignment $batchAssignment): RedirectResponse { $this->ensureAccess($batchAssignment); $this->service->cancel($batchAssignment); return back()->with('status', 'Batch assignment cancelled.'); }

    public function eligibleItems(Request $request): JsonResponse
    {
        $data = $request->validate(['company_id' => ['required', 'exists:companies,id'], 'branch_id' => ['required', 'exists:branches,id'], 'warehouse_id' => ['required', 'exists:warehouses,id']]);
        $warehouse = $this->accessibleWarehouse((int) $data['warehouse_id']);
        if (! $warehouse || (int) $warehouse->branch_id !== (int) $data['branch_id'] || (int) ($warehouse->company_id ?: $warehouse->branch?->company_id) !== (int) $data['company_id']) abort(403);
        $batchTotals = StockBatchBalance::query()->selectRaw('warehouse_id, item_id, SUM(qty_on_hand) batch_total')->groupBy('warehouse_id', 'item_id');
        $rows = StockBalance::query()->select('stock_balances.*')->selectRaw('COALESCE(batch_totals.batch_total,0) batch_total')->leftJoinSub($batchTotals, 'batch_totals', fn ($join) => $join->on('batch_totals.warehouse_id', '=', 'stock_balances.warehouse_id')->on('batch_totals.item_id', '=', 'stock_balances.item_id'))->with('item.baseUnit')->where('stock_balances.company_id', $data['company_id'])->where('stock_balances.branch_id', $data['branch_id'])->where('stock_balances.warehouse_id', $warehouse->id)->whereHas('item', fn (Builder $item) => $item->where('track_inventory', true)->where('is_batch_tracked', true))->get()->map(fn (StockBalance $row) => ['item_id' => $row->item_id, 'sku' => $row->item?->sku, 'name' => $row->item?->name, 'uom_id' => $row->item?->base_unit_id, 'uom' => $row->item?->baseUnit?->code, 'warehouse_total' => $this->onHand($row), 'batch_total' => (float) $row->batch_total, 'unallocated_qty' => $this->onHand($row) - (float) $row->batch_total])->filter(fn ($row) => $row['unallocated_qty'] > 0.000001)->values();
        return response()->json($rows);
    }

    public function batches(Request $request): JsonResponse
    {
        $data = $request->validate(['warehouse_id' => ['required', 'exists:warehouses,id'], 'item_id' => ['required', 'exists:items,id']]); if (! $this->accessibleWarehouse((int) $data['warehouse_id'])) abort(403);
        return response()->json(StockBatchBalance::query()->where('warehouse_id', $data['warehouse_id'])->where('item_id', $data['item_id'])->where('qty_on_hand', '!=', 0)->orderBy('batch_no')->get(['batch_no', 'expiry_date', 'qty_on_hand']));
    }

    public function warehouseOptions(Request $request): JsonResponse
    {
        $data = $request->validate(['company_id' => ['nullable', 'integer', 'exists:companies,id'], 'branch_id' => ['required', 'integer', 'exists:branches,id']]);
        $branchId = (int) $data['branch_id'];
        $branch = $this->accessibleBranches()->firstWhere('id', $branchId);
        $companyId = (int) ($data['company_id'] ?? $branch?->company_id);

        abort_unless($branch && (int) $branch->company_id === $companyId, 403);

        return response()->json(
            $this->warehouses($branchId, $companyId)->map(fn (Warehouse $warehouse): array => [
                'id' => $warehouse->id,
                'label' => trim(($warehouse->branch?->name ? $warehouse->branch->name.' - ' : '').$warehouse->name),
            ])->values()
        );
    }

    public function branchOptions(Request $request): JsonResponse
    {
        $companyId = (int) $request->validate(['company_id' => ['required', 'integer', 'exists:companies,id']])['company_id'];
        $branches = $this->accessibleBranches()->where('company_id', $companyId)->values();
        abort_if($branches->isEmpty(), 403);

        return response()->json($branches->map(fn (Branch $branch): array => ['id' => $branch->id, 'name' => $branch->name]));
    }

    private function form(BatchAssignment $record): View { $accessibleBranches = $this->accessibleBranches(); $companies = Company::query()->whereIn('id', $accessibleBranches->pluck('company_id'))->orderBy('name')->get(); $selectedCompanyId = (int) old('company_id', $record->company_id ?: ($companies->count() === 1 ? $companies->first()->id : 0)); $companyBranches = $selectedCompanyId ? $accessibleBranches->where('company_id', $selectedCompanyId)->values() : collect(); $selectedBranchId = (int) old('branch_id', $record->branch_id ?: ($companyBranches->count() === 1 ? $companyBranches->first()->id : 0)); return view('inventory.batch_assignments.form', ['record' => $record, 'companies' => $companies, 'selectedCompanyId' => $selectedCompanyId, 'branches' => $companyBranches, 'selectedBranchId' => $selectedBranchId, 'warehouses' => $selectedBranchId ? $this->warehouses($selectedBranchId, $selectedCompanyId) : collect()]); }
    private function accessibleBranches() { return Branch::query()->with('company')->where('is_active', true)->when(! Auth::user()?->isSuperAdmin(), fn (Builder $query) => $query->whereHas('users', fn (Builder $users) => $users->whereKey(Auth::id())))->orderBy('name')->get(); }
    private function warehouses(?int $branchId = null, ?int $companyId = null) { return Warehouse::query()->with(['branch', 'company'])->where('is_active', true)->whereIn('branch_id', $this->accessibleBranches()->pluck('id'))->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))->when($companyId, fn (Builder $query) => $query->where(fn (Builder $scope) => $scope->where('company_id', $companyId)->orWhere(fn (Builder $legacy) => $legacy->whereNull('company_id')->whereHas('branch', fn (Builder $branch) => $branch->where('company_id', $companyId)))))->orderBy('branch_id')->orderBy('name')->get(); }
    private function accessibleWarehouse(int $id): ?Warehouse { return $this->warehouses()->firstWhere('id', $id); }
    private function ensureAccess(BatchAssignment $record): void { abort_unless($this->accessibleBranches()->contains('id', $record->branch_id) && (int) $record->warehouse?->branch_id === (int) $record->branch_id, 403); }
    private function validateAccess(array $data): void { $branch = $this->accessibleBranches()->firstWhere('id', (int) $data['branch_id']); $warehouse = $this->accessibleWarehouse((int) $data['warehouse_id']); if (! $branch || (int) $branch->company_id !== (int) $data['company_id']) throw ValidationException::withMessages(['branch_id' => 'Branch is not accessible or does not belong to the selected company.']); if (! $warehouse || (int) $warehouse->branch_id !== (int) $branch->id || (int) ($warehouse->company_id ?: $warehouse->branch?->company_id) !== (int) $data['company_id']) throw ValidationException::withMessages(['warehouse_id' => 'Warehouse is not accessible or does not belong to the selected company and branch.']); }
    private function validateDraftLines(array $data): void { $requested = collect($data['lines'])->groupBy('item_id')->map(fn ($lines) => (float) $lines->sum('quantity')); foreach ($requested as $itemId => $quantity) { $balance = StockBalance::query()->where('warehouse_id', $data['warehouse_id'])->where('item_id', $itemId)->first(); $warehouseTotal = $balance ? $this->onHand($balance) : 0; $batchTotal = (float) StockBatchBalance::query()->where('warehouse_id', $data['warehouse_id'])->where('item_id', $itemId)->sum('qty_on_hand'); if ($quantity > $warehouseTotal - $batchTotal + 0.000001) throw ValidationException::withMessages(['lines' => 'Total requested quantity exceeds current unallocated No Batch stock.']); } foreach ($data['lines'] as $index => $line) { $existing = StockBatchBalance::query()->where('warehouse_id', $data['warehouse_id'])->where('item_id', $line['item_id'])->where('batch_no', trim($line['destination_batch_no']))->get(); $expiry = $line['destination_expiry_date'] ?? null; if ($existing->isNotEmpty() && ! $existing->contains(fn ($batch) => $batch->expiry_date?->format('Y-m-d') === $expiry)) throw ValidationException::withMessages(["lines.$index.destination_expiry_date" => 'Expiry must match the existing destination batch.']); } }
    private function onHand(StockBalance $balance): float { return (float) ($balance->qty_on_hand ?: $balance->quantity_on_hand ?: 0); }
    private function preview(BatchAssignment $record): array { $before = 0.0; $batch = 0.0; $assigned = (float) $record->lines->sum('quantity'); foreach ($record->lines->pluck('item_id')->unique() as $itemId) { $balance = StockBalance::where('warehouse_id', $record->warehouse_id)->where('item_id', $itemId)->first(); $before += $balance ? $this->onHand($balance) : 0; $batch += (float) StockBatchBalance::where('warehouse_id', $record->warehouse_id)->where('item_id', $itemId)->sum('qty_on_hand'); } $posted = $record->isPosted(); return ['warehouse_before' => $before, 'warehouse_after' => $before, 'batch_before' => $posted ? $batch - $assigned : $batch, 'batch_after' => $posted ? $batch : $batch + $assigned, 'difference_before' => $posted ? $before - ($batch - $assigned) : $before - $batch, 'difference_after' => $posted ? $before - $batch : $before - ($batch + $assigned)]; }
}
