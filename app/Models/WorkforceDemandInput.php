<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class WorkforceDemandInput extends Model { protected $fillable=['unit_id','period_date','metric','value','source','source_reference','recorded_by']; protected $casts=['period_date'=>'date']; public function unit(){return $this->belongsTo(Unit::class);} }
