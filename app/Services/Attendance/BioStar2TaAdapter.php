<?php
namespace App\Services\Attendance;
use App\Models\AttendanceDevice;
use Illuminate\Support\Facades\Http;
class BioStar2TaAdapter implements AttendanceAdapter {
 public function pull(AttendanceDevice $device, \DateTimeInterface $from, \DateTimeInterface $to): array {
  $cfg=$device->encrypted_config?:[]; $path=$cfg['punch_path']??null; if(!$path) throw new \RuntimeException('BioStar 2 punch endpoint must be configured from the installed BioStar TA API documentation for the deployed version.');
  $req=Http::timeout((int)($cfg['timeout']??20))->acceptJson(); if(!empty($cfg['token'])) $req=$req->withToken($cfg['token']); if(!empty($cfg['headers'])&&is_array($cfg['headers'])) $req=$req->withHeaders($cfg['headers']);
  $res=$req->get(rtrim((string)$device->base_url,'/').'/'.ltrim($path,'/'),['from'=>$from->format(DATE_ATOM),'to'=>$to->format(DATE_ATOM)]); $res->throw(); $json=$res->json(); return is_array($json['records']??null)?$json['records']:(is_array($json)?$json:[]);
 }
}
