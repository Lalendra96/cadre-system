<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
return new class extends Migration { public function up(): void { if(!DB::getSchemaBuilder()->hasTable('nav_items')) return; $now=now(); DB::table('nav_items')->updateOrInsert(['route_name'=>'roster.operations.index'],['section'=>'Workforce','label'=>'🔄 Roster Operations','allowed_roles'=>json_encode(['super_admin','admin_group','planning_officer','unit_manager']),'custom_checks'=>json_encode([]),'sort_order'=>14,'is_active'=>true,'open_in_new_tab'=>false,'created_at'=>$now,'updated_at'=>$now]); } public function down(): void {} };
