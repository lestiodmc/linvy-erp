<?php

namespace App\Http\Controllers\Admin;

use App\Models\UnitOfMeasure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class UnitOfMeasureController extends ResourceController
{
    protected string $model = UnitOfMeasure::class;
    protected string $route = 'units-of-measure';
    protected string $title = 'Unit of Measure';
    protected string $viewPath = 'master.units';
    protected array $with = ['baseUnit'];
    protected array $columns = ['code', 'name', 'type', 'baseUnit.code', 'conversion_factor', 'precision', 'allow_decimal', 'is_active'];
    protected array $rules = [
        'code' => ['required', 'string', 'max:255'],
        'name' => ['required', 'string', 'max:255'],
        'type' => ['required', 'string', 'in:BASE,PURCHASE,SALES,PACKAGING'],
        'base_unit_id' => ['nullable', 'exists:units_of_measure,id'],
        'conversion_factor' => ['required', 'numeric', 'gt:0'],
        'precision' => ['required', 'integer', 'min:0'],
        'allow_decimal' => ['nullable'],
        'description' => ['nullable', 'string'],
        'is_active' => ['nullable'],
    ];

    public function __construct()
    {
        $this->fields = $this->baseFields();
    }

    protected function visibleFields(): array
    {
        if (request()->routeIs($this->route.'.create') || request()->routeIs($this->route.'.edit')) {
            return $this->baseFields(
                UnitOfMeasure::where('is_active', true)->orderBy('code')->pluck('code', 'id')->toArray()
            );
        }

        return parent::visibleFields();
    }

    protected function validated(Request $request, ?Model $record = null): array
    {
        $data = parent::validated($request, $record);

        if (($data['type'] ?? null) === 'BASE') {
            $data['base_unit_id'] = null;
            $data['conversion_factor'] = 1;
        }

        return $data;
    }

    private function baseFields(array $baseUnits = []): array
    {
        $this->fields = [
            'code' => ['label' => 'Code', 'type' => 'text'],
            'name' => ['label' => 'Name', 'type' => 'text'],
            'type' => ['label' => 'Type', 'type' => 'select', 'options' => ['BASE' => 'Base', 'PURCHASE' => 'Purchase', 'SALES' => 'Sales', 'PACKAGING' => 'Packaging'], 'default' => 'BASE'],
            'base_unit_id' => ['label' => 'Base Unit', 'type' => 'select', 'options' => $baseUnits, 'nullable' => true],
            'conversion_factor' => ['label' => 'Conversion Factor', 'type' => 'number', 'step' => '0.000001', 'default' => 1],
            'precision' => ['label' => 'Precision', 'type' => 'number', 'step' => '1'],
            'allow_decimal' => ['label' => 'Allow Decimal', 'type' => 'checkbox'],
            'description' => ['label' => 'Description', 'type' => 'textarea'],
            'is_active' => ['label' => 'Active', 'type' => 'checkbox'],
        ];

        return $this->fields;
    }
}
