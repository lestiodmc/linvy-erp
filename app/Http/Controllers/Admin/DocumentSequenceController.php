<?php

namespace App\Http\Controllers\Admin;

use App\Models\DocumentSequence;

class DocumentSequenceController extends ResourceController
{
    protected string $model = DocumentSequence::class;
    protected string $route = 'document-sequences';
    protected string $title = 'Document Sequence';
    protected string $viewPath = 'settings.document_sequences';
    protected array $columns = ['document_type', 'name', 'prefix', 'period_type', 'current_period', 'last_number', 'padding', 'is_active'];
    protected array $fields = [
        'document_type' => ['label' => 'Document Type', 'type' => 'text'],
        'name' => ['label' => 'Name', 'type' => 'text'],
        'prefix' => ['label' => 'Prefix', 'type' => 'text'],
        'period_type' => ['label' => 'Period Type', 'type' => 'select', 'options' => ['daily' => 'Daily', 'monthly' => 'Monthly', 'yearly' => 'Yearly']],
        'padding' => ['label' => 'Padding', 'type' => 'number', 'step' => '1'],
        'separator' => ['label' => 'Separator', 'type' => 'text'],
        'is_active' => ['label' => 'Active', 'type' => 'checkbox'],
    ];
    protected array $rules = [
        'document_type' => ['required', 'string', 'max:255'],
        'name' => ['required', 'string', 'max:255'],
        'prefix' => ['required', 'string', 'max:255'],
        'period_type' => ['required', 'string'],
        'padding' => ['required', 'integer', 'min:1', 'max:12'],
        'separator' => ['required', 'string', 'max:5'],
        'is_active' => ['nullable'],
    ];
}
