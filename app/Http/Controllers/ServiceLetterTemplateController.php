<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDisableToggle;
use App\Models\ServiceLetterTemplate;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Super-Admin-managed Service Letter templates, one per (name, language)
 * pair. Body text uses {{placeholder}} tokens — see
 * App\Services\ServiceLetterService for the full supported placeholder
 * list and the escaping rule applied at render time.
 */
class ServiceLetterTemplateController extends Controller
{
    use HandlesDisableToggle;

    public function index(Request $request)
    {
        $this->authorizeSuperAdmin($request);

        $templates = ServiceLetterTemplate::orderBy('name')->orderBy('language')->paginate(20);

        return view('service-letter-templates.index', compact('templates'));
    }

    public function create(Request $request)
    {
        $this->authorizeSuperAdmin($request);

        return view('service-letter-templates.form', [
            'template'   => new ServiceLetterTemplate(),
            'placeholders' => \App\Services\ServiceLetterService::PLACEHOLDERS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:150'],
            'language'    => ['required', Rule::in(array_keys(ServiceLetterTemplate::LANGUAGES))],
            'body'        => ['required', 'string', 'max:5000'],
            'description' => ['nullable', 'string', 'max:300'],
        ]);
        // Compound unique(name, language) — surface a friendly message rather than a raw DB error.
        if (ServiceLetterTemplate::where('name', $data['name'])->where('language', $data['language'])->exists()) {
            return back()->withInput()->withErrors(['name' => 'A template with this name already exists in this language.']);
        }

        $data['created_by'] = $request->user()->id;
        $data['is_active']  = true;

        $template = ServiceLetterTemplate::create($data);
        AuditLogService::created($template, "Created service letter template \"{$template->name}\" ({$template->language})");

        return redirect()->route('service-letter-templates.index')->with('success', "Template \"{$template->name}\" created.");
    }

    public function edit(Request $request, ServiceLetterTemplate $serviceLetterTemplate)
    {
        $this->authorizeSuperAdmin($request);

        return view('service-letter-templates.form', [
            'template'     => $serviceLetterTemplate,
            'placeholders' => \App\Services\ServiceLetterService::PLACEHOLDERS,
        ]);
    }

    public function update(Request $request, ServiceLetterTemplate $serviceLetterTemplate): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:150'],
            'language'    => ['required', Rule::in(array_keys(ServiceLetterTemplate::LANGUAGES))],
            'body'        => ['required', 'string', 'max:5000'],
            'description' => ['nullable', 'string', 'max:300'],
        ]);
        if (ServiceLetterTemplate::where('name', $data['name'])->where('language', $data['language'])
                ->where('id', '!=', $serviceLetterTemplate->id)->exists()) {
            return back()->withInput()->withErrors(['name' => 'A template with this name already exists in this language.']);
        }

        $old = $serviceLetterTemplate->getOriginal();
        $serviceLetterTemplate->update($data);
        AuditLogService::updated($serviceLetterTemplate, $old, "Updated service letter template \"{$serviceLetterTemplate->name}\"");

        return redirect()->route('service-letter-templates.index')->with('success', "Template \"{$serviceLetterTemplate->name}\" updated.");
    }

    public function toggle(Request $request, ServiceLetterTemplate $serviceLetterTemplate): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        return $this->performToggle(
            request:       $request,
            model:         $serviceLetterTemplate,
            label:         "Service letter template \"{$serviceLetterTemplate->name}\" ({$serviceLetterTemplate->language})",
            requireReason: false,
        );
    }

    private function authorizeSuperAdmin(Request $request): void
    {
        abort_unless($request->user()->isSuperAdmin(), 403, 'Only Super Admin may manage service letter templates.');
    }
}
