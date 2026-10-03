<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class OvertimeRequest extends Model
{
    protected $fillable=['employee_id','work_date','attendance_record_id','roster_assignment_id','overtime_type','minutes_requested','minutes_approved','rate_multiplier','reason','status','requested_by','approved_by','approved_at','included_in_payroll']; protected $casts=['work_date'=>'date','approved_at'=>'datetime','included_in_payroll'=>'boolean'];
}
