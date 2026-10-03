<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RosterApproval extends Model
{
    protected $fillable=['roster_plan_id','roster_workflow_level_id','level_no','status','acted_by','acted_at','remarks'];
    protected $casts=['acted_at'=>'datetime'];
    public function level(){ return $this->belongsTo(RosterWorkflowLevel::class,'roster_workflow_level_id'); }
    public function plan(){ return $this->belongsTo(RosterPlan::class,'roster_plan_id'); }
}
