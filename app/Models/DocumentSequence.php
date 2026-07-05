<?php

namespace App\Models;

use App\Services\DocumentSequenceService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentSequence extends Model
{
    protected $fillable = [
        'code',
        'document_type',
        'name',
        'description',
        'prefix',
        'date_format',
        'digits',
        'reset_type',
        'company_id',
        'branch_id',
        'period_type',
        'current_period',
        'last_number',
        'padding',
        'separator',
        'is_active',
    ];

    protected $casts = [
        'digits' => 'integer',
        'padding' => 'integer',
        'last_number' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'preview_number',
        'current_counter',
        'company_label',
        'branch_label',
    ];

    public function counters(): HasMany
    {
        return $this->hasMany(DocumentSequenceCounter::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function getPreviewNumberAttribute(): string
    {
        return app(DocumentSequenceService::class)->preview($this, $this->company_id, $this->branch_id);
    }

    public function getCurrentCounterAttribute(): int
    {
        return app(DocumentSequenceService::class)->currentCounter($this, $this->company_id, $this->branch_id);
    }

    public function getCompanyLabelAttribute(): string
    {
        return $this->company?->name ?? 'Global / All Company';
    }

    public function getBranchLabelAttribute(): string
    {
        return $this->branch?->name ?? 'Global / All Branch';
    }
}
