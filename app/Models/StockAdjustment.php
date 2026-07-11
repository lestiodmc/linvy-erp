<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockAdjustment extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_POSTED = 'posted';
    public const STATUS_CANCELLED = 'cancelled';

    public const REASON_STOCK_COUNT = 'STOCK_COUNT';
    public const REASON_DAMAGE = 'DAMAGE';
    public const REASON_LOST = 'LOST';
    public const REASON_EXPIRED = 'EXPIRED';
    public const REASON_INITIAL_BALANCE = 'INITIAL_BALANCE';
    public const REASON_CORRECTION = 'CORRECTION';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_POSTED,
        self::STATUS_CANCELLED,
    ];

    public const REASON_CODES = [
        self::REASON_STOCK_COUNT,
        self::REASON_DAMAGE,
        self::REASON_LOST,
        self::REASON_EXPIRED,
        self::REASON_INITIAL_BALANCE,
        self::REASON_CORRECTION,
    ];

    public static function reasonLabels(): array
    {
        return [
            self::REASON_STOCK_COUNT => 'Stock Count',
            self::REASON_DAMAGE => 'Damage',
            self::REASON_LOST => 'Lost',
            self::REASON_EXPIRED => 'Expired',
            self::REASON_INITIAL_BALANCE => 'Initial Balance',
            self::REASON_CORRECTION => 'Correction',
        ];
    }

    protected $guarded = [];

    protected $casts = [
        'adjustment_date' => 'date',
        'posted_at' => 'datetime',
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

    public function lines(): HasMany
    {
        return $this->hasMany(StockAdjustmentLine::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }
}
