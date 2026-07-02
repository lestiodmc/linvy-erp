<?php

namespace App\Http\Controllers\Admin;

use App\Models\Supplier;

class SupplierController extends ResourceController
{
    protected string $model = Supplier::class;
    protected string $route = 'suppliers';
    protected string $title = 'Supplier';
    protected array $columns = ['code', 'name', 'contact_person', 'phone', 'is_active'];
    protected array $fields = ['code' => ['label' => 'Code', 'type' => 'text'], 'name' => ['label' => 'Name', 'type' => 'text'], 'contact_person' => ['label' => 'Contact Person', 'type' => 'text'], 'phone' => ['label' => 'Phone', 'type' => 'text'], 'email' => ['label' => 'Email', 'type' => 'email'], 'address' => ['label' => 'Address', 'type' => 'textarea'], 'is_active' => ['label' => 'Active', 'type' => 'checkbox']];
    protected array $rules = ['code' => ['required', 'string', 'max:255'], 'name' => ['required', 'string', 'max:255'], 'contact_person' => ['nullable', 'string'], 'phone' => ['nullable', 'string'], 'email' => ['nullable', 'email'], 'address' => ['nullable', 'string'], 'is_active' => ['nullable']];
}
