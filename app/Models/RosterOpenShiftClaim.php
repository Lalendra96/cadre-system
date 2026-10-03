<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RosterOpenShiftClaim extends Model
{
    protected $fillable=['roster_open_shift_id','employee_id','requested_by','note','status','compliance_snapshot','reviewed_by','reviewed_at','review_note'];
    protected $casts=['compliance_snapshot'=>'array','reviewed_at'=>'datetime'];
    public function shift(){return $this->belongsTo(RosterOpenShift::class,'roster_open_shift_id');}
    public function employee(){return $this->belongsTo(Employee::class);}
}
