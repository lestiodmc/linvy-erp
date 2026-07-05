<?php

namespace App\Http\Controllers\Admin;

use App\Models\AccountingAccount;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\PaymentTerm;
use App\Models\Tax;

class CustomerController extends ResourceController
{
    protected string $model = Customer::class;
    protected string $route = 'customers';
    protected string $title = 'Customer';
    protected string $viewPath = 'master.customers';
    protected array $columns = ['code', 'name', 'customer_type', 'billing_city', 'phone', 'is_active'];
    protected array $searchableColumns = ['code', 'name', 'phone', 'email', 'tax_number', 'billing_city', 'shipping_city'];
    protected array $rules = [
        'code' => ['required', 'string', 'max:255'],
        'name' => ['required', 'string', 'max:255'],
        'customer_group' => ['nullable', 'string', 'max:255'],
        'customer_type' => ['required', 'string', 'in:LOCAL,EXPORT,DISTRIBUTOR,RETAIL,INTERNAL,OTHER'],
        'tax_number' => ['nullable', 'string', 'max:255'],
        'contact_person' => ['nullable', 'string', 'max:255'],
        'phone' => ['nullable', 'string', 'max:255'],
        'mobile' => ['nullable', 'string', 'max:255'],
        'email' => ['nullable', 'email', 'max:255'],
        'website' => ['nullable', 'url', 'max:255'],
        'billing_address' => ['nullable', 'string'],
        'billing_city' => ['nullable', 'string', 'max:255'],
        'billing_province' => ['nullable', 'string', 'max:255'],
        'billing_country' => ['nullable', 'string', 'max:255'],
        'billing_postal_code' => ['nullable', 'string', 'max:255'],
        'shipping_address' => ['nullable', 'string'],
        'shipping_city' => ['nullable', 'string', 'max:255'],
        'shipping_province' => ['nullable', 'string', 'max:255'],
        'shipping_country' => ['nullable', 'string', 'max:255'],
        'shipping_postal_code' => ['nullable', 'string', 'max:255'],
        'default_currency_id' => ['nullable', 'exists:currencies,id'],
        'payment_term_id' => ['nullable', 'exists:payment_terms,id'],
        'default_tax_id' => ['nullable', 'exists:taxes,id'],
        'credit_limit' => ['nullable', 'numeric', 'gte:0'],
        'salesman' => ['nullable', 'string', 'max:255'],
        'price_level' => ['nullable', 'string', 'max:255'],
        'ar_account_id' => ['nullable', 'exists:accounting_accounts,id'],
        'blocked_sales' => ['nullable'],
        'is_active' => ['nullable'],
    ];

    public function __construct()
    {
        $this->fields = $this->baseFields();
    }

    protected function visibleFields(): array
    {
        if (request()->routeIs($this->route.'.create') || request()->routeIs($this->route.'.edit')) {
            return $this->baseFields(
                Currency::where('is_active', true)->orderBy('code')->pluck('code', 'id')->toArray(),
                PaymentTerm::where('is_active', true)->orderBy('due_days')->orderBy('code')->pluck('name', 'id')->toArray(),
                Tax::where('is_active', true)->orderBy('code')->pluck('name', 'id')->toArray(),
                AccountingAccount::where('is_active', true)->orderBy('code')->pluck('name', 'id')->toArray()
            );
        }

        return parent::visibleFields();
    }

    private function baseFields(array $currencies = [], array $paymentTerms = [], array $taxes = [], array $arAccounts = []): array
    {
        $customerTypes = array_combine(Customer::TYPES, Customer::TYPES);

        $this->fields = [
            'code' => ['label' => 'Code', 'type' => 'text'],
            'name' => ['label' => 'Name', 'type' => 'text'],
            'customer_group' => ['label' => 'Customer Group', 'type' => 'text'],
            'customer_type' => ['label' => 'Customer Type', 'type' => 'select', 'options' => $customerTypes, 'default' => 'LOCAL'],
            'tax_number' => ['label' => 'Tax Number', 'type' => 'text'],
            'contact_person' => ['label' => 'Contact Person', 'type' => 'text'],
            'phone' => ['label' => 'Phone', 'type' => 'text'],
            'mobile' => ['label' => 'Mobile', 'type' => 'text'],
            'email' => ['label' => 'Email', 'type' => 'email'],
            'website' => ['label' => 'Website', 'type' => 'url'],
            'billing_address' => ['label' => 'Billing Address', 'type' => 'textarea'],
            'billing_city' => ['label' => 'Billing City', 'type' => 'text'],
            'billing_province' => ['label' => 'Billing Province', 'type' => 'text'],
            'billing_country' => ['label' => 'Billing Country', 'type' => 'text'],
            'billing_postal_code' => ['label' => 'Billing Postal Code', 'type' => 'text'],
            'shipping_address' => ['label' => 'Shipping Address', 'type' => 'textarea'],
            'shipping_city' => ['label' => 'Shipping City', 'type' => 'text'],
            'shipping_province' => ['label' => 'Shipping Province', 'type' => 'text'],
            'shipping_country' => ['label' => 'Shipping Country', 'type' => 'text'],
            'shipping_postal_code' => ['label' => 'Shipping Postal Code', 'type' => 'text'],
            'default_currency_id' => ['label' => 'Default Currency', 'type' => 'select', 'options' => $currencies, 'nullable' => true],
            'payment_term_id' => ['label' => 'Payment Term', 'type' => 'select', 'options' => $paymentTerms, 'nullable' => true],
            'default_tax_id' => ['label' => 'Default Tax', 'type' => 'select', 'options' => $taxes, 'nullable' => true],
            'credit_limit' => ['label' => 'Credit Limit', 'type' => 'number', 'step' => '0.01', 'default' => 0],
            'salesman' => ['label' => 'Salesman', 'type' => 'text'],
            'price_level' => ['label' => 'Price Level', 'type' => 'text'],
            'ar_account_id' => ['label' => 'AR Account', 'type' => 'select', 'options' => $arAccounts, 'nullable' => true],
            'blocked_sales' => ['label' => 'Blocked Sales', 'type' => 'checkbox'],
            'is_active' => ['label' => 'Active', 'type' => 'checkbox', 'default' => true],
        ];

        return $this->fields;
    }
}
