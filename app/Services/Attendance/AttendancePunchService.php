<?php
namespace App\Services\Attendance;
use App\Models\AttendanceDeviceEmployeeMap; use App\Models\AttendancePunch; use App\Models\AttendanceRecord; use Illuminate\Support\Facades\DB; use Carbon\Carbon;
class AttendancePunchService {
 public function ingest(array $event, ?int $deviceId=null): AttendancePunch {
  $external=(string)($event['external_user_ref']??$event['user_id']??$event['userId']??''); $at=Carbon::parse($event['punched_at']??$event['timestamp']??$event['datetime']??now());
  $map=$deviceId&&$external!==''?AttendanceDeviceEmployeeMap::where('attendance_device_id',$deviceId)->where('external_user_ref',$external)->where('is_active',true)->first():null;
  $ref=(string)($event['source_reference']??$event['id']??''); $hash=hash('sha256',implode('|',[$deviceId,$external,$at->toIso8601String(),$ref,(string)($event['punch_type']??$event['type']??'unknown')]));
  return AttendancePunch::firstOrCreate(['event_hash'=>$hash],['attendance_device_id'=>$deviceId,'employee_id'=>$map?->employee_id,'external_user_ref'=>$external?:null,'punched_at'=>$at,'punch_type'=>(string)($event['punch_type']??$event['type']??'unknown'),'source_reference'=>$ref?:null,'raw_payload'=>array_filter(['external_user_ref'=>$external?:null,'punched_at'=>$at->toIso8601String(),'punch_type'=>(string)($event['punch_type']??$event['type']??'unknown'),'source_reference'=>$ref?:null]),'processing_status'=>$map?'mapped':'unmapped']);
 }
 public function rebuildDay(int $employeeId, string $date): AttendanceRecord {
  return DB::transaction(function()use($employeeId,$date){ $p=AttendancePunch::where('employee_id',$employeeId)->whereDate('punched_at',$date)->orderBy('punched_at')->get(); if($p->isEmpty()) throw new \RuntimeException('No mapped punches found.');
   $worked=0; for($i=0;$i+1<$p->count();$i+=2)$worked += $p[$i]->punched_at->diffInMinutes($p[$i+1]->punched_at); $in=$p->first()->punched_at; $odd=$p->count()%2===1; $out=$p->count()>1?($odd?$p[$p->count()-2]->punched_at:$p->last()->punched_at):null; $methodId=DB::table('attendance_methods')->where('code','ATT_BIO')->value('id');
   $rec=AttendanceRecord::updateOrCreate(['employee_id'=>$employeeId,'work_date'=>$date],['clock_in_at'=>$in,'clock_out_at'=>$out,'status'=>'present','worked_minutes'=>$worked,'attendance_method_id'=>$methodId,'device_reference'=>'multi-punch','is_exception'=>$odd,'exception_reason'=>$odd?'Odd number of attendance punches requires review.':null]);
   AttendancePunch::whereIn('id',$p->pluck('id'))->update(['processing_status'=>'processed','processed_at'=>now()]); return $rec; });
 }
}
