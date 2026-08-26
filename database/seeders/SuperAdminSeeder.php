<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Creates the initial Super Admin account so you can log in and configure
 * subject codes, categories, positions, and other users from the UI.
 *
 * IMPORTANT: change this password immediately after first login.
 * Run with: php artisan db:seed --class=SuperAdminSeeder
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@hims.local'],
            [
                'name'      => 'System Administrator',
                'password'  => Hash::make('ChangeMe@123'),
                'is_active' => true,
            ]
        );

        UserRole::firstOrCreate([
            'user_id' => $user->id,
            'role'    => User::ROLE_SUPER_ADMIN,
        ]);
    }
}
