<?php

namespace App\Http\Controllers\Admin;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Warehouse;
use App\Models\WarehouseType;

class WarehouseController extends ResourceController
{
    protected string $model = Warehouse::class;
    protected string $route = 'warehouses';
    protected string $title = 'Warehouse';
    protected string $viewPath = 'master.warehouses';
    protected array $with = ['company', 'branch', 'warehouseType'];
    protected array $columns = ['code', 'name', 'branch.name', 'warehouseType.name', 'is_active'];
    protected array $rules = ['company_id' => ['required', 'exists:companies,id'], 'branch_id' => ['required', 'exists:branches,id'], 'warehouse_type_id' => ['required', 'exists:warehouse_types,id'], 'code' => ['required', 'string', 'max:255'], 'name' => ['required', 'string', 'max:255'], 'address' => ['nullable', 'string'], 'is_active' => ['nullable']];

    public function __construct()
    {
        $this->fields = [
            'company_id' => ['label' => 'Company', 'type' => 'select', 'options' => Company::where('is_active', true)->orderBy('name')->pluck('name', 'id')->toArray()],
            'branch_id' => ['label' => 'Branch', 'type' => 'select', 'options' => Branch::where('is_active', true)->orderBy('name')->pluck('name', 'id')->toArray()],
            'warehouse_type_id' => ['label' => 'Warehouse Type', 'type' => 'select', 'options' => WarehouseType::where('is_active', true)->orderBy('name')->pluck('name', 'id')->toArray()],
            'code' => ['label' => 'Code', 'type' => 'text'],
            'name' => ['label' => 'Name', 'type' => 'text'],
            'address' => ['label' => 'Address', 'type' => 'textarea'],
            'is_active' => ['label' => 'Active', 'type' => 'checkbox'],
        ];
    }
}
