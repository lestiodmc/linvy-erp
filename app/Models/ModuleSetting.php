<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModuleSetting extends Model
{
    protected $guarded = [];

    protected $casts = [
        'enabled' => 'boolean',
    ];
}
