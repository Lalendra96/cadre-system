<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LocumSession extends Model
{
    public function employee(){ return $this->belongsTo(Employee::class); }
    protected $fillable=['employee_id','contract_id','unit_id','cost_centre_id','session_date','start_time','end_time','patients_seen','revenue_amount','calculation_method','calculated_amount','status','recorded_by','approved_by','approved_at','included_in_payroll','booking_id','reconciliation_status','reconciliation_note','verified_by','verified_at','external_payment_reference']; protected $casts=['session_date'=>'date','approved_at'=>'datetime','included_in_payroll'=>'boolean','verified_at'=>'datetime'];
}
