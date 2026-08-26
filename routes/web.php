<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ApprovedCarderController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\CarderEntryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SystemSettingController;
use App\Http\Controllers\EntryAmendmentController;
use App\Http\Controllers\TransferRecordController;
use App\Http\Controllers\ActingAppointmentController;
use App\Http\Controllers\CadreReviewController;
use App\Http\Controllers\IpAllowlistController;
use App\Http\Controllers\ExportAuditController;
use App\Http\Middleware\IpAllowlistMiddleware;
use App\Http\Middleware\ConcurrentSessionMiddleware;
use App\Http\Controllers\ForcePasswordChangeController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LetterController;
use App\Http\Controllers\LetterReviewController;
use App\Http\Controllers\OfficerSubmissionController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\PlanningOfficerSummaryController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SubjectCodeController;
use App\Http\Controllers\UnitAllocationController;
use App\Http\Controllers\UnitBreakdownController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UnitTypeController;
use App\Http\Controllers\UserCategoryController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Carder Management Module Routes
|--------------------------------------------------------------------------
| Paste this block into your existing routes/web.php (or include it via
| Route::middleware('web')->group(base_path('routes/carder.php')) — your call).
| Requires the 'role' middleware alias registered in app/Http/Kernel.php — see README.md.
*/

// ── Public Circular/Memo sharing — DELIBERATELY outside both 'guest' and
// 'auth' middleware. 'guest' would wrongly redirect an already-logged-in
// staff member away the moment they click a shared link; 'auth' would
// defeat the entire point of "anyone with the link can view." Resolution
// is by the unguessable share_token only — see Circular model / migration
// docblocks and CircularController::show()/download() for the full
// data-security reasoning.
Route::get('/circulars',                       [\App\Http\Controllers\CircularController::class, 'publicIndex'])->name('circulars.index');
Route::get('/circulars/view/{token}',          [\App\Http\Controllers\CircularController::class, 'show'])->name('circulars.public-show');
Route::get('/circulars/view/{token}/download', [\App\Http\Controllers\CircularController::class, 'download'])->name('circulars.public-download');

// Presentation Mode — public, unauthenticated, aggregate-only cadre
// statistics display (e.g. a lobby/planning-office screen). See
// PresentationController docblock for exactly what is and isn't exposed
// here and why.
Route::get('/presentation', [\App\Http\Controllers\PresentationController::class, 'index'])->name('presentation.index');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    Route::get('/login/users-for-group', [LoginController::class, 'usersForGroup'])->name('login.users-for-group');


    // Forgot password — unauthenticated, no middleware restrictions
    Route::get('/forgot-password',  [ForgotPasswordController::class, 'show'])->name('forgot-password');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'submit'])->name('forgot-password.submit');
});

