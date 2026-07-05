<?php

namespace Database\Seeders;

use App\Models\PaymentTerm;
use Illuminate\Database\Seeder;

class PaymentTermSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['code' => 'CASH', 'name' => 'Cash', 'due_days' => 0],
            ['code' => 'COD', 'name' => 'Cash On Delivery', 'due_days' => 0],
            ['code' => 'NET07', 'name' => 'Net 7 Days', 'due_days' => 7],
            ['code' => 'NET14', 'name' => 'Net 14 Days', 'due_days' => 14],
            ['code' => 'NET30', 'name' => 'Net 30 Days', 'due_days' => 30],
        ] as $term) {
            PaymentTerm::updateOrCreate(['code' => $term['code']], [
                'name' => $term['name'],
                'due_days' => $term['due_days'],
                'description' => $term['name'].' payment term',
                'is_active' => true,
            ]);
        }
    }
}
