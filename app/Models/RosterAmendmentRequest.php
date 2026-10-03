<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RosterAmendmentRequest extends Model
{
    protected $fillable=['roster_plan_id','roster_assignment_id','action','source_type','source_id','is_emergency','proposed_values','reason','status','requested_by','requested_at','reviewed_by','reviewed_at','review_note','applied_revision_no'];
    protected $casts=['is_emergency'=>'boolean','proposed_values'=>'array','requested_at'=>'datetime','reviewed_at'=>'datetime'];
    public function plan(){return $this->belongsTo(RosterPlan::class,'roster_plan_id');}
    public function assignment(){return $this->belongsTo(RosterAssignment::class,'roster_assignment_id');}
}
