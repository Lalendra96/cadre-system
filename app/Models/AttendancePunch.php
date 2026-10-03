<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AttendancePunch extends Model { protected $fillable=['attendance_device_id','employee_id','external_user_ref','punched_at','punch_type','source_reference','event_hash','raw_payload','processing_status','processing_note','processed_at']; protected $casts=['punched_at'=>'datetime','processed_at'=>'datetime','raw_payload'=>'array']; public function employee(){return $this->belongsTo(Employee::class);} }
