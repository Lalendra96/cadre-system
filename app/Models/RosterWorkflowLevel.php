<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RosterWorkflowLevel extends Model
{
    protected $fillable=['roster_workflow_id','level_no','label','approver_type','approver_config','is_required','allow_return','notify_email'];
    protected $casts=['approver_config'=>'array','is_required'=>'boolean','allow_return'=>'boolean','notify_email'=>'boolean'];
}
