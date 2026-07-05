<?php

namespace App\Http\Controllers\Admin;

use App\Models\ItemCategory;
use App\Models\WarehouseType;

class ItemCategoryController extends ResourceController
{
    protected string $model = ItemCategory::class;
    protected string $route = 'item-categories';
    protected string $title = 'Item Category';
    protected string $viewPath = 'master.item_categories';
    protected array $with = ['defaultWarehouseType'];
    protected array $columns = ['code', 'name', 'item_type', 'defaultWarehouseType.name', 'allow_purchase', 'allow_sales', 'is_active'];
    protected array $rules = [
        'code' => ['required', 'string', 'max:255'],
        'name' => ['required', 'string', 'max:255'],
        'item_type' => ['required', 'string', 'in:INVENTORY,NON_INVENTORY,SERVICE'],
        'default_warehouse_type_id' => ['nullable', 'exists:warehouse_types,id'],
        'allow_purchase' => ['nullable'],
        'allow_sales' => ['nullable'],
        'description' => ['nullable', 'string'],
        'is_active' => ['nullable'],
    ];

    public function __construct()
    {
        $this->fields = $this->baseFields();
    }

    protected function visibleFields(): array
    {
        if (request()->routeIs($this->route.'.create') || request()->routeIs($this->route.'.edit')) {
            return $this->baseFields(
                WarehouseType::where('is_active', true)->orderBy('name')->pluck('name', 'id')->toArray()
            );
        }

        return parent::visibleFields();
    }

    private function baseFields(array $warehouseTypes = []): array
    {
        $this->fields = [
            'code' => ['label' => 'Code', 'type' => 'text'],
            'name' => ['label' => 'Name', 'type' => 'text'],
            'item_type' => ['label' => 'Item Type', 'type' => 'select', 'options' => ['INVENTORY' => 'Inventory', 'NON_INVENTORY' => 'Non Inventory', 'SERVICE' => 'Service'], 'default' => 'INVENTORY'],
            'default_warehouse_type_id' => ['label' => 'Default Warehouse Type', 'type' => 'select', 'options' => $warehouseTypes, 'nullable' => true],
            'allow_purchase' => ['label' => 'Allow Purchase', 'type' => 'checkbox', 'default' => true],
            'allow_sales' => ['label' => 'Allow Sales', 'type' => 'checkbox', 'default' => false],
            'description' => ['label' => 'Description', 'type' => 'textarea'],
            'is_active' => ['label' => 'Active', 'type' => 'checkbox'],
        ];

        return $this->fields;
    }
}
