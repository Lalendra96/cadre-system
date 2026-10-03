<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AttendanceCorrection extends Model { protected $fillable=['attendance_record_id','employee_id','requested_clock_in_at','requested_clock_out_at','reason','status','requested_by','reviewed_by','reviewed_at','review_note']; protected $casts=['requested_clock_in_at'=>'datetime','requested_clock_out_at'=>'datetime','reviewed_at'=>'datetime']; public function attendanceRecord(){return $this->belongsTo(AttendanceRecord::class);} public function employee(){return $this->belongsTo(Employee::class);} }
