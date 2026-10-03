<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Seeds the dedicated Client Version & Feature Manager account.
 *
 * Governance boundary:
 * - receives only the client_feature_manager role;
 * - does not receive operational HR, payroll, PII or Super Admin roles;
 * - employee/letter/circular/intern-management permissions are disabled;
 * - the initial password must be changed on first login.
 *
 * Optional environment variables:
 *   FEATURE_MANAGER_SEED_NAME="Client Feature Manager"
 *   FEATURE_MANAGER_SEED_EMAIL="feature.manager@hims.local"
 *   FEATURE_MANAGER_SEED_PASSWORD="<strong temporary password>"
 *
 * In production, FEATURE_MANAGER_SEED_PASSWORD must be supplied explicitly.
 * The development fallback exists only for local/test installations.
 *
 * Usage:
 *   php artisan db:seed --class=ClientFeatureManagerSeeder
 */
class ClientFeatureManagerSeeder extends Seeder
{
    public function run(): void
    {
        $name = (string) env('FEATURE_MANAGER_SEED_NAME', 'Client Feature Manager');
        $email = strtolower(trim((string) env('FEATURE_MANAGER_SEED_EMAIL', 'feature.manager@hims.local')));
        $configuredPassword = env('FEATURE_MANAGER_SEED_PASSWORD');

        if (app()->environment('production') && blank($configuredPassword)) {
            throw new RuntimeException(
                'FEATURE_MANAGER_SEED_PASSWORD must be configured before running ClientFeatureManagerSeeder in production.'
            );
        }

        $temporaryPassword = (string) ($configuredPassword ?: 'ChangeMe@123');

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($temporaryPassword),
                'is_active' => true,
                'can_view_employees' => false,
                'can_view_letters' => false,
                'can_manage_circular_groups' => false,
                'can_manage_intern_assignments' => false,
                'hr_scope_configured' => false,
                'force_password_change' => true,
            ]
        );

        // Reassert the least-privilege boundary without changing an existing
        // account's password when the seeder is safely re-run.
        $user->forceFill([
            'is_active' => true,
            'can_view_employees' => false,
            'can_view_letters' => false,
            'can_manage_circular_groups' => false,
            'can_manage_intern_assignments' => false,
            'hr_scope_configured' => false,
        ])->save();

        if ($user->wasRecentlyCreated) {
            $user->forceFill(['force_password_change' => true])->save();
        }

        UserRole::firstOrCreate([
            'user_id' => $user->id,
            'role' => User::ROLE_CLIENT_FEATURE_MANAGER,
        ]);

        // Do not silently grant any operational role if the same account was
        // previously used for another purpose. Surface the condition instead.
        $unexpectedRoles = $user->userRoles()
            ->where('role', '!=', User::ROLE_CLIENT_FEATURE_MANAGER)
            ->pluck('role')
            ->all();

        if ($unexpectedRoles !== []) {
            $this->command?->warn(
                'Feature Manager user also has additional role(s): '.implode(', ', $unexpectedRoles).
                '. Review and remove them unless explicitly authorized.'
            );
        }

        $this->command?->info('Client Version & Feature Manager seeded successfully.');
        $this->command?->line('  Email: '.$email);

        if ($user->wasRecentlyCreated && ! app()->environment('production')) {
            $this->command?->warn('  Temporary development password: '.$temporaryPassword);
            $this->command?->warn('  Password change is required at first login.');
        } else {
            $this->command?->line('  Existing password was not changed by this seeder.');
        }
    }
}
