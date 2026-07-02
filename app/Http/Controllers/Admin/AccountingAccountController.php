<?php

namespace App\Http\Controllers\Admin;

use App\Models\AccountingAccount;

class AccountingAccountController extends ResourceController
{
    protected string $model = AccountingAccount::class;
    protected string $route = 'accounting-accounts';
    protected string $title = 'Accounting Account';
    protected array $columns = ['code', 'name', 'type', 'is_active'];
    protected array $fields = [
        'code' => ['label' => 'Code', 'type' => 'text'],
        'name' => ['label' => 'Name', 'type' => 'text'],
        'type' => ['label' => 'Type', 'type' => 'select', 'options' => ['asset' => 'Asset', 'liability' => 'Liability', 'equity' => 'Equity', 'revenue' => 'Revenue', 'expense' => 'Expense']],
        'is_active' => ['label' => 'Active', 'type' => 'checkbox'],
    ];
    protected array $rules = ['code' => ['required', 'string', 'max:255'], 'name' => ['required', 'string', 'max:255'], 'type' => ['required', 'string'], 'is_active' => ['nullable']];
}
