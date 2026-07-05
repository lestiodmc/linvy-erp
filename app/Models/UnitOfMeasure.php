<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnitOfMeasure extends Model
{
    protected $table = 'units_of_measure';

    protected $fillable = [
        'code',
        'name',
        'type',
        'base_unit_id',
        'conversion_factor',
        'precision',
        'allow_decimal',
        'description',
        'is_active',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:6',
        'allow_decimal' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(self::class, 'base_unit_id');
    }

    public function derivedUnits(): HasMany
    {
        return $this->hasMany(self::class, 'base_unit_id');
    }
}
