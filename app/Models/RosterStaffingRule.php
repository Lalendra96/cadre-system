<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RosterStaffingRule extends Model { protected $fillable=['unit_id','day_of_week','start_time','end_time','position_id','competency_id','minimum_staff','is_hard_stop','is_active','updated_by']; protected $casts=['is_hard_stop'=>'boolean','is_active'=>'boolean']; public function position(){return $this->belongsTo(Position::class);} public function competency(){return $this->belongsTo(Competency::class);} }
