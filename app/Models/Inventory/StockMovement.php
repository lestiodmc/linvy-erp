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
use Illuminate\Database\Eloquent\Builder;

class StockMovement extends Model
{
    public const MOVEMENT_IN = 'IN';
    public const MOVEMENT_OUT = 'OUT';

    public const TRANSACTION_RCV = 'RCV';
    public const TRANSACTION_ADJ_IN = 'ADJUSTMENT_IN';
    public const TRANSACTION_ADJ_OUT = 'ADJUSTMENT_OUT';
    public const LEGACY_TRANSACTION_ADJ_IN = 'ADJ-IN';
    public const LEGACY_TRANSACTION_ADJ_OUT = 'ADJ-OUT';
    public const TRANSACTION_TRF_IN = 'TRANSFER_IN';
    public const TRANSACTION_TRF_OUT = 'TRANSFER_OUT';
    public const LEGACY_TRANSACTION_TRF_IN = 'TRF-IN';
    public const LEGACY_TRANSACTION_TRF_OUT = 'TRF-OUT';
    public const TRANSACTION_DO = 'DO';
    public const TRANSACTION_SERVICE = 'SERVICE';
    public const TRANSACTION_RETURN_IN = 'RETURN-IN';
    public const TRANSACTION_RETURN_OUT = 'RETURN-OUT';
    public const TRANSACTION_BATCH_ASSIGNMENT_IN = 'BATCH_ASSIGNMENT_IN';
    public const TRANSACTION_BATCH_ASSIGNMENT_OUT = 'BATCH_ASSIGNMENT_OUT';

    public static function transactionTypeLabels(): array
    {
        return [
            'RCV' => 'Purchase Receive', 'RECEIVE' => 'Purchase Receive', 'PURCHASE_RECEIVE' => 'Purchase Receive',
            'IN' => 'Inventory In', 'OUT' => 'Inventory Out',
            self::TRANSACTION_TRF_IN => 'Transfer In', self::TRANSACTION_TRF_OUT => 'Transfer Out', self::LEGACY_TRANSACTION_TRF_IN => 'Transfer In', self::LEGACY_TRANSACTION_TRF_OUT => 'Transfer Out',
            self::TRANSACTION_ADJ_IN => 'Adjustment In', self::TRANSACTION_ADJ_OUT => 'Adjustment Out', self::LEGACY_TRANSACTION_ADJ_IN => 'Adjustment In', self::LEGACY_TRANSACTION_ADJ_OUT => 'Adjustment Out',
            'ADJUSTMENT_PLUS' => 'Adjustment In', 'ADJUSTMENT_MINUS' => 'Adjustment Out',
            self::TRANSACTION_BATCH_ASSIGNMENT_IN => 'Batch Assignment In', self::TRANSACTION_BATCH_ASSIGNMENT_OUT => 'Batch Assignment Out',
            self::TRANSACTION_DO => 'Sales Delivery', 'SALE_DELIVERY' => 'Sales Delivery', self::TRANSACTION_SERVICE => 'Production Consumption',
            'RETURN-IN' => 'Return In', 'RETURN-OUT' => 'Return Out', 'PRODUCTION_OUTPUT' => 'Production Output', 'PRODUCTION_INPUT' => 'Production Input',
        ];
    }

    public static function typeLabel(?string $type): string
    {
        $normalized = strtoupper(str_replace('-', '_', (string) $type));
        $labels = collect(self::transactionTypeLabels())->mapWithKeys(fn (string $label, string $key): array => [strtoupper(str_replace('-', '_', $key)) => $label]);

        return $labels[$normalized] ?? str($type ?: 'Unknown')->replace(['_', '-'], ' ')->title()->toString();
    }

    public function quantityIn(): float { return (float) ($this->quantity_in ?? 0); }
    public function quantityOut(): float { return (float) ($this->quantity_out ?? 0); }
    public function direction(): string
    {
        $in = $this->quantityIn() > 0; $out = $this->quantityOut() > 0;
        return $in && $out ? 'INVALID' : ($in ? self::MOVEMENT_IN : ($out ? self::MOVEMENT_OUT : 'NEUTRAL'));
    }

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

    /**
     * Restrict movements to warehouses owned by the permitted branches.
     *
     * The warehouse relationship is the authoritative fallback for legacy
     * movements which were created before company_id and branch_id were
     * consistently populated. Requiring an accessible warehouse prevents a
     * legacy NULL branch from bypassing branch access.
     */
    public function scopeAccessibleFromBranches(Builder $query, array $branchIds): Builder
    {
        return $query
            ->whereHas('warehouse', fn (Builder $warehouse) => $warehouse->whereIn('branch_id', $branchIds))
            ->where(function (Builder $movement) use ($branchIds): void {
                $movement->whereIn('branch_id', $branchIds)
                    ->orWhereNull('branch_id');
            });
    }

    /**
     * Apply a branch filter without hiding legacy movements whose branch is
     * NULL but whose warehouse belongs to the requested branch.
     */
    public function scopeForBranch(Builder $query, int $branchId): Builder
    {
        return $query->where(function (Builder $movement) use ($branchId): void {
            $movement->where('branch_id', $branchId)
                ->orWhere(function (Builder $legacy) use ($branchId): void {
                    $legacy->whereNull('branch_id')
                        ->whereHas('warehouse', fn (Builder $warehouse) => $warehouse->where('branch_id', $branchId));
                });
        });
    }

    /**
     * Apply a company filter with a warehouse/branch fallback for legacy NULL
     * company values. A populated movement company remains authoritative.
     */
    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where(function (Builder $movement) use ($companyId): void {
            $movement->where('company_id', $companyId)
                ->orWhere(function (Builder $legacy) use ($companyId): void {
                    $legacy->whereNull('company_id')
                        ->whereHas('warehouse', fn (Builder $warehouse) => $warehouse
                            ->where('company_id', $companyId)
                            ->orWhereHas('branch', fn (Builder $branch) => $branch->where('company_id', $companyId)));
                });
        });
    }
}
