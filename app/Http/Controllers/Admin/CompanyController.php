<?php

namespace App\Http\Controllers\Admin;

use App\Models\Company;

class CompanyController extends ResourceController
{
    protected string $model = Company::class;
    protected string $route = 'companies';
    protected string $title = 'Company';
    protected string $viewPath = 'master.companies';
    protected array $columns = ['code', 'name', 'is_active'];
    protected array $fields = [
        'code' => ['label' => 'Code', 'type' => 'text'],
        'name' => ['label' => 'Name', 'type' => 'text'],
        'is_active' => ['label' => 'Active', 'type' => 'checkbox'],
    ];
    protected array $rules = [
        'code' => ['required', 'string', 'max:255'],
        'name' => ['required', 'string', 'max:255'],
        'is_active' => ['nullable'],
    ];
}
