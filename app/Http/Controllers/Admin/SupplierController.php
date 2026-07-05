<?php

namespace App\Http\Controllers\Admin;

use App\Models\AccountingAccount;
use App\Models\Supplier;

class SupplierController extends ResourceController
{
    protected string $model = Supplier::class;
    protected string $route = 'suppliers';
    protected string $title = 'Supplier';
    protected string $viewPath = 'master.suppliers';
    protected array $columns = ['code', 'name', 'supplier_type', 'city', 'phone', 'is_active'];
    protected array $rules = [
        'code' => ['required', 'string', 'max:255'],
        'name' => ['required', 'string', 'max:255'],
        'supplier_group' => ['nullable', 'string', 'max:255'],
        'supplier_type' => ['required', 'string', 'in:MANUFACTURER,DISTRIBUTOR,IMPORTER,LOCAL,SERVICE,FARMER,INTERNAL'],
        'tax_number' => ['nullable', 'string', 'max:255'],
        'contact_person' => ['nullable', 'string', 'max:255'],
        'phone' => ['required', 'string', 'max:255'],
        'mobile' => ['nullable', 'string', 'max:255'],
        'email' => ['nullable', 'email', 'max:255'],
        'website' => ['nullable', 'url', 'max:255'],
        'address' => ['required', 'string'],
        'city' => ['nullable', 'string', 'max:255'],
        'province' => ['nullable', 'string', 'max:255'],
        'country' => ['nullable', 'string', 'max:255'],
        'postal_code' => ['nullable', 'string', 'max:255'],
        'default_currency_id' => ['nullable', 'integer', 'min:1'],
        'payment_term_id' => ['nullable', 'integer', 'min:1'],
        'lead_time_days' => ['nullable', 'integer', 'min:0'],
        'default_tax_id' => ['nullable', 'integer', 'min:1'],
        'ap_account_id' => ['nullable', 'exists:accounting_accounts,id'],
        'blocked_purchase' => ['nullable'],
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
                AccountingAccount::where('is_active', true)->orderBy('code')->pluck('name', 'id')->toArray()
            );
        }

        return parent::visibleFields();
    }

    private function baseFields(array $apAccounts = []): array
    {
        $supplierTypes = array_combine(Supplier::TYPES, Supplier::TYPES);

        $this->fields = [
            'code' => ['label' => 'Code', 'type' => 'text'],
            'name' => ['label' => 'Name', 'type' => 'text'],
            'supplier_group' => ['label' => 'Supplier Group', 'type' => 'text'],
            'supplier_type' => ['label' => 'Supplier Type', 'type' => 'select', 'options' => $supplierTypes, 'default' => 'LOCAL'],
            'tax_number' => ['label' => 'Tax Number', 'type' => 'text'],
            'contact_person' => ['label' => 'Contact Person', 'type' => 'text'],
            'phone' => ['label' => 'Phone', 'type' => 'text'],
            'mobile' => ['label' => 'Mobile', 'type' => 'text'],
            'email' => ['label' => 'Email', 'type' => 'email'],
            'website' => ['label' => 'Website', 'type' => 'url'],
            'address' => ['label' => 'Address', 'type' => 'textarea'],
            'city' => ['label' => 'City', 'type' => 'text'],
            'province' => ['label' => 'Province', 'type' => 'text'],
            'country' => ['label' => 'Country', 'type' => 'text'],
            'postal_code' => ['label' => 'Postal Code', 'type' => 'text'],
            'default_currency_id' => ['label' => 'Default Currency ID', 'type' => 'number', 'step' => '1'],
            'payment_term_id' => ['label' => 'Payment Term ID', 'type' => 'number', 'step' => '1'],
            'lead_time_days' => ['label' => 'Lead Time Days', 'type' => 'number', 'step' => '1', 'default' => 0],
            'default_tax_id' => ['label' => 'Default Tax ID', 'type' => 'number', 'step' => '1'],
            'ap_account_id' => ['label' => 'AP Account', 'type' => 'select', 'options' => $apAccounts, 'nullable' => true],
            'blocked_purchase' => ['label' => 'Blocked Purchase', 'type' => 'checkbox'],
            'is_active' => ['label' => 'Active', 'type' => 'checkbox', 'default' => true],
        ];

        return $this->fields;
    }
}
