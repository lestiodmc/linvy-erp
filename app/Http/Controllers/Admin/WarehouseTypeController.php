<?php

namespace App\Http\Controllers\Admin;

use App\Models\WarehouseType;

class WarehouseTypeController extends ResourceController
{
    protected string $model = WarehouseType::class;
    protected string $route = 'warehouse-types';
    protected string $title = 'Warehouse Type';
    protected string $viewPath = 'master.warehouse_types';
    protected array $columns = ['code', 'name', 'is_active'];
    protected array $fields = [
        'code' => ['label' => 'Code', 'type' => 'text'],
        'name' => ['label' => 'Name', 'type' => 'text'],
        'is_active' => ['label' => 'Active', 'type' => 'checkbox'],
    ];
    protected array $rules = [
        'code' => ['required', 'string', 'max:255'],
        'name' => ['required', 'string', 'max:255'],
        'is_active' => ['nullable'],
    ];
}
