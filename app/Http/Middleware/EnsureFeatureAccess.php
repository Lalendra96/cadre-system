<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates optional feature sections.
 *
 * IMPORTANT: The `feature:employees` and `feature:letters` restrictions
 * apply ONLY to pure Subject Officers — users whose sole role is
 * `subject_officer`. In a multi-role system, a user who holds BOTH
 * `subject_officer` AND any elevated role (Planning Officer, Admin Group,
 * Super Admin) must be treated as the elevated role for access purposes.
 * These users reach this middleware through role middleware that has already
 * granted them access; blocking them again here creates confusing 403s.
 *
 * Guards:
 *   can_view_employees = false          → block Employee Profiles + Monthly Entries
 *   can_view_letters   = false          → block Letter Sharing
 *   can_manage_circular_groups = false  → block Position Groups + Circular sending
 *   hasAnyPositionBoundCode()           → block Entries/Profiles with no position link
 *
 * Usage in routes: ->middleware('feature:employees') or ->middleware('feature:letters')
 *                   or ->middleware('feature:circular-groups')
 */
class EnsureFeatureAccess
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Role precedence: Super Admin > Admin Group > Planning Officer > Subject Officer
        // Elevated roles bypass ALL feature restrictions — they access these sections
        // for administrative, verification, and reporting purposes.
        if ($user->isSuperAdmin() || $user->isAdminGroup() || $user->isPlanningOfficer()) {
            return $next($request);
        }

        // Below this line: pure Subject Officers only.
        if (! $user->isSubjectOfficer()) {
            // Unknown role reaching a feature-gated route — deny safely.
            abort(403, 'You do not have access to this section.');
        }

        // ── Subject Officer-specific gates ───────────────────────────────

        $column = match ($feature) {
            'employees'       => 'can_view_employees',
            'letters'         => 'can_view_letters',
            'circular-groups' => 'can_manage_circular_groups',
            default           => null,
        };

        if ($column && ! $user->{$column}) {
            abort(403,
                'You do not have access to this section. ' .
                'Contact the system administrator to enable it for your account.'
            );
        }

        // For the employees feature, require at least one subject code
        // linked to a position — there is nothing position-related to
        // display or enter without this link.
        if ($feature === 'employees' && ! $user->hasAnyPositionBoundCode()) {
            abort(403,
                'None of your assigned subject codes are linked to a position. ' .
                'Monthly entries and employee profiles require a position assignment. ' .
                'Contact the system administrator.'
            );
        }

        return $next($request);
    }
}
