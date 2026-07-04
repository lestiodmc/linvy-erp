<?php

namespace Database\Seeders;

use App\Models\UnitOfMeasure;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['code' => 'KG', 'name' => 'Kilogram', 'precision' => 3],
            ['code' => 'PCS', 'name' => 'Pieces', 'precision' => 0],
            ['code' => 'BOX', 'name' => 'Box', 'precision' => 0],
            ['code' => 'PACK', 'name' => 'Pack', 'precision' => 0],
            ['code' => 'ROLL', 'name' => 'Roll', 'precision' => 0],
        ] as $unit) {
            UnitOfMeasure::updateOrCreate(['code' => $unit['code']], $unit + ['is_active' => true]);
        }
    }
}
