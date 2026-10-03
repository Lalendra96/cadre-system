<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RosterPlan extends Model
{
    protected $fillable=['reference_no','unit_id','roster_template_id','title','start_date','end_date','status','notes','created_by','submitted_by','submitted_at','approved_at','started_by','started_at','completed_by','completed_at','cancelled_by','cancelled_at','cancel_reason','revision_no'];
    protected $casts=['start_date'=>'date','end_date'=>'date','submitted_at'=>'datetime','approved_at'=>'datetime','started_at'=>'datetime','completed_at'=>'datetime','cancelled_at'=>'datetime'];
    public function assignments(){ return $this->hasMany(RosterAssignment::class); }
    public function creator(){ return $this->belongsTo(User::class,'created_by'); }
    public function approvals(){ return $this->hasMany(RosterApproval::class)->orderBy('level_no'); }
    public function unit(){ return $this->belongsTo(Unit::class); }
    public function template(){ return $this->belongsTo(RosterTemplate::class,'roster_template_id'); }
    public function amendments(){ return $this->hasMany(RosterAmendmentRequest::class)->latest(); }
}
