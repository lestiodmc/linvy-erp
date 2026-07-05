<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            [
                'code' => 'SUP001',
                'name' => 'PT Laut Biru Seafood',
                'supplier_group' => 'Seafood Processor',
                'supplier_type' => 'MANUFACTURER',
                'city' => 'Surabaya',
                'province' => 'Jawa Timur',
                'phone' => '031-548-1088',
            ],
            [
                'code' => 'SUP002',
                'name' => 'PT Mina Bahari Nusantara',
                'supplier_group' => 'Raw Material',
                'supplier_type' => 'DISTRIBUTOR',
                'city' => 'Sidoarjo',
                'province' => 'Jawa Timur',
                'phone' => '031-805-2288',
            ],
            [
                'code' => 'SUP003',
                'name' => 'PT Samudera Segar Indonesia',
                'supplier_group' => 'Frozen Seafood',
                'supplier_type' => 'IMPORTER',
                'city' => 'Jakarta Utara',
                'province' => 'DKI Jakarta',
                'phone' => '021-669-3288',
            ],
            [
                'code' => 'SUP004',
                'name' => 'CV Anugerah Laut',
                'supplier_group' => 'Local Fishery',
                'supplier_type' => 'LOCAL',
                'city' => 'Gresik',
                'province' => 'Jawa Timur',
                'phone' => '031-398-4188',
            ],
            [
                'code' => 'SUP005',
                'name' => 'CV Bahari Makmur',
                'supplier_group' => 'Packaging',
                'supplier_type' => 'DISTRIBUTOR',
                'city' => 'Mojokerto',
                'province' => 'Jawa Timur',
                'phone' => '0321-556-778',
            ],
            [
                'code' => 'SUP006',
                'name' => 'PT Ocean Fresh Indonesia',
                'supplier_group' => 'Cold Chain Service',
                'supplier_type' => 'SERVICE',
                'city' => 'Surabaya',
                'province' => 'Jawa Timur',
                'phone' => '031-749-9088',
            ],
            [
                'code' => 'SUP007',
                'name' => 'PT Laut Nusantara',
                'supplier_group' => 'Aquaculture',
                'supplier_type' => 'FARMER',
                'city' => 'Banyuwangi',
                'province' => 'Jawa Timur',
                'phone' => '0333-421-880',
            ],
            [
                'code' => 'SUP008',
                'name' => 'CV Mitra Seafood',
                'supplier_group' => 'Local Fishery',
                'supplier_type' => 'LOCAL',
                'city' => 'Lamongan',
                'province' => 'Jawa Timur',
                'phone' => '0322-318-880',
            ],
            [
                'code' => 'SUP009',
                'name' => 'PT Sumber Laut Abadi',
                'supplier_group' => 'Consumable',
                'supplier_type' => 'DISTRIBUTOR',
                'city' => 'Semarang',
                'province' => 'Jawa Tengah',
                'phone' => '024-764-1188',
            ],
            [
                'code' => 'SUP010',
                'name' => 'PT Indo Marine Food',
                'supplier_group' => 'Internal Supply',
                'supplier_type' => 'INTERNAL',
                'city' => 'Surabaya',
                'province' => 'Jawa Timur',
                'phone' => '031-990-2188',
            ],
        ] as $supplier) {
            $slug = strtolower(str_replace(['PT ', 'CV ', ' '], ['', '', '.'], $supplier['name']));

            Supplier::updateOrCreate(['code' => $supplier['code']], [
                'name' => $supplier['name'],
                'supplier_group' => $supplier['supplier_group'],
                'supplier_type' => $supplier['supplier_type'],
                'tax_number' => '0'.substr($supplier['code'], -3).'.456.789.0-'.substr($supplier['code'], -3).'.000',
                'contact_person' => 'Purchasing Desk',
                'phone' => $supplier['phone'],
                'mobile' => '0812-33'.substr($supplier['code'], -3).'-7788',
                'email' => 'procurement@'.$slug.'.co.id',
                'website' => 'https://'.$slug.'.co.id',
                'address' => 'Kawasan Industri '.$supplier['city'],
                'city' => $supplier['city'],
                'province' => $supplier['province'],
                'country' => 'Indonesia',
                'postal_code' => '60'.substr($supplier['code'], -3),
                'default_currency_id' => null,
                'payment_term_id' => null,
                'lead_time_days' => 3,
                'default_tax_id' => null,
                'ap_account_id' => null,
                'blocked_purchase' => false,
                'is_active' => true,
            ]);
        }
    }
}
