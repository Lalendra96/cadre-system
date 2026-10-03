<?php
namespace App\Services\Attendance;
use App\Models\AttendanceDevice;
class AttendanceAdapterManager { public function for(AttendanceDevice $d): AttendanceAdapter { return match($d->adapter){'biostar2_ta'=>app(BioStar2TaAdapter::class),'generic_rest'=>app(GenericRestAttendanceAdapter::class),default=>throw new \InvalidArgumentException('Unsupported attendance adapter: '.$d->adapter)}; } }
