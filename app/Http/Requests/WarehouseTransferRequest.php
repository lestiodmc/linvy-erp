<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WarehouseTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'exists:companies,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'transfer_date' => ['required', 'date'],
            'from_warehouse_id' => ['required', 'exists:warehouses,id', 'different:to_warehouse_id'],
            'to_warehouse_id' => ['required', 'exists:warehouses,id'],
            'notes' => ['nullable', 'string'],
            'action' => ['nullable', 'in:draft,post'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'exists:items,id'],
            'lines.*.batch_no' => ['nullable', 'string', 'max:255'],
            'lines.*.expiry_date' => ['nullable', 'date'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_of_measure_id' => ['nullable', 'exists:units_of_measure,id'],
            'lines.*.notes' => ['nullable', 'string'],
        ];
    }
}
