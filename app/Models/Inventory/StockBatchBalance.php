<?php

namespace App\Models\Inventory;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Item;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockBatchBalance extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'warehouse_id',
        'item_id',
        'batch_no',
        'expiry_date',
        'qty_on_hand',
        'qty_reserved',
        'qty_available',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'qty_on_hand' => 'decimal:6',
        'qty_reserved' => 'decimal:6',
        'qty_available' => 'decimal:6',
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
}
