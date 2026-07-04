<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseType extends Model
{
    protected $guarded = [];

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }

    public function defaultItems(): HasMany
    {
        return $this->hasMany(Item::class, 'default_warehouse_type_id');
    }
}
