<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Supplier extends Model
{
    public const TYPES = [
        'MANUFACTURER',
        'DISTRIBUTOR',
        'IMPORTER',
        'LOCAL',
        'SERVICE',
        'FARMER',
        'INTERNAL',
    ];

    protected $fillable = [
        'code',
        'name',
        'supplier_group',
        'supplier_type',
        'tax_number',
        'contact_person',
        'phone',
        'mobile',
        'email',
        'website',
        'address',
        'city',
        'province',
        'country',
        'postal_code',
        'default_currency_id',
        'payment_term_id',
        'lead_time_days',
        'default_tax_id',
        'ap_account_id',
        'blocked_purchase',
        'is_active',
    ];

    protected $casts = [
        'lead_time_days' => 'integer',
        'blocked_purchase' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function apAccount(): BelongsTo
    {
        return $this->belongsTo(AccountingAccount::class, 'ap_account_id');
    }
}
