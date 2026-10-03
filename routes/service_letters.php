<?php

use App\Http\Controllers\AiAssistantController;
use App\Http\Controllers\ServiceLetterController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'role:subject_officer,super_admin,planning_officer,admin_group',
    'module:service_letters',
])->group(function (): void {
    Route::controller(ServiceLetterController::class)->group(function (): void {
        Route::get('/service-letters', 'index')->name('service-letters.index');
        Route::get('/service-letters/create', 'create')->name('service-letters.create');
        Route::post('/service-letters/preview', 'preview')->name('service-letters.preview');
        Route::post('/service-letters', 'store')->name('service-letters.store');

        Route::get('/service-letters/{serviceLetter}/edit', 'edit')
            ->name('service-letters.edit');
        Route::get('/service-letters/{serviceLetter}/workspace', 'workspace')
            ->name('service-letters.workspace');
        Route::patch('/service-letters/{serviceLetter}/autosave', 'autosave')
            ->name('service-letters.autosave');
        Route::post('/service-letters/{serviceLetter}/heartbeat', 'heartbeat')
            ->name('service-letters.heartbeat');
        Route::post('/service-letters/{serviceLetter}/comments', 'addComment')
            ->name('service-letters.comments.store');
        Route::patch(
            '/service-letters/{serviceLetter}/comments/{comment}/resolve',
            'resolveComment'
        )->name('service-letters.comments.resolve');
        Route::post(
            '/service-letters/{serviceLetter}/revisions/{revision}/restore',
            'restoreRevision'
        )->name('service-letters.revisions.restore');
        Route::put('/service-letters/{serviceLetter}', 'update')
            ->name('service-letters.update');
        Route::get('/service-letters/{serviceLetter}', 'show')
            ->name('service-letters.show');
        Route::get('/service-letters/{serviceLetter}/print', 'print')
            ->name('service-letters.print');
        Route::post('/service-letters/{serviceLetter}/submit', 'submit')
            ->name('service-letters.submit');
        Route::post('/service-letters/{serviceLetter}/approve', 'approve')
            ->name('service-letters.approve');
        Route::post('/service-letters/{serviceLetter}/reject', 'reject')
            ->name('service-letters.reject');
    });

    Route::post('/service-letters/ai-apply', [AiAssistantController::class, 'applyDraft'])
        ->middleware('module:ai_service_letter_assistant')
        ->name('service-letters.ai-apply');

    Route::post('/service-letters/ai-draft', [AiAssistantController::class, 'serviceLetter'])
        ->middleware('module:ai_service_letter_assistant')
        ->name('service-letters.ai-draft');
});
