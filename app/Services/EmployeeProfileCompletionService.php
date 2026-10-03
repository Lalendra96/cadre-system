<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Employee;
use App\Models\ProfileCompletionTarget;
use App\Models\User;
use Illuminate\Support\Collection;

class EmployeeProfileCompletionService
{
    public function rowsFor(User $viewer): Collection
    {
        if ($viewer->isSuperAdmin() || $viewer->isPlanningOfficer() || $viewer->isAdminGroup()) {
            return $this->rowsForInstitution();
        }

        if ($viewer->isSubjectOfficer()) {
            return collect([$this->buildOfficerRow($viewer)]);
        }

        return collect();
    }

    public function summaryFor(User $viewer): array
    {
        return $this->summaryFromRows($viewer, $this->rowsFor($viewer));
    }

    public function summaryFromRows(User $viewer, Collection $rows): array
    {
        $handlingCount = (int) $rows->sum(fn (array $row) => (int) ($row['handling_count'] ?? 0));
        $profileCount = (int) $rows->sum(fn (array $row) => (int) ($row['profile_count'] ?? 0));
        $remaining = max($handlingCount - $profileCount, 0);

        return [
            'handling_count' => $handlingCount,
            'profile_count' => $profileCount,
            'remaining_count' => $remaining,
            'completion_pct' => $handlingCount > 0
                ? round(min(100, ($profileCount / $handlingCount) * 100), 1)
                : null,
            'rows' => $rows->count(),
        ];
    }

    private function rowsForInstitution(): Collection
    {
        return User::query()
            ->active()
            ->whereHas('userRoles', fn ($query) => $query->where('role', User::ROLE_SUBJECT_OFFICER))
            ->with(['userRoles', 'subjectCodes:id,code,name'])
            ->orderBy('name')
            ->get()
            ->map(fn (User $officer) => $this->buildOfficerRow($officer))
            ->values();
    }

    private function buildOfficerRow(User $officer): array
    {
        $allocatedIds = WorkforceScopeService::allocatedEmployeeIds($officer)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $employees = Employee::query()
            ->whereIn('id', $allocatedIds->all())
            ->where('is_active', true)
            ->with([
                'position:id,code,title',
                'subjectCode:id,code,name',
            ])
            ->get(['id', 'position_id', 'subject_code_id']);

        $profileCount = $employees->count();

        $target = ProfileCompletionTarget::query()
            ->where('is_active', true)
            ->where('user_id', $officer->id)
            ->whereNull('subject_code_id')
            ->whereNull('position_id')
            ->latest('effective_from')
            ->latest('id')
            ->first();

        $handlingCount = $target?->target_count;
        $remainingCount = $handlingCount === null ? null : max($handlingCount - $profileCount, 0);
        $completionPct = $handlingCount && $handlingCount > 0
            ? round(min(100, ($profileCount / $handlingCount) * 100), 1)
            : null;

        $status = match (true) {
            $handlingCount === null => 'handling_not_set',
            $profileCount <= 0 => 'not_started',
            $profileCount >= $handlingCount => 'completed',
            default => 'in_progress',
        };

        $positionBreakdown = $employees
            ->groupBy(fn (Employee $employee) => $employee->position_id ?: 0)
            ->map(function (Collection $group): array {
                /** @var Employee $first */
                $first = $group->first();

                return [
                    'position_id' => $first->position_id,
                    'position_code' => $first->position?->code ?? 'UNASSIGNED',
                    'position_title' => $first->position?->title ?? 'Position not assigned',
                    'profile_count' => $group->count(),
                ];
            })
            ->sortByDesc('profile_count')
            ->values()
            ->all();

        $subjectCodes = $officer->effectiveSubjectCodeIds()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $subjectLabels = $officer->subjectCodes
            ->whereIn('id', $subjectCodes->all())
            ->map(fn ($subject) => $subject->code)
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        return [
            'officer_id' => $officer->id,
            'officer_name' => $officer->name,
            'subject_codes' => $subjectLabels,
            'target_id' => $target?->id,
            'handling_count' => $handlingCount,
            'target_source_reference' => $target?->source_reference,
            'profile_count' => $profileCount,
            'remaining_count' => $remainingCount,
            'completion_pct' => $completionPct,
            'status' => $status,
            'position_breakdown' => $positionBreakdown,
        ];
    }
}
