<?php

namespace App\Http\Controllers\Admin;

use App\Models\Branch;
use App\Models\Company;
use App\Models\DocumentSequence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DocumentSequenceController extends ResourceController
{
    protected string $model = DocumentSequence::class;
    protected string $route = 'document-sequences';
    protected string $title = 'Document Sequence';
    protected string $viewPath = 'settings.document_sequences';
    protected array $with = ['company', 'branch', 'counters'];
    protected array $columns = ['code', 'name', 'prefix', 'reset_type', 'preview_number', 'current_counter', 'company_label', 'branch_label', 'is_active'];
    protected array $fields = [
        'code' => ['label' => 'Code', 'type' => 'text'],
        'name' => ['label' => 'Name', 'type' => 'text'],
        'description' => ['label' => 'Description', 'type' => 'textarea'],
        'prefix' => ['label' => 'Prefix', 'type' => 'text'],
        'date_format' => ['label' => 'Date Format', 'type' => 'select', 'options' => ['YYYYMM' => 'YYYYMM', 'YYYY' => 'YYYY', 'YYMM' => 'YYMM', 'YYYYMMDD' => 'YYYYMMDD', 'CUSTOM' => 'CUSTOM'], 'default' => 'YYYYMM'],
        'digits' => ['label' => 'Digits', 'type' => 'number', 'step' => '1', 'default' => 5],
        'separator' => ['label' => 'Separator', 'type' => 'text', 'default' => '-'],
        'reset_type' => ['label' => 'Reset Type', 'type' => 'select', 'options' => ['never' => 'Never', 'monthly' => 'Monthly', 'yearly' => 'Yearly'], 'default' => 'monthly'],
        'company_id' => ['label' => 'Company', 'type' => 'select'],
        'branch_id' => ['label' => 'Branch', 'type' => 'select'],
        'is_active' => ['label' => 'Active', 'type' => 'checkbox'],
    ];

    protected function validated(Request $request, ?Model $record = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:255', Rule::unique('document_sequences', 'code')->ignore($record?->id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'prefix' => ['required', 'string', 'max:255'],
            'date_format' => ['required', 'string', 'in:YYYYMM,YYYY,YYMM,YYYYMMDD,CUSTOM'],
            'custom_date_format' => ['nullable', 'required_if:date_format,CUSTOM', 'string', 'max:30'],
            'digits' => ['required', 'integer', 'min:1', 'max:10'],
            'separator' => ['nullable', 'string', 'max:3'],
            'reset_type' => ['required', 'string', 'in:never,monthly,yearly'],
            'company_id' => ['nullable', 'exists:companies,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['code'] = strtoupper($data['code']);
        $data['prefix'] = strtoupper($data['prefix']);
        $data['date_format'] = $data['date_format'] === 'CUSTOM'
            ? ($data['custom_date_format'] ?: 'YYYYMM')
            : $data['date_format'];
        unset($data['custom_date_format']);

        $data['document_type'] = $data['code'];
        $data['period_type'] = $data['reset_type'] === 'yearly' ? 'yearly' : 'monthly';
        $data['padding'] = $data['digits'];
        $data['separator'] = $data['separator'] ?? '-';
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    protected function viewData(array $data = []): array
    {
        return parent::viewData(array_merge([
            'companies' => Company::orderBy('name')->get(['id', 'name']),
            'branches' => Branch::with('company')->orderBy('company_id')->orderBy('name')->get(['id', 'company_id', 'name']),
            'dateFormatOptions' => ['YYYYMM', 'YYYY', 'YYMM', 'YYYYMMDD'],
            'resetTypeHelp' => [
                'monthly' => 'Monthly: nomor reset setiap bulan.',
                'yearly' => 'Yearly: nomor reset setiap tahun.',
                'never' => 'Never: nomor tidak reset, terus bertambah.',
            ],
        ], $data));
    }
}
