<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LeaveType extends Model
{
    protected $fillable=['code','name','annual_entitlement','is_paid','allow_half_day','allow_carry_forward','max_carry_forward','requires_attachment','is_active']; protected $casts=['is_paid'=>'boolean','allow_half_day'=>'boolean','allow_carry_forward'=>'boolean','requires_attachment'=>'boolean','is_active'=>'boolean'];
}
