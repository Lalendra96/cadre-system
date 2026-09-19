<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Services\WorkforceScopeService;
use Illuminate\Http\Request;

class Employee360Controller extends Controller
{
    public function show(Request $request, Employee $employee)
    {
        WorkforceScopeService::authorizeEmployee(
            $request->user(),
            $employee
        );

        $employee->load([
            'position',
            'unit',
            'subjectCode',
            'salaryScale',
            'incrementRecords',
            'gradeRecords.positionGrade',
            'qualifications',
            'examRecords',
            'actingAppointments',
            'transferRecords',
            'leaveRecords',
            'interdictions',
            'serviceLetters',
            'lifecycleEvents.recordedBy',
            'dataQualityIssues',
            'documents.uploader',
            'servicePeriods.position',
            'servicePeriods.positionGrade',
            'servicePeriods.sourceDocument',
        ]);

        $timeline = collect();

        if ($employee->date_of_appointment) {
            $timeline->push([
                'date' => $employee->date_of_appointment,
                'type' => 'appointment',
                'title' => 'Joined service / appointment',
                'details' => $employee->position?->title,
            ]);
        }

        foreach ($employee->gradeRecords as $record) {
            $timeline->push([
                'date' => $record->effective_date,
                'type' => 'grade',
                'title' => 'Grade change',
                'details' => $record->positionGrade?->name,
            ]);
        }

        foreach (
            $employee->servicePeriods->where('is_active', true)
            as $record
        ) {
            $timeline->push([
                'date' => $record->start_date,
                'type' => 'service_period',
                'title' => $record->is_current
                    ? 'Current posting'
                    : 'Service / posting period',
                'details' => trim(
                    ($record->institution_name ?? '')
                    . ' · '
                    . $record->display_position
                    . ($record->display_grade
                        ? ' · ' . $record->display_grade
                        : '')
                ),
            ]);
        }

        foreach ($employee->incrementRecords as $record) {
            $timeline->push([
                'date' => $record->increment_date,
                'type' => 'increment',
                'title' => 'Increment ' . $record->workflow_status,
                'details' => $record->reference_no,
            ]);
        }

        foreach ($employee->transferRecords as $record) {
            $timeline->push([
                'date' => $record->effective_date,
                'type' => 'transfer',
                'title' => 'Transfer ' . strtoupper($record->direction),
                'details' => trim(
                    ($record->from_location ?? '')
                    . ' → '
                    . ($record->to_location ?? '')
                ),
            ]);
        }

        foreach ($employee->actingAppointments as $record) {
            $timeline->push([
                'date' => $record->start_date,
                'type' => 'acting',
                'title' => 'Acting appointment',
                'details' => $record->remarks,
            ]);
        }

        foreach ($employee->leaveRecords as $record) {
            $timeline->push([
                'date' => $record->start_date,
                'type' => 'leave',
                'title' => 'Leave / LWOP',
                'details' => $record->notes,
            ]);
        }

        foreach ($employee->interdictions as $record) {
            $timeline->push([
                'date' => $record->interdiction_date
                    ?? $record->created_at,
                'type' => 'interdiction',
                'title' => 'Interdiction',
                'details' => $record->reason
                    ?? $record->notes,
            ]);
        }

        foreach ($employee->lifecycleEvents as $record) {
            $timeline->push([
                'date' => $record->effective_date,
                'type' => $record->event_type,
                'title' => $record->title,
                'details' => $record->details,
            ]);
        }

        $timeline = $timeline
            ->filter(
                fn (array $item) => $item['date']
            )
            ->sortByDesc('date')
            ->values();

        $auditLogs = AuditLog::with('user')
            ->where('auditable_type', Employee::class)
            ->where('auditable_id', $employee->id)
            ->latest('created_at')
            ->limit(50)
            ->get();

        return view(
            'employees.show',
            compact(
                'employee',
                'timeline',
                'auditLogs'
            )
        );
    }
}
