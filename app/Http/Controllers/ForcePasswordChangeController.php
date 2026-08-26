<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Handles the mandatory password-change screen that appears when a Super
 * Admin has assigned a temporary password and set force_password_change=true.
 * The user must provide their current (temporary) password before they can
 * choose a new one — this proves they are the legitimate account holder and
 * not someone who has hijacked an already-authenticated session.
 */
class ForcePasswordChangeController extends Controller
{
    public function show(Request $request)
    {
        // If the flag is not set, they do not belong here.
        if (! $request->user()->force_password_change) {
            return redirect()->route('dashboard');
        }

        return view('auth.change-password');
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password'         => ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ], [
            'current_password.current_password' => 'The current password you entered is incorrect.',
            'password.min'         => 'Your new password must be at least 8 characters.',
            'password.mixed_case'  => 'Your new password must contain both upper and lower case letters.',
            'password.numbers'     => 'Your new password must contain at least one number.',
        ]);

        $user = $request->user();

        $user->update([
            'password'              => Hash::make($validated['password']),
            'force_password_change' => false,
        ]);

        AuditLogService::updated($user, [], "User changed password after temporary password login");

        return redirect()->route('dashboard')
            ->with('success', 'Password updated successfully. Welcome back.');
    }
}
