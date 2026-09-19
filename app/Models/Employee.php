<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasDisableWorkflow;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasDisableWorkflow, HasFactory, SoftDeletes;
    protected $table='employees';
    protected $fillable=['salutation','name','pay_no','service_file_no','nic_number','wop_number','professional_registration_no','professional_registration_expiry','gender','preferred_language','email','whatsapp_mobile','permanent_address','current_address','emergency_contact_name','emergency_contact_relationship','emergency_contact_mobile','subject_code_id','position_id','unit_id','salary_scale_id','date_of_birth','date_of_appointment','date_joined_public_service','date_joined_institution','date_current_grade','last_increment_date','next_increment_date','date_reported_for_duty','retirement_age','is_confirmed','date_confirmed','confirmation_reference_no','notes','is_active','employment_status','current_service_name','combined_service_name','date_joined_combined_service','created_by','updated_by','disabled_by','disabled_at','disable_reason'];
    protected $casts=['date_of_birth'=>'date','professional_registration_expiry'=>'date','date_of_appointment'=>'date','date_joined_public_service'=>'date','date_joined_combined_service'=>'date','date_joined_institution'=>'date','date_current_grade'=>'date','last_increment_date'=>'date','next_increment_date'=>'date','date_reported_for_duty'=>'date','retirement_age'=>'integer','is_confirmed'=>'boolean','date_confirmed'=>'date','is_active'=>'boolean','disabled_at'=>'datetime'];
    public function subjectCode(){return $this->belongsTo(SubjectCode::class,'subject_code_id');}
    public function position(){return $this->belongsTo(Position::class,'position_id');}
    public function unit(){return $this->belongsTo(Unit::class,'unit_id');}
    public function salaryScale(){return $this->belongsTo(SalaryScale::class,'salary_scale_id');}
    public function createdBy(){return $this->belongsTo(User::class,'created_by');}
    public function updatedBy(){return $this->belongsTo(User::class,'updated_by');}
    public function actingAppointments(){return $this->hasMany(ActingAppointment::class,'employee_id');}
    public function transferRecords(){return $this->hasMany(TransferRecord::class,'employee_id');}
    public function incrementRecords(){return $this->hasMany(EmployeeIncrement::class,'employee_id');}
    public function gradeRecords(){return $this->hasMany(EmployeeGradeRecord::class,'employee_id');}
    public function qualifications(){return $this->hasMany(EmployeeQualification::class,'employee_id');}
    public function examRecords(){return $this->hasMany(EmployeeExamRecord::class,'employee_id');}
    public function serviceLetters(){return $this->hasMany(ServiceLetter::class,'employee_id');}
    public function interdictions(){return $this->hasMany(EmployeeInterdiction::class,'employee_id');}
    public function leaveRecords(){return $this->hasMany(EmployeeLeaveRecord::class,'employee_id');}
    public function lifecycleEvents(){return $this->hasMany(EmployeeLifecycleEvent::class,'employee_id');}
    public function documents(){return $this->hasMany(EmployeeDocument::class,'employee_id');}
    public function retirementProjects(){return $this->hasMany(RetirementProject::class,'employee_id');}
    public function competencies(){return $this->hasMany(EmployeeCompetency::class,'employee_id');}
    public function dataQualityIssues(){return $this->hasMany(DataQualityIssue::class,'employee_id');}
    public function servicePeriods(){return $this->hasMany(EmployeeServicePeriod::class,'employee_id');}
    public function currentServicePeriod(){return $this->hasOne(EmployeeServicePeriod::class,'employee_id')->where('is_current',true)->whereNull('end_date');}
    public function getRetireDateAttribute():?\Carbon\Carbon{if(!$this->date_of_birth)return null;return $this->date_of_birth->addYears($this->retirement_age??60);}
    public function getMonthsToRetirementAttribute():?int{if(!$this->retire_date)return null;return (int)now()->diffInMonths($this->retire_date,false);}
    public function getServiceYearsAttribute():?int{$start=$this->date_joined_public_service??$this->date_of_appointment;if(!$start)return null;return (int)$start->diffInYears(now());}
    public function getRetirementStatusAttribute():string{$months=$this->months_to_retirement;if($months===null)return'unknown';if($months<0)return'overdue';if($months<=6)return'critical';if($months<=12)return'soon';if($months<=24)return'upcoming';return'normal';}
    public function getDisplayNameAttribute():string{return trim(($this->salutation?$this->salutation.' ':'').$this->name);}
    public function getMaskedNicAttribute():?string{if(!$this->nic_number)return null;$visible=substr($this->nic_number,-4);return str_repeat('*',max(strlen($this->nic_number)-4,0)).$visible;}
    public function getCurrentGradeAttribute():?EmployeeGradeRecord{return $this->gradeRecords()->current()->with('positionGrade')->orderByDesc('effective_date')->first();}
    public function getNextIncrementAttribute():?EmployeeIncrement{return $this->incrementRecords()->active()->whereDate('increment_date','>=',now()->toDateString())->orderBy('increment_date')->first()??$this->incrementRecords()->mostRecent()->first();}
    public function isCurrentlyInterdicted():bool{return $this->interdictions()->active()->where('inquiry_status','ongoing')->exists();}
    public function isCurrentlyOnLeave():bool{return $this->leaveRecords()->active()->whereNull('actual_return_date')->where('start_date','<=',now()->toDateString())->exists();}
}
