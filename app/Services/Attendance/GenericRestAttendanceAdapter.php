<?php
namespace App\Services\Attendance;
use App\Models\AttendanceDevice;
use Illuminate\Support\Facades\Http;
class GenericRestAttendanceAdapter implements AttendanceAdapter {
 public function pull(AttendanceDevice $device, \DateTimeInterface $from, \DateTimeInterface $to): array {
  $cfg=$device->encrypted_config?:[]; $path=$cfg['event_path']??'/events'; $req=Http::timeout((int)($cfg['timeout']??20))->acceptJson(); if(!empty($cfg['token']))$req=$req->withToken($cfg['token']);
  $res=$req->get(rtrim((string)$device->base_url,'/').'/'.ltrim($path,'/'),['from'=>$from->format(DATE_ATOM),'to'=>$to->format(DATE_ATOM)]); $res->throw(); $json=$res->json(); $records=data_get($json,$cfg['records_path']??'records',$json); return is_array($records)?$records:[];
 }
}
