<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PayrollLine extends Model
{
    protected $fillable=['payroll_run_id','employee_id','contract_id','cost_centre_id','basic_salary','allowances','overtime_amount','locum_amount','gross_pay','epf_employee','epf_employer','etf_employer','apit','other_deductions','net_pay','calculation_snapshot']; protected $casts=['calculation_snapshot'=>'array'];
}
