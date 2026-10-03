<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class EmployeePayrollProfile extends Model
{
    protected $fillable=['employee_id','bank_name_encrypted','bank_branch_encrypted','bank_account_no_encrypted','tax_reference','epf_member_no','epf_applicable','etf_applicable','apit_applicable','updated_by']; protected $casts=['bank_name_encrypted'=>'encrypted','bank_branch_encrypted'=>'encrypted','bank_account_no_encrypted'=>'encrypted','epf_applicable'=>'boolean','etf_applicable'=>'boolean','apit_applicable'=>'boolean'];
}
