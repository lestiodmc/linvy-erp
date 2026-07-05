<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemCategory extends Model
{
    protected $fillable = [
        'code',
        'name',
        'item_type',
        'default_warehouse_type_id',
        'allow_purchase',
        'allow_sales',
        'description',
        'default_inventory_account_id',
        'default_cogs_account_id',
        'default_sales_account_id',
        'default_purchase_account_id',
        'default_wip_account_id',
        'default_adjustment_account_id',
        'default_waste_account_id',
        'is_active',
    ];

    protected $casts = [
        'allow_purchase' => 'boolean',
        'allow_sales' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function inventoryAccount(): BelongsTo
    {
        return $this->belongsTo(AccountingAccount::class, 'default_inventory_account_id');
    }

    public function defaultWarehouseType(): BelongsTo
    {
        return $this->belongsTo(WarehouseType::class, 'default_warehouse_type_id');
    }

    public function cogsAccount(): BelongsTo
    {
        return $this->belongsTo(AccountingAccount::class, 'default_cogs_account_id');
    }

    public function salesAccount(): BelongsTo
    {
        return $this->belongsTo(AccountingAccount::class, 'default_sales_account_id');
    }

    public function purchaseAccount(): BelongsTo
    {
        return $this->belongsTo(AccountingAccount::class, 'default_purchase_account_id');
    }

    public function wipAccount(): BelongsTo
    {
        return $this->belongsTo(AccountingAccount::class, 'default_wip_account_id');
    }

    public function adjustmentAccount(): BelongsTo
    {
        return $this->belongsTo(AccountingAccount::class, 'default_adjustment_account_id');
    }

    public function wasteAccount(): BelongsTo
    {
        return $this->belongsTo(AccountingAccount::class, 'default_waste_account_id');
    }
}
