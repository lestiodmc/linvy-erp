<?php

namespace App\Http\Controllers\Admin;

use App\Models\Warehouse;

class WarehouseController extends ResourceController
{
    protected string $model = Warehouse::class;
    protected string $route = 'warehouses';
    protected string $title = 'Warehouse';
    protected array $columns = ['code', 'name', 'type', 'is_active'];
    protected array $fields = ['code' => ['label' => 'Code', 'type' => 'text'], 'name' => ['label' => 'Name', 'type' => 'text'], 'type' => ['label' => 'Type', 'type' => 'select', 'options' => ['raw_material' => 'Raw Material Warehouse', 'production' => 'Production Warehouse', 'finished_goods' => 'Finished Goods Warehouse', 'reject' => 'Reject Warehouse', 'transit' => 'Transit Warehouse']], 'address' => ['label' => 'Address', 'type' => 'textarea'], 'is_active' => ['label' => 'Active', 'type' => 'checkbox']];
    protected array $rules = ['code' => ['required', 'string', 'max:255'], 'name' => ['required', 'string', 'max:255'], 'type' => ['required', 'string'], 'address' => ['nullable', 'string'], 'is_active' => ['nullable']];
}
