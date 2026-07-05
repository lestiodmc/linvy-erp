<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = [
        'code',
        'name',
        'symbol',
        'decimal_places',
        'is_base_currency',
        'is_active',
    ];

    protected $casts = [
        'decimal_places' => 'integer',
        'is_base_currency' => 'boolean',
        'is_active' => 'boolean',
    ];
}
