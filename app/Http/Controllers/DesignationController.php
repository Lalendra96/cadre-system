<?php

namespace App\Http\Controllers;

use App\Http\Requests\DesignationRequest;
use App\Models\Designation;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

/** Super Admin + Planning Officer — CRUD on the designation master list. */
class DesignationController extends Controller
{
    public function index(Request $request)
    {
        $query = Designation::query();

        if ($search = $request->query('q')) {
            $query->search($search);
        }

        $designations = $query->orderBy('title')->paginate(20)->withQueryString();

        return view('designations.index', compact('designations'));
    }

    public function create()
    {
        return view('designations.form', ['designation' => new Designation]);
    }

    public function store(DesignationRequest $request)
    {
        $designation = Designation::create($request->validated());
        AuditLogService::created($designation, "Created designation {$designation->code} — {$designation->title}");

        return redirect()->route('designations.index')->with('success', 'Designation created.');
    }

    public function edit(Designation $designation)
    {
        return view('designations.form', compact('designation'));
    }

    public function update(DesignationRequest $request, Designation $designation)
    {
        $old = $designation->getOriginal();
        $designation->update($request->validated());
        AuditLogService::updated($designation, $old, "Updated designation {$designation->code}");

        return redirect()->route('designations.index')->with('success', 'Designation updated.');
    }

    public function destroy(Designation $designation)
    {
        if ($designation->positions()->exists()) {
            return back()->with('error', 'Cannot remove a designation that has positions under it. Remove or reassign its positions first, or deactivate it instead.');
        }

        AuditLogService::deleted($designation, "Removed designation {$designation->code}");
        $designation->delete();

        return redirect()->route('designations.index')->with('success', 'Designation removed.');
    }
}
