<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RosterAcknowledgement extends Model
{
    protected $fillable=['roster_assignment_id','employee_id','acknowledged_by','acknowledged_at','source_ip','user_agent'];
    protected $casts=['acknowledged_at'=>'datetime'];
    public function assignment(){return $this->belongsTo(RosterAssignment::class,'roster_assignment_id');}
    public function employee(){return $this->belongsTo(Employee::class);}
}
