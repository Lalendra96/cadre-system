<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AttendanceDevice extends Model { protected $fillable=['name','vendor','adapter','base_url','encrypted_config','is_active','last_sync_at','last_sync_status','last_sync_message','created_by']; protected $casts=['encrypted_config'=>'encrypted:array','is_active'=>'boolean','last_sync_at'=>'datetime']; public function maps(){return $this->hasMany(AttendanceDeviceEmployeeMap::class);} }
