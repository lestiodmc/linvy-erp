<?php

namespace App\Http\Controllers\Admin;

use App\Models\Currency;

class CurrencyController extends ResourceController
{
    protected string $model = Currency::class;
    protected string $route = 'currencies';
    protected string $title = 'Currency';
    protected string $viewPath = 'master.currencies';
    protected array $columns = ['code', 'name', 'symbol', 'decimal_places', 'is_base_currency', 'is_active'];
    protected array $searchableColumns = ['code', 'name', 'symbol'];
    protected array $fields = [
        'code' => ['label' => 'Code', 'type' => 'text'],
        'name' => ['label' => 'Name', 'type' => 'text'],
        'symbol' => ['label' => 'Symbol', 'type' => 'text'],
        'decimal_places' => ['label' => 'Decimal Places', 'type' => 'number', 'step' => '1', 'default' => 2],
        'is_base_currency' => ['label' => 'Base Currency', 'type' => 'checkbox'],
        'is_active' => ['label' => 'Active', 'type' => 'checkbox', 'default' => true],
    ];
    protected array $rules = [
        'code' => ['required', 'string', 'max:255'],
        'name' => ['required', 'string', 'max:255'],
        'symbol' => ['nullable', 'string', 'max:255'],
        'decimal_places' => ['nullable', 'integer', 'min:0', 'max:8'],
        'is_base_currency' => ['nullable'],
        'is_active' => ['nullable'],
    ];
}
