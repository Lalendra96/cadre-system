<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RosterTemplate extends Model
{
    protected $fillable = ['unit_id','name','description','assignment_type','is_active','created_by','updated_by'];
    protected $casts = ['is_active' => 'boolean'];
    public function slots() { return $this->hasMany(RosterTemplateSlot::class)->orderBy('sort_order'); }
    public function unit() { return $this->belongsTo(Unit::class); }
}
