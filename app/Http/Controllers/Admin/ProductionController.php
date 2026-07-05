<?php

namespace App\Http\Controllers\Admin;

use App\Models\Production;
use App\Models\Warehouse;

class ProductionController extends ResourceController
{
    protected string $model = Production::class;
    protected string $route = 'productions';
    protected string $title = 'Production / Repacking';
    protected string $viewPath = 'production.repacking_orders';
    protected ?string $documentType = 'PRODUCTION_ORDER';
    protected array $with = ['productionWarehouse', 'outputWarehouse'];
    protected array $columns = ['number', 'production_date', 'productionWarehouse.name', 'outputWarehouse.name', 'type', 'status'];
    protected array $rules = ['number' => ['nullable', 'string', 'max:255'], 'production_date' => ['required', 'date'], 'production_warehouse_id' => ['required', 'integer'], 'output_warehouse_id' => ['required', 'integer'], 'type' => ['required', 'string'], 'status' => ['required', 'string'], 'notes' => ['nullable', 'string']];

    public function __construct()
    {
        $warehouses = Warehouse::orderBy('name')->pluck('name', 'id')->toArray();
        $this->fields = ['number' => ['label' => 'Number', 'type' => 'text'], 'production_date' => ['label' => 'Production Date', 'type' => 'date'], 'production_warehouse_id' => ['label' => 'Production Warehouse', 'type' => 'select', 'options' => $warehouses], 'output_warehouse_id' => ['label' => 'Output Warehouse', 'type' => 'select', 'options' => $warehouses], 'type' => ['label' => 'Type', 'type' => 'select', 'options' => ['repacking' => 'Repacking']], 'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['draft' => 'Draft', 'posted' => 'Posted', 'cancelled' => 'Cancelled']], 'notes' => ['label' => 'Notes', 'type' => 'textarea']];
    }
}
