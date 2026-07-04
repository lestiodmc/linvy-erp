<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\UnitOfMeasure;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        $categories = ItemCategory::pluck('id', 'code');
        $units = UnitOfMeasure::pluck('id', 'code');

        $items = [
            ['RM001', 'Kepiting Raw Jumbo', 'raw_material', 'RM', 'KG', 95000],
            ['RM002', 'Kepiting Raw Medium', 'raw_material', 'RM', 'KG', 78000],
            ['RM003', 'Kepiting Raw Small', 'raw_material', 'RM', 'KG', 62000],
            ['RM004', 'Kepiting Soft Shell', 'raw_material', 'RM', 'KG', 115000],
            ['RM005', 'Kepiting Rajungan', 'raw_material', 'RM', 'KG', 88000],
            ['PK001', 'Plastik Vacuum 500gr', 'packaging_material', 'PK', 'PACK', 450],
            ['PK002', 'Plastik Vacuum 1kg', 'packaging_material', 'PK', 'PACK', 700],
            ['PK003', 'Plastik Vacuum 2kg', 'packaging_material', 'PK', 'PACK', 1100],
            ['PK004', 'Kardus Export', 'packaging_material', 'PK', 'PCS', 8500],
            ['PK005', 'Label Produk', 'packaging_material', 'PK', 'PCS', 150],
            ['PK006', 'Lakban', 'packaging_material', 'PK', 'ROLL', 12000],
            ['PK007', 'Plastik PE', 'packaging_material', 'PK', 'ROLL', 28000],
            ['PK008', 'Plastik HD', 'packaging_material', 'PK', 'ROLL', 32000],
            ['PK009', 'Tray Foam', 'packaging_material', 'PK', 'PCS', 900],
            ['PK010', 'Ice Gel', 'packaging_material', 'PK', 'PCS', 2500],
            ['FG001', 'Kepiting Frozen 500gr', 'finished_goods', 'FG', 'PACK', 62000],
            ['FG002', 'Kepiting Frozen 1kg', 'finished_goods', 'FG', 'PACK', 118000],
            ['FG003', 'Kepiting Frozen 2kg', 'finished_goods', 'FG', 'PACK', 225000],
            ['FG004', 'Kepiting Premium Export', 'finished_goods', 'FG', 'BOX', 950000],
            ['FG005', 'Kepiting Vacuum Pack', 'finished_goods', 'FG', 'PACK', 135000],
            ['FG006', 'Rajungan Frozen', 'finished_goods', 'FG', 'PACK', 105000],
            ['FG007', 'Soft Shell Frozen', 'finished_goods', 'FG', 'PACK', 145000],
            ['FG008', 'Seafood Mix', 'finished_goods', 'FG', 'PACK', 98000],
            ['FG009', 'Crab Meat Premium', 'finished_goods', 'FG', 'KG', 260000],
            ['FG010', 'Crab Meat Regular', 'finished_goods', 'FG', 'KG', 185000],
            ['CS001', 'Sarung Tangan', 'consumable', 'CS', 'BOX', 45000],
            ['CS002', 'Hairnet', 'consumable', 'CS', 'BOX', 38000],
            ['CS003', 'Masker', 'consumable', 'CS', 'BOX', 42000],
            ['CS004', 'Cleaning Chemical', 'consumable', 'CS', 'KG', 30000],
            ['CS005', 'Disinfectant', 'consumable', 'CS', 'KG', 36000],
        ];

        foreach ($items as [$sku, $name, $type, $categoryCode, $unitCode, $standardCost]) {
            Item::updateOrCreate(['sku' => $sku], [
                'name' => $name,
                'type' => $type,
                'item_category_id' => $categories[$categoryCode],
                'unit_of_measure_id' => $units[$unitCode],
                'is_stock_item' => true,
                'standard_cost' => $standardCost,
                'is_active' => true,
                'notes' => 'PT Linvy Seafood Indonesia demo item',
            ]);
        }
    }
}
