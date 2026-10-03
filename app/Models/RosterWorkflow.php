<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RosterWorkflow extends Model
{
    protected $fillable=['name','unit_id','is_default','is_active','created_by'];
    protected $casts=['is_default'=>'boolean','is_active'=>'boolean'];
    public function levels(){ return $this->hasMany(RosterWorkflowLevel::class)->orderBy('level_no'); }
}
