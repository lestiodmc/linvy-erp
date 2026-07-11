<?php

namespace App\Support;

class InventoryReconciliation
{
    public static function tolerance(): float
    {
        return (float) config('linvy.inventory.reconciliation_tolerance', 0.000001);
    }

    public static function onHandExpression(string $table = 'stock_balances'): string
    {
        return "COALESCE({$table}.qty_on_hand, 0)";
    }

    public static function differenceExpression(string $onHand, string $batchTotal = 'batch_totals.batch_total'): string
    {
        return "ABS({$onHand} - COALESCE({$batchTotal}, 0))";
    }

    public static function isMatched(float $warehouseTotal, float $batchTotal): bool
    {
        return abs($warehouseTotal - $batchTotal) <= self::tolerance();
    }
}
