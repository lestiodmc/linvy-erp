<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo; use Illuminate\Database\Eloquent\Relations\HasMany;
class BatchAssignment extends Model { public const DRAFT='draft'; public const POSTED='posted'; public const CANCELLED='cancelled'; protected $guarded=[]; protected $casts=['assignment_date'=>'date','posted_at'=>'datetime']; public function lines(): HasMany{return $this->hasMany(BatchAssignmentLine::class);} public function warehouse(): BelongsTo{return $this->belongsTo(Warehouse::class);} public function branch(): BelongsTo{return $this->belongsTo(Branch::class);} public function company(): BelongsTo{return $this->belongsTo(Company::class);} }
