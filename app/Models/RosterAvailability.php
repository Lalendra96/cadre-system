<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RosterAvailability extends Model { protected $fillable=['employee_id','available_date','available_from','available_to','availability_type','preferred_shift','note','created_by']; protected $casts=['available_date'=>'date']; public function employee(){return $this->belongsTo(Employee::class);} }
