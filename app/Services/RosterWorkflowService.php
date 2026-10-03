<?php
namespace App\Services;
use App\Models\RosterApproval;
use App\Models\RosterPlan;
use App\Models\RosterWorkflow;
use Illuminate\Support\Facades\DB;
class RosterWorkflowService
{
    public static function workflowFor(RosterPlan $plan): ?RosterWorkflow
    {
        return RosterWorkflow::with('levels')->where('is_active',true)
            ->where(function($q) use($plan){ $q->where('unit_id',$plan->unit_id)->orWhereNull('unit_id'); })
            ->orderByRaw('CASE WHEN unit_id IS NULL THEN 1 ELSE 0 END')->orderByDesc('is_default')->first();
    }
    public static function initialize(RosterPlan $plan): void
    {
        $workflow=self::workflowFor($plan);
        if(!$workflow){ throw new \RuntimeException('No active Roster approval workflow is configured.'); }
        $plan->approvals()->delete();
        foreach($workflow->levels as $level){
            RosterApproval::create(['roster_plan_id'=>$plan->id,'roster_workflow_level_id'=>$level->id,'level_no'=>$level->level_no,'status'=>'pending']);
        }
    }
    public static function canAccessApprovals($user): bool
    {
        if (! $user || ! $user->is_active) {
            return false;
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        // Roster creators may need to action an initiator level when configured.
        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['admin_group', 'planning_officer', 'unit_manager'])) {
            return true;
        }

        $roles = DB::table('user_roles')->where('user_id', $user->id)->pluck('role')->all();
        $category = $user->category_id
            ? DB::table('user_categories')->where('id', $user->category_id)->value('name')
            : null;

        $levels = DB::table('roster_workflow_levels as l')
            ->join('roster_workflows as w', 'w.id', '=', 'l.roster_workflow_id')
            ->where('w.is_active', true)
            ->select('l.approver_type', 'l.approver_config')
            ->get();

        foreach ($levels as $level) {
            $cfg = is_array($level->approver_config)
                ? $level->approver_config
                : (json_decode((string) $level->approver_config, true) ?: []);

            if (! empty($cfg['user_ids']) && in_array($user->id, $cfg['user_ids'])) {
                return true;
            }
            if (! empty($cfg['roles']) && array_intersect($roles, $cfg['roles'])) {
                return true;
            }
            if ($category && ! empty($cfg['categories'])) {
                foreach ($cfg['categories'] as $needle) {
                    if (str_contains(strtolower($category), strtolower((string) $needle))) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public static function canAct($user, $approval): bool
    {
        $cfg=$approval->level->approver_config ?? [];
        if(($approval->level->approver_type ?? '')==='initiator') return $approval->plan->created_by===$user->id;
        if(!empty($cfg['user_ids']) && in_array($user->id,$cfg['user_ids'])) return true;
        if(!empty($cfg['roles'])){
            $roles=DB::table('user_roles')->where('user_id',$user->id)->pluck('role')->all();
            if(array_intersect($roles,$cfg['roles'])) return true;
        }
        if(!empty($cfg['categories'])){
            $category=DB::table('user_categories')->where('id',$user->category_id)->value('name');
            foreach($cfg['categories'] as $needle){ if($category && str_contains(strtolower($category), strtolower($needle))) return true; }
        }
        return false;
    }
}
