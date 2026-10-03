<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
    public function up(): void {
        if (!DB::getSchemaBuilder()->hasTable('nav_items')) return;
        $now=now();
        DB::table('nav_items')->updateOrInsert(['route_name'=>'roster.employee.index'],[
            'section'=>'Workforce','label'=>'🙋 My Roster Actions','allowed_roles'=>null,
            'custom_checks'=>json_encode(['canAccessRosterEmployeeActions']),'sort_order'=>15,
            'is_active'=>true,'open_in_new_tab'=>false,'created_at'=>$now,'updated_at'=>$now,
        ]);
    }
    public function down(): void {}
};
