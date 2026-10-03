<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ProfileCompletionTarget;
use App\Services\AuditLogService;
use App\Services\EmployeeProfileCompletionService;
use App\Services\PdfExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeProfileCompletionController extends Controller
{
    public function __construct(private readonly EmployeeProfileCompletionService $service) {}

    public function index(Request $request): View
    {
        $user = $request->user()->loadMissing(['userRoles', 'subjectCodes']);
        $this->authorizeView($user);

        $rows = $this->service->rowsFor($user);
        $summary = $this->service->summaryFromRows($user, $rows);

        return view('employee-profile-completion.index', [
            'rows' => $rows,
            'summary' => $summary,
            'canManageTargets' => $user->isSuperAdmin() || $user->isPlanningOfficer(),
            'canFilterExports' => $rows->count() > 1,
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $user = $request->user()->loadMissing(['userRoles', 'subjectCodes']);
        $this->authorizeView($user);

        $rows = $this->service->rowsFor($user);
        [$rows, $selectedOfficer] = $this->filterExportRows($request, $rows);
        $summary = $this->service->summaryFromRows($user, $rows);
        $officerSuffix = $selectedOfficer ? '-'.Str::slug($selectedOfficer['officer_name']) : '';
        $filename = 'employee-profile-handling-progress'.$officerSuffix.'-'.now()->format('Ymd-His').'.csv';

        AuditLogService::logExport(
            $user->id,
            'employee_profile_handling_csv',
            $filename,
            'profile_completion_tracker',
            null,
            null,
            $request->ip(),
            $request->userAgent()
        );

        return response()->streamDownload(function () use ($rows, $summary, $selectedOfficer): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Employee Profile Handling Count Progress']);
            fputcsv($out, ['Generated', now()->format('d M Y H:i')]);
            fputcsv($out, ['Export Scope', $selectedOfficer ? 'Subject Officer: '.$selectedOfficer['officer_name'] : 'All authorised Subject Officers']);
            fputcsv($out, ['Total Handling Count', $summary['handling_count']]);
            fputcsv($out, ['Current Profile Count', $summary['profile_count']]);
            fputcsv($out, ['Remaining', $summary['remaining_count']]);
            fputcsv($out, ['Completion %', $summary['completion_pct']]);
            fputcsv($out, []);
            fputcsv($out, [
                'Subject Officer',
                'Assigned Subject Codes',
                'Handling Count',
                'Current Profile Count',
                'Remaining',
                'Completion %',
                'Status',
                'Current Profiles by Post',
            ]);

            foreach ($rows as $row) {
                $breakdown = collect($row['position_breakdown'])
                    ->map(fn (array $position) => $position['position_title'].': '.$position['profile_count'])
                    ->implode(' | ');

                fputcsv($out, [
                    $row['officer_name'],
                    implode(', ', $row['subject_codes']),
                    $row['handling_count'],
                    $row['profile_count'],
                    $row['remaining_count'],
                    $row['completion_pct'],
                    str_replace('_', ' ', ucfirst($row['status'])),
                    $breakdown,
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, private',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $user = $request->user()->loadMissing(['userRoles', 'subjectCodes']);
        $this->authorizeView($user);

        $rows = $this->service->rowsFor($user);
        [$rows, $selectedOfficer] = $this->filterExportRows($request, $rows);
        $summary = $this->service->summaryFromRows($user, $rows);
        $officerSuffix = $selectedOfficer ? '-'.Str::slug($selectedOfficer['officer_name']) : '';
        $filename = 'employee-profile-handling-progress'.$officerSuffix.'-'.now()->format('Ymd-His').'.pdf';

        return PdfExportService::make(
            'pdf.employee-profile-handling-progress',
            [
                'rows' => $rows,
                'summary' => $summary,
                'generatedAt' => now(),
                'generatedBy' => $user->name,
                'selectedOfficer' => $selectedOfficer,
            ],
            $filename,
            'landscape',
            'a4',
            $user->id,
            'employee_profile_handling_pdf'
        )->download($request->ip(), $request->userAgent());
    }

    public function saveTarget(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isSuperAdmin() || $user->isPlanningOfficer(), 403);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'target_count' => ['required', 'integer', 'min:0', 'max:100000'],
            'effective_from' => ['required', 'date'],
            'source_reference' => ['required', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $previous = ProfileCompletionTarget::query()
            ->where('user_id', $validated['user_id'])
            ->whereNull('subject_code_id')
            ->whereNull('position_id')
            ->where('is_active', true)
            ->get();

        foreach ($previous as $oldTarget) {
            $oldTarget->update([
                'is_active' => false,
                'disabled_by' => $user->id,
                'disabled_at' => now(),
                'disable_reason' => 'Superseded by a new Subject Officer total handling count.',
                'updated_by' => $user->id,
            ]);
        }

        $target = ProfileCompletionTarget::create([
            'user_id' => $validated['user_id'],
            'subject_code_id' => null,
            'position_id' => null,
            'target_count' => $validated['target_count'],
            'effective_from' => $validated['effective_from'],
            'source_reference' => $validated['source_reference'],
            'notes' => $validated['notes'] ?? null,
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        AuditLog::query()->create([
            'user_id' => $user->id,
            'action' => 'created',
            'auditable_type' => ProfileCompletionTarget::class,
            'auditable_id' => $target->id,
            'description' => 'Set total employee-profile handling count for a Subject Officer.',
            'old_values' => null,
            'new_values' => [
                'user_id' => $target->user_id,
                'target_count' => $target->target_count,
                'effective_from' => $target->effective_from?->toDateString(),
                'source_reference' => $target->source_reference,
            ],
            'ip_address' => $request->ip(),
        ]);

        return redirect()
            ->route('employee-profile-completion.index')
            ->with('success', 'Total handling count updated for the Subject Officer. Previous totals were retained as history.');
    }

    /**
     * Apply the optional Subject Officer export filter without widening the
     * authenticated viewer's existing completion-tracker scope.
     *
     * @return array{0: Collection, 1: array|null}
     */
    private function filterExportRows(Request $request, Collection $authorisedRows): array
    {
        $validated = $request->validate([
            'officer_id' => ['nullable', 'integer'],
        ]);

        if (empty($validated['officer_id'])) {
            return [$authorisedRows->values(), null];
        }

        $officerId = (int) $validated['officer_id'];
        $selectedOfficer = $authorisedRows->first(
            fn (array $row): bool => (int) ($row['officer_id'] ?? 0) === $officerId
        );

        abort_unless($selectedOfficer !== null, 403, 'The selected Subject Officer is outside your authorised export scope.');

        return [
            $authorisedRows
                ->filter(fn (array $row): bool => (int) ($row['officer_id'] ?? 0) === $officerId)
                ->values(),
            $selectedOfficer,
        ];
    }

    private function authorizeView($user): void
    {
        abort_unless(
            $user->is_active && (
                $user->isSuperAdmin()
                || $user->isPlanningOfficer()
                || $user->isAdminGroup()
                || $user->isSubjectOfficer()
            ),
            403
        );
    }
}
