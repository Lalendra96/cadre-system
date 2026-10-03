<?php
namespace App\Http\Controllers;
use App\Services\WorkforceFeatureService;
use Illuminate\Support\Facades\DB;
class WorkforceDashboardController extends Controller { public function index(WorkforceFeatureService $features){ $cards=[
'Employees'=>DB::table('employees')->where('is_active',true)->count(),
'Present Today'=>DB::table('attendance_records')->whereDate('work_date',today())->where('status','present')->count(),
'On Leave'=>DB::table('leave_requests')->where('status','approved')->whereDate('start_date','<=',today())->whereDate('end_date','>=',today())->count(),
'Pending OT'=>DB::table('overtime_requests')->where('status','pending')->count(),
'Active Contracts'=>DB::table('employee_contracts')->where('is_active',true)->count(),]; return view('workforce.dashboard',compact('cards','features')); } }