<?php
declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ServiceLetterLetterhead extends Model
{
protected $fillable=['name','institution_name','ministry_name','department_name','address_line_1','address_line_2','telephone','fax','email','website','reference_prefix','logo_path','signatory_designation','header_note','footer_note','show_national_emblem','is_default','is_active','created_by'];
protected $casts=['show_national_emblem'=>'boolean','is_default'=>'boolean','is_active'=>'boolean'];
public function creator(){return $this->belongsTo(User::class,'created_by');} public function scopeActive($query){return $query->where('is_active',true);}
}
