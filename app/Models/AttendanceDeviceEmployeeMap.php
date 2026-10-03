<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AttendanceDeviceEmployeeMap extends Model { protected $fillable=['attendance_device_id','external_user_ref','employee_id','is_active']; protected $casts=['is_active'=>'boolean']; public function device(){return $this->belongsTo(AttendanceDevice::class,'attendance_device_id');} public function employee(){return $this->belongsTo(Employee::class);} }
