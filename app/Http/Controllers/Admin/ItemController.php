<?php

namespace App\Http\Controllers\Admin;

use App\Models\Brand;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\UnitOfMeasure;
use App\Models\WarehouseType;
use App\Support\ModuleManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ItemController extends ResourceController
{
    protected string $model = Item::class;
    protected string $route = 'items';
    protected string $title = 'Item';
    protected string $viewPath = 'master.items';
    protected array $with = ['category', 'brand', 'baseUnit'];
    protected array $columns = ['sku', 'name', 'category.name', 'brand.name', 'item_type', 'baseUnit.code', 'is_active'];
    protected array $rules = [
        'sku' => ['required', 'string', 'max:255'],
        'name' => ['required', 'string', 'max:255'],
        'item_category_id' => ['required', 'exists:item_categories,id'],
        'brand_id' => ['nullable', 'exists:brands,id'],
        'item_type' => ['required', 'string', 'in:INVENTORY,NON_INVENTORY,SERVICE'],
        'default_warehouse_type_id' => ['nullable', 'exists:warehouse_types,id'],
        'base_unit_id' => ['required', 'exists:units_of_measure,id'],
        'purchase_unit_id' => ['nullable', 'exists:units_of_measure,id'],
        'sales_unit_id' => ['nullable', 'exists:units_of_measure,id'],
        'track_inventory' => ['nullable'],
        'allow_negative_stock' => ['nullable'],
        'is_batch_tracked' => ['nullable'],
        'is_serial_tracked' => ['nullable'],
        'has_expiry_date' => ['nullable'],
        'purchase_price' => ['nullable', 'numeric', 'gte:0'],
        'sales_price' => ['nullable', 'numeric', 'gte:0'],
        'cost_method' => ['required', 'string', 'in:standard,average,fifo'],
        'standard_cost' => ['nullable', 'numeric', 'gte:0'],
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
                ItemCategory::orderBy('name')->pluck('name', 'id')->toArray(),
                Brand::where('is_active', true)->orderBy('name')->pluck('name', 'id')->toArray(),
                UnitOfMeasure::orderBy('name')->pluck('name', 'id')->toArray(),
                WarehouseType::where('is_active', true)->orderBy('name')->pluck('name', 'id')->toArray(),
            );
        }

        return parent::visibleFields();
    }

    protected function validated(Request $request, ?Model $record = null): array
    {
        $data = parent::validated($request, $record);
        $category = ItemCategory::find($data['item_category_id']);

        $data['purchase_unit_id'] = $data['purchase_unit_id'] ?? $data['base_unit_id'];
        $data['sales_unit_id'] = $data['sales_unit_id'] ?? $data['base_unit_id'];
        $data['purchase_price'] = $data['purchase_price'] ?? 0;
        $data['sales_price'] = $data['sales_price'] ?? 0;
        $data['standard_cost'] = $data['standard_cost'] ?? $data['purchase_price'];
        $data['cost_method'] = $data['cost_method'] ?? Item::COST_METHOD_STANDARD;

        $this->enforceInventoryTrackingRules($data);

        // Keep legacy columns populated for backward compatibility.
        $data['minimum_order_qty'] = $data['minimum_order_qty'] ?? 0;
        $data['lead_time_days'] = $data['lead_time_days'] ?? 0;
        $data['minimum_sales_qty'] = $data['minimum_sales_qty'] ?? 0;
        $data['type'] = $this->legacyType($data['item_type'], $category);
        $data['unit_of_measure_id'] = $data['base_unit_id'];
        $data['is_stock_item'] = (bool) ($data['track_inventory'] ?? false);

        if (! ModuleManager::enabled('accounting')) {
            $data['use_category_default_accounts'] = true;

            foreach ($this->accountFields() as $field) {
                $data[$field] = null;
            }

            return $data;
        }

        $data['use_category_default_accounts'] = $record?->use_category_default_accounts ?? true;

        return $data;
    }

    protected function viewData(array $data = []): array
    {
        if (request()->routeIs($this->route.'.create') || request()->routeIs($this->route.'.edit')) {
            return parent::viewData($data) + [
                'categoryRecords' => ItemCategory::orderBy('name')->get(),
            ];
        }

        return parent::viewData($data);
    }

    private function legacyType(string $itemType, ?ItemCategory $category): string
    {
        if ($itemType !== 'INVENTORY') {
            return 'non_stock';
        }

        return match ($category?->code) {
            'PK' => 'packaging_material',
            'FG' => 'finished_goods',
            'CS' => 'consumable',
            default => 'raw_material',
        };
    }

    private function enforceInventoryTrackingRules(array &$data): void
    {
        $data['track_inventory'] = (bool) ($data['track_inventory'] ?? false);
        $data['allow_negative_stock'] = (bool) ($data['allow_negative_stock'] ?? false);
        $data['is_batch_tracked'] = (bool) ($data['is_batch_tracked'] ?? false);
        $data['is_serial_tracked'] = (bool) ($data['is_serial_tracked'] ?? false);
        $data['has_expiry_date'] = (bool) ($data['has_expiry_date'] ?? false);

        if (($data['item_type'] ?? null) !== 'INVENTORY') {
            $data['track_inventory'] = false;
        }

        if (! $data['track_inventory']) {
            $data['allow_negative_stock'] = false;
            $data['is_batch_tracked'] = false;
            $data['is_serial_tracked'] = false;
            $data['has_expiry_date'] = false;

            return;
        }

        if ($data['is_batch_tracked'] && $data['is_serial_tracked']) {
            throw ValidationException::withMessages([
                'is_serial_tracked' => 'Batch tracked and serial tracked cannot both be enabled.',
            ]);
        }

        if ($data['has_expiry_date'] && ! $data['is_batch_tracked']) {
            throw ValidationException::withMessages([
                'has_expiry_date' => 'Expiry date tracking requires batch tracking.',
            ]);
        }
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

    private function baseFields(
        array $categories = [],
        array $brands = [],
        array $uoms = [],
        array $warehouseTypes = [],
    ): array {
        $this->fields = [
            'sku' => ['label' => 'Code', 'type' => 'text'],
            'name' => ['label' => 'Name', 'type' => 'text'],
            'item_category_id' => ['label' => 'Category', 'type' => 'select', 'options' => $categories],
            'brand_id' => ['label' => 'Brand', 'type' => 'select', 'options' => $brands, 'nullable' => true],
            'item_type' => ['label' => 'Item Type', 'type' => 'select', 'options' => ['INVENTORY' => 'Inventory', 'NON_INVENTORY' => 'Non Inventory', 'SERVICE' => 'Service'], 'default' => 'INVENTORY'],
            'base_unit_id' => ['label' => 'Base UoM', 'type' => 'select', 'options' => $uoms],
            'purchase_unit_id' => ['label' => 'Purchase UoM', 'type' => 'select', 'options' => $uoms, 'nullable' => true],
            'sales_unit_id' => ['label' => 'Sales UoM', 'type' => 'select', 'options' => $uoms, 'nullable' => true],
            'default_warehouse_type_id' => ['label' => 'Default Warehouse Type', 'type' => 'select', 'options' => $warehouseTypes, 'nullable' => true],
            'track_inventory' => ['label' => 'Track Inventory', 'type' => 'checkbox', 'default' => true],
            'allow_negative_stock' => ['label' => 'Allow Negative Stock', 'type' => 'checkbox'],
            'is_batch_tracked' => ['label' => 'Batch Tracked', 'type' => 'checkbox'],
            'is_serial_tracked' => ['label' => 'Serial Tracked', 'type' => 'checkbox'],
            'has_expiry_date' => ['label' => 'Has Expiry Date', 'type' => 'checkbox'],
            'purchase_price' => ['label' => 'Purchase Price', 'type' => 'number', 'step' => '0.01'],
            'sales_price' => ['label' => 'Sales Price', 'type' => 'number', 'step' => '0.01'],
            'cost_method' => ['label' => 'Cost Method', 'type' => 'select', 'options' => array_combine(Item::COST_METHODS, ['Standard', 'Average', 'FIFO']), 'default' => Item::COST_METHOD_STANDARD],
            'standard_cost' => ['label' => 'Standard Cost', 'type' => 'number', 'step' => '0.0001'],
            'is_active' => ['label' => 'Active', 'type' => 'checkbox'],
        ];

        return $this->fields;
    }
}
