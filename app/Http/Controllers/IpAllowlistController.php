<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDisableToggle;
use App\Models\IpAllowlist;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class IpAllowlistController extends Controller
{
    use HandlesDisableToggle;

    public function index()
    {
        $entries = IpAllowlist::with('creator')->orderByDesc('created_at')->get();
        $yourIp  = request()->ip();
        return view('admin.ip-allowlist', compact('entries', 'yourIp'));
    }

    public function create()
    {
        return view('admin.ip-allowlist-form', ['entry' => new IpAllowlist()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cidr'        => ['required', 'string', 'max:45'],
            'description' => ['nullable', 'string', 'max:120'],
            'is_active'   => ['boolean'],
        ]);

        [$subnet] = explode('/', $data['cidr'] . '/0', 2);
        if (@inet_pton($subnet) === false) {
            return back()->withErrors(['cidr' => 'Invalid IP address or CIDR notation.'])->withInput();
        }

        $data['created_by'] = $request->user()->id;
        $data['is_active']  = $request->boolean('is_active', true);

        $entry = IpAllowlist::create($data);
        AuditLogService::created($entry, "Added IP allowlist: {$entry->cidr}");

        return redirect()->route('ip-allowlist.index')
            ->with('success', "IP range {$entry->cidr} added.");
    }

    public function toggle(Request $request, IpAllowlist $ipAllowlist): RedirectResponse
    {
        return $this->performToggle(
            request:       $request,
            model:         $ipAllowlist,
            label:         "IP range {$ipAllowlist->cidr}",
            requireReason: false,
        );
    }
}