Route::middleware('auth')->group(function () {
    // ── These two routes are ALWAYS reachable for authenticated users ──────
    // They must remain outside the password.change middleware group so that
    // a user with force_password_change = true can log out or change their
    // password without being bounced between redirects.
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/change-password',  [ForcePasswordChangeController::class, 'show'])->name('change-password');
    Route::post('/change-password', [ForcePasswordChangeController::class, 'update'])->name('change-password.update');

    // ── Notifications — polled by the header bell via plain JS fetch().
    // Deliberately outside the EnsurePasswordNotExpired group: if these were
    // gated, a user who hasn't changed their temporary password yet would
    // have every poll silently redirected to an HTML page instead of JSON,
    // breaking the bell with a confusing console error instead of a clean
    // empty state.
    Route::get('/notifications',               [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count',   [\App\Http\Controllers\NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::post('/notifications/{notification}/read', [\App\Http\Controllers\NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all',      [\App\Http\Controllers\NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    // ── All other protected routes — blocked until password is updated ──────
    Route::middleware(\App\Http\Middleware\EnsurePasswordNotExpired::class)->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // ---- Super Admin only ----------------------------------------------
        Route::middleware('role:super_admin')->group(function () {
            Route::resource('users', UserController::class)->except(['show','destroy']);
        Route::patch('/users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
            Route::get('/users/{user}/reset-password',  [UserController::class, 'resetPasswordForm'])->name('users.reset-password');
            Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password.update');
            Route::resource('categories', UserCategoryController::class)->except(['show','destroy']);
        Route::patch('/categories/{category}/toggle', [UserCategoryController::class, 'toggle'])->name('categories.toggle');
            Route::resource('subject-codes', SubjectCodeController::class)->except(['show','destroy']);
        Route::patch('/subject-codes/{subjectCode}/toggle', [SubjectCodeController::class, 'toggle'])->name('subject-codes.toggle');
        });

    // ---- Super Admin + Planning Officer --------------------------------
    Route::middleware('role:super_admin,planning_officer')->group(function () {
        Route::resource('positions', PositionController::class)->except(['show','destroy']);
        Route::patch('/positions/{position}/toggle', [PositionController::class, 'toggle'])->name('positions.toggle');
        Route::resource('units', UnitController::class)->except(['show','destroy']);
        Route::patch('/units/{unit}/toggle', [UnitController::class, 'toggle'])->name('units.toggle');
        Route::resource('unit-types', UnitTypeController::class)->except(['show','destroy']);
        Route::patch('/unit-types/{unitType}/toggle', [UnitTypeController::class, 'toggle'])->name('unit-types.toggle');
    });

    // ── Planning Summary ───────────────────────────────────────────────────
    Route::middleware('role:super_admin,planning_officer,admin_group')->group(function () {
        Route::get('/planning/summary', [PlanningOfficerSummaryController::class, 'index'])->name('planning.summary');
    });

    // ── Approved Carder ───────────────────────────────────────────────────
    // VIEW: Super Admin, Planning Officer, Admin Group (any category)
    Route::middleware('role:super_admin,planning_officer,admin_group')->group(function () {
        Route::get('/approved-carders',        [ApprovedCarderController::class, 'index'])->name('approved-carders.index');
        Route::get('/approved-carders-print',  [ApprovedCarderController::class, 'print'])->name('approved-carders.print');
    });

    // WRITE: Super Admin, Planning Officer, or Admin Group (Director/Deputy/MO Planning)
    // Fine-grained category check is enforced inside the controller and FormRequest.
    Route::middleware('role:super_admin,planning_officer,admin_group')->group(function () {
        Route::get('/approved-carders/create',             [ApprovedCarderController::class, 'create'])->name('approved-carders.create');
        Route::post('/approved-carders',                   [ApprovedCarderController::class, 'store'])->name('approved-carders.store');
        Route::get('/approved-carders/{approved_carder}/edit',     [ApprovedCarderController::class, 'edit'])->name('approved-carders.edit');
        Route::put('/approved-carders/{approved_carder}',          [ApprovedCarderController::class, 'update'])->name('approved-carders.update');
        Route::patch('/approved-carders/{approved_carder}/toggle', [ApprovedCarderController::class, 'toggle'])->name('approved-carders.toggle');
    });

    // ---- Subject Officer — monthly data entry --------------------------
    // Gated by feature:employees — an officer with Employee Profile access
    // disabled also loses access to the monthly entry section, since both
    // deal with cadre/carder information for their assigned subject codes.
    Route::middleware('role:subject_officer')->middleware('feature:employees')->group(function () {
        Route::get('/carder-entries', [CarderEntryController::class, 'index'])->name('carder-entries.index');
        Route::get('/carder-entries/create', [CarderEntryController::class, 'create'])->name('carder-entries.create');
        Route::post('/carder-entries', [CarderEntryController::class, 'store'])->name('carder-entries.store');
        Route::get('/carder-entries/{carderEntry}/edit', [CarderEntryController::class, 'edit'])->name('carder-entries.edit');
        Route::put('/carder-entries/{carderEntry}', [CarderEntryController::class, 'update'])->name('carder-entries.update');
    });

    // ---- Employee Bulk Import — Super Admin uploads/maps/imports;
    // Subject Officers get read-only summary access for their own codes.
    // NOTE: /employee-imports/create (literal) MUST be registered before
    // /employee-imports/{batch} (wildcard) — otherwise Laravel tries to
    // route-model-bind the literal string "create" as a batch ID first.
    Route::middleware('role:super_admin')->group(function () {
        Route::get('/employee-imports/create',                    [\App\Http\Controllers\EmployeeImportController::class, 'create'])->name('employee-imports.create');
        Route::post('/employee-imports',                           [\App\Http\Controllers\EmployeeImportController::class, 'store'])->name('employee-imports.store');
        Route::get('/employee-imports/{batch}/mapping',            [\App\Http\Controllers\EmployeeImportController::class, 'mapping'])->name('employee-imports.mapping');
        Route::post('/employee-imports/{batch}/mapping',            [\App\Http\Controllers\EmployeeImportController::class, 'storeMapping'])->name('employee-imports.mapping.store');
        Route::get('/employee-imports/{batch}/preview',             [\App\Http\Controllers\EmployeeImportController::class, 'preview'])->name('employee-imports.preview');
        Route::post('/employee-imports/{batch}/import',             [\App\Http\Controllers\EmployeeImportController::class, 'import'])->name('employee-imports.import');
    });
    Route::middleware('role:subject_officer,super_admin,planning_officer')->group(function () {
        Route::get('/employee-imports',              [\App\Http\Controllers\EmployeeImportController::class, 'index'])->name('employee-imports.index');
        Route::get('/employee-imports/{batch}',       [\App\Http\Controllers\EmployeeImportController::class, 'show'])->name('employee-imports.show');
    });

    // ---- Employee Profiles — Subject Officer (scoped to own codes, gated by
    // can_view_employees) + Super Admin (full oversight) ----
    Route::middleware('role:subject_officer,super_admin')->group(function () {
        Route::middleware('feature:employees')->group(function () {
            Route::resource('employees', EmployeeController::class)->except(['show','destroy','index']);
        Route::patch('/employees/{employee}/toggle', [EmployeeController::class, 'toggle'])->name('employees.toggle');
        });
    });

    // employees.index specifically is ALSO open to Planning Officer and the
    // HR-records Admin Group categories (Director, DDG, Deputy Director, AO,
    // Chief Clerk) — browse/search only, so they can reach a specific
    // employee's Transfer/Interdiction/Leave/Confirmation/Acting-Appointment
    // pages. Deliberately NOT extended to create/store/edit/update/toggle —
    // full profile editing stays Subject-Officer/Super-Admin only; these
    // categories get exactly the HR-record access this feature asked for,
    // nothing more.
    Route::middleware('role:subject_officer,super_admin,planning_officer,admin_group')->group(function () {
        Route::middleware('feature:employees')->group(function () {
            Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
        });
    });

    // ---- Employee Increment & Grade history — same audience as Employee
    // Profiles; ownership is re-checked inside each controller against the
    // officer's EFFECTIVE subject codes (permanent + acting). ----
    Route::middleware('role:subject_officer,super_admin,planning_officer')->group(function () {
        Route::get('/employees/{employee}/increments',           [\App\Http\Controllers\EmployeeIncrementController::class, 'index'])->name('employee-increments.index');
        Route::get('/employees/{employee}/increments/create',    [\App\Http\Controllers\EmployeeIncrementController::class, 'create'])->name('employee-increments.create');
        Route::post('/employees/{employee}/increments',          [\App\Http\Controllers\EmployeeIncrementController::class, 'store'])->name('employee-increments.store');
        Route::patch('/employees/{employee}/increments/{increment}/toggle', [\App\Http\Controllers\EmployeeIncrementController::class, 'toggle'])->name('employee-increments.toggle');

        Route::get('/bulk-increments',  [\App\Http\Controllers\BulkIncrementController::class, 'create'])->name('bulk-increments.create');
        Route::post('/bulk-increments', [\App\Http\Controllers\BulkIncrementController::class, 'store'])->name('bulk-increments.store');

        Route::get('/employees/{employee}/grades',                [\App\Http\Controllers\EmployeeGradeController::class, 'index'])->name('employee-grades.index');
        Route::get('/employees/{employee}/grades/create',         [\App\Http\Controllers\EmployeeGradeController::class, 'create'])->name('employee-grades.create');
        Route::post('/employees/{employee}/grades',                [\App\Http\Controllers\EmployeeGradeController::class, 'store'])->name('employee-grades.store');
        Route::patch('/employees/{employee}/grades/{record}/toggle', [\App\Http\Controllers\EmployeeGradeController::class, 'toggle'])->name('employee-grades.toggle');

        Route::get('/employees/{employee}/qualifications',        [\App\Http\Controllers\EmployeeQualificationController::class, 'index'])->name('employee-qualifications.index');
        Route::get('/employees/{employee}/qualifications/create', [\App\Http\Controllers\EmployeeQualificationController::class, 'create'])->name('employee-qualifications.create');
        Route::post('/employees/{employee}/qualifications',        [\App\Http\Controllers\EmployeeQualificationController::class, 'store'])->name('employee-qualifications.store');
        Route::patch('/employees/{employee}/qualifications/{qualification}/toggle', [\App\Http\Controllers\EmployeeQualificationController::class, 'toggle'])->name('employee-qualifications.toggle');

        Route::get('/employees/{employee}/exam-records',        [\App\Http\Controllers\EmployeeExamRecordController::class, 'index'])->name('employee-exam-records.index');
        Route::get('/employees/{employee}/exam-records/create', [\App\Http\Controllers\EmployeeExamRecordController::class, 'create'])->name('employee-exam-records.create');
        Route::post('/employees/{employee}/exam-records',        [\App\Http\Controllers\EmployeeExamRecordController::class, 'store'])->name('employee-exam-records.store');
        Route::patch('/employees/{employee}/exam-records/{record}/toggle', [\App\Http\Controllers\EmployeeExamRecordController::class, 'toggle'])->name('employee-exam-records.toggle');
    });

    // ---- Interdiction / Leave (LWOP) records — Subject Officer (own codes)
    // PLUS the specific Admin Group categories authorised to manage
    // sensitive HR records: Director, Deputy Director General, Deputy
    // Director, Administrative Officer, Chief Clerk. Route middleware
    // admits admin_group broadly; User::canManageEmployeeHrRecord() does
    // the fine-grained category check inside each controller. ----
    Route::middleware('role:subject_officer,super_admin,planning_officer,admin_group')->group(function () {
        Route::get('/employees/{employee}/interdictions',        [\App\Http\Controllers\EmployeeInterdictionController::class, 'index'])->name('employee-interdictions.index');
        Route::get('/employees/{employee}/interdictions/create', [\App\Http\Controllers\EmployeeInterdictionController::class, 'create'])->name('employee-interdictions.create');
        Route::post('/employees/{employee}/interdictions',        [\App\Http\Controllers\EmployeeInterdictionController::class, 'store'])->name('employee-interdictions.store');
        Route::patch('/employees/{employee}/interdictions/{interdiction}/toggle', [\App\Http\Controllers\EmployeeInterdictionController::class, 'toggle'])->name('employee-interdictions.toggle');

        Route::get('/employees/{employee}/leave-records',        [\App\Http\Controllers\EmployeeLeaveRecordController::class, 'index'])->name('employee-leave-records.index');
        Route::get('/employees/{employee}/leave-records/create', [\App\Http\Controllers\EmployeeLeaveRecordController::class, 'create'])->name('employee-leave-records.create');
        Route::post('/employees/{employee}/leave-records',        [\App\Http\Controllers\EmployeeLeaveRecordController::class, 'store'])->name('employee-leave-records.store');
        Route::post('/employees/{employee}/leave-records/{record}/mark-returned', [\App\Http\Controllers\EmployeeLeaveRecordController::class, 'markReturned'])->name('employee-leave-records.mark-returned');
        Route::patch('/employees/{employee}/leave-records/{record}/toggle', [\App\Http\Controllers\EmployeeLeaveRecordController::class, 'toggle'])->name('employee-leave-records.toggle');

        Route::get('/employees/{employee}/confirmation', [\App\Http\Controllers\EmployeeConfirmationController::class, 'edit'])->name('employee-confirmation.edit');
        Route::put('/employees/{employee}/confirmation',  [\App\Http\Controllers\EmployeeConfirmationController::class, 'update'])->name('employee-confirmation.update');
    });

    // ---- Salary Scales — Super Admin / Planning Officer configuration ----
    Route::middleware('role:super_admin,planning_officer')->group(function () {
        Route::resource('salary-scales', \App\Http\Controllers\SalaryScaleController::class)->except(['show','destroy']);
        Route::patch('/salary-scales/{salaryScale}/toggle', [\App\Http\Controllers\SalaryScaleController::class, 'toggle'])->name('salary-scales.toggle');
    });

    // ---- Position Grading Criteria — flexible per-position configuration.
    // Subject Officer access is scoped inside the controller to positions
    // under their own subject codes (see PositionGradeController::authorizeConfigure()). ----
    Route::middleware('role:super_admin,planning_officer,subject_officer')->group(function () {
        Route::get('/positions/{position}/grades',             [\App\Http\Controllers\PositionGradeController::class, 'index'])->name('position-grades.index');
        Route::get('/positions/{position}/grades/create',      [\App\Http\Controllers\PositionGradeController::class, 'create'])->name('position-grades.create');
        Route::post('/positions/{position}/grades',             [\App\Http\Controllers\PositionGradeController::class, 'store'])->name('position-grades.store');
        Route::get('/positions/{position}/grades/{positionGrade}/edit', [\App\Http\Controllers\PositionGradeController::class, 'edit'])->name('position-grades.edit');
        Route::put('/positions/{position}/grades/{positionGrade}',      [\App\Http\Controllers\PositionGradeController::class, 'update'])->name('position-grades.update');
        Route::patch('/positions/{position}/grades/{positionGrade}/toggle', [\App\Http\Controllers\PositionGradeController::class, 'toggle'])->name('position-grades.toggle');
    });

    // ---- Acting Allowance Rules — Super Admin / Planning Officer only.
    // Deliberately NOT extended to Subject Officer, unlike grading criteria
    // above — this configures a pay-rate calculation policy, not an
    // operational HR record a Subject Officer would need to set day to day. ----
    Route::middleware('role:super_admin,planning_officer')->group(function () {
        Route::get('/acting-allowance-rules', [\App\Http\Controllers\PositionActingAllowanceRuleController::class, 'index'])->name('position-acting-allowance-rules.index');
        Route::get('/positions/{position}/acting-allowance-rule/create',  [\App\Http\Controllers\PositionActingAllowanceRuleController::class, 'create'])->name('position-acting-allowance-rules.create');
        Route::post('/positions/{position}/acting-allowance-rule',        [\App\Http\Controllers\PositionActingAllowanceRuleController::class, 'store'])->name('position-acting-allowance-rules.store');
        Route::get('/positions/{position}/acting-allowance-rule/edit',    [\App\Http\Controllers\PositionActingAllowanceRuleController::class, 'edit'])->name('position-acting-allowance-rules.edit');
        Route::put('/positions/{position}/acting-allowance-rule',         [\App\Http\Controllers\PositionActingAllowanceRuleController::class, 'update'])->name('position-acting-allowance-rules.update');
        Route::patch('/positions/{position}/acting-allowance-rule/toggle',[\App\Http\Controllers\PositionActingAllowanceRuleController::class, 'toggle'])->name('position-acting-allowance-rules.toggle');
    });

    // ---- Acting Subject Officer appointments — Super Admin only ----
    Route::middleware('role:super_admin')->group(function () {
        Route::get('/acting-subject-officers',            [\App\Http\Controllers\ActingSubjectOfficerController::class, 'index'])->name('acting-subject-officers.index');
        Route::get('/acting-subject-officers/create',      [\App\Http\Controllers\ActingSubjectOfficerController::class, 'create'])->name('acting-subject-officers.create');
        Route::post('/acting-subject-officers',             [\App\Http\Controllers\ActingSubjectOfficerController::class, 'store'])->name('acting-subject-officers.store');
        Route::patch('/acting-subject-officers/{actingSubjectOfficer}/toggle', [\App\Http\Controllers\ActingSubjectOfficerController::class, 'toggle'])->name('acting-subject-officers.toggle');
    });

    // ---- E-Signatures — Admin Group registers their own signature ----
    Route::middleware('role:admin_group,super_admin')->group(function () {
        Route::get('/e-signature',  [\App\Http\Controllers\ESignatureController::class, 'edit'])->name('e-signatures.edit');
        Route::post('/e-signature', [\App\Http\Controllers\ESignatureController::class, 'store'])->name('e-signatures.store');
    });
    Route::middleware('role:admin_group,super_admin,planning_officer,subject_officer')->group(function () {
        Route::get('/e-signature/{eSignature}/image', [\App\Http\Controllers\ESignatureController::class, 'show'])->name('e-signatures.show');
    });

    // ---- Executive Unit-wise Breakdown — Director / Deputy Director /
    // Deputy Director General only (fine-grained category check happens
    // inside the controller; route middleware just admits Admin Group +
    // Super Admin broadly, matching the isDirector()/isAdministrativeOfficer()
    // pattern used throughout this codebase) ----
    Route::middleware('role:admin_group,super_admin')->group(function () {
        Route::get('/executive/unit-breakdown', [\App\Http\Controllers\ExecutiveUnitBreakdownController::class, 'index'])->name('executive.unit-breakdown');
    });

    // ---- Vacancy Availability Letters — Admin Group issues (e-signed,
    // 90-day cooldown); everyone with a role can view what's currently available ----
    Route::middleware('role:subject_officer,super_admin,planning_officer,admin_group')->group(function () {
        Route::get('/vacancy-availability-letters', [\App\Http\Controllers\VacancyAvailabilityLetterController::class, 'index'])->name('vacancy-availability-letters.index');
    });
    Route::middleware('role:admin_group,super_admin')->group(function () {
        Route::get('/vacancy-availability-letters/create', [\App\Http\Controllers\VacancyAvailabilityLetterController::class, 'create'])->name('vacancy-availability-letters.create');
        Route::post('/vacancy-availability-letters',        [\App\Http\Controllers\VacancyAvailabilityLetterController::class, 'store'])->name('vacancy-availability-letters.store');
    });

    // ---- Service Letter Templates — Super Admin, Sinhala & English ----
    Route::middleware('role:super_admin')->group(function () {
        Route::resource('service-letter-templates', \App\Http\Controllers\ServiceLetterTemplateController::class)->except(['show','destroy']);
        Route::patch('/service-letter-templates/{serviceLetterTemplate}/toggle', [\App\Http\Controllers\ServiceLetterTemplateController::class, 'toggle'])->name('service-letter-templates.toggle');
    });

    // ---- Service Letters — Subject Officer drafts for own employees;
    // AO (Admin Group) approves/rejects with e-signature ----
    Route::middleware('role:subject_officer,super_admin,planning_officer,admin_group')->group(function () {
        Route::get('/service-letters',          [\App\Http\Controllers\ServiceLetterController::class, 'index'])->name('service-letters.index');
        Route::get('/service-letters/create',   [\App\Http\Controllers\ServiceLetterController::class, 'create'])->name('service-letters.create');
        Route::post('/service-letters/preview', [\App\Http\Controllers\ServiceLetterController::class, 'preview'])->name('service-letters.preview');
        Route::post('/service-letters',          [\App\Http\Controllers\ServiceLetterController::class, 'store'])->name('service-letters.store');
        Route::get('/service-letters/{serviceLetter}', [\App\Http\Controllers\ServiceLetterController::class, 'show'])->name('service-letters.show');
        Route::post('/service-letters/{serviceLetter}/submit',  [\App\Http\Controllers\ServiceLetterController::class, 'submit'])->name('service-letters.submit');
        Route::post('/service-letters/{serviceLetter}/approve', [\App\Http\Controllers\ServiceLetterController::class, 'approve'])->name('service-letters.approve');
        Route::post('/service-letters/{serviceLetter}/reject',  [\App\Http\Controllers\ServiceLetterController::class, 'reject'])->name('service-letters.reject');
    });

    // ---- Circulars/Memos internal portal — the browsing index is useful
    // information for every authenticated role, so it's open broadly;
    // upload rights are checked inside the controller (Subject Officer,
    // Admin Group, or Super Admin — see authorizeUpload()). ----
    // ---- Circulars/Memos staff management — the PUBLIC read-only portal
    // lives outside this auth group entirely (see top of this file, near
    // the login routes) for "easy access" with zero auth overhead. This
    // block is just the staff-only upload/manage side. ----
    Route::get('/circulars/manage',        [\App\Http\Controllers\CircularController::class, 'index'])->name('circulars.manage');
    Route::middleware('role:subject_officer,super_admin,admin_group')->group(function () {
        Route::get('/circulars/create',    [\App\Http\Controllers\CircularController::class, 'create'])->name('circulars.create');
        Route::post('/circulars',           [\App\Http\Controllers\CircularController::class, 'store'])->name('circulars.store');
        Route::patch('/circulars/{circular}/toggle', [\App\Http\Controllers\CircularController::class, 'toggle'])->name('circulars.toggle');
    });

    // Dispatch (send to groups) is a SEPARATE permission from upload/revoke
    // above — gated by the same can_manage_circular_groups flag as Position
    // Groups themselves, since "create groups and dispatch" was specified
    // as one combined capability.
    Route::middleware('role:subject_officer,super_admin,admin_group')->middleware('feature:circular-groups')->group(function () {
        Route::get('/circulars/{circular}/send',  [\App\Http\Controllers\CircularController::class, 'sendForm'])->name('circulars.send-form');
        Route::post('/circulars/{circular}/send', [\App\Http\Controllers\CircularController::class, 'sendToGroups'])->name('circulars.send');
    });

    // ---- Position Groups — created by Subject Officers themselves (gated
    // by can_manage_circular_groups, configured per-account by Super Admin —
    // see User::canManageCircularGroups()), with Super Admin retaining
    // oversight access to every group hospital-wide. ----
    Route::middleware('role:subject_officer,super_admin,admin_group,planning_officer')->middleware('feature:circular-groups')->group(function () {
        Route::resource('position-groups', \App\Http\Controllers\PositionGroupController::class)->except(['show','destroy']);
        Route::patch('/position-groups/{positionGroup}/toggle', [\App\Http\Controllers\PositionGroupController::class, 'toggle'])->name('position-groups.toggle');
    });

    // ---- Position Subcategories — Super Admin only. Subgrouping under a
    // main Position (e.g. Medical Consultant -> Health Information
    // Consultant, PGIM Trainee). counts_toward_parent_total controls
    // whether a subcategory rolls into the parent's total on the
    // Unit-wise Breakdown — see PositionSubcategory model docblock. ----
    Route::middleware('role:super_admin')->group(function () {
        Route::get('/position-subcategories', [\App\Http\Controllers\PositionSubcategoryController::class, 'index'])->name('position-subcategories.index');
        Route::get('/positions/{position}/subcategories/create', [\App\Http\Controllers\PositionSubcategoryController::class, 'create'])->name('position-subcategories.create');
        Route::post('/positions/{position}/subcategories',        [\App\Http\Controllers\PositionSubcategoryController::class, 'store'])->name('position-subcategories.store');
        Route::get('/positions/{position}/subcategories/{subcategory}/edit', [\App\Http\Controllers\PositionSubcategoryController::class, 'edit'])->name('position-subcategories.edit');
        Route::put('/positions/{position}/subcategories/{subcategory}',      [\App\Http\Controllers\PositionSubcategoryController::class, 'update'])->name('position-subcategories.update');
        Route::patch('/positions/{position}/subcategories/{subcategory}/toggle', [\App\Http\Controllers\PositionSubcategoryController::class, 'toggle'])->name('position-subcategories.toggle');
    });

    // ---- Letter Sharing: Subject Officer side (gated by can_view_letters) ----
    Route::middleware('role:subject_officer')->middleware('feature:letters')->group(function () {
        Route::get('/letters', [LetterController::class, 'index'])->name('letters.index');
        Route::get('/letters/create', [LetterController::class, 'create'])->name('letters.create');
        Route::post('/letters', [LetterController::class, 'store'])->name('letters.store');
        Route::get('/letters/{letter}', [LetterController::class, 'show'])->name('letters.show');
        Route::delete('/letters/{letter}', [LetterController::class, 'destroy'])->name('letters.destroy');
    });

    // ---- Letter Sharing: Director / Deputy Director / Administrative Officer
    // side, + Super Admin read-only oversight ----
    Route::middleware('role:admin_group,super_admin')->group(function () {
        Route::get('/letter-reviews', [LetterReviewController::class, 'index'])->name('letter-reviews.index');
        Route::get('/letter-reviews/{letter}', [LetterReviewController::class, 'show'])->name('letter-reviews.show');
        Route::post('/letter-reviews/{letter}', [LetterReviewController::class, 'update'])->name('letter-reviews.update');
    });

    // Shared file access endpoints — creator, assigned recipient, or Super Admin
    Route::get('/letters/{letter}/attachments/{attachment}/download', [LetterController::class, 'downloadAttachment'])->name('letters.attachments.download');
    Route::get('/letters/{letter}/attachments/{attachment}/preview',  [LetterController::class, 'previewAttachment'])->name('letters.attachments.preview');

    // ---- Admin Group + Super Admin — summarised reports & charts -------
    Route::middleware('role:super_admin,admin_group')->group(function () {
        Route::get('/reports/summary', [ReportController::class, 'summary'])->name('reports.summary');
        Route::get('/reports/chart-data', [ReportController::class, 'chartData'])->name('reports.chart-data');
        Route::get('/reports/officer-submissions', [OfficerSubmissionController::class, 'index'])->name('reports.officer-submissions');
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    });

    }); // end password.change group
});

    // ════════════════════════════════════════════════════════════════
    // CADRE MANAGEMENT — Extended Features
    // ════════════════════════════════════════════════════════════════

    // ── Super Admin only ─────────────────────────────────────────────────
    Route::middleware(\App\Http\Middleware\EnsureUserRole::class . ':super_admin')->group(function () {
        // System Settings (Features 1 & 10 configuration)
        Route::get('/admin/settings',         [SystemSettingController::class, 'index'])->name('admin.settings');
        Route::post('/admin/settings',        [SystemSettingController::class, 'update'])->name('admin.settings.update');

        // IP Allowlist (Security 17)
        Route::resource('admin/ip-allowlist', IpAllowlistController::class)->only(['index','create','store'])->names([
            'index'   => 'ip-allowlist.index',
            'create'  => 'ip-allowlist.create',
            'store'   => 'ip-allowlist.store',
            'destroy' => 'ip-allowlist.destroy',
        ])->only(['index','create','store','destroy']);

        // Export Audit Log (Security 18)
        Route::get('/admin/export-audit', [ExportAuditController::class, 'index'])->name('export-audit.index');
    });

    // ── Super Admin + Planning Officer + Admin Group (verification) ──────
    // Admin Group verifiers are allowed here; EntryAmendmentController::authorizeVerifier()
    // enforces the fine-grained category check INSIDE the controller so only
    // the configured category can actually approve/reject.
    Route::middleware(\App\Http\Middleware\EnsureUserRole::class . ':super_admin,planning_officer,admin_group')->group(function () {
        // Verification (Feature 10)
        Route::get('/carder-entries/pending-verification', [EntryAmendmentController::class, 'pendingVerification'])->name('carder-entries.pending-verification');
        Route::post('/carder-entries/{entry}/verify',      [EntryAmendmentController::class, 'verify'])->name('carder-entries.verify');
        Route::post('/carder-entries/{entry}/reject-verification', [EntryAmendmentController::class, 'rejectVerification'])->name('carder-entries.reject-verification');

        // Amendment approvals
        Route::get('/entry-amendments',                 [EntryAmendmentController::class, 'index'])->name('entry-amendments.index');
        Route::post('/entry-amendments/{entry}/approve', [EntryAmendmentController::class, 'approve'])->name('entry-amendments.approve');
        Route::post('/entry-amendments/{entry}/reject',  [EntryAmendmentController::class, 'reject'])->name('entry-amendments.reject');

        // Cadre Review (Feature 8) — also usable by Admin Group for review workflow
        Route::resource('cadre-reviews', CadreReviewController::class)->except(['show']);
        Route::post('/cadre-reviews/{review}/submit',  [CadreReviewController::class, 'submit'])->name('cadre-reviews.submit');
        Route::post('/cadre-reviews/{review}/approve', [CadreReviewController::class, 'approve'])->name('cadre-reviews.approve');
        Route::post('/cadre-reviews/{review}/reject',  [CadreReviewController::class, 'reject'])->name('cadre-reviews.reject');
        Route::post('/cadre-reviews/{review}/apply',   [CadreReviewController::class, 'apply'])->name('cadre-reviews.apply');
        Route::get('/cadre-reviews/{review}',          [CadreReviewController::class, 'show'])->name('cadre-reviews.show');
    });

    // ── Admin Group + Planning Officer + Super Admin (reports) ───────────
    Route::middleware(\App\Http\Middleware\EnsureUserRole::class . ':super_admin,admin_group,planning_officer')->group(function () {
        // Extended Reports (Features 11, 12, 13)
        Route::get('/reports/trend',    [\App\Http\Controllers\ReportController::class, 'trend'])->name('reports.trend');
        Route::get('/reports/yoy',      [\App\Http\Controllers\ReportController::class, 'yoy'])->name('reports.yoy');
        Route::get('/reports/snapshot', [\App\Http\Controllers\ReportController::class, 'snapshot'])->name('reports.snapshot');

        // PDF exports (barryvdh/laravel-dompdf) — audit-logged in PdfExportService
        Route::get('/reports/trend/export-pdf',    [\App\Http\Controllers\ReportController::class, 'exportTrendPdf'])->name('reports.trend.pdf');
        Route::get('/reports/yoy/export-pdf',      [\App\Http\Controllers\ReportController::class, 'exportYoyPdf'])->name('reports.yoy.pdf');
        Route::get('/reports/snapshot/export-pdf', [\App\Http\Controllers\ReportController::class, 'exportSnapshotPdf'])->name('reports.snapshot.pdf');

        // MoH Format Export (Feature 3)
        Route::get('/reports/export/moh-csv',  [\App\Http\Controllers\ReportController::class, 'exportMohCsv'])->name('reports.export.moh-csv');
        Route::get('/reports/export/moh-print',[\App\Http\Controllers\ReportController::class, 'exportMohPrint'])->name('reports.export.moh-print');
        Route::get('/reports/export/carder-register', [\App\Http\Controllers\ReportController::class, 'exportCarderRegister'])->name('reports.export.carder-register');

        // Retirement Projections (Feature 5)
        Route::get('/reports/retirement-projections', [\App\Http\Controllers\ReportController::class, 'retirementProjections'])->name('reports.retirement-projections');
        Route::get('/reports/retirement-projections/export-pdf', [\App\Http\Controllers\ReportController::class, 'exportRetirementPdf'])->name('reports.retirement-projections.pdf');

        // Unit-wise post availability breakdown (for MO Planning)
        Route::get('/reports/unit-breakdown', [UnitBreakdownController::class, 'index'])->name('reports.unit-breakdown');
        Route::get('/reports/unit-breakdown/export-pdf', [UnitBreakdownController::class, 'exportPdf'])->name('reports.unit-breakdown.pdf');

        // Unit Post Allocation — Planning Officer / MO Planning enters headcount per unit
        Route::get('/unit-allocations',              [UnitAllocationController::class, 'index'])->name('unit-allocations.index');
        Route::get('/unit-allocations/{unit}/edit',  [UnitAllocationController::class, 'edit'])->name('unit-allocations.edit');
        Route::put('/unit-allocations/{unit}',       [UnitAllocationController::class, 'update'])->name('unit-allocations.update');
    });

    // ---- Unit-Position Bindings — Super Admin only. Which positions are
    // relevant/expected for a given unit, used to scope the allocation
    // entry grid above. See unit_position migration docblock. ----
    Route::middleware('role:super_admin')->group(function () {
        Route::get('/unit-position-bindings',             [\App\Http\Controllers\UnitPositionBindingController::class, 'index'])->name('unit-position-bindings.index');
        Route::get('/unit-position-bindings/{unit}/edit',  [\App\Http\Controllers\UnitPositionBindingController::class, 'edit'])->name('unit-position-bindings.edit');
        Route::put('/unit-position-bindings/{unit}',       [\App\Http\Controllers\UnitPositionBindingController::class, 'update'])->name('unit-position-bindings.update');
    });

    // ---- Nav Items — Super Admin only. Configurable navigation, replacing
    // the previously hardcoded @if($user->isX()) chains. See nav_items
    // migration docblock for the visibility model. /preview and /create
    // (literal) are registered before /{navItem} (wildcard) — otherwise
    // Laravel tries to route-model-bind those literal strings as an ID. ----
    Route::middleware('role:super_admin')->group(function () {
        Route::get('/nav-items/preview', [\App\Http\Controllers\NavItemController::class, 'preview'])->name('nav-items.preview');
        Route::resource('nav-items', \App\Http\Controllers\NavItemController::class)->except(['show', 'destroy']);
        Route::patch('/nav-items/{navItem}/toggle', [\App\Http\Controllers\NavItemController::class, 'toggle'])->name('nav-items.toggle');
    });

    Route::middleware(\App\Http\Middleware\EnsureUserRole::class . ':super_admin,admin_group,planning_officer')->group(function () {
        // Acting Appointments view (all roles may view)
        Route::get('/acting-appointments', [ActingAppointmentController::class, 'index'])->name('acting-appointments.index');
    });

    // ── Subject Officer scope ────────────────────────────────────────────
    Route::middleware(\App\Http\Middleware\EnsureUserRole::class . ':subject_officer,super_admin,planning_officer')->group(function () {
        // Bulk entry / copy previous (Feature 9)
        Route::get('/carder-entries/copy-previous', [\App\Http\Controllers\CarderEntryController::class, 'copyPrevious'])->name('carder-entries.copy-previous');
        Route::get('/carder-entries/deadline-status', [\App\Http\Controllers\CarderEntryController::class, 'deadlineStatus'])->name('carder-entries.deadline-status');

        // Amendment request (Feature 2)
        Route::post('/carder-entries/{carderEntry}/cancel', [\App\Http\Controllers\CarderEntryController::class, 'cancel'])->name('carder-entries.cancel');
        Route::post('/carder-entries/{entry}/request-amendment', [EntryAmendmentController::class, 'request'])->name('carder-entries.request-amendment');
    });

    // ── Transfer Records / Acting Appointments — Subject Officer (own
    // codes) PLUS the specific Admin Group categories authorised to
    // manage HR records: Director, Deputy Director General, Deputy
    // Director, Administrative Officer, Chief Clerk. See
    // User::canManageEmployeeHrRecord() for the single source of truth
    // both controllers use internally. ──────────────────────────────────
    Route::middleware(\App\Http\Middleware\EnsureUserRole::class . ':subject_officer,super_admin,planning_officer,admin_group')->group(function () {
        // Transfer records (Feature 4) — includes Transfer Board / PSC circular fields
        Route::resource('transfer-records', TransferRecordController::class)->except(['show','destroy']);
        Route::patch('/transfer-records/{transferRecord}/toggle', [TransferRecordController::class, 'toggle'])->name('transfer-records.toggle');

        // Acting Appointments CRUD
        Route::resource('acting-appointments', ActingAppointmentController::class)->except(['show','index','destroy']);
        Route::patch('/acting-appointments/{actingAppointment}/toggle', [ActingAppointmentController::class, 'toggle'])->name('acting-appointments.toggle');
    });

