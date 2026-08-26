<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function create()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $showUsername = \App\Models\SystemSetting::getBool('login_show_username', true);
        $groups       = $showUsername ? [] : $this->loginGroups();

        return view('auth.login', compact('showUsername', 'groups'));
    }

    /**
     * Users belonging to a selected group/category, for the alternate
     * login flow's second dropdown. Deliberately public (the login page
     * itself is pre-auth) but tightly scoped: only id + name, only active
     * accounts, and rate-limited per IP to slow down someone scripting
     * their way through every group to build a full account list.
     */
    public function usersForGroup(Request $request)
    {
        $throttleKey = 'login-group-lookup|' . $request->ip();
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($throttleKey, 30)) {
            return response()->json(['users' => []], 429);
        }
        \Illuminate\Support\Facades\RateLimiter::hit($throttleKey, 60);

        $group = (string) $request->query('group', '');
        $users = collect();

        if (str_starts_with($group, 'role:')) {
            $role = substr($group, 5);
            if (in_array($role, ['super_admin', 'planning_officer', 'subject_officer'], true)) {
                $users = \App\Models\User::active()->havingRole($role)->orderBy('name')->get(['id', 'name']);
            }
        } elseif (str_starts_with($group, 'category:')) {
            $categoryId = (int) substr($group, 9);
            $users = \App\Models\User::active()->havingRole(\App\Models\User::ROLE_ADMIN_GROUP)
                ->where('category_id', $categoryId)
                ->orderBy('name')->get(['id', 'name']);
        }

        return response()->json([
            'users' => $users->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])->values(),
        ]);
    }

    private function loginGroups(): array
    {
        $groups = [
            ['value' => 'role:super_admin', 'label' => 'Super Admin'],
            ['value' => 'role:planning_officer', 'label' => 'Planning Officer'],
            ['value' => 'role:subject_officer', 'label' => 'Subject Officer'],
        ];

        foreach (\App\Models\UserCategory::active()->orderBy('sort_order')->get(['id', 'name']) as $cat) {
            $groups[] = ['value' => "category:{$cat->id}", 'label' => $cat->name];
        }

        return $groups;
    }

    public function store(Request $request)
    {
        $showUsername = \App\Models\SystemSetting::getBool('login_show_username', true);

        if ($showUsername) {
            $credentials = $request->validate([
                'email'    => ['required', 'email'],
                'password' => ['required', 'string'],
            ]);
            $throttleKey = strtolower($credentials['email']) . '|' . $request->ip();
        } else {
            // Alternate flow: the person picked a group, then their own
            // name from the resulting dropdown, so we already know the
            // exact account — resolve email from that user_id and
            // authenticate the same way as the normal flow from here on.
            $validated = $request->validate([
                'user_id'  => ['required', 'integer', 'exists:users,id'],
                'password' => ['required', 'string'],
            ]);
            $selectedUser = \App\Models\User::find($validated['user_id']);
            if (! $selectedUser) {
                throw ValidationException::withMessages(['user_id' => 'Please select your account again.']);
            }
            $credentials = ['email' => $selectedUser->email, 'password' => $validated['password']];
            $throttleKey = 'uid-' . $validated['user_id'] . '|' . $request->ip();
        }

        // Rate limit login attempts per email+IP to slow down brute force.
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($throttleKey);
            $field = $showUsername ? 'email' : 'user_id';
            throw ValidationException::withMessages([
                $field => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            \Illuminate\Support\Facades\RateLimiter::hit($throttleKey, 60);
            $field = $showUsername ? 'email' : 'user_id';
            throw ValidationException::withMessages([
                $field => 'These credentials do not match our records.',
            ]);
        }

        \Illuminate\Support\Facades\RateLimiter::clear($throttleKey);

        if (! $request->user()->is_active) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => 'Your account has been deactivated. Contact the system administrator.',
            ]);
        }

        $request->session()->regenerate();

        
        // Security 16: Store the new session ID so concurrent session middleware
        // can invalidate any older sessions on their next request.
        $user = auth()->user();
        if ($user) {
            $user->updateSessionToken($request->session()->getId());
            // Clear any pending password reset request on successful login
            if ($user->password_reset_requested_at) {
                $user->update(['password_reset_requested_at' => null]);
            }
        }

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
