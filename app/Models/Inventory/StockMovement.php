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

class StockMovement extends Model
{
    public const MOVEMENT_IN = 'IN';
    public const MOVEMENT_OUT = 'OUT';

    public const TRANSACTION_RCV = 'RCV';
    public const TRANSACTION_ADJ_IN = 'ADJ-IN';
    public const TRANSACTION_ADJ_OUT = 'ADJ-OUT';
    public const TRANSACTION_TRF_IN = 'TRF-IN';
    public const TRANSACTION_TRF_OUT = 'TRF-OUT';
    public const TRANSACTION_DO = 'DO';
    public const TRANSACTION_SERVICE = 'SERVICE';
    public const TRANSACTION_RETURN_IN = 'RETURN-IN';
    public const TRANSACTION_RETURN_OUT = 'RETURN-OUT';

    protected $table = 'stock_movements';

    protected $fillable = [
        'company_id',
        'branch_id',
        'warehouse_id',
        'item_id',
        'uom_id',
        'base_uom_id',
        'transaction_type',
        'transaction_id',
        'transaction_number',
        'transaction_date',
        'movement_type',
        'qty',
        'base_qty',
        'quantity_in',
        'quantity_out',
        'unit_cost',
        'total_cost',
        'batch_no',
        'serial_no',
        'expiry_date',
        'reference_type',
        'reference_id',
        'reference_number',
        'movement_date',
        'notes',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'movement_date' => 'datetime',
        'expiry_date' => 'date',
        'qty' => 'decimal:6',
        'base_qty' => 'decimal:6',
        'quantity_in' => 'decimal:6',
        'quantity_out' => 'decimal:6',
        'unit_cost' => 'decimal:6',
        'total_cost' => 'decimal:6',
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
