<?php

namespace App\Http\Controllers\Admin;

use App\Models\PaymentTerm;

class PaymentTermController extends ResourceController
{
    protected string $model = PaymentTerm::class;
    protected string $route = 'payment-terms';
    protected string $title = 'Payment Term';
    protected string $viewPath = 'master.payment-terms';
    protected array $columns = ['code', 'name', 'due_days', 'is_active'];
    protected array $searchableColumns = ['code', 'name', 'description'];
    protected array $fields = [
        'code' => ['label' => 'Code', 'type' => 'text'],
        'name' => ['label' => 'Name', 'type' => 'text'],
        'due_days' => ['label' => 'Due Days', 'type' => 'number', 'step' => '1', 'default' => 0],
        'description' => ['label' => 'Description', 'type' => 'textarea'],
        'is_active' => ['label' => 'Active', 'type' => 'checkbox', 'default' => true],
    ];
    protected array $rules = [
        'code' => ['required', 'string', 'max:255'],
        'name' => ['required', 'string', 'max:255'],
        'due_days' => ['nullable', 'integer', 'min:0'],
        'description' => ['nullable', 'string'],
        'is_active' => ['nullable'],
    ];
}
