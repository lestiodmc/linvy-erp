<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BatchAssignment extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_POSTED = 'posted';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUSES = [self::STATUS_DRAFT, self::STATUS_POSTED, self::STATUS_CANCELLED];

    protected $guarded = [];

    protected $casts = ['assignment_date' => 'date', 'posted_at' => 'datetime'];

    public function isDraft(): bool { return $this->status === self::STATUS_DRAFT; }
    public function isPosted(): bool { return $this->status === self::STATUS_POSTED; }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function lines(): HasMany { return $this->hasMany(BatchAssignmentLine::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function postedBy(): BelongsTo { return $this->belongsTo(User::class, 'posted_by'); }
}
