<?php

namespace App\Http\Requests;

use App\Models\StockAdjustment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'exists:branches,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'adjustment_date' => ['required', 'date'],
            'reason_code' => ['required', Rule::in(StockAdjustment::REASON_CODES)],
            'reason' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string'],
            'action' => ['nullable', 'in:draft,post'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'exists:items,id'],
            'lines.*.system_qty' => ['nullable', 'numeric'],
            'lines.*.counted_qty' => ['required', 'numeric', 'gte:0'],
            'lines.*.uom_id' => ['nullable', 'exists:units_of_measure,id'],
            'lines.*.batch_no' => ['nullable', 'string', 'max:255'],
            'lines.*.serial_numbers' => ['nullable', 'string'],
            'lines.*.expiry_date' => ['nullable', 'date'],
            'lines.*.remarks' => ['nullable', 'string'],
        ];
    }
}
