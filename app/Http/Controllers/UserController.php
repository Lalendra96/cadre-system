<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use App\Models\UserCategory;
use App\Models\SubjectCode;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

/** Super Admin only — full user lifecycle management. */
class UserController extends Controller
{
    public function index(Request $request)
    {
        $showInactive   = $request->boolean('show_inactive');
        $canSeeInactive = $request->user()->isSuperAdmin();

        $query = User::with(['userRoles', 'category', 'subjectCodes'])->orderBy('name');

        // By default show only active accounts; Super Admin can toggle to see disabled
        if (! $showInactive || ! $canSeeInactive) {
            $query->active();
        }

        if ($search = trim($request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('name ILIKE ?', ["%{$search}%"])
                  ->orWhereRaw('email ILIKE ?', ["%{$search}%"]);
            });
        }

        if ($role = $request->query('role')) {
            $query->havingRole($role);
        }

        if ($status = $request->query('status')) {
            match ($status) {
                'active'    => $query->where('is_active', true),
                'inactive'  => $query->where('is_active', false),
                'pending'   => $query->where('force_password_change', true),
                'requested' => $query->whereNotNull('password_reset_requested_at'),
                default     => null,
            };
        }

        $users = $query->paginate(20)->withQueryString();

        return view('users.index', compact('users', 'showInactive', 'canSeeInactive'));
    }

    public function create()
    {
        $categories   = UserCategory::active()->orderBy('name')->get();
        $subjectCodes = SubjectCode::active()->orderBy('code')->get();

        return view('users.form', [
            'user'         => new User(),
            'allRoles'     => User::ROLES,
            'roleLabels'   => User::ROLE_LABELS,
            'categories'   => $categories,
            'subjectCodes' => $subjectCodes,
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();
        $roles          = $data['roles'];
        $subjectCodeIds = $data['subject_code_ids'] ?? [];
        unset($data['roles'], $data['subject_code_ids']);

        $data['password']          = Hash::make($data['password']);
        $data['is_active']         = $request->boolean('is_active');
        $data['can_view_employees'] = $request->boolean('can_view_employees');
        $data['can_view_letters']   = $request->boolean('can_view_letters');
        $data['can_manage_circular_groups'] = $request->boolean('can_manage_circular_groups');

        try {
            $user = DB::transaction(function () use ($data, $roles, $subjectCodeIds, $request) {
                $user = User::create($data);
    
                foreach ($roles as $role) {
                    UserRole::create(['user_id' => $user->id, 'role' => $role]);
                }
    
                if (! empty($subjectCodeIds)) {
                    $user->subjectCodes()->sync(
                        collect($subjectCodeIds)->mapWithKeys(fn ($id) => [
                            $id => ['assigned_by' => $request->user()->id, 'assigned_at' => now()],
                        ])
                    );
                }
    
                return $user;
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error(
                '[Transaction failed] ' . $e->getMessage(),
                ['exception' => $e, 'file' => __FILE__, 'line' => __LINE__]
            );
            return back()->with('error', 'A database error occurred. Please try again or contact support.');
        }

        AuditLogService::created($user, "Created user {$user->email} with roles: " . implode(', ', $roles));

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $user->load(['userRoles', 'subjectCodes']);
        $categories   = UserCategory::active()->orderBy('name')->get();
        $subjectCodes = SubjectCode::active()->orderBy('code')->get();

        return view('users.form', [
            'user'         => $user,
            'allRoles'     => User::ROLES,
            'roleLabels'   => User::ROLE_LABELS,
            'categories'   => $categories,
            'subjectCodes' => $subjectCodes,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $old  = $user->getOriginal();
        $data = $request->validated();
        $roles          = $data['roles'];
        $subjectCodeIds = $data['subject_code_ids'] ?? [];
        unset($data['roles'], $data['subject_code_ids']);

        $data['is_active']         = $request->boolean('is_active');
        $data['can_view_employees'] = $request->boolean('can_view_employees');
        $data['can_view_letters']   = $request->boolean('can_view_letters');
        $data['can_manage_circular_groups'] = $request->boolean('can_manage_circular_groups');

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        try {
            DB::transaction(function () use ($user, $data, $roles, $subjectCodeIds, $request) {
                $user->update($data);
    
                // Re-sync roles completely
                $user->userRoles()->delete();
                foreach ($roles as $role) {
                    UserRole::create(['user_id' => $user->id, 'role' => $role]);
                }
    
                $user->subjectCodes()->sync(
                    collect($subjectCodeIds)->mapWithKeys(fn ($id) => [
                        $id => ['assigned_by' => $request->user()->id, 'assigned_at' => now()],
                    ])
                );
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error(
                '[Transaction failed] ' . $e->getMessage(),
                ['exception' => $e, 'file' => __FILE__, 'line' => __LINE__]
            );
            return back()->with('error', 'A database error occurred. Please try again or contact support.');
        }

        AuditLogService::updated($user, $old, "Updated user {$user->email}");

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    /** Show the admin-facing form to set a temporary password for a user. */
    public function resetPasswordForm(User $user)
    {
        return view('users.reset-password', compact('user'));
    }

    /**
     * Set a temporary password for a user account.
     * - If auto_generate is checked, a secure random password is generated.
     * - The plaintext temp password is flashed to the session ONCE and shown
     *   to the admin on the next page load; it cannot be retrieved afterwards.
     * - force_password_change = true forces the user to choose a new password
     *   on their very next login before accessing any other route.
     * - Any existing remember-me tokens for the user are invalidated so they
     *   must log in fresh with the new temp password.
     */
    public function resetPassword(Request $request, User $user)
    {
        $autoGenerate = $request->boolean('auto_generate');

        if (! $autoGenerate) {
            $request->validate([
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ], [
                'password.min' => 'The temporary password must be at least 8 characters.',
            ]);
        }

        // Generate or use the supplied password.
        $plaintext = $autoGenerate
            ? $this->generateTempPassword()
            : $request->input('password');

        $old = $user->getOriginal();

        $user->update([
            'password'                    => Hash::make($plaintext),
            'force_password_change'       => true,
            'password_reset_requested_at' => null,  // clear the "forgot password" request
            'remember_token'              => null,  // invalidate any existing "remember me" sessions
        ]);

        AuditLogService::updated($user, $old, "Temporary password set for {$user->email} by " . $request->user()->email);

        // Flash the plaintext password ONCE so the admin can share it.
        // It is never stored in plain text anywhere else.
        return redirect()->route('users.reset-password', $user)
            ->with('temp_password', $plaintext);
    }

    /**
     * Generates a memorable but secure temporary password:
     * one uppercase letter, several lowercase, digits, and a symbol.
     * Avoids visually ambiguous characters (0/O, 1/I/l).
     */
    private function generateTempPassword(): string
    {
        $upper   = 'ABCDEFGHJKMNPQRSTUVWXYZ';
        $lower   = 'abcdefghjkmnpqrstuvwxyz';
        $digits  = '23456789';
        $symbols = '@#$!';

        $password  = $upper[random_int(0, strlen($upper) - 1)];
        $password .= $symbols[random_int(0, strlen($symbols) - 1)];

        for ($i = 0; $i < 5; $i++) {
            $password .= $lower[random_int(0, strlen($lower) - 1)];
        }
        for ($i = 0; $i < 3; $i++) {
            $password .= $digits[random_int(0, strlen($digits) - 1)];
        }

        // Shuffle so the pattern isn't predictable
        return str_shuffle($password);
    }

    /** Toggle user active/disabled. Cannot self-disable. */
    public function toggle(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot disable your own account.');
        }

        if ($user->isSuperAdmin() && ! $request->user()->isSuperAdmin()) {
            return back()->with('error', 'Only Super Admin can disable another Super Admin account.');
        }

        if ($user->is_active) {
            $request->validate([
                'disable_reason' => ['required','string','min:10','max:500'],
            ]);
            $user->disableRecord($request->user()->id, $request->input('disable_reason'));
            return back()->with('success', "{$user->name} account disabled.");
        }

        $user->enableRecord($request->user()->id);
        return back()->with('success', "{$user->name} account re-enabled.");
    }
}
