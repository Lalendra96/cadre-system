<?php
namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\LeaveRequest;
use App\Models\RosterAssignment;
use App\Models\RosterAvailability;
use App\Services\FeatureToggleService;
use Illuminate\Support\Facades\DB;

class SelfServiceController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $employeeId = $user->employee_id;
        $roster = $employeeId ? RosterAssignment::with('dutyUnit')->where('employee_id',$employeeId)->whereDate('duty_date','>=',today())->orderBy('duty_date')->limit(12)->get() : collect();
        $leaveEnabled = FeatureToggleService::enabled('leave_management');
        $leave = ($employeeId && $leaveEnabled) ? LeaveRequest::with('leaveType')->where('employee_id',$employeeId)->latest()->limit(8)->get() : collect();
        $attendance = $employeeId ? AttendanceRecord::where('employee_id',$employeeId)->latest('work_date')->limit(10)->get() : collect();
        $availability = $employeeId ? RosterAvailability::where('employee_id',$employeeId)->whereDate('available_date','>=',today())->orderBy('available_date')->limit(10)->get() : collect();
        $payslips = collect();
        if ($employeeId && FeatureToggleService::enabled('payroll')) {
            $payslips = DB::table('payroll_lines as pl')->join('payroll_runs as pr','pr.id','=','pl.payroll_run_id')->where('pl.employee_id',$employeeId)->where('pr.is_locked',true)->select('pr.year','pr.month','pl.gross_pay','pl.net_pay')->latest('pr.year')->latest('pr.month')->limit(12)->get();
        }
        return view('workforce.self-service.index',compact('roster','leave','attendance','availability','payslips','employeeId','leaveEnabled'));
    }
}
