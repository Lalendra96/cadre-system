<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
public function up():void{DB::statement('ALTER TABLE service_letter_templates DROP CONSTRAINT IF EXISTS service_letter_templates_language_check');DB::statement("ALTER TABLE service_letter_templates ADD CONSTRAINT service_letter_templates_language_check CHECK (language IN ('si','ta','en'))");}
public function down():void{$count=(int)DB::table('service_letter_templates')->where('language','ta')->count();if($count>0)throw new RuntimeException('Cannot remove Tamil language support while Tamil service-letter templates exist. Disable/delete those rows deliberately before rolling back this migration.');DB::statement('ALTER TABLE service_letter_templates DROP CONSTRAINT IF EXISTS service_letter_templates_language_check');DB::statement("ALTER TABLE service_letter_templates ADD CONSTRAINT service_letter_templates_language_check CHECK (language IN ('si','en'))");}
};
