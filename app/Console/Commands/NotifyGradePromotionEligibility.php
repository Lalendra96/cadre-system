<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Notifies Super Admin AND the employee's Subject Officer when an
 * employee has spent enough time in their current grade to become
 * eligible for the next grade in their position's ladder.
 *
 * Example (from the original request): a Medical Officer position
 * configured with Grade III -> Grade II -> Grade I. Grade II has
 * criteria {"min_years_in_grade": 2}. An employee who has held Grade III
 * for 2+ years is flagged as eligible for promotion to Grade II.
 *
 * See PositionGrade::minYearsInGrade() / nextGrade() for how the
 * eligible-for grade and its threshold are resolved, and the
 * PositionGrade model docblock for the min_years_in_grade convention.
 *
 * Recipients are explicitly BOTH Super Admin and the relevant Subject
 * Officer(s) — Super Admin because grade promotion is ultimately a
 * hospital-wide HR action, the Subject Officer because they are the one
 * who will actually action it (recording the new EmployeeGradeRecord).
 *
 * SCHEDULING — add to app/Console/Kernel.php:
 *   $schedule->command('employees:notify-grade-promotion-eligibility')->dailyAt('07:35');
 */
class NotifyGradePromotionEligibility extends Command
{
    protected $signature = 'employees:notify-grade-promotion-eligibility';

    protected $description = 'Notify Super Admin and Subject Officers when an employee becomes eligible for promotion to their position\'s next configured grade';

    public function handle(): int
    {
        $employees = Employee::active()
            ->whereNotNull('position_id')
            ->whereNotNull('subject_code_id')
            ->with(['position', 'subjectCode'])
            ->get();

        $notified = 0;

        foreach ($employees as $employee) {
            $currentGrade = $employee->current_grade; // EmployeeGradeRecord|null, via accessor
            if (! $currentGrade || ! $currentGrade->positionGrade) {
                continue;
            }

            $nextGrade = $currentGrade->positionGrade->nextGrade();
            if (! $nextGrade) {
                continue; // already at the most senior configured grade
            }

            $threshold = $nextGrade->minYearsInGrade();
            if ($threshold === null) {
                continue; // next grade has no min_years_in_grade configured
            }

            // diffInDays / 365.25 rather than floatDiffInYears() — the
            // latter isn't used anywhere else in this codebase and its
            // availability depends on the exact Carbon minor version;
            // this calculation works identically on any Carbon 2.x.
            $yearsInGrade = $currentGrade->effective_date->diffInDays(now()) / 365.25;
            if ($yearsInGrade < $threshold) {
                continue;
            }

            // Idempotency: one notification per employee per (current grade
            // record, next grade) pair — re-running daily after the first
            // notification must not repeat it. Keyed to the grade record's
            // ID (not just the employee), so a later promotion followed by
            // a further eligibility for the grade after THAT still notifies.
            $cacheKey = "grade-promotion-notified:{$currentGrade->id}:{$nextGrade->id}";
            if (Cache::has($cacheKey)) {
                continue;
            }

            $recipients = User::active()
                ->where(function ($q) use ($employee) {
                    $q->havingRole(User::ROLE_SUPER_ADMIN)
                      ->orWhereHas('subjectCodes', fn ($sq) => $sq->where('subject_codes.id', $employee->subject_code_id));
                })
                ->get();

            if ($recipients->isEmpty()) {
                continue;
            }

            NotificationService::sendToMany(
                $recipients,
                type:  'grade_promotion_eligible',
                title: 'Employee eligible for grade promotion',
                body:  "{$employee->display_name} ({$employee->position->title}) has served "
                     . number_format($yearsInGrade, 1) . " years in {$currentGrade->positionGrade->name} "
                     . "and is now eligible for {$nextGrade->name}.",
                link:  route('employee-grades.index', $employee),
            );

            Cache::put($cacheKey, true, now()->addDays(2));
            $notified++;
        }

        $this->info("Notified for {$notified} grade-promotion-eligible employee(s).");
        return self::SUCCESS;
    }
}
