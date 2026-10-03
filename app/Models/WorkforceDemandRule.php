<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class WorkforceDemandRule extends Model { protected $fillable=['unit_id','position_id','demand_metric','units_per_fte','minimum_fte','is_active','updated_by']; protected $casts=['is_active'=>'boolean']; public function unit(){return $this->belongsTo(Unit::class);} public function position(){return $this->belongsTo(Position::class);} }
