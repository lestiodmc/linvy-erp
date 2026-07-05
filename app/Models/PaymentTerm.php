<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentTerm extends Model
{
    protected $fillable = [
        'code',
        'name',
        'due_days',
        'description',
        'is_active',
    ];

    protected $casts = [
        'due_days' => 'integer',
        'is_active' => 'boolean',
    ];
}
