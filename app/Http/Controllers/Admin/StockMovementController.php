<?php

namespace App\Http\Controllers\Admin;

use App\Models\StockMovement;

class StockMovementController extends ResourceController
{
    protected string $model = StockMovement::class;
    protected string $route = 'stock-movements';
    protected string $title = 'Stock Movement';
    protected string $viewPath = 'inventory.stock_movements';
    protected array $with = ['item', 'warehouse'];
    protected array $columns = ['movement_date', 'item.name', 'warehouse.name', 'movement_type', 'quantity_in', 'quantity_out', 'reference_number'];
    protected array $fields = [];
}
