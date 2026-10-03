<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CostCentre extends Model
{
    protected $fillable=['code','name','unit_id','is_active','created_by','disabled_by','disabled_at','disable_reason']; protected $casts=['is_active'=>'boolean','disabled_at'=>'datetime'];
}
