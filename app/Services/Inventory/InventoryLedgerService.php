<?php

namespace App\Services\Inventory;

use App\Models\StockMovement;
use App\Models\Inventory\StockBalance;
use App\Models\Inventory\StockBatchBalance;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class InventoryLedgerService
{
    private const IN_TYPES = [
        'IN',
        'RCV',
        'PURCHASE-RECEIVE',
        'ADJ-IN',
        'TRF-IN',
        'RETURN-IN',
        'PRODUCTION-OUTPUT',
    ];

    private const OUT_TYPES = [
        'OUT',
        'DO',
        'SALE-DELIVERY',
        'ADJ-OUT',
        'TRF-OUT',
        'RETURN-OUT',
        'SERVICE',
        'PRODUCTION-INPUT',
    ];

    public function getOpeningBalance(array $filters): float
    {
        if (! $this->hasItemFilter($filters) || blank($filters['date_from'] ?? null)) {
            return 0.0;
        }

        return $this->sumDelta(
            $this->baseQuery($filters)
                ->where($this->movementDateFilter('<', $filters['date_from']))
        );
    }

    public function getMovements(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        if (! $this->hasItemFilter($filters)) {
            return StockMovement::query()->whereKey([])->paginate($perPage)->withQueryString();
        }

        return $this->baseQuery($filters)
            ->with(['warehouse', 'item.baseUnit', 'uom', 'baseUom', 'createdBy'])
            ->when(filled($filters['date_from'] ?? null), fn (Builder $query) => $query->where($this->movementDateFilter('>=', $filters['date_from'])))
            ->when(filled($filters['date_to'] ?? null), fn (Builder $query) => $query->where($this->movementDateFilter('<=', $filters['date_to'])))
            ->orderByRaw('COALESCE(transaction_date, movement_date) ASC')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function calculateRunningBalance(LengthAwarePaginator $movements, float $openingBalance, array $filters): array
    {
        $pageOpeningBalance = $openingBalance + $this->getPreviousPageDelta($movements, $filters);
        $runningBalance = $pageOpeningBalance;

        $rows = $movements->getCollection()->map(function (StockMovement $movement) use (&$runningBalance): array {
            $inQty = $this->inQty($movement);
            $outQty = $this->outQty($movement);

            $runningBalance += $inQty - $outQty;

            return [
                'movement' => $movement,
                'date' => $movement->transaction_date ?: $movement->movement_date,
                'reference_no' => $movement->transaction_number ?: $movement->reference_number ?: '-',
                'reference_url' => $this->referenceUrl($movement),
                'document_type' => $this->documentType($movement),
                'movement_label' => $this->movementLabel($movement),
                'movement_category' => $this->movementCategory($movement),
                'description' => $movement->remarks ?: $movement->notes ?: $movement->item?->name ?: '-',
                'batch_no' => $this->batchLabel($movement),
                'expiry_date' => $movement->expiry_date,
                'in_qty' => $inQty,
                'out_qty' => $outQty,
                'running_balance' => $runningBalance,
                'uom' => $movement->uom?->code ?: $movement->baseUom?->code ?: $movement->item?->baseUnit?->code ?: '-',
            ];
        });

        $periodTotals = $this->periodTotals($filters);
        $closingBalance = $this->summaryClosingBalance($filters, $openingBalance + $periodTotals['total_in'] - $periodTotals['total_out']);

        return [
            'rows' => $rows,
            'opening_rows' => $this->openingRows($filters),
            'page_opening_balance' => $pageOpeningBalance,
            'total_in' => $periodTotals['total_in'],
            'total_out' => $periodTotals['total_out'],
            'closing_balance' => $closingBalance,
        ];
    }

    private function baseQuery(array $filters): Builder
    {
        return StockMovement::query()
            ->whereHas('item', fn (Builder $item) => $item->where('track_inventory', true))
            ->when(filled($filters['company_id'] ?? null), fn (Builder $query) => $query->where('company_id', $filters['company_id']))
            ->when(filled($filters['branch_id'] ?? null), fn (Builder $query) => $query->where('branch_id', $filters['branch_id']))
            ->when(filled($filters['warehouse_id'] ?? null), fn (Builder $query) => $query->where('warehouse_id', $filters['warehouse_id']))
            ->when(filled($filters['item_id'] ?? null), fn (Builder $query) => $query->where('item_id', $filters['item_id']))
            ->when(filled($filters['sku'] ?? null), fn (Builder $query) => $query->whereHas('item', fn (Builder $item) => $item->where('sku', 'like', '%'.$filters['sku'].'%')))
            ->when(($filters['batch_no'] ?? '__all') !== '__all', function (Builder $query) use ($filters): void {
                if ($filters['batch_no'] === '__no_batch') {
                    $query->where(function (Builder $batchQuery): void {
                        $batchQuery->whereNull('batch_no')->orWhere('batch_no', '');
                    });

                    return;
                }

                $query->where('batch_no', $filters['batch_no']);
            })
            ->when(filled($filters['movement_type'] ?? null), function (Builder $query) use ($filters): void {
                $query->where(function (Builder $movementQuery) use ($filters): void {
                    $movementQuery->where('movement_type', $filters['movement_type'])
                        ->orWhere('transaction_type', $filters['movement_type']);
                });
            });
    }

    private function periodTotals(array $filters): array
    {
        if (! $this->hasItemFilter($filters)) {
            return ['total_in' => 0.0, 'total_out' => 0.0];
        }

        $movements = $this->baseQuery($filters)
            ->with(['item.baseUnit', 'uom'])
            ->when(filled($filters['date_from'] ?? null), fn (Builder $query) => $query->where($this->movementDateFilter('>=', $filters['date_from'])))
            ->when(filled($filters['date_to'] ?? null), fn (Builder $query) => $query->where($this->movementDateFilter('<=', $filters['date_to'])))
            ->get();

        return [
            'total_in' => $movements->sum(fn (StockMovement $movement): float => $this->inQty($movement)),
            'total_out' => $movements->sum(fn (StockMovement $movement): float => $this->outQty($movement)),
        ];
    }

    private function getPreviousPageDelta(LengthAwarePaginator $movements, array $filters): float
    {
        $offset = max(0, ($movements->currentPage() - 1) * $movements->perPage());

        if ($offset === 0 || ! $this->hasItemFilter($filters)) {
            return 0.0;
        }

        $previousMovements = $this->baseQuery($filters)
            ->when(filled($filters['date_from'] ?? null), fn (Builder $query) => $query->where($this->movementDateFilter('>=', $filters['date_from'])))
            ->when(filled($filters['date_to'] ?? null), fn (Builder $query) => $query->where($this->movementDateFilter('<=', $filters['date_to'])))
            ->orderByRaw('COALESCE(transaction_date, movement_date) ASC')
            ->orderBy('id')
            ->limit($offset)
            ->get();

        return $this->sumDelta($previousMovements);
    }

    private function sumDelta(Builder|Collection $movements): float
    {
        $records = $movements instanceof Builder ? $movements->get() : $movements;

        return $records->sum(fn (StockMovement $movement): float => $this->inQty($movement) - $this->outQty($movement));
    }

    private function openingRows(array $filters): Collection
    {
        if (! $this->hasItemFilter($filters) || blank($filters['date_from'] ?? null)) {
            return collect();
        }

        return $this->baseQuery($filters)
            ->where($this->movementDateFilter('<', $filters['date_from']))
            ->get()
            ->groupBy(fn (StockMovement $movement): string => (filled($movement->batch_no) ? $movement->batch_no : 'No Batch').'|'.($movement->expiry_date?->format('Y-m-d') ?: '-'))
            ->map(function (Collection $movements, string $key): array {
                [$batchNo, $expiryDate] = explode('|', $key, 2);

                return [
                    'batch_no' => $batchNo,
                    'expiry_date' => $expiryDate === '-' ? null : $expiryDate,
                    'balance' => $movements->sum(fn (StockMovement $movement): float => $this->inQty($movement) - $this->outQty($movement)),
                ];
            })
            ->filter(fn (array $row): bool => abs((float) $row['balance']) > 0.000001)
            ->values();
    }

    private function movementDateFilter(string $operator, string $date): callable
    {
        return function (Builder $query) use ($operator, $date): void {
            $query->whereDate('transaction_date', $operator, $date)
                ->orWhere(function (Builder $fallbackQuery) use ($operator, $date): void {
                    $fallbackQuery
                        ->whereNull('transaction_date')
                        ->whereDate('movement_date', $operator, $date);
                });
        };
    }

    private function hasItemFilter(array $filters): bool
    {
        return filled($filters['item_id'] ?? null) || filled($filters['sku'] ?? null);
    }

    private function summaryClosingBalance(array $filters, float $movementClosingBalance): float
    {
        if (! filled($filters['item_id'] ?? null)) {
            return $movementClosingBalance;
        }

        $batchFilter = $filters['batch_no'] ?? '__all';

        if ($batchFilter === '__all') {
            $balance = StockBalance::query()
                ->where('item_id', $filters['item_id'])
                ->when(filled($filters['company_id'] ?? null), fn ($query) => $query->where('company_id', $filters['company_id']))
                ->when(filled($filters['branch_id'] ?? null), fn ($query) => $query->where('branch_id', $filters['branch_id']))
                ->when(filled($filters['warehouse_id'] ?? null), fn ($query) => $query->where('warehouse_id', $filters['warehouse_id']))
                ->get();

            if ($balance->isNotEmpty()) {
                return (float) $balance->sum(fn (StockBalance $row): float => (float) (($row->qty_on_hand ?? null) ?: ($row->quantity_on_hand ?? 0)));
            }

            return $movementClosingBalance;
        }

        if ($batchFilter === '__no_batch') {
            $totalBalance = $this->summaryClosingBalance(array_merge($filters, ['batch_no' => '__all']), $movementClosingBalance);
            $knownBatchBalance = (float) StockBatchBalance::query()
                ->where('item_id', $filters['item_id'])
                ->when(filled($filters['company_id'] ?? null), fn ($query) => $query->where('company_id', $filters['company_id']))
                ->when(filled($filters['branch_id'] ?? null), fn ($query) => $query->where('branch_id', $filters['branch_id']))
                ->when(filled($filters['warehouse_id'] ?? null), fn ($query) => $query->where('warehouse_id', $filters['warehouse_id']))
                ->sum('qty_on_hand');

            return max(0, $totalBalance - $knownBatchBalance);
        }

        $batchBalance = StockBatchBalance::query()
            ->where('item_id', $filters['item_id'])
            ->where('batch_no', $batchFilter)
            ->when(filled($filters['company_id'] ?? null), fn ($query) => $query->where('company_id', $filters['company_id']))
            ->when(filled($filters['branch_id'] ?? null), fn ($query) => $query->where('branch_id', $filters['branch_id']))
            ->when(filled($filters['warehouse_id'] ?? null), fn ($query) => $query->where('warehouse_id', $filters['warehouse_id']))
            ->get();

        return $batchBalance->isNotEmpty()
            ? (float) $batchBalance->sum(fn (StockBatchBalance $row): float => (float) $row->qty_on_hand)
            : $movementClosingBalance;
    }

    private function batchLabel(StockMovement $movement): string
    {
        return filled($movement->batch_no) ? (string) $movement->batch_no : 'No Batch';
    }

    private function inQty(StockMovement $movement): float
    {
        $legacyQty = (float) ($movement->quantity_in ?? 0);

        if ($legacyQty > 0) {
            return $legacyQty;
        }

        return in_array($this->normalizedType($movement), self::IN_TYPES, true)
            ? abs((float) ($movement->base_qty ?: $movement->qty))
            : 0.0;
    }

    private function outQty(StockMovement $movement): float
    {
        $legacyQty = (float) ($movement->quantity_out ?? 0);

        if ($legacyQty > 0) {
            return $legacyQty;
        }

        return in_array($this->normalizedType($movement), self::OUT_TYPES, true)
            ? abs((float) ($movement->base_qty ?: $movement->qty))
            : 0.0;
    }

    private function normalizedType(StockMovement $movement): string
    {
        return strtoupper(str_replace('_', '-', (string) ($movement->transaction_type ?: $movement->movement_type)));
    }

    private function movementCategory(StockMovement $movement): string
    {
        $type = $this->normalizedType($movement);

        return match (true) {
            in_array($type, ['IN', 'RCV', 'PURCHASE-RECEIVE'], true) => 'receive',
            in_array($type, ['OUT', 'DO', 'SALE-DELIVERY', 'SERVICE'], true) => 'issue',
            str_starts_with($type, 'TRF') || str_starts_with($type, 'TRANSFER') => 'transfer',
            str_starts_with($type, 'ADJ') || str_starts_with($type, 'ADJUSTMENT') => 'adjustment',
            default => 'opening',
        };
    }

    private function movementLabel(StockMovement $movement): string
    {
        return match ($this->movementCategory($movement)) {
            'receive' => 'Receive',
            'issue' => 'Issue',
            'transfer' => 'Transfer',
            'adjustment' => 'Adjustment',
            default => str($movement->transaction_type ?: $movement->movement_type ?: 'Opening')->replace(['_', '-'], ' ')->title()->toString(),
        };
    }

    private function documentType(StockMovement $movement): string
    {
        return str($movement->transaction_type ?: $movement->reference_type ?: $movement->movement_type ?: '-')
            ->replace(['_', '-'], ' ')
            ->title()
            ->toString();
    }

    private function referenceUrl(StockMovement $movement): string
    {
        $routeName = match ($this->normalizedType($movement)) {
            'RCV', 'PURCHASE-RECEIVE' => 'receivings.show',
            'ADJ-IN', 'ADJ-OUT' => 'stock-adjustments.show',
            default => null,
        };

        if (! $routeName || ! $movement->transaction_id || ! Route::has($routeName)) {
            return '#';
        }

        return route($routeName, $movement->transaction_id);
    }
}
