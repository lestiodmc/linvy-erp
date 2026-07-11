<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class ItemLedgerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'item_id' => ['nullable', 'integer', 'exists:items,id'],
            'sku' => ['nullable', 'string', 'max:100'],
            'batch_no' => ['nullable', 'string', 'max:100'],
            'movement_type' => ['nullable', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:100'],
        ];
    }
}
