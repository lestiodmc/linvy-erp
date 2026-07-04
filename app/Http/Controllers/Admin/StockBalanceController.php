<?php

namespace App\Http\Controllers\Admin;

use App\Models\StockBalance;

class StockBalanceController extends ResourceController
{
    protected string $model = StockBalance::class;
    protected string $route = 'stock-balances';
    protected string $title = 'Stock Balance';
    protected string $viewPath = 'inventory.stock_balances';
    protected array $with = ['item', 'warehouse'];
    protected array $columns = ['item.name', 'warehouse.name', 'quantity_on_hand', 'quantity_reserved', 'average_cost', 'last_movement_at'];
    protected array $fields = [];
}
