<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    public const TYPES = [
        'LOCAL',
        'EXPORT',
        'DISTRIBUTOR',
        'RETAIL',
        'INTERNAL',
        'OTHER',
    ];

    protected $fillable = [
        'code',
        'name',
        'customer_group',
        'customer_type',
        'tax_number',
        'contact_person',
        'phone',
        'mobile',
        'email',
        'website',
        'billing_address',
        'billing_city',
        'billing_province',
        'billing_country',
        'billing_postal_code',
        'shipping_address',
        'shipping_city',
        'shipping_province',
        'shipping_country',
        'shipping_postal_code',
        'default_currency_id',
        'payment_term_id',
        'default_tax_id',
        'credit_limit',
        'salesman',
        'price_level',
        'ar_account_id',
        'blocked_sales',
        'is_active',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'blocked_sales' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function defaultCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'default_currency_id');
    }

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    public function defaultTax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'default_tax_id');
    }

    public function arAccount(): BelongsTo
    {
        return $this->belongsTo(AccountingAccount::class, 'ar_account_id');
    }
}
