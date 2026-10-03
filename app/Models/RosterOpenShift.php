<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RosterOpenShift extends Model { protected $fillable=['roster_plan_id','duty_date','start_time','end_time','duty_unit_id','position_id','duty_role','required_staff','filled_staff','status','details','created_by']; protected $casts=['duty_date'=>'date']; public function plan(){return $this->belongsTo(RosterPlan::class,'roster_plan_id');} public function unit(){return $this->belongsTo(Unit::class,'duty_unit_id');} public function claims(){return $this->hasMany(RosterOpenShiftClaim::class,'roster_open_shift_id');} }
