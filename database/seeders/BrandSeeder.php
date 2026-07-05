<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['code' => 'NOBRAND', 'name' => 'No Brand'],
            ['code' => 'LOCAL', 'name' => 'Local Brand'],
            ['code' => 'OEM', 'name' => 'OEM'],
            ['code' => 'IMPORT', 'name' => 'Import Brand'],
        ] as $brand) {
            Brand::updateOrCreate(['code' => $brand['code']], [
                'name' => $brand['name'],
                'description' => $brand['name'],
                'is_active' => true,
            ]);
        }
    }
}
