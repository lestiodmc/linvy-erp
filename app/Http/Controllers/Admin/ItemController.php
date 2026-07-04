<?php

namespace App\Http\Controllers\Admin;

use App\Models\AccountingAccount;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\UnitOfMeasure;
use App\Support\ModuleManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ItemController extends ResourceController
{
    protected string $model = Item::class;
    protected string $route = 'items';
    protected string $title = 'Item';
    protected string $viewPath = 'master.items';
    protected array $columns = ['sku', 'name', 'type', 'is_stock_item', 'is_active'];
    protected array $rules = ['sku' => ['required', 'string', 'max:255'], 'name' => ['required', 'string', 'max:255'], 'type' => ['required', 'string'], 'item_category_id' => ['required', 'integer'], 'unit_of_measure_id' => ['required', 'integer'], 'is_stock_item' => ['nullable'], 'standard_cost' => ['required', 'numeric'], 'cost_method' => ['required', 'string'], 'use_category_default_accounts' => ['nullable'], 'inventory_account_id' => ['nullable', 'integer'], 'cogs_account_id' => ['nullable', 'integer'], 'sales_account_id' => ['nullable', 'integer'], 'purchase_account_id' => ['nullable', 'integer'], 'wip_account_id' => ['nullable', 'integer'], 'adjustment_account_id' => ['nullable', 'integer'], 'waste_account_id' => ['nullable', 'integer'], 'is_active' => ['nullable'], 'notes' => ['nullable', 'string']];

    public function __construct()
    {
        $categories = ItemCategory::orderBy('name')->pluck('name', 'id')->toArray();
        $uoms = UnitOfMeasure::orderBy('name')->pluck('name', 'id')->toArray();
        $accounts = AccountingAccount::orderBy('code')->pluck('name', 'id')->toArray();
        $types = ['raw_material' => 'Raw Material', 'packaging_material' => 'Packaging Material', 'finished_goods' => 'Finished Goods', 'consumable' => 'Consumable', 'non_stock' => 'Non Stock'];

        $this->fields = [
            'sku' => ['label' => 'SKU', 'type' => 'text'],
            'name' => ['label' => 'Name', 'type' => 'text'],
            'type' => ['label' => 'Type', 'type' => 'select', 'options' => $types],
            'item_category_id' => ['label' => 'Category', 'type' => 'select', 'options' => $categories],
            'unit_of_measure_id' => ['label' => 'UOM', 'type' => 'select', 'options' => $uoms],
            'is_stock_item' => ['label' => 'Stock Item', 'type' => 'checkbox'],
            'standard_cost' => ['label' => 'Standard Cost', 'type' => 'number', 'step' => '0.0001'],
            'cost_method' => ['label' => 'Cost Method', 'type' => 'select', 'options' => ['standard' => 'Standard', 'average' => 'Average', 'fifo' => 'FIFO']],
            'use_category_default_accounts' => ['label' => 'Use Category Default Accounts', 'type' => 'checkbox', 'default' => true],
            'inventory_account_id' => ['label' => 'Inventory Account Override', 'type' => 'select', 'options' => $accounts, 'nullable' => true],
            'cogs_account_id' => ['label' => 'COGS Account Override', 'type' => 'select', 'options' => $accounts, 'nullable' => true],
            'sales_account_id' => ['label' => 'Sales Account Override', 'type' => 'select', 'options' => $accounts, 'nullable' => true],
            'purchase_account_id' => ['label' => 'Purchase Account Override', 'type' => 'select', 'options' => $accounts, 'nullable' => true],
            'wip_account_id' => ['label' => 'WIP Account Override', 'type' => 'select', 'options' => $accounts, 'nullable' => true],
            'adjustment_account_id' => ['label' => 'Adjustment Account Override', 'type' => 'select', 'options' => $accounts, 'nullable' => true],
            'waste_account_id' => ['label' => 'Waste Account Override', 'type' => 'select', 'options' => $accounts, 'nullable' => true],
            'is_active' => ['label' => 'Active', 'type' => 'checkbox'],
            'notes' => ['label' => 'Notes', 'type' => 'textarea'],
        ];
    }

    protected function validated(Request $request, ?Model $record = null): array
    {
        $data = parent::validated($request, $record);

        if (! ModuleManager::enabled('accounting')) {
            $data['use_category_default_accounts'] = true;

            foreach ($this->accountFields() as $field) {
                $data[$field] = null;
            }

            return $data;
        }

        $data['use_category_default_accounts'] = $request->boolean('use_category_default_accounts');

        if ($data['use_category_default_accounts']) {
            foreach ($this->accountFields() as $field) {
                $data[$field] = null;
            }
        }

        return $data;
    }

    private function accountFields(): array
    {
        return [
            'inventory_account_id',
            'cogs_account_id',
            'sales_account_id',
            'purchase_account_id',
            'wip_account_id',
            'adjustment_account_id',
            'waste_account_id',
        ];
    }
}
