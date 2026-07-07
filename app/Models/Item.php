<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    public const COST_METHOD_STANDARD = 'standard';
    public const COST_METHOD_AVERAGE = 'average';
    public const COST_METHOD_FIFO = 'fifo';

    public const COST_METHODS = [
        self::COST_METHOD_STANDARD,
        self::COST_METHOD_AVERAGE,
        self::COST_METHOD_FIFO,
    ];

    protected $fillable = [
        'sku',
        'name',
        'type',
        'item_category_id',
        'brand_id',
        'item_type',
        'unit_of_measure_id',
        'base_unit_id',
        'purchase_unit_id',
        'sales_unit_id',
        'default_warehouse_type_id',
        'track_inventory',
        'allow_negative_stock',
        'is_batch_tracked',
        'is_serial_tracked',
        'has_expiry_date',
        'default_supplier_id',
        'purchase_price',
        'minimum_order_qty',
        'lead_time_days',
        'blocked_purchase',
        'sales_price',
        'minimum_sales_qty',
        'blocked_sales',
        'barcode',
        'description',
        'is_stock_item',
        'standard_cost',
        'cost_method',
        'use_category_default_accounts',
        'inventory_account_id',
        'cogs_account_id',
        'sales_account_id',
        'purchase_account_id',
        'wip_account_id',
        'adjustment_account_id',
        'waste_account_id',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'track_inventory' => 'boolean',
        'allow_negative_stock' => 'boolean',
        'is_batch_tracked' => 'boolean',
        'is_serial_tracked' => 'boolean',
        'has_expiry_date' => 'boolean',
        'blocked_purchase' => 'boolean',
        'blocked_sales' => 'boolean',
        'is_stock_item' => 'boolean',
        'is_active' => 'boolean',
        'use_category_default_accounts' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'item_category_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class);
    }

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'base_unit_id');
    }

    public function purchaseUnit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'purchase_unit_id');
    }

    public function salesUnit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'sales_unit_id');
    }

    public function defaultWarehouseType(): BelongsTo
    {
        return $this->belongsTo(WarehouseType::class, 'default_warehouse_type_id');
    }

    public function defaultSupplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'default_supplier_id');
    }

    public function stockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
