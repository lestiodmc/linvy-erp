<?php

namespace App\Http\Controllers\Admin;

use App\Models\Tax;

class TaxController extends ResourceController
{
    protected string $model = Tax::class;
    protected string $route = 'taxes';
    protected string $title = 'Tax';
    protected string $viewPath = 'master.taxes';
    protected array $columns = ['code', 'name', 'tax_type', 'rate', 'is_inclusive', 'is_active'];
    protected array $searchableColumns = ['code', 'name', 'description'];
    protected array $fields = [
        'code' => ['label' => 'Code', 'type' => 'text'],
        'name' => ['label' => 'Name', 'type' => 'text'],
        'tax_type' => ['label' => 'Type', 'type' => 'select', 'options' => [
            'VAT' => 'VAT',
            'WITHHOLDING' => 'WITHHOLDING',
            'SALES' => 'SALES',
            'PURCHASE' => 'PURCHASE',
            'OTHER' => 'OTHER',
        ]],
        'rate' => ['label' => 'Rate', 'type' => 'number', 'step' => '0.0001', 'default' => 0],
        'is_inclusive' => ['label' => 'Inclusive', 'type' => 'checkbox'],
        'description' => ['label' => 'Description', 'type' => 'textarea'],
        'is_active' => ['label' => 'Active', 'type' => 'checkbox', 'default' => true],
    ];
    protected array $rules = [
        'code' => ['required', 'string', 'max:255'],
        'name' => ['required', 'string', 'max:255'],
        'tax_type' => ['required', 'string', 'in:VAT,WITHHOLDING,SALES,PURCHASE,OTHER'],
        'rate' => ['nullable', 'numeric', 'gte:0'],
        'is_inclusive' => ['nullable'],
        'description' => ['nullable', 'string'],
        'is_active' => ['nullable'],
    ];
}
