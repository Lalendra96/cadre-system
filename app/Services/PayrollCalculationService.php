<?php
namespace App\Services;

use App\Models\EmployeeContract;
use App\Models\LocumSession;
use App\Models\OvertimeRequest;
use App\Models\PayrollAdjustment;
use App\Models\StatutoryRateProfile;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PayrollCalculationService
{
    public function calculate(int $employeeId, int $year, int $month, StatutoryRateProfile $rates): array
    {
        $contract = EmployeeContract::query()->where('employee_id',$employeeId)->where('is_active',true)
            ->whereDate('start_date','<=',sprintf('%04d-%02d-01',$year,$month))
            ->where(function($q) use($year,$month){ $q->whereNull('end_date')->orWhereDate('end_date','>=',sprintf('%04d-%02d-01',$year,$month)); })
            ->latest('start_date')->first();
        if (! $contract) throw new RuntimeException('No active contract for payroll period.');

        $basic=(float)($contract->basic_salary ?? 0);
        $adjustments=DB::table('payroll_adjustments')->where('employee_id',$employeeId)->where('year',$year)->where('month',$month)->where('status','approved')->get();
        $allowances=(float)$adjustments->where('type','allowance')->sum('amount');
        $deductions=(float)$adjustments->where('type','deduction')->sum('amount');

        $ot=OvertimeRequest::query()->where('employee_id',$employeeId)->whereYear('work_date',$year)->whereMonth('work_date',$month)->where('status','approved')->get();
        $hourly=(float)($contract->hourly_rate ?: ($basic > 0 ? $basic/240 : 0));
        $overtime=$ot->sum(fn($r)=> (($r->minutes_approved ?? $r->minutes_requested)/60) * $hourly * (float)$r->rate_multiplier);

        $locum=(float)LocumSession::query()->where('employee_id',$employeeId)->whereYear('session_date',$year)->whereMonth('session_date',$month)->where('status','approved')->sum('calculated_amount');
        $gross=$basic+$allowances+$overtime+$locum;
        $epfEmployee=$gross*((float)$rates->epf_employee_percent/100);
        $epfEmployer=$gross*((float)$rates->epf_employer_percent/100);
        $etfEmployer=$gross*((float)$rates->etf_employer_percent/100);

        // APIT is intentionally not guessed. Configure the current IRD schedule in statutory_rate_profiles.
        $apit=0.0;
        if (($rates->apit_rules['automatic_calculation'] ?? false) !== true) {
            // Keep at zero until an authorized payroll administrator configures/validates current tax rules.
        }

        return compact('contract','basic','allowances','overtime','locum','gross','epfEmployee','epfEmployer','etfEmployer','apit','deductions') + [
            'net' => $gross-$epfEmployee-$apit-$deductions,
            'snapshot' => ['rates'=>$rates->toArray(),'generated_at'=>now()->toIso8601String()],
        ];
    }
}
