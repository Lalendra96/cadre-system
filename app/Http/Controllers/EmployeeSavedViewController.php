<?php

namespace App\Http\Controllers;

use App\Models\SavedEmployeeView;
use Illuminate\Http\Request;

class EmployeeSavedViewController extends Controller
{
    public function store(Request $r)
    {
        $d = $r->validate(['name' => 'required|string|max:100', 'filters' => 'required|array']);
        $r->user()->savedEmployeeViews()->updateOrCreate(['name' => $d['name']], ['filters' => $d['filters']]);

        return back()->with('success', 'Employee view saved.');
    }

    public function destroy(Request $r, SavedEmployeeView $savedView)
    {
        abort_unless($savedView->user_id === $r->user()->id, 403);
        $savedView->delete();

        return back()->with('success', 'Saved view removed.');
    }
}
