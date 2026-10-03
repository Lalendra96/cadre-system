<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AttendanceRecord extends Model
{
    protected $fillable=['employee_id','work_date','clock_in_at','clock_out_at','status','attendance_method_id','device_reference','unit_id','roster_assignment_id','worked_minutes','late_minutes','early_departure_minutes','is_exception','exception_reason','recorded_by','verified_by','verified_at']; protected $casts=['work_date'=>'date','clock_in_at'=>'datetime','clock_out_at'=>'datetime','verified_at'=>'datetime','is_exception'=>'boolean'];
    public function employee(){ return $this->belongsTo(Employee::class); }
    public function rosterAssignment(){ return $this->belongsTo(RosterAssignment::class); }
    public function corrections(){ return $this->hasMany(AttendanceCorrection::class); }
}
