<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ServiceLetterLetterhead;
use Illuminate\Http\Request;

class ServiceLetterLetterheadController extends Controller
{
    public function index()
    {
        $letterheads = ServiceLetterLetterhead::query()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view(
            'service-letter-letterheads.index',
            compact('letterheads')
        );
    }

    public function create()
    {
        return view(
            'service-letter-letterheads.form',
            [
                'letterhead' => new ServiceLetterLetterhead(),
            ]
        );
    }

    public function edit(
        ServiceLetterLetterhead $serviceLetterLetterhead
    ) {
        return view(
            'service-letter-letterheads.form',
            [
                'letterhead' => $serviceLetterLetterhead,
            ]
        );
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request
                ->file('logo')
                ->store('letterheads', 'public');
        }

        $letterhead = ServiceLetterLetterhead::create(
            $data + [
                'created_by' => $request->user()->id,
            ]
        );

        $this->syncDefault($letterhead);

        return redirect()
            ->route('service-letter-letterheads.index')
            ->with('success', 'Letterhead created.');
    }

    public function update(
        Request $request,
        ServiceLetterLetterhead $serviceLetterLetterhead
    ) {
        $data = $this->validated($request);

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request
                ->file('logo')
                ->store('letterheads', 'public');
        }

        $serviceLetterLetterhead->update($data);

        $this->syncDefault($serviceLetterLetterhead);

        return redirect()
            ->route('service-letter-letterheads.index')
            ->with('success', 'Letterhead updated.');
    }

    public function toggle(
        ServiceLetterLetterhead $serviceLetterLetterhead
    ) {
        $serviceLetterLetterhead->update([
            'is_active' => ! $serviceLetterLetterhead->is_active,
        ]);

        return back()->with(
            'success',
            'Letterhead status updated.'
        );
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'institution_name' => ['required', 'string', 'max:180'],
            'ministry_name' => ['nullable', 'string', 'max:180'],
            'department_name' => ['nullable', 'string', 'max:180'],
            'address_line_1' => ['nullable', 'string', 'max:180'],
            'address_line_2' => ['nullable', 'string', 'max:180'],
            'telephone' => ['nullable', 'string', 'max:80'],
            'fax' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:160'],
            'website' => ['nullable', 'string', 'max:160'],
            'reference_prefix' => ['nullable', 'string', 'max:40'],
            'signatory_designation' => ['nullable', 'string', 'max:180'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'header_note' => ['nullable', 'string', 'max:1000'],
            'footer_note' => ['nullable', 'string', 'max:1000'],
            'show_national_emblem' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]) + [
            'show_national_emblem' => $request->boolean(
                'show_national_emblem'
            ),
            'is_default' => $request->boolean('is_default'),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function syncDefault(
        ServiceLetterLetterhead $letterhead
    ): void {
        if (! $letterhead->is_default) {
            return;
        }

        ServiceLetterLetterhead::query()
            ->where('id', '<>', $letterhead->id)
            ->update([
                'is_default' => false,
            ]);
    }
}
