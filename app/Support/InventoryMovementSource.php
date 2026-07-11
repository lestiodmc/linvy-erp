<?php

namespace App\Support;

use App\Models\BatchAssignment;
use App\Models\Receiving;
use App\Models\StockAdjustment;
use App\Models\WarehouseTransfer;
use Illuminate\Support\Collection;

class InventoryMovementSource
{
    public static function links(Collection $movements): array
    {
        $definitions = [
            Receiving::class => ['route' => 'receivings.show', 'model' => Receiving::class],
            WarehouseTransfer::class => ['route' => 'warehouse-transfers.show', 'model' => WarehouseTransfer::class],
            StockAdjustment::class => ['route' => 'stock-adjustments.show', 'model' => StockAdjustment::class],
            BatchAssignment::class => ['route' => 'batch-assignments.show', 'model' => BatchAssignment::class],
        ];
        $links = [];
        foreach ($definitions as $type => $definition) {
            $subset = $movements->where('reference_type', $type)->filter(fn ($movement) => filled($movement->reference_id));
            $existing = $definition['model']::query()->whereIn('id', $subset->pluck('reference_id'))->pluck('id')->flip();
            foreach ($subset as $movement) $links[$movement->id] = $existing->has($movement->reference_id) ? route($definition['route'], $movement->reference_id) : null;
        }
        return $links;
    }
}
