<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Inventory\StockBatchBalance;
use App\Models\Item;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ItemLedgerController extends Controller
{
    public function __construct(private readonly InventoryLedgerService $inventoryLedgerService)
    {
    }

    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        $openingBalance = $this->inventoryLedgerService->getOpeningBalance($filters);
        $movements = $this->inventoryLedgerService->getMovements($filters);
        $ledger = $this->inventoryLedgerService->calculateRunningBalance($movements, $openingBalance, $filters);

        return view('inventory.item-ledger.index', [
            'filters' => $filters,
            'openingBalance' => $openingBalance,
            'movements' => $movements,
            'ledger' => $ledger,
            'companies' => Company::orderBy('name')->pluck('name', 'id')->all(),
            'branches' => Branch::orderBy('name')->pluck('name', 'id')->all(),
            'warehouses' => $this->warehouseRecords(),
            'batches' => $this->batchOptions($filters),
            'items' => Item::where('track_inventory', true)
                ->orderBy('sku')
                ->limit(500)
                ->get(['id', 'sku', 'name'])
                ->mapWithKeys(fn (Item $item): array => [$item->id => trim($item->sku.' - '.$item->name)])
                ->all(),
            'movementTypes' => $this->movementTypes(),
        ]);
    }

    public function exportExcel(Request $request): RedirectResponse
    {
        return redirect()
            ->route('item-ledger.index', $request->query())
            ->with('status', 'Export Excel placeholder is ready for future implementation.');
    }

    public function exportPdf(Request $request): RedirectResponse
    {
        return redirect()
            ->route('item-ledger.index', $request->query())
            ->with('status', 'Export PDF placeholder is ready for future implementation.');
    }

    private function filters(Request $request): array
    {
        $filters = $request->only([
            'company_id',
            'branch_id',
            'warehouse_id',
            'item_id',
            'sku',
            'movement_type',
            'batch_no',
            'date_from',
            'date_to',
        ]);

        $filters['batch_no'] = $filters['batch_no'] ?? '__all';

        if (! $request->has('date_from')) {
            $filters['date_from'] = now()->startOfMonth()->toDateString();
        }

        if (! $request->has('date_to')) {
            $filters['date_to'] = now()->toDateString();
        }

        return $filters;
    }

    private function movementTypes(): array
    {
        return collect([
            'RCV',
            'IN',
            'OUT',
            'PURCHASE_RECEIVE',
            'TRF-IN',
            'TRF-OUT',
            'ADJ-IN',
            'ADJ-OUT',
            'DO',
            'SERVICE',
            'RETURN-IN',
            'RETURN-OUT',
            'PRODUCTION_OUTPUT',
            'PRODUCTION_INPUT',
        ])->mapWithKeys(fn (string $type): array => [$type => str($type)->replace(['_', '-'], ' ')->title()->toString()])->all();
    }

    private function batchOptions(array $filters): array
    {
        $options = [
            '__all' => 'All Batch',
            '__no_batch' => 'No Batch',
        ];

        if (! filled($filters['item_id'] ?? null)) {
            return $options;
        }

        $batches = StockBatchBalance::query()
            ->where('item_id', $filters['item_id'])
            ->when(filled($filters['company_id'] ?? null), fn ($query) => $query->where('company_id', $filters['company_id']))
            ->when(filled($filters['branch_id'] ?? null), fn ($query) => $query->where('branch_id', $filters['branch_id']))
            ->when(filled($filters['warehouse_id'] ?? null), fn ($query) => $query->where('warehouse_id', $filters['warehouse_id']))
            ->orderBy('batch_no')
            ->pluck('batch_no')
            ->filter()
            ->unique()
            ->values();

        foreach ($batches as $batchNo) {
            $options[$batchNo] = $batchNo;
        }

        return $options;
    }

    private function warehouseRecords()
    {
        return Warehouse::with('branch')
            ->orderBy('branch_id')
            ->orderBy('name')
            ->get();
    }
}
