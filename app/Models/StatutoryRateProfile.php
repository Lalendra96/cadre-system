<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class StatutoryRateProfile extends Model
{
    protected $fillable=['name','effective_from','effective_to','epf_employee_percent','epf_employer_percent','etf_employer_percent','apit_rules','is_active','created_by']; protected $casts=['effective_from'=>'date','effective_to'=>'date','apit_rules'=>'array','is_active'=>'boolean'];
}
