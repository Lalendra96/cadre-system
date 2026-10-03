<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class EmployeeContract extends Model
{
    public function employee(){ return $this->belongsTo(Employee::class); }
    protected $fillable=['employee_id','contract_type','start_date','end_date','probation_end_date','basic_salary','pay_frequency','hourly_rate','session_rate','patient_rate','revenue_share_percent','cost_centre_id','status','reference_no','terms_summary','created_by','approved_by','approved_at','is_active']; protected $casts=['start_date'=>'date','end_date'=>'date','probation_end_date'=>'date','approved_at'=>'datetime','is_active'=>'boolean'];
}
