<?php

namespace App\Http\Requests;

use App\Models\Item;
use App\Models\Branch;
use App\Models\Warehouse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class BatchAssignmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return ['company_id' => ['required', 'exists:companies,id'], 'branch_id' => ['required', 'exists:branches,id'], 'warehouse_id' => ['required', 'exists:warehouses,id'], 'assignment_date' => ['required', 'date'], 'reason' => ['required', 'string', 'max:255'], 'notes' => ['nullable', 'string'], 'action' => ['nullable', Rule::in(['draft', 'post'])], 'lines' => ['required', 'array', 'min:1'], 'lines.*.item_id' => ['required', 'exists:items,id'], 'lines.*.source_batch_no' => ['nullable', Rule::in([''])], 'lines.*.destination_batch_no' => ['required', 'string', 'max:255'], 'lines.*.destination_expiry_date' => ['nullable', 'date'], 'lines.*.quantity' => ['required', 'numeric', 'gt:0'], 'lines.*.unit_of_measure_id' => ['nullable', 'exists:units_of_measure,id'], 'lines.*.notes' => ['nullable', 'string']];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $branch = Branch::query()->find($this->input('branch_id'));
            $warehouse = Warehouse::query()->with('branch')->find($this->input('warehouse_id'));
            $branchAccessible = Auth::user()?->isSuperAdmin() || Auth::user()?->branches()->whereKey($branch?->id)->exists();
            if (! $branch || ! $branchAccessible || (int) $branch->company_id !== (int) $this->input('company_id')) $validator->errors()->add('branch_id', 'Branch is not accessible or does not belong to the selected company.');
            if (! $warehouse || ! $branchAccessible || (int) $warehouse->branch_id !== (int) $branch?->id || (int) ($warehouse->company_id ?: $warehouse->branch?->company_id) !== (int) $this->input('company_id')) $validator->errors()->add('warehouse_id', 'Warehouse is not accessible or does not belong to the selected company and branch.');
            foreach ($this->input('lines', []) as $index => $line) {
                $item = Item::query()->find($line['item_id'] ?? null);
                if (! $item || ! $item->track_inventory || ! $item->is_batch_tracked) $validator->errors()->add("lines.$index.item_id", 'Item must be inventory and batch tracked.');
                if ($item?->has_expiry_date && blank($line['destination_expiry_date'] ?? null)) $validator->errors()->add("lines.$index.destination_expiry_date", 'Expiry date is required for this item.');
            }
            $keys = collect($this->input('lines', []))->map(fn ($line) => ($line['item_id'] ?? '').'|'.strtoupper(trim($line['destination_batch_no'] ?? '')).'|'.($line['destination_expiry_date'] ?? ''))->filter();
            if ($keys->count() !== $keys->unique()->count()) $validator->errors()->add('lines', 'Duplicate item, destination batch, and expiry lines are not allowed.');
        });
    }
}
