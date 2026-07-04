<?php

namespace App\Http\Controllers\Admin;

use App\Models\Branch;
use App\Models\Company;

class BranchController extends ResourceController
{
    protected string $model = Branch::class;
    protected string $route = 'branches';
    protected string $title = 'Branch';
    protected string $viewPath = 'master.branches';
    protected array $with = ['company'];
    protected array $columns = ['code', 'name', 'company.name', 'is_active'];
    protected array $rules = [
        'company_id' => ['required', 'exists:companies,id'],
        'code' => ['required', 'string', 'max:255'],
        'name' => ['required', 'string', 'max:255'],
        'address' => ['nullable', 'string'],
        'is_active' => ['nullable'],
    ];

    public function __construct()
    {
        $this->fields = [
            'company_id' => ['label' => 'Company', 'type' => 'select', 'options' => Company::where('is_active', true)->orderBy('name')->pluck('name', 'id')->toArray()],
            'code' => ['label' => 'Code', 'type' => 'text'],
            'name' => ['label' => 'Name', 'type' => 'text'],
            'address' => ['label' => 'Address', 'type' => 'textarea'],
            'is_active' => ['label' => 'Active', 'type' => 'checkbox'],
        ];
    }
}
