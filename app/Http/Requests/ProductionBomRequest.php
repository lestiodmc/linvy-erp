<?php

namespace App\Http\Requests;

use App\Models\ProductionBom;
use App\Models\ProductionBomMaterial;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductionBomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->canPerform('production.bom.create', 'production')
            || $this->user()?->canPerform('production.bom.edit', 'production'));
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:255'],
            'production_type' => ['required', Rule::in(ProductionBom::TYPES)],
            'finished_item_id' => ['required', 'integer', 'exists:items,id'],
            'base_output_quantity' => ['required', 'numeric', 'gt:0'],
            'output_uom_id' => ['required', 'integer', 'exists:units_of_measure,id'],
            'default_source_warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'default_destination_warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'version' => ['prohibited'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'materials' => ['required', 'array', 'min:1'],
            'materials.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'materials.*.quantity' => ['required', 'numeric', 'gt:0'],
            'materials.*.uom_id' => ['required', 'integer', 'exists:units_of_measure,id'],
            'materials.*.quantity_type' => ['required', Rule::in(ProductionBomMaterial::TYPES)],
            'materials.*.source_warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'materials.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
