<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employee_hr_allocations')) {
            Schema::create(
                'employee_hr_allocations',
                function (Blueprint $table): void {
                    $table->id();

                    $table->foreignId('employee_id')
                        ->constrained('employees');

                    $table->foreignId('user_id')
                        ->constrained('users');

                    $table->foreignId('position_id')
                        ->constrained('positions');

                    $table->date('starts_on');
                    $table->date('ends_on')->nullable();

                    $table->foreignId('assigned_by')
                        ->nullable()
                        ->constrained('users');

                    $table->text('reason')->nullable();

                    $table->timestamp('ended_at')->nullable();

                    $table->foreignId('ended_by')
                        ->nullable()
                        ->constrained('users');

                    $table->text('end_reason')->nullable();

                    $table->timestamps();

                    $table->index(
                        [
                            'user_id',
                            'ended_at',
                            'starts_on',
                            'ends_on',
                        ],
                        'employee_hr_alloc_user_effective_idx'
                    );

                    $table->index(
                        [
                            'employee_id',
                            'ended_at',
                        ],
                        'employee_hr_alloc_employee_active_idx'
                    );

                    $table->index(
                        [
                            'position_id',
                            'ended_at',
                        ],
                        'employee_hr_alloc_position_active_idx'
                    );
                }
            );
        }

        if (
            Schema::hasTable('hr_responsibilities')
            && Schema::hasTable('user_roles')
        ) {
            $today = now()->toDateString();

            $explicitOwners = DB::table(
                'hr_responsibilities as r'
            )
                ->join(
                    'users as u',
                    'u.id',
                    '=',
                    'r.user_id'
                )
                ->join(
                    'user_roles as ur',
                    function ($join): void {
                        $join->on(
                            'ur.user_id',
                            '=',
                            'u.id'
                        )->where(
                            'ur.role',
                            '=',
                            'subject_officer'
                        );
                    }
                )
                ->where('u.is_active', true)
                ->whereNull('r.ended_at')
                ->whereDate(
                    'r.starts_on',
                    '<=',
                    $today
                )
                ->where(
                    fn ($query) => $query
                        ->whereNull('r.ends_on')
                        ->orWhereDate(
                            'r.ends_on',
                            '>=',
                            $today
                        )
                )
                ->select(
                    'r.position_id',
                    'r.user_id'
                )
                ->distinct()
                ->get();

            $legacyOwners = DB::table(
                'subject_code_user as scu'
            )
                ->join(
                    'users as u',
                    'u.id',
                    '=',
                    'scu.user_id'
                )
                ->join(
                    'user_roles as ur',
                    function ($join): void {
                        $join->on(
                            'ur.user_id',
                            '=',
                            'u.id'
                        )->where(
                            'ur.role',
                            '=',
                            'subject_officer'
                        );
                    }
                )
                ->join(
                    'position_subject_code as psc',
                    'psc.subject_code_id',
                    '=',
                    'scu.subject_code_id'
                )
                ->join(
                    'positions as p',
                    'p.id',
                    '=',
                    'psc.position_id'
                )
                ->where('u.is_active', true)
                ->where(
                    'u.hr_scope_configured',
                    false
                )
                ->where('p.is_active', true)
                ->select(
                    'psc.position_id',
                    'scu.user_id'
                )
                ->distinct()
                ->get();

            $owners = $explicitOwners
                ->concat($legacyOwners)
                ->unique(
                    fn ($row) => $row->position_id
                        . ':'
                        . $row->user_id
                )
                ->groupBy('position_id');

            foreach (
                $owners as $positionId => $positionOwners
            ) {
                $userIds = $positionOwners
                    ->pluck('user_id')
                    ->unique()
                    ->values();

                if ($userIds->count() !== 1) {
                    continue;
                }

                $userId = (int) $userIds->first();

                DB::table('employees')
                    ->where('is_active', true)
                    ->where(
                        'position_id',
                        $positionId
                    )
                    ->orderBy('id')
                    ->pluck('id')
                    ->each(
                        function ($employeeId) use (
                            $userId,
                            $positionId,
                            $today
                        ): void {
                            $alreadyAllocated = DB::table(
                                'employee_hr_allocations'
                            )
                                ->where(
                                    'employee_id',
                                    $employeeId
                                )
                                ->whereNull('ended_at')
                                ->exists();

                            if ($alreadyAllocated) {
                                return;
                            }

                            DB::table(
                                'employee_hr_allocations'
                            )->insert([
                                'employee_id' => $employeeId,
                                'user_id' => $userId,
                                'position_id' => $positionId,
                                'starts_on' => $today,
                                'reason' => 'Automatic baseline: sole effective Subject Officer for position',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    );
            }
        }

        if (Schema::hasTable('nav_items')) {
            DB::table('nav_items')
                ->orderBy('id')
                ->get([
                    'id',
                    'label',
                ])
                ->each(
                    function ($row): void {
                        $clean = preg_replace(
                            '/^[\x{2600}-\x{27BF}\x{1F300}-\x{1FAFF}\x{2190}-\x{21FF}\x{2300}-\x{23FF}\x{25A0}-\x{25FF}\x{2700}-\x{27BF}\x{FE0F}\x{200D}\s]+/u',
                            '',
                            (string) $row->label
                        );

                        $clean = preg_replace(
                            '/\s*↗\s*$/u',
                            '',
                            (string) $clean
                        );

                        if (
                            $clean !== $row->label
                            && trim((string) $clean) !== ''
                        ) {
                            DB::table('nav_items')
                                ->where(
                                    'id',
                                    $row->id
                                )
                                ->update([
                                    'label' => trim($clean),
                                    'updated_at' => now(),
                                ]);
                        }
                    }
                );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'employee_hr_allocations'
        );
    }
};
