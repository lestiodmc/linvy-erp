<?php

namespace App\Http\Controllers\Admin;

use App\Models\Customer;

class CustomerController extends ResourceController
{
    protected string $model = Customer::class;
    protected string $route = 'customers';
    protected string $title = 'Customer';
    protected array $columns = ['code', 'name', 'contact_person', 'phone', 'is_active'];
    protected array $fields = ['code' => ['label' => 'Code', 'type' => 'text'], 'name' => ['label' => 'Name', 'type' => 'text'], 'contact_person' => ['label' => 'Contact Person', 'type' => 'text'], 'phone' => ['label' => 'Phone', 'type' => 'text'], 'email' => ['label' => 'Email', 'type' => 'email'], 'billing_address' => ['label' => 'Billing Address', 'type' => 'textarea'], 'shipping_address' => ['label' => 'Shipping Address', 'type' => 'textarea'], 'is_active' => ['label' => 'Active', 'type' => 'checkbox']];
    protected array $rules = ['code' => ['required', 'string', 'max:255'], 'name' => ['required', 'string', 'max:255'], 'contact_person' => ['nullable', 'string'], 'phone' => ['nullable', 'string'], 'email' => ['nullable', 'email'], 'billing_address' => ['nullable', 'string'], 'shipping_address' => ['nullable', 'string'], 'is_active' => ['nullable']];
}
