<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LocumPoolProfile extends Model { protected $fillable=['employee_id','pool_status','preferred_hourly_rate','preferred_session_rate','preferred_unit_ids','available_from','available_to','notes','is_active','updated_by']; protected $casts=['preferred_unit_ids'=>'array','available_from'=>'date','available_to'=>'date','is_active'=>'boolean']; public function employee(){return $this->belongsTo(Employee::class);} }
