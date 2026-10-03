<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RosterAssignment extends Model
{
    protected $fillable=['roster_plan_id','employee_id','duty_date','start_time','end_time','home_unit_id','duty_unit_id','position_id','duty_role','coverage_type','location','details','status','assigned_by','acknowledged_at'];
    protected $casts=['duty_date'=>'date','acknowledged_at'=>'datetime'];
    public function employee(){ return $this->belongsTo(Employee::class); }
    public function plan(){ return $this->belongsTo(RosterPlan::class,'roster_plan_id'); }
    public function dutyUnit(){ return $this->belongsTo(Unit::class,'duty_unit_id'); }
    public function homeUnit(){ return $this->belongsTo(Unit::class,'home_unit_id'); }
    public function acknowledgement(){ return $this->hasOne(RosterAcknowledgement::class); }
}
