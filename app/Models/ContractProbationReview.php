<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ContractProbationReview extends Model { protected $fillable=['employee_contract_id','review_due_date','status','recommendation','review_note','reviewed_by','reviewed_at','extended_to']; protected $casts=['review_due_date'=>'date','reviewed_at'=>'datetime','extended_to'=>'date']; public function contract(){return $this->belongsTo(EmployeeContract::class,'employee_contract_id');} }
