<?php

namespace App\Http\Controllers\Admin;

use App\Models\UnitOfMeasure;

class UnitOfMeasureController extends ResourceController
{
    protected string $model = UnitOfMeasure::class;
    protected string $route = 'units-of-measure';
    protected string $title = 'Unit of Measure';
    protected array $columns = ['code', 'name', 'precision', 'is_active'];
    protected array $fields = [
        'code' => ['label' => 'Code', 'type' => 'text'],
        'name' => ['label' => 'Name', 'type' => 'text'],
        'precision' => ['label' => 'Precision', 'type' => 'number', 'step' => '1'],
        'is_active' => ['label' => 'Active', 'type' => 'checkbox'],
    ];
    protected array $rules = ['code' => ['required', 'string', 'max:255'], 'name' => ['required', 'string', 'max:255'], 'precision' => ['required', 'integer', 'min:0'], 'is_active' => ['nullable']];
}
