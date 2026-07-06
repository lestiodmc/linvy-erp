<?php

namespace App\Models\Inventory;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Item;
use App\Models\UnitOfMeasure;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockBalance extends Model
{
    protected $table = 'stock_balances';

    protected $fillable = [
        'company_id',
        'branch_id',
        'warehouse_id',
        'item_id',
        'uom_id',
        'base_uom_id',
        'qty_on_hand',
        'qty_reserved',
        'qty_available',
        'qty_incoming',
        'qty_outgoing',
        'quantity_on_hand',
        'quantity_reserved',
        'average_cost',
        'last_cost',
        'total_value',
        'last_movement_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'qty_on_hand' => 'decimal:6',
        'qty_reserved' => 'decimal:6',
        'qty_available' => 'decimal:6',
        'qty_incoming' => 'decimal:6',
        'qty_outgoing' => 'decimal:6',
        'quantity_on_hand' => 'decimal:6',
        'quantity_reserved' => 'decimal:6',
        'average_cost' => 'decimal:6',
        'last_cost' => 'decimal:6',
        'total_value' => 'decimal:6',
        'last_movement_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'uom_id');
    }

    public function baseUom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'base_uom_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
