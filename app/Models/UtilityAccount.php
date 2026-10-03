<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UtilityAccount extends Model
{
    protected $fillable = ['utility_type','provider_name','account_no','meter_no','service_location','unit_id','contact_reference','notes','is_active','created_by','disabled_by','disabled_at','disable_reason'];
    protected $casts = ['is_active' => 'boolean', 'disabled_at' => 'datetime'];
    public function unit() { return $this->belongsTo(Unit::class); }
    public function bills() { return $this->hasMany(UtilityBill::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
