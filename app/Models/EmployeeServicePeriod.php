<?php
declare(strict_types=1);
namespace App\Models;
use App\Traits\HasDisableWorkflow;
use Illuminate\Database\Eloquent\Model;
class EmployeeServicePeriod extends Model
{
use HasDisableWorkflow;
public const VERIFICATION=['verified_official_record'=>'Verified from official record','document_pending'=>'Entered – document pending','imported'=>'Imported from previous system','employee_declared'=>'Employee declared'];
public const MOVEMENT_TYPES=['internal_unit_transfer'=>'Internal Unit Transfer','internal_institution_transfer'=>'Internal Institutional Transfer','incoming_external_transfer'=>'Incoming External Transfer','outgoing_external_transfer'=>'Outgoing External Transfer','temporary_attachment'=>'Temporary Attachment','permanent_release'=>'Permanent Release','secondment'=>'Secondment','deputation'=>'Deputation','reversion'=>'Reversion','inter_ministry_transfer'=>'Inter-Ministry Transfer','inter_department_transfer'=>'Inter-Department Transfer','provincial_central_movement'=>'Provincial ↔ Central Service Movement','promotion'=>'Promotion / Grade Change','appointment'=>'Appointment','other'=>'Other'];
protected $fillable=['employee_id','period_kind','sector','service_name','service_category','institution_name','ministry_department','province','position_id','position_text','position_grade_id','grade_text','appointment_type','start_date','end_date','movement_type','movement_reference','movement_reference_date','from_institution','to_institution','salary_scale_text','confirmed_status','counts_for_service','counts_for_grade_service','counts_for_pension','source_document_id','verification_status','verified_by','verified_at','remarks','is_current','is_active','created_by','updated_by','disabled_by','disabled_at','disable_reason'];
protected $casts=['start_date'=>'date','end_date'=>'date','movement_reference_date'=>'date','verified_at'=>'datetime','confirmed_status'=>'boolean','counts_for_service'=>'boolean','counts_for_grade_service'=>'boolean','counts_for_pension'=>'boolean','is_current'=>'boolean','is_active'=>'boolean','disabled_at'=>'datetime'];
public function employee(){return $this->belongsTo(Employee::class);} public function position(){return $this->belongsTo(Position::class);} public function positionGrade(){return $this->belongsTo(PositionGrade::class);} public function sourceDocument(){return $this->belongsTo(EmployeeDocument::class,'source_document_id');} public function verifiedBy(){return $this->belongsTo(User::class,'verified_by');}
public function scopeActive($q){return $q->where('is_active',true);} public function scopeCurrent($q){return $q->where('is_current',true)->whereNull('end_date');}
public function getDisplayPositionAttribute():string{return $this->position?->title?:($this->position_text?:'Position not recorded');}
public function getDisplayGradeAttribute():?string{return $this->positionGrade?->name?:$this->grade_text;}
public function getVerificationLabelAttribute():string{return self::VERIFICATION[$this->verification_status]??ucwords(str_replace('_',' ',$this->verification_status));}
}
