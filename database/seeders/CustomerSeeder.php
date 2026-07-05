<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\Customer;
use App\Models\PaymentTerm;
use App\Models\Tax;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $idr = Currency::where('code', 'IDR')->first();
        $cash = PaymentTerm::where('code', 'CASH')->first();
        $net30 = PaymentTerm::where('code', 'NET30')->first();
        $vat = Tax::whereIn('code', ['VAT11', 'VAT12'])->orderBy('code')->first();

        foreach ([
            ['code' => 'CUS001', 'name' => 'PT Sumber Pangan Indonesia', 'type' => 'DISTRIBUTOR', 'city' => 'Jakarta Barat', 'term' => $net30],
            ['code' => 'CUS002', 'name' => 'PT Seafood Prima', 'type' => 'LOCAL', 'city' => 'Surabaya', 'term' => $net30],
            ['code' => 'CUS003', 'name' => 'PT Frozen Food Indonesia', 'type' => 'DISTRIBUTOR', 'city' => 'Tangerang', 'term' => $net30],
            ['code' => 'CUS004', 'name' => 'CV Nusantara Food', 'type' => 'LOCAL', 'city' => 'Bandung', 'term' => $cash],
            ['code' => 'CUS005', 'name' => 'PT Mitra Retail Indonesia', 'type' => 'RETAIL', 'city' => 'Jakarta Selatan', 'term' => $net30],
            ['code' => 'CUS006', 'name' => 'PT Indo Fresh Market', 'type' => 'RETAIL', 'city' => 'Sidoarjo', 'term' => $cash],
            ['code' => 'CUS007', 'name' => 'PT Citra Boga', 'type' => 'LOCAL', 'city' => 'Semarang', 'term' => $net30],
            ['code' => 'CUS008', 'name' => 'PT Surya Frozen', 'type' => 'DISTRIBUTOR', 'city' => 'Malang', 'term' => $net30],
            ['code' => 'CUS009', 'name' => 'PT Bahagia Mart', 'type' => 'RETAIL', 'city' => 'Yogyakarta', 'term' => $cash],
            ['code' => 'CUS010', 'name' => 'CV Sentosa Abadi', 'type' => 'LOCAL', 'city' => 'Denpasar', 'term' => $net30],
        ] as $customer) {
            $slug = strtolower(str_replace(['PT ', 'CV ', ' '], ['', '', '.'], $customer['name']));

            Customer::updateOrCreate(['code' => $customer['code']], [
                'name' => $customer['name'],
                'customer_group' => 'Food Distribution',
                'customer_type' => $customer['type'],
                'tax_number' => '0'.substr($customer['code'], -3).'.123.456.7-'.substr($customer['code'], -3).'.000',
                'contact_person' => 'Procurement Team',
                'phone' => '021-77'.substr($customer['code'], -3).'66',
                'mobile' => '0812-44'.substr($customer['code'], -3).'-5566',
                'email' => 'procurement@'.$slug.'.co.id',
                'website' => 'https://'.$slug.'.co.id',
                'billing_address' => $customer['city'].' Distribution Center',
                'billing_city' => $customer['city'],
                'billing_province' => 'Indonesia',
                'billing_country' => 'Indonesia',
                'billing_postal_code' => '10'.substr($customer['code'], -3),
                'shipping_address' => $customer['city'].' Cold Storage Hub',
                'shipping_city' => $customer['city'],
                'shipping_province' => 'Indonesia',
                'shipping_country' => 'Indonesia',
                'shipping_postal_code' => '11'.substr($customer['code'], -3),
                'default_currency_id' => $idr?->id,
                'payment_term_id' => $customer['term']?->id,
                'default_tax_id' => $vat?->id,
                'credit_limit' => $customer['term']?->code === 'CASH' ? 0 : 50000000,
                'salesman' => 'Linvy Sales Team',
                'price_level' => $customer['type'] === 'RETAIL' ? 'Retail' : 'Wholesale',
                'ar_account_id' => null,
                'blocked_sales' => false,
                'is_active' => true,
            ]);
        }
    }
}
