<?php
namespace Database\Seeders;
use App\Models\RosterWorkflow;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class RosterModuleSeeder extends Seeder
{
    public function run(): void
    {
        $admin=DB::table('users')->orderBy('id')->value('id'); if(!$admin) return;
        DB::table('system_settings')->updateOrInsert(['key'=>'feature_roster'],['value'=>'1','type'=>'boolean','group'=>'features','label'=>'Roster Module','description'=>'Enable or disable Roster Management','created_at'=>now(),'updated_at'=>now()]);
        if(RosterWorkflow::count()===0){
            $w=RosterWorkflow::create(['name'=>'Default Hospital Roster Approval','is_default'=>true,'is_active'=>true,'created_by'=>$admin]);
            $w->levels()->create(['level_no'=>1,'label'=>'Unit In-Charge','approver_type'=>'initiator','approver_config'=>[],'is_required'=>true,'allow_return'=>false]);
            $w->levels()->create(['level_no'=>2,'label'=>'Consultant','approver_type'=>'category','approver_config'=>['categories'=>['Consultant']],'is_required'=>true,'allow_return'=>true]);
            $w->levels()->create(['level_no'=>3,'label'=>'Director / Deputy Director','approver_type'=>'category','approver_config'=>['categories'=>['Director','Deputy Director']],'is_required'=>true,'allow_return'=>true]);
        }
    }
}
