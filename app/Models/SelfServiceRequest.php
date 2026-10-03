<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SelfServiceRequest extends Model
{
    protected $fillable=['employee_id','request_type','payload','status','requested_by','reviewed_by','reviewed_at','review_note']; protected $casts=['payload'=>'array','reviewed_at'=>'datetime'];
}
