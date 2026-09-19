<?php

use App\Http\Controllers\AiAssistantController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeServicePeriodController;
use App\Http\Controllers\FeatureManagementController;
use App\Http\Controllers\IncomingOfficerController;
use App\Http\Controllers\IncidentReportController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\GovernanceController;
use App\Http\Controllers\AdministrativeDecisionController;
use App\Http\Controllers\ServiceLetterController;
use App\Http\Controllers\ServiceLetterLetterheadController;
use App\Http\Controllers\ServiceLetterTemplateController;
use Illuminate\Support\Facades\Route;

Route::post(
    '/locale',
    [
        LocaleController::class,
        'update',
    ]
)->name('locale.update');

Route::middleware('auth')->group(
    function (): void {
        Route::get(
            '/employees/{employee}/ai-summary',
            [
                AiAssistantController::class,
                'employee',
            ]
        )
            ->middleware(
                'module:ai_record_assistant'
            )
            ->name(
                'ai-record-assistant.employee'
            );

        Route::middleware([
            'role:super_admin,planning_officer,subject_officer',
            'module:service_history',
        ])->group(
            function (): void {
                Route::get(
                    '/service-history',
                    [
                        EmployeeServicePeriodController::class,
                        'overview',
                    ]
                )->name(
                    'employee-service-periods.overview'
                );

                Route::get(
                    '/employees/{employee}/service-history',
                    [
                        EmployeeServicePeriodController::class,
                        'index',
                    ]
                )->name(
                    'employee-service-periods.index'
                );

                Route::get(
                    '/employees/{employee}/service-history/create',
                    [
                        EmployeeServicePeriodController::class,
                        'create',
                    ]
                )->name(
                    'employee-service-periods.create'
                );

                Route::post(
                    '/employees/{employee}/service-history',
                    [
                        EmployeeServicePeriodController::class,
                        'store',
                    ]
                )->name(
                    'employee-service-periods.store'
                );

                Route::get(
                    '/employees/{employee}/service-history/{period}/edit',
                    [
                        EmployeeServicePeriodController::class,
                        'edit',
                    ]
                )->name(
                    'employee-service-periods.edit'
                );

                Route::put(
                    '/employees/{employee}/service-history/{period}',
                    [
                        EmployeeServicePeriodController::class,
                        'update',
                    ]
                )->name(
                    'employee-service-periods.update'
                );
            }
        );

        Route::middleware([
            'role:subject_officer,super_admin',
            'feature:employees',
        ])->group(
            function (): void {
                Route::get(
                    '/employees/guided-create',
                    [
                        EmployeeController::class,
                        'guidedCreate',
                    ]
                )->name(
                    'employees.guided-create'
                );

                Route::get(
                    '/incoming-officers/create',
                    [
                        IncomingOfficerController::class,
                        'create',
                    ]
                )
                    ->middleware(
                        'module:service_history'
                    )
                    ->name(
                        'incoming-officers.create'
                    );

                Route::post(
                    '/incoming-officers',
                    [
                        IncomingOfficerController::class,
                        'store',
                    ]
                )
                    ->middleware(
                        'module:service_history'
                    )
                    ->name(
                        'incoming-officers.store'
                    );
            }
        );

        Route::middleware([
            'role:super_admin',
            'module:service_letters',
        ])->group(
            function (): void {
                Route::resource(
                    'service-letter-templates',
                    ServiceLetterTemplateController::class
                )->except([
                    'show',
                    'destroy',
                ]);

                Route::patch(
                    '/service-letter-templates/{serviceLetterTemplate}/toggle',
                    [
                        ServiceLetterTemplateController::class,
                        'toggle',
                    ]
                )->name(
                    'service-letter-templates.toggle'
                );

                Route::resource(
                    'service-letter-letterheads',
                    ServiceLetterLetterheadController::class
                )
                    ->parameters([
                        'service-letter-letterheads'
                            => 'serviceLetterLetterhead',
                    ])
                    ->except([
                        'show',
                        'destroy',
                    ]);

                Route::patch(
                    '/service-letter-letterheads/{serviceLetterLetterhead}/toggle',
                    [
                        ServiceLetterLetterheadController::class,
                        'toggle',
                    ]
                )->name(
                    'service-letter-letterheads.toggle'
                );
            }
        );

        Route::middleware([
            'role:subject_officer,super_admin,planning_officer,admin_group',
            'module:service_letters',
        ])->group(
            function (): void {
                Route::get(
                    '/service-letters',
                    [
                        ServiceLetterController::class,
                        'index',
                    ]
                )->name(
                    'service-letters.index'
                );

                Route::get(
                    '/service-letters/create',
                    [
                        ServiceLetterController::class,
                        'create',
                    ]
                )->name(
                    'service-letters.create'
                );

                Route::post(
                    '/service-letters/preview',
                    [
                        ServiceLetterController::class,
                        'preview',
                    ]
                )->name(
                    'service-letters.preview'
                );

                Route::post(
                    '/service-letters/ai-draft',
                    [
                        AiAssistantController::class,
                        'serviceLetter',
                    ]
                )
                    ->middleware(
                        'module:ai_service_letter_assistant'
                    )
                    ->name(
                        'service-letters.ai-draft'
                    );

                Route::post(
                    '/service-letters',
                    [
                        ServiceLetterController::class,
                        'store',
                    ]
                )->name(
                    'service-letters.store'
                );

                Route::get(
                    '/service-letters/{serviceLetter}/edit',
                    [
                        ServiceLetterController::class,
                        'edit',
                    ]
                )->name(
                    'service-letters.edit'
                );

                Route::put(
                    '/service-letters/{serviceLetter}',
                    [
                        ServiceLetterController::class,
                        'update',
                    ]
                )->name(
                    'service-letters.update'
                );

                Route::get(
                    '/service-letters/{serviceLetter}',
                    [
                        ServiceLetterController::class,
                        'show',
                    ]
                )->name(
                    'service-letters.show'
                );

                Route::get(
                    '/service-letters/{serviceLetter}/print',
                    [
                        ServiceLetterController::class,
                        'print',
                    ]
                )->name(
                    'service-letters.print'
                );

                Route::post(
                    '/service-letters/{serviceLetter}/submit',
                    [
                        ServiceLetterController::class,
                        'submit',
                    ]
                )->name(
                    'service-letters.submit'
                );

                Route::post(
                    '/service-letters/{serviceLetter}/approve',
                    [
                        ServiceLetterController::class,
                        'approve',
                    ]
                )->name(
                    'service-letters.approve'
                );

                Route::post(
                    '/service-letters/{serviceLetter}/reject',
                    [
                        ServiceLetterController::class,
                        'reject',
                    ]
                )->name(
                    'service-letters.reject'
                );
            }
        );

        Route::get(
            '/incidents',
            [
                IncidentReportController::class,
                'index',
            ]
        )->name('incidents.index');

        Route::get(
            '/incidents/create',
            [
                IncidentReportController::class,
                'create',
            ]
        )->name('incidents.create');

        Route::post(
            '/incidents',
            [
                IncidentReportController::class,
                'store',
            ]
        )->name('incidents.store');

        Route::get(
            '/incidents/{incidentReport}',
            [
                IncidentReportController::class,
                'show',
            ]
        )->name('incidents.show');

        Route::post(
            '/incidents/{incidentReport}/triage',
            [
                IncidentReportController::class,
                'triage',
            ]
        )->name('incidents.triage');

        Route::post(
            '/incidents/{incidentReport}/investigation',
            [
                IncidentReportController::class,
                'recordInvestigation',
            ]
        )->name('incidents.investigation');

        Route::post(
            '/incidents/{incidentReport}/corrections',
            [
                IncidentReportController::class,
                'recordCorrection',
            ]
        )->name('incidents.corrections.store');

        Route::post(
            '/incidents/{incidentReport}/resolve',
            [
                IncidentReportController::class,
                'resolve',
            ]
        )->name('incidents.resolve');

        Route::post(
            '/incidents/{incidentReport}/close',
            [
                IncidentReportController::class,
                'close',
            ]
        )->name('incidents.close');

        Route::post(
            '/incidents/{incidentReport}/reopen',
            [
                IncidentReportController::class,
                'reopen',
            ]
        )->name('incidents.reopen');

        Route::get(
            '/administrative-decisions',
            [
                AdministrativeDecisionController::class,
                'index',
            ]
        )->name('administrative-decisions.index');

        Route::get(
            '/administrative-decisions/{administrativeDecision}',
            [
                AdministrativeDecisionController::class,
                'show',
            ]
        )->name('administrative-decisions.show');

        Route::post(
            '/administrative-decisions/{administrativeDecision}/approve',
            [
                AdministrativeDecisionController::class,
                'approve',
            ]
        )->name('administrative-decisions.approve');

        Route::post(
            '/administrative-decisions/{administrativeDecision}/reject',
            [
                AdministrativeDecisionController::class,
                'reject',
            ]
        )->name('administrative-decisions.reject');

        Route::get(
            '/governance',
            [
                GovernanceController::class,
                'index',
            ]
        )->name('governance.index');

        Route::middleware(
            'role:super_admin'
        )->group(
            function (): void {
                Route::get(
                    '/admin/features',
                    [
                        FeatureManagementController::class,
                        'index',
                    ]
                )->name(
                    'admin.features'
                );

                Route::post(
                    '/admin/features',
                    [
                        FeatureManagementController::class,
                        'update',
                    ]
                )->name(
                    'admin.features.update'
                );

                Route::post(
                    '/governance/profile',
                    [
                        GovernanceController::class,
                        'updateProfile',
                    ]
                )->name('governance.profile.update');

                Route::post(
                    '/governance/business-rules',
                    [
                        GovernanceController::class,
                        'storeRule',
                    ]
                )->name('governance.rules.store');

                Route::put(
                    '/governance/business-rules/{businessRule}',
                    [
                        GovernanceController::class,
                        'updateRule',
                    ]
                )->name('governance.rules.update');

                Route::get(
                    '/governance/business-rules/{businessRule}/history',
                    [
                        GovernanceController::class,
                        'ruleHistory',
                    ]
                )->name('governance.rules.history');

                Route::patch(
                    '/governance/business-rules/{businessRule}/toggle',
                    [
                        GovernanceController::class,
                        'toggleRule',
                    ]
                )->name('governance.rules.toggle');
            }
        );
    }
);
