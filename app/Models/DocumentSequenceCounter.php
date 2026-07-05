<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentSequenceCounter extends Model
{
    protected $fillable = [
        'document_sequence_id',
        'company_id',
        'branch_id',
        'period',
        'last_number',
    ];

    protected $casts = [
        'last_number' => 'integer',
    ];

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(DocumentSequence::class, 'document_sequence_id');
    }
}
