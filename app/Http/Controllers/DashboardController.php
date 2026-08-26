<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Routes the user to the correct landing dashboard based on their roles.
 * A user with multiple roles is routed by highest-privilege role.
 */
class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isSuperAdmin() || $user->isAdminGroup()) {
            return redirect()->route('reports.summary');
        }

        if ($user->isPlanningOfficer()) {
            return redirect()->route('planning.summary');
        }

        if ($user->isSubjectOfficer()) {
            if ($user->can_view_employees && $user->hasAnyPositionBoundCode()) {
                return redirect()->route('carder-entries.index');
            }

            if ($user->can_view_letters) {
                return redirect()->route('letters.index');
            }

            return view('dashboard.no-access');
        }

        return view('dashboard.blank');
    }
}
