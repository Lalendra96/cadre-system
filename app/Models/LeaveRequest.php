<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LeaveRequest extends Model
{
    protected $fillable=['employee_id','leave_type_id','start_date','end_date','days','portion','reason','attachment_path','status','requested_by','approved_by','approved_at','cancelled_by','cancelled_at','decision_note']; protected $casts=['start_date'=>'date','end_date'=>'date','approved_at'=>'datetime','cancelled_at'=>'datetime'];
    public function employee(){ return $this->belongsTo(Employee::class); }
    public function leaveType(){ return $this->belongsTo(LeaveType::class); }
}
