<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LocumBooking extends Model { protected $fillable=['employee_id','unit_id','contract_id','booking_date','start_time','end_time','status','request_reason','requested_by','accepted_by','accepted_at','approved_by','approved_at','locum_session_id']; protected $casts=['booking_date'=>'date','accepted_at'=>'datetime','approved_at'=>'datetime']; public function employee(){return $this->belongsTo(Employee::class);} public function unit(){return $this->belongsTo(Unit::class);} }
