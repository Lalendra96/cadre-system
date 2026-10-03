<?php

declare(strict_types=1);

use App\Models\NavItem;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $createdBy = User::havingRole(User::ROLE_SUPER_ADMIN)->value('id')
            ?? User::query()->value('id');

        NavItem::query()->updateOrCreate(
            [
                'section' => null,
                'route_name' => 'dashboard',
            ],
            [
                'label' => '📊 Dashboard',
                'open_in_new_tab' => false,
                'allowed_roles' => [
                    User::ROLE_SUPER_ADMIN,
                    User::ROLE_ADMIN_GROUP,
                    User::ROLE_PLANNING_OFFICER,
                    User::ROLE_UNIT_MANAGER,
                    User::ROLE_SUBJECT_OFFICER,
                ],
                'custom_checks' => null,
                'sort_order' => 1,
                'is_active' => true,
                'created_by' => $createdBy,
            ]
        );
    }

    public function down(): void
    {
        NavItem::query()
            ->whereNull('section')
            ->where('route_name', 'dashboard')
            ->delete();
    }
};
