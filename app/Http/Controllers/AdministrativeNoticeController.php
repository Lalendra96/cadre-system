<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdministrativeNoticeController extends Controller
{
    public function acknowledge(
        Request $request
    ): RedirectResponse {
        $request->validate([
            'notice_acknowledged' => [
                'accepted',
            ],
        ]);

        $user = $request->user();
        $old = $user->getOriginal();

        $version = (string) SystemSetting::get(
            'administrative_notice_version',
            '1.0'
        );

        $user->update([
            'administrative_notice_acknowledged_version' => $version,
            'administrative_notice_acknowledged_at' => now(),
        ]);

        AuditLogService::updated(
            $user,
            $old,
            'Acknowledged Administrative Support System notice version '
                .$version
                .' after login.'
        );

        $request->session()->forget(
            'administrative_notice_required'
        );

        return back();
    }
}
