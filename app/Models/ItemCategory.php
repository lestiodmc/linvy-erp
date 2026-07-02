<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemCategory extends Model
{
    protected $guarded = [];

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function inventoryAccount(): BelongsTo
    {
        return $this->belongsTo(AccountingAccount::class, 'default_inventory_account_id');
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
