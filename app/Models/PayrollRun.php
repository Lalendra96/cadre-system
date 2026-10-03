<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PayrollRun extends Model
{
    protected $fillable=['year','month','name','status','statutory_rate_profile_id','prepared_by','checked_by','approved_by','checked_at','approved_at','finalized_at','is_locked']; protected $casts=['checked_at'=>'datetime','approved_at'=>'datetime','finalized_at'=>'datetime','is_locked'=>'boolean'];
}
