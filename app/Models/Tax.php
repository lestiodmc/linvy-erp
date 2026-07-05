<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    public const TYPES = [
        'VAT',
        'WITHHOLDING',
        'SALES',
        'PURCHASE',
        'OTHER',
    ];

    protected $fillable = [
        'code',
        'name',
        'tax_type',
        'rate',
        'is_inclusive',
        'description',
        'is_active',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'is_inclusive' => 'boolean',
        'is_active' => 'boolean',
    ];
}
