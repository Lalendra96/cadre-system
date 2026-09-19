<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up():void{
Schema::create('service_letter_letterheads',function(Blueprint $table){$table->bigIncrements('id');$table->string('name',120);$table->string('institution_name',180)->default('Teaching Hospital Peradeniya');$table->string('ministry_name',180)->nullable();$table->string('department_name',180)->nullable();$table->string('address_line_1',180)->nullable();$table->string('address_line_2',180)->nullable();$table->string('telephone',80)->nullable();$table->string('fax',80)->nullable();$table->string('email',160)->nullable();$table->string('website',160)->nullable();$table->string('reference_prefix',40)->nullable();$table->string('logo_path',255)->nullable();$table->string('signatory_designation',180)->nullable();$table->text('header_note')->nullable();$table->text('footer_note')->nullable();$table->boolean('show_national_emblem')->default(true);$table->boolean('is_default')->default(false);$table->boolean('is_active')->default(true);$table->unsignedBigInteger('created_by')->nullable();$table->timestamps();$table->index(['is_active','is_default']);});
Schema::table('service_letters',function(Blueprint $table){$table->unsignedBigInteger('letterhead_id')->nullable()->after('template_id');$table->string('language',2)->default('en')->after('letterhead_id');$table->string('purpose',80)->nullable()->after('language');$table->string('recipient_name',180)->nullable()->after('purpose');$table->string('recipient_address',300)->nullable()->after('recipient_name');$table->string('reference_no',100)->nullable()->after('recipient_address');$table->string('copy_type',30)->default('original')->after('reference_no');$table->foreign('letterhead_id')->references('id')->on('service_letter_letterheads')->nullOnDelete();$table->index(['language','purpose']);});
Schema::table('users',fn(Blueprint $table)=>$table->string('locale',2)->default('en')->after('employee_id'));
$adminId=DB::table('users')->orderBy('id')->value('id');DB::table('service_letter_letterheads')->insert(['name'=>"Director's Office — TH Peradeniya",'institution_name'=>'Teaching Hospital Peradeniya','ministry_name'=>'Ministry of Health','department_name'=>'Government of Sri Lanka','address_line_1'=>'Peradeniya, Sri Lanka','reference_prefix'=>'THP/','signatory_designation'=>'Administrative Officer / Hospital Secretary','show_national_emblem'=>true,'is_default'=>true,'is_active'=>true,'created_by'=>$adminId,'created_at'=>now(),'updated_at'=>now()]);
}
public function down():void{Schema::table('users',fn(Blueprint $table)=>$table->dropColumn('locale'));Schema::table('service_letters',function(Blueprint $table){$table->dropForeign(['letterhead_id']);$table->dropColumn(['letterhead_id','language','purpose','recipient_name','recipient_address','reference_no','copy_type']);});Schema::dropIfExists('service_letter_letterheads');}
};
