<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RosterTemplateSlot extends Model
{
    protected $fillable=['roster_template_id','label','start_time','end_time','default_duty_unit_id','position_id','duty_role','additional_details','required_staff','sort_order'];
    public function template(){ return $this->belongsTo(RosterTemplate::class,'roster_template_id'); }
}
