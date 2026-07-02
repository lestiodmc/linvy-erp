<?php

namespace App\Http\Controllers\Admin;

use App\Models\StockAdjustment;
use App\Models\Warehouse;

class StockAdjustmentController extends ResourceController
{
    protected string $model = StockAdjustment::class;
    protected string $route = 'stock-adjustments';
    protected string $title = 'Stock Adjustment';
    protected ?string $documentType = 'stock_adjustment';
    protected array $with = ['warehouse'];
    protected array $columns = ['number', 'warehouse.name', 'adjustment_date', 'status', 'reason'];
    protected array $rules = ['number' => ['nullable', 'string', 'max:255'], 'warehouse_id' => ['required', 'integer'], 'adjustment_date' => ['required', 'date'], 'status' => ['required', 'string'], 'reason' => ['nullable', 'string'], 'notes' => ['nullable', 'string']];

    public function __construct()
    {
        $this->fields = ['number' => ['label' => 'Number', 'type' => 'text'], 'warehouse_id' => ['label' => 'Warehouse', 'type' => 'select', 'options' => Warehouse::orderBy('name')->pluck('name', 'id')->toArray()], 'adjustment_date' => ['label' => 'Adjustment Date', 'type' => 'date'], 'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['draft' => 'Draft', 'posted' => 'Posted', 'cancelled' => 'Cancelled']], 'reason' => ['label' => 'Reason', 'type' => 'textarea'], 'notes' => ['label' => 'Notes', 'type' => 'textarea']];
    }
}
