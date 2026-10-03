<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceCorrectionController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\CostCentreAnalyticsController;
use App\Http\Controllers\LeaveManagementController;
use App\Http\Controllers\LocumSessionController;
use App\Http\Controllers\OvertimeController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\SelfServiceController;
use App\Http\Controllers\WorkforceDashboardController;
use App\Http\Controllers\WorkforceSettingsController;
use App\Http\Controllers\AttendanceIntegrationController;
use App\Http\Controllers\LeaveAutomationController;
use App\Http\Controllers\ContractLifecycleController;
use App\Http\Controllers\LocumPoolController;
use App\Http\Controllers\DemandForecastController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:super_admin,admin_group,planning_officer,unit_manager'])
    ->prefix('workforce')->name('workforce.')->group(function () {
        Route::get('/', [WorkforceDashboardController::class, 'index'])->name('dashboard');

        Route::middleware('workforce.feature:attendance')->group(function () {
            Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
            Route::post('attendance', [AttendanceController::class, 'store'])->name('attendance.store');
            Route::post('attendance/corrections/{correction}/decide', [AttendanceCorrectionController::class, 'decide'])->name('attendance.corrections.decide');
        });

        Route::middleware(['workforce.feature:attendance','workforce.feature:biometric_attendance'])->group(function () {
            Route::get('attendance/integrations', [AttendanceIntegrationController::class, 'index'])->name('attendance-integration.index');
            Route::post('attendance/integrations/devices', [AttendanceIntegrationController::class, 'storeDevice'])->name('attendance-integration.devices.store');
            Route::post('attendance/integrations/devices/{device}/map', [AttendanceIntegrationController::class, 'mapEmployee'])->name('attendance-integration.map');
            Route::post('attendance/integrations/devices/{device}/sync', [AttendanceIntegrationController::class, 'sync'])->name('attendance-integration.sync');
            Route::post('attendance/integrations/rebuild', [AttendanceIntegrationController::class, 'rebuild'])->name('attendance-integration.rebuild');
        });

        Route::middleware('workforce.feature:leave')->group(function () {
            Route::get('leave', [LeaveManagementController::class, 'index'])->name('leave.index');
            Route::post('leave', [LeaveManagementController::class, 'store'])->name('leave.store');
            Route::post('leave/{leave}/decide', [LeaveManagementController::class, 'decide'])->name('leave.decide');
        });

        Route::middleware(['workforce.feature:leave','workforce.feature:leave_automation'])->group(function () {
            Route::get('leave/automation', [LeaveAutomationController::class, 'index'])->name('leave-automation.index');
            Route::post('leave/automation/policies', [LeaveAutomationController::class, 'storePolicy'])->name('leave-automation.policies.store');
            Route::post('leave/automation/apply', [LeaveAutomationController::class, 'apply'])->name('leave-automation.apply');
            Route::post('leave/automation/carry-forward', [LeaveAutomationController::class, 'carry'])->name('leave-automation.carry');
        });

        Route::middleware('workforce.feature:overtime')->group(function () {
            Route::get('overtime', [OvertimeController::class, 'index'])->name('overtime.index');
            Route::post('overtime', [OvertimeController::class, 'store'])->name('overtime.store');
            Route::post('overtime/{overtime}/approve', [OvertimeController::class, 'approve'])->name('overtime.approve');
        });

        Route::middleware('workforce.feature:contracts')->group(function () {
            Route::get('contracts', [ContractController::class, 'index'])->name('contracts.index');
            Route::post('contracts', [ContractController::class, 'store'])->name('contracts.store');
        });

        Route::middleware(['workforce.feature:contracts','workforce.feature:contract_lifecycle'])->group(function () {
            Route::get('contracts/lifecycle', [ContractLifecycleController::class, 'index'])->name('contract-lifecycle.index');
            Route::post('contracts/{contract}/probation-reviews', [ContractLifecycleController::class, 'createReview'])->name('contract-lifecycle.probation.store');
            Route::post('contracts/probation-reviews/{review}/decide', [ContractLifecycleController::class, 'decideReview'])->name('contract-lifecycle.probation.decide');
            Route::post('contracts/{contract}/renewals', [ContractLifecycleController::class, 'requestRenewal'])->name('contract-lifecycle.renewal.store');
            Route::post('contracts/renewals/{renewal}/decide', [ContractLifecycleController::class, 'decideRenewal'])->name('contract-lifecycle.renewal.decide');
        });

        Route::middleware('workforce.feature:payroll')->group(function () {
            Route::get('payroll', [PayrollController::class, 'index'])->name('payroll.index');
            Route::post('payroll/run', [PayrollController::class, 'createRun'])->name('payroll.run');
            Route::post('payroll/{run}/finalize', [PayrollController::class, 'finalize'])->name('payroll.finalize');
        });

        Route::middleware('workforce.feature:locum')->group(function () {
            Route::get('locum', [LocumSessionController::class, 'index'])->name('locum.index');
            Route::post('locum', [LocumSessionController::class, 'store'])->name('locum.store');
        });

        Route::middleware(['workforce.feature:locum','workforce.feature:locum_pool'])->group(function () {
            Route::get('locum/pool', [LocumPoolController::class, 'index'])->name('locum-pool.index');
            Route::post('locum/pool/profiles', [LocumPoolController::class, 'saveProfile'])->name('locum-pool.profile.store');
            Route::post('locum/pool/bookings', [LocumPoolController::class, 'book'])->name('locum-pool.booking.store');
            Route::post('locum/pool/bookings/{booking}/decide', [LocumPoolController::class, 'decide'])->name('locum-pool.booking.decide');
            Route::post('locum/pool/sessions/{session}/reconcile', [LocumPoolController::class, 'reconcile'])->name('locum-pool.reconcile');
        });

        Route::middleware('workforce.feature:demand_forecasting')->group(function () {
            Route::get('demand-forecast', [DemandForecastController::class, 'index'])->name('demand.index');
            Route::post('demand-forecast/rules', [DemandForecastController::class, 'storeRule'])->name('demand.rules.store');
            Route::post('demand-forecast/inputs', [DemandForecastController::class, 'storeInput'])->name('demand.inputs.store');
        });

        Route::middleware('workforce.feature:cost_centre')
            ->get('cost-centres', [CostCentreAnalyticsController::class, 'index'])
            ->name('cost-centre.index');
    });

Route::middleware(['auth', 'workforce.feature:self_service'])
    ->prefix('workforce')->name('workforce.')->group(function () {
        Route::get('self-service', [SelfServiceController::class, 'index'])->name('self-service.index');
        Route::post('self-service/attendance/{attendance}/correction', [AttendanceCorrectionController::class, 'store'])->name('self-service.attendance-correction.store');
        Route::post('self-service/availability', [\App\Http\Controllers\RosterOperationsController::class, 'saveAvailability'])->name('self-service.availability.store');
        Route::post('self-service/roster/{assignment}/swap', [\App\Http\Controllers\RosterOperationsController::class, 'requestSwap'])->name('self-service.swap.store');
    });

Route::middleware(['auth', 'workforce.superadmin'])
    ->prefix('system/workforce')->name('workforce.settings.')->group(function () {
        Route::get('/', [WorkforceSettingsController::class, 'index'])->name('index');
        Route::post('toggle', [WorkforceSettingsController::class, 'toggle'])->name('toggle');
        Route::post('holidays', [WorkforceSettingsController::class, 'storeHoliday'])->name('holidays.store');
    });
