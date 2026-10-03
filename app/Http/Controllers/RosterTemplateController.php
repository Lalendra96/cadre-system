<?php
namespace App\Http\Controllers;
use App\Models\RosterTemplate;
use App\Services\RosterAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class RosterTemplateController extends Controller
{
    public function index(){ $templates=RosterTemplate::with('unit','slots')->orderByDesc('id')->paginate(20); return view('roster.templates.index',compact('templates')); }
    public function create(){ $units=DB::table('units')->where('is_active',true)->orderBy('name')->get(); $positions=DB::table('positions')->where('is_active',true)->orderBy('title')->get(); return view('roster.templates.form',compact('units','positions')); }
    public function store(Request $r)
    {
        $data=$r->validate(['name'=>'required|string|max:150','unit_id'=>'required|exists:units,id','description'=>'nullable|string','assignment_type'=>'required|string|max:50','slots'=>'required|array|min:1','slots.*.label'=>'required|string|max:120','slots.*.start_time'=>'required','slots.*.end_time'=>'required','slots.*.default_duty_unit_id'=>'nullable|exists:units,id','slots.*.position_id'=>'nullable|exists:positions,id','slots.*.duty_role'=>'nullable|string|max:150','slots.*.additional_details'=>'nullable|string','slots.*.required_staff'=>'required|integer|min:1|max:100']);
        return DB::transaction(function() use($data){
            $slots=$data['slots']; unset($data['slots']); $data['created_by']=auth()->id();
            $template=RosterTemplate::create($data);
            foreach($slots as $i=>$slot){ $slot['sort_order']=$i; $template->slots()->create($slot); }
            RosterAuditService::log('created',RosterTemplate::class,$template->id,'Created roster template',null,$template->load('slots')->toArray());
            return redirect()->route('roster.templates.index')->with('success','Roster template created.');
        });
    }
    public function edit(RosterTemplate $template){ $template->load('slots'); $units=DB::table('units')->where('is_active',true)->orderBy('name')->get(); $positions=DB::table('positions')->where('is_active',true)->orderBy('title')->get(); return view('roster.templates.form',compact('template','units','positions')); }
    public function update(Request $r,RosterTemplate $template)
    {
        $old=$template->load('slots')->toArray();
        $data=$r->validate(['name'=>'required|string|max:150','unit_id'=>'required|exists:units,id','description'=>'nullable|string','assignment_type'=>'required|string|max:50','slots'=>'required|array|min:1','slots.*.label'=>'required|string|max:120','slots.*.start_time'=>'required','slots.*.end_time'=>'required','slots.*.default_duty_unit_id'=>'nullable|exists:units,id','slots.*.position_id'=>'nullable|exists:positions,id','slots.*.duty_role'=>'nullable|string|max:150','slots.*.additional_details'=>'nullable|string','slots.*.required_staff'=>'required|integer|min:1|max:100']);
        DB::transaction(function() use($data,$template){ $slots=$data['slots']; unset($data['slots']); $data['updated_by']=auth()->id(); $template->update($data); $template->slots()->delete(); foreach($slots as $i=>$slot){$slot['sort_order']=$i;$template->slots()->create($slot);} });
        RosterAuditService::log('updated',RosterTemplate::class,$template->id,'Updated roster template',$old,$template->load('slots')->toArray());
        return redirect()->route('roster.templates.index')->with('success','Roster template updated.');
    }
    public function disable(RosterTemplate $template){ $template->update(['is_active'=>false,'updated_by'=>auth()->id()]); RosterAuditService::log('updated',RosterTemplate::class,$template->id,'Disabled roster template'); return back()->with('success','Template disabled.'); }
}
