<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PayrollAdjustment extends Model { protected $fillable=['employee_id','type','code','description','amount','year','month','recurring','status','created_by']; protected $casts=['recurring'=>'boolean']; }
