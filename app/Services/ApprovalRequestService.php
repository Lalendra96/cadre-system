<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ApprovalRequestService
{
    public static function countFor(User $user): int
    {
        return self::forUser($user)->sum('count');
    }

    /**
     * Consolidated approval queue. Every source is guarded by its actual role rule.
     */
    public static function forUser(User $user): Collection
    {
        $items = collect();

        if (Schema::hasTable('service_letters') && ($user->isSuperAdmin() || $user->isAdministrativeOfficer())) {
            $count = DB::table('service_letters')->where('status', 'pending_approval')->count();
            if ($count > 0) {
                $items->push([
                    'key' => 'service_letters',
                    'label' => 'Service Letters',
                    'description' => 'Official service letters waiting for Administrative Officer / Hospital Secretary approval.',
                    'count' => $count,
                    'route' => route('service-letters.index', ['status' => 'pending_approval']),
                    'priority' => 10,
                ]);
            }
        }

        if (Schema::hasTable('cadre_review_proposals') && ($user->isSuperAdmin() || $user->isDirector())) {
            $count = DB::table('cadre_review_proposals')
                ->whereIn('status', ['submitted', 'director_approved', 'moh_submitted'])
                ->count();
            if ($count > 0) {
                $items->push([
                    'key' => 'cadre_reviews',
                    'label' => 'Cadre Review Proposals',
                    'description' => 'Submitted cadre review proposals requiring Director-stage action or recording of the external decision.',
                    'count' => $count,
                    'route' => route('cadre-reviews.index'),
                    'priority' => 20,
                ]);
            }
        }

        if (Schema::hasTable('car_pass_requests')) {
            $access = app(CarPassAccessService::class);
            if ($access->canApprove($user)) {
                $count = DB::table('car_pass_requests')->where('status', 'pending')->count();
                if ($count > 0) {
                    $items->push([
                        'key' => 'car_passes',
                        'label' => 'Car Pass Requests',
                        'description' => 'Independently prepared Car Pass requests waiting for approval.',
                        'count' => $count,
                        'route' => route('car-passes.index', ['status' => 'pending']),
                        'priority' => 30,
                    ]);
                }
            }
        }

        return $items->sortBy('priority')->values();
    }
}
