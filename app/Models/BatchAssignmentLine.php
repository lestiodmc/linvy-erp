<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class BatchAssignmentLine extends Model { protected $guarded=[]; protected $casts=['destination_expiry_date'=>'date','quantity'=>'decimal:6']; public function assignment(): BelongsTo{return $this->belongsTo(BatchAssignment::class,'batch_assignment_id');} public function item(): BelongsTo{return $this->belongsTo(Item::class);} }
