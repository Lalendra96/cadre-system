<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ContractRenewalRequest extends Model { protected $fillable=['employee_contract_id','proposed_start_date','proposed_end_date','proposed_terms','status','reason','requested_by','reviewed_by','reviewed_at','review_note','new_contract_id']; protected $casts=['proposed_start_date'=>'date','proposed_end_date'=>'date','proposed_terms'=>'array','reviewed_at'=>'datetime']; public function contract(){return $this->belongsTo(EmployeeContract::class,'employee_contract_id');} }
