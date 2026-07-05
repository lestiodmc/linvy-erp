<?php

namespace Database\Seeders;

use App\Models\UnitOfMeasure;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['code' => 'PCS', 'name' => 'Pieces', 'type' => 'BASE', 'base_unit_code' => null, 'conversion_factor' => 1, 'precision' => 0, 'allow_decimal' => false],
            ['code' => 'KG', 'name' => 'Kilogram', 'type' => 'BASE', 'base_unit_code' => null, 'conversion_factor' => 1, 'precision' => 3, 'allow_decimal' => true],
            ['code' => 'BOX', 'name' => 'Box', 'type' => 'PACKAGING', 'base_unit_code' => 'PCS', 'conversion_factor' => 1, 'precision' => 0, 'allow_decimal' => false],
            ['code' => 'PACK', 'name' => 'Pack', 'type' => 'PACKAGING', 'base_unit_code' => 'PCS', 'conversion_factor' => 1, 'precision' => 0, 'allow_decimal' => false],
            ['code' => 'ROLL', 'name' => 'Roll', 'type' => 'PACKAGING', 'base_unit_code' => 'PCS', 'conversion_factor' => 1, 'precision' => 0, 'allow_decimal' => false],
        ] as $unit) {
            $baseUnitId = $unit['base_unit_code']
                ? UnitOfMeasure::where('code', $unit['base_unit_code'])->value('id')
                : null;

            UnitOfMeasure::updateOrCreate(['code' => $unit['code']], [
                'name' => $unit['name'],
                'type' => $unit['type'],
                'base_unit_id' => $baseUnitId,
                'conversion_factor' => $unit['type'] === 'BASE' ? 1 : $unit['conversion_factor'],
                'precision' => $unit['precision'],
                'allow_decimal' => $unit['allow_decimal'],
                'description' => $unit['name'].' unit of measure',
                'is_active' => true,
            ]);
        }
    }
}
