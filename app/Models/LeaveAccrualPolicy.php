<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LeaveAccrualPolicy extends Model { protected $fillable=['leave_type_id','contract_type','accrual_frequency','accrual_amount','annual_cap','carry_forward_enabled','carry_forward_cap','support_only','effective_from','effective_to','is_active','updated_by']; protected $casts=['effective_from'=>'date','effective_to'=>'date','support_only'=>'boolean','carry_forward_enabled'=>'boolean','is_active'=>'boolean']; public function leaveType(){return $this->belongsTo(LeaveType::class);} }
