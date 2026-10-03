<?php

use App\Http\Controllers\RosterApprovalController;
use App\Http\Controllers\RosterController;
use App\Http\Controllers\RosterPlanController;
use App\Http\Controllers\RosterSettingsController;
use App\Http\Controllers\RosterTemplateController;
use App\Http\Controllers\RosterOperationsController;
use App\Http\Controllers\RosterEmployeeController;
use App\Http\Controllers\RosterAmendmentController;
use App\Http\Controllers\RosterOptimizationController;
use Illuminate\Support\Facades\Route;

// Roster creation/maintenance stays limited to authorised operational managers.
Route::middleware(['auth', 'roster.enabled', 'role:super_admin,admin_group,planning_officer,unit_manager'])
    ->prefix('roster')->name('roster.')->group(function () {
        Route::get('/', [RosterController::class, 'index'])->name('dashboard');
        Route::resource('templates', RosterTemplateController::class)->except(['show', 'destroy']);
        Route::post('templates/{template}/disable', [RosterTemplateController::class, 'disable'])->name('templates.disable');
        Route::resource('plans', RosterPlanController::class)->only(['index', 'create', 'store']);
        Route::post('plans/{plan}/submit', [RosterPlanController::class, 'submit'])->name('plans.submit');
        Route::post('plans/{plan}/start', [RosterPlanController::class, 'start'])->name('plans.start');
        Route::post('plans/{plan}/complete', [RosterPlanController::class, 'complete'])->name('plans.complete');

        Route::post('conflict-check', [RosterPlanController::class, 'checkConflict'])->name('conflict-check');
        Route::get('operations', [RosterOperationsController::class, 'operations'])->name('operations.index');
        Route::post('availability', [RosterOperationsController::class, 'saveAvailability'])->name('availability.store');
        Route::post('open-shifts', [RosterOperationsController::class, 'createOpenShift'])->name('open-shifts.store');
        Route::post('open-shifts/{shift}/claim', [RosterOperationsController::class, 'claimOpenShift'])->name('open-shifts.claim');
        Route::post('open-shift-claims/{claim}/review', [RosterOperationsController::class, 'reviewOpenShiftClaim'])->name('open-shift-claims.review');
        Route::post('assignments/{assignment}/swap', [RosterOperationsController::class, 'requestSwap'])->name('swaps.store');
        Route::post('swaps/{swap}/decide', [RosterOperationsController::class, 'approveSwap'])->name('swaps.decide');
        Route::post('staffing-rules', [RosterOperationsController::class, 'saveStaffingRule'])->name('staffing-rules.store');
        Route::post('plans/{plan}/amendments', [RosterAmendmentController::class, 'store'])->name('amendments.store');
        Route::middleware('workforce.feature:roster_optimizer')->get('optimizer', [RosterOptimizationController::class, 'index'])->name('optimizer.index');
    });

// Read-only plan detail is also available to the configured approver so a
// Consultant/Director can inspect the complete roster before deciding.
Route::middleware(['auth', 'roster.enabled'])
    ->prefix('roster')->name('roster.')->group(function () {
        Route::get('plans/{plan}', [RosterPlanController::class, 'show'])->name('plans.show');
    });

// Approvers are resolved from the configured workflow (user, role or category),
// therefore this route must not inherit roster-creator roles.
Route::middleware(['auth', 'roster.enabled'])
    ->prefix('roster')->name('roster.')->group(function () {
        Route::get('approvals', [RosterApprovalController::class, 'index'])->name('approvals.index');
        Route::post('approvals/{approval}/act', [RosterApprovalController::class, 'act'])->name('approvals.act');
        Route::post('amendments/{amendment}/review', [RosterAmendmentController::class, 'review'])->name('amendments.review');
    });

Route::middleware(['auth', 'roster.enabled'])
    ->prefix('roster/my')->name('roster.employee.')->group(function () {
        Route::get('/', [RosterEmployeeController::class, 'index'])->name('index');
        Route::post('open-shifts/{shift}/claim', [RosterEmployeeController::class, 'claim'])->name('claims.store');
        Route::post('assignments/{assignment}/swap', [RosterEmployeeController::class, 'requestSwap'])->name('swaps.store');
        Route::post('swaps/{swap}/respond', [RosterEmployeeController::class, 'respondSwap'])->name('swaps.respond');
        Route::post('assignments/{assignment}/acknowledge', [RosterEmployeeController::class, 'acknowledge'])->name('acknowledge');
    });

Route::middleware(['auth', 'workforce.superadmin'])
    ->prefix('system/roster')->name('roster.settings.')->group(function () {
        Route::get('/', [RosterSettingsController::class, 'index'])->name('index');
        Route::post('toggle', [RosterSettingsController::class, 'toggle'])->name('toggle');
        Route::post('workflow', [RosterSettingsController::class, 'saveWorkflow'])->name('workflow.store');
    });
