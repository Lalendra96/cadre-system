<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RosterShiftSwapRequest extends Model { protected $fillable=['assignment_id','requested_by_employee_id','target_employee_id','replacement_assignment_id','swap_type','reason','status','peer_accepted_by','peer_accepted_at','peer_responded_at','peer_response_note','reviewed_by','reviewed_at','review_note']; protected $casts=['peer_accepted_at'=>'datetime','peer_responded_at'=>'datetime','reviewed_at'=>'datetime']; public function assignment(){return $this->belongsTo(RosterAssignment::class,'assignment_id');} public function requester(){return $this->belongsTo(Employee::class,'requested_by_employee_id');} public function target(){return $this->belongsTo(Employee::class,'target_employee_id');} }
