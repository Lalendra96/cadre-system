<?php
namespace App\Services\Attendance;
use App\Models\AttendanceDevice;
interface AttendanceAdapter { public function pull(AttendanceDevice $device, \DateTimeInterface $from, \DateTimeInterface $to): array; }
