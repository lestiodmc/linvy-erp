<?php

namespace App\Http\Controllers\Admin;

use App\Models\Brand;

class BrandController extends ResourceController
{
    protected string $model = Brand::class;
    protected string $route = 'brands';
    protected string $title = 'Brand';
    protected string $viewPath = 'master.brands';
    protected array $columns = ['code', 'name', 'description', 'is_active'];
    protected array $fields = [
        'code' => ['label' => 'Code', 'type' => 'text'],
        'name' => ['label' => 'Name', 'type' => 'text'],
        'description' => ['label' => 'Description', 'type' => 'textarea'],
        'is_active' => ['label' => 'Active', 'type' => 'checkbox'],
    ];
    protected array $rules = [
        'code' => ['required', 'string', 'max:255'],
        'name' => ['required', 'string', 'max:255'],
        'description' => ['nullable', 'string'],
        'is_active' => ['nullable'],
    ];
}
