<?php
namespace App\Http\Controllers;
use App\Models\RosterWorkflow;
use App\Services\RosterAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class RosterSettingsController extends Controller
{
    public function index(){ $enabled=DB::table('system_settings')->where('key','feature_roster')->value('value'); $workflows=RosterWorkflow::with('levels')->orderByDesc('is_default')->get(); $units=DB::table('units')->where('is_active',true)->orderBy('name')->get(); return view('roster.settings.index',compact('enabled','workflows','units')); }
    public function toggle(Request $r){ $r->validate(['enabled'=>'required|boolean']); DB::table('system_settings')->updateOrInsert(['key'=>'feature_roster'],['value'=>$r->boolean('enabled')?'1':'0','type'=>'boolean','group'=>'modules','label'=>'Roster Module','updated_at'=>now(),'created_at'=>now()]); RosterAuditService::log('updated','SystemSetting',null,'Roster module '.($r->boolean('enabled')?'enabled':'disabled')); return back()->with('success','Roster module setting updated.'); }
    public function saveWorkflow(Request $r)
    {
        $data=$r->validate(['name'=>'required|string|max:120','unit_id'=>'nullable|exists:units,id','is_default'=>'nullable|boolean','levels'=>'required|array|min:1','levels.*.label'=>'required|string|max:120','levels.*.approver_type'=>'required|in:initiator,category,role,user','levels.*.categories'=>'nullable|string','levels.*.roles'=>'nullable|string','levels.*.user_ids'=>'nullable|string','levels.*.is_required'=>'nullable|boolean','levels.*.allow_return'=>'nullable|boolean']);
        DB::transaction(function() use($data){
            if(!empty($data['is_default'])) RosterWorkflow::query()->update(['is_default'=>false]);
            $workflow=RosterWorkflow::create(['name'=>$data['name'],'unit_id'=>$data['unit_id']??null,'is_default'=>!empty($data['is_default']),'is_active'=>true,'created_by'=>auth()->id()]);
            foreach($data['levels'] as $i=>$level){
                $config=['categories'=>array_values(array_filter(array_map('trim',explode(',',$level['categories']??'')))),'roles'=>array_values(array_filter(array_map('trim',explode(',',$level['roles']??'')))),'user_ids'=>array_values(array_map('intval',array_filter(array_map('trim',explode(',',$level['user_ids']??'')))))];
                $workflow->levels()->create(['level_no'=>$i+1,'label'=>$level['label'],'approver_type'=>$level['approver_type'],'approver_config'=>$config,'is_required'=>!empty($level['is_required']),'allow_return'=>!empty($level['allow_return'])]);
            }
            RosterAuditService::log('created',RosterWorkflow::class,$workflow->id,'Created roster approval workflow');
        });
        return back()->with('success','Workflow created.');
    }
}
