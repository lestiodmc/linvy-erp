<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    protected $guarded = [];

    public function stockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }
}
