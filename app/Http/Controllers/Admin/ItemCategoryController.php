<?php

namespace App\Http\Controllers\Admin;

use App\Models\AccountingAccount;
use App\Models\ItemCategory;

class ItemCategoryController extends ResourceController
{
    protected string $model = ItemCategory::class;
    protected string $route = 'item-categories';
    protected string $title = 'Item Category';
    protected array $columns = ['code', 'name', 'is_active'];
    protected array $rules = ['code' => ['required', 'string', 'max:255'], 'name' => ['required', 'string', 'max:255'], 'default_inventory_account_id' => ['nullable', 'integer'], 'default_cogs_account_id' => ['nullable', 'integer'], 'default_sales_account_id' => ['nullable', 'integer'], 'default_purchase_account_id' => ['nullable', 'integer'], 'default_wip_account_id' => ['nullable', 'integer'], 'default_adjustment_account_id' => ['nullable', 'integer'], 'default_waste_account_id' => ['nullable', 'integer'], 'is_active' => ['nullable']];

    public function __construct()
    {
        $accounts = AccountingAccount::orderBy('code')->pluck('name', 'id')->toArray();
        $this->fields = [
            'code' => ['label' => 'Code', 'type' => 'text'],
            'name' => ['label' => 'Name', 'type' => 'text'],
            'default_inventory_account_id' => ['label' => 'Inventory Account', 'type' => 'select', 'options' => $accounts, 'nullable' => true],
            'default_cogs_account_id' => ['label' => 'COGS Account', 'type' => 'select', 'options' => $accounts, 'nullable' => true],
            'default_sales_account_id' => ['label' => 'Sales Account', 'type' => 'select', 'options' => $accounts, 'nullable' => true],
            'default_purchase_account_id' => ['label' => 'Purchase Account', 'type' => 'select', 'options' => $accounts, 'nullable' => true],
            'default_wip_account_id' => ['label' => 'WIP Account', 'type' => 'select', 'options' => $accounts, 'nullable' => true],
            'default_adjustment_account_id' => ['label' => 'Adjustment Account', 'type' => 'select', 'options' => $accounts, 'nullable' => true],
            'default_waste_account_id' => ['label' => 'Waste Account', 'type' => 'select', 'options' => $accounts, 'nullable' => true],
            'is_active' => ['label' => 'Active', 'type' => 'checkbox'],
        ];
    }
}
