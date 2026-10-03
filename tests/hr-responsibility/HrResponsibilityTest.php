<?php

declare(strict_types=1);

use App\Http\Controllers\HrResponsibilityController;
use App\Models\Employee;
use App\Models\HrReassignmentCase;
use App\Models\HrResponsibility;
use App\Models\User;
use App\Services\HrIntelligenceService;
use App\Services\HrReminderService;
use App\Services\HrResponsibilityService;
use App\Services\WorkforceScopeService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Orchestra\Testbench\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class HrResponsibilityTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        $app['config']->set('auth.providers.users.model', User::class);
    }

    protected function defineRoutes($router): void
    {
        Route::get('/hr-intelligence', fn () => 'HR intelligence')->name('hr-intelligence.index');
        Route::get('/hr-responsibilities', fn () => 'Responsibilities')->name('hr-responsibilities.index');
        Route::get('/employees/{employee}', fn () => 'Employee')->name('employees.show');
        Route::get('/employees/{employee}/increments', fn () => 'Increments')->name('employee-increments.index');
        Route::middleware(['web', 'auth'])->group(function () {
            Route::post('/hr-responsibilities', [HrResponsibilityController::class, 'store']);
            Route::post('/hr-handovers/{handover}/accept', [HrResponsibilityController::class, 'accept']);
        });
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-09-17 12:00:00'));
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('role');
            $table->timestamps();
        });
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('subject_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code');
        });
        Schema::create('subject_code_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('subject_code_id');
        });
        Schema::create('position_subject_code', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('position_id');
            $table->unsignedBigInteger('subject_code_id');
        });
        Schema::create('acting_subject_officers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('subject_code_id');
            $table->boolean('is_active')->default(true);
            $table->date('start_date');
            $table->date('end_date');
        });
        Schema::create('hr_position_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('position_id');
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();
        });
        Schema::create('nav_items', function (Blueprint $table) {
            $table->id();
            $table->string('section');
            $table->string('label');
            $table->string('route_name');
            $table->json('allowed_roles');
            $table->json('custom_checks')->nullable();
            $table->integer('sort_order');
            $table->boolean('is_active');
            $table->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action');
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->text('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamp('created_at');
        });
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('type');
            $table->string('title');
            $table->text('body')->nullable();
            $table->text('link_url')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('position_id')->nullable();
            $table->unsignedBigInteger('subject_code_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->string('name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->unsignedInteger('retirement_age')->nullable();
            $table->softDeletes();
        });
        DB::table('positions')->insert([['id' => 1, 'title' => 'Nursing Officer'], ['id' => 2, 'title' => 'Clerk']]);
        $migration = require __DIR__.
            '/../../database/migrations/2026_09_17_000093_create_hr_responsibility_history.php';
        $migration->up();
        Schema::create('user_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::create('data_quality_issues', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->string('status');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->timestamps();
        });
        Schema::create('employee_increments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->boolean('is_active')->default(true);
            $table->date('increment_date')->nullable();
            $table->date('granted_date')->nullable();
            $table->string('workflow_status')->nullable();
            $table->timestamps();
        });
        Schema::create('retirement_projects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->string('status');
            $table->date('completed_at')->nullable();
            $table->timestamps();
        });
        $intelligenceMigration = require __DIR__.
            '/../../database/migrations/2026_09_18_000094_create_hr_intelligence_tables.php';
        $intelligenceMigration->up();
    }

    private function officer(string $role = 'subject_officer'): User
    {
        $number = DB::table('users')->count() + 1;
        $user = User::create([
            'name' => 'Officer '.$number,
            'email' => 'officer'.$number.'@example.test',
            'is_active' => true,
        ]);
        DB::table('user_roles')->insert(['user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    private function assign(User $officer, array $overrides = []): HrResponsibility
    {
        return HrResponsibilityService::assign(
            $this->officer('super_admin'),
            array_merge(
                [
                    'user_id' => $officer->id,
                    'position_id' => 1,
                    'kind' => 'permanent',
                    'starts_on' => today()->toDateString(),
                    'notes' => 'Test responsibility',
                ],
                $overrides,
            ),
        );
    }

    private function legacy(User $officer): void
    {
        DB::table('subject_codes')->insertOrIgnore(['id' => 1, 'code' => 'HR']);
        DB::table('subject_code_user')->insert(['user_id' => $officer->id, 'subject_code_id' => 1]);
        DB::table('position_subject_code')->insert(['position_id' => 2, 'subject_code_id' => 1]);
    }

    public function test_explicit_positions_replace_legacy_and_route_notifications_identically(): void
    {
        $officer = $this->officer();
        $this->legacy($officer);
        $this->assertEquals([2], $officer->effectiveHrPositionIds()->all());
        $this->assign($officer);
        $officer->refresh();
        $this->assertEquals([1], $officer->effectiveHrPositionIds()->all());
        $map = HrResponsibilityService::recipientMap();
        $this->assertTrue($map->get(1)->contains('id', $officer->id));
        $this->assertFalse($map->has(2));
    }

    public function test_temporary_scope_is_inclusive_and_never_restores_legacy_after_expiry(): void
    {
        $officer = $this->officer();
        $this->legacy($officer);
        $this->assign($officer, ['kind' => 'temporary', 'starts_on' => '2026-09-18', 'ends_on' => '2026-09-19']);
        $officer->refresh();
        $this->assertCount(0, $officer->effectiveHrPositionIds());
        $this->travelTo(Carbon::parse('2026-09-18 00:00:00'));
        $this->assertEquals([1], $officer->effectiveHrPositionIds()->all());
        $this->travelTo(Carbon::parse('2026-09-19 23:59:59'));
        $this->assertEquals([1], $officer->effectiveHrPositionIds()->all());
        $this->travelTo(Carbon::parse('2026-09-20 00:00:00'));
        $this->assertCount(0, $officer->effectiveHrPositionIds());
        $this->assertSame(1, HrResponsibility::count());
    }

    public function test_ending_retains_history_and_does_not_restore_legacy(): void
    {
        $officer = $this->officer();
        $this->legacy($officer);
        $assignment = $this->assign($officer);
        HrResponsibilityService::end($this->officer('super_admin'), $assignment, 'Officer transferred');
        $this->assertCount(0, $officer->refresh()->effectiveHrPositionIds());
        $this->assertSame('Officer transferred', $assignment->refresh()->end_reason);
        $this->assertSame(1, HrResponsibility::count());
        $this->assertGreaterThan(0, DB::table('audit_logs')->where('action', 'updated')->count());
    }

    public function test_disabled_officers_and_positions_receive_no_scope_or_reminders(): void
    {
        $officer = $this->officer();
        $this->assign($officer);
        $officer->update(['is_active' => false]);
        $this->assertCount(0, $officer->refresh()->effectiveHrPositionIds());
        $this->assertCount(0, HrResponsibilityService::recipientMap());
        $officer->update(['is_active' => true]);
        DB::table('positions')
            ->where('id', 1)
            ->update(['is_active' => false]);
        $this->assertCount(0, $officer->effectiveHrPositionIds());
    }

    public function test_employee_queries_deny_cross_position_and_unknown_roles(): void
    {
        $officer = $this->officer();
        $this->assign($officer);
        DB::table('employees')->insert([['position_id' => 1], ['position_id' => 2]]);
        $this->assertSame(1, WorkforceScopeService::employeeQuery($officer->refresh())->count());
        $this->assertSame(0, WorkforceScopeService::employeeQuery($this->officer('unknown'))->count());
        $this->assertSame(2, WorkforceScopeService::employeeQuery($this->officer('planning_officer'))->count());
    }

    public function test_only_the_named_recipient_can_accept_and_future_dates_are_honoured(): void
    {
        $admin = $this->officer('super_admin');
        $source = $this->officer();
        $recipient = $this->officer();
        $assignment = $this->assign($source);
        $handover = HrResponsibilityService::requestHandover($admin, $assignment, [
            'to_user_id' => $recipient->id,
            'effective_on' => '2026-09-19',
            'notes' => 'Transfer of duties',
            'outstanding_actions' => 'Review pending increments',
        ]);
        $this->actingAs($admin)
            ->post('/hr-handovers/'.$handover->id.'/accept')
            ->assertForbidden();
        $this->assertSame('pending', $handover->fresh()->status);
        HrResponsibilityService::accept($recipient, $handover);
        $this->assertEquals([1], $source->refresh()->effectiveHrPositionIds()->all());
        $this->assertCount(0, $recipient->refresh()->effectiveHrPositionIds());
        $this->travelTo(Carbon::parse('2026-09-19 00:00:00'));
        $this->assertCount(0, $source->effectiveHrPositionIds());
        $this->assertEquals([1], $recipient->effectiveHrPositionIds()->all());
        HrResponsibilityService::accept($recipient, $handover->fresh());
        $this->assertSame(2, HrResponsibility::count());
    }

    public function test_same_day_handover_ends_source_without_invalid_date_range(): void
    {
        $source = $this->officer();
        $recipient = $this->officer();
        $assignment = $this->assign($source);
        $handover = HrResponsibilityService::requestHandover($this->officer('super_admin'), $assignment, [
            'to_user_id' => $recipient->id,
            'effective_on' => today()->toDateString(),
            'notes' => 'Immediate transfer',
            'outstanding_actions' => 'None',
        ]);
        HrResponsibilityService::accept($recipient, $handover);
        $this->assertNotNull($assignment->refresh()->ended_at);
        $this->assertNull($assignment->ends_on);
        $this->assertCount(0, $source->refresh()->effectiveHrPositionIds());
        $this->assertEquals([1], $recipient->refresh()->effectiveHrPositionIds()->all());
    }

    public function test_overlapping_assignments_are_rejected(): void
    {
        $officer = $this->officer();
        $this->assign($officer);
        $this->expectException(ValidationException::class);
        $this->assign($officer, ['kind' => 'temporary', 'ends_on' => '2026-09-20']);
    }

    public function test_ordinary_officers_cannot_assign_responsibility(): void
    {
        $officer = $this->officer();
        $this->actingAs($officer)
            ->post('/hr-responsibilities', [
                'position_id' => 1,
                'user_id' => $officer->id,
                'kind' => 'permanent',
                'starts_on' => today()->toDateString(),
                'notes' => 'Unauthorised',
            ])
            ->assertForbidden();
        $this->assertSame(0, HrResponsibility::count());
    }

    public function test_duplicate_reminders_are_suppressed_per_recipient_and_date(): void
    {
        $officer = $this->officer();
        $send = fn (User $recipient, string $key) => HrReminderService::deliver(
            $recipient,
            $key,
            'retirement_upcoming',
            'Due',
            'Reminder',
            '/employees/1',
        );
        $this->assertTrue($send($officer, 'retirement:1:2026-10-17:30'));
        $this->assertFalse($send($officer, 'retirement:1:2026-10-17:30'));
        $this->assertTrue($send($this->officer(), 'retirement:1:2026-10-17:30'));
        $this->assertTrue($send($officer, 'retirement:1:2026-10-18:30'));
        $this->assertSame(3, DB::table('notifications')->count());
        $this->assertSame(3, DB::table('hr_reminder_deliveries')->count());
    }

    public function test_missed_scheduler_uses_only_current_reminder_band(): void
    {
        $markers = HrReminderService::markers([90, 30, 60, 30, -1]);
        $this->assertSame([30, 60, 90], $markers);
        $this->assertSame(30, HrReminderService::markerFor(29, $markers));
        $this->assertSame(60, HrReminderService::markerFor(31, $markers));
        $this->assertNull(HrReminderService::markerFor(-1, $markers));
        $this->assertNull(HrReminderService::markerFor(91, $markers));
    }

    public function test_failed_notification_rolls_back_delivery_marker(): void
    {
        $officer = $this->officer();
        DB::statement(
            "CREATE TRIGGER reject_notifications BEFORE INSERT ON notifications BEGIN SELECT RAISE(ABORT, 'test failure'); END",
        );
        try {
            HrReminderService::deliver(
                $officer,
                'test:rollback',
                'increment_upcoming',
                'Due',
                'Reminder',
                '/employees/1',
            );
            $this->fail('Expected notification insert failure.');
        } catch (QueryException $exception) {
            $this->assertSame(0, DB::table('hr_reminder_deliveries')->count());
        }
        DB::statement('DROP TRIGGER reject_notifications');
        $this->assertTrue(
            HrReminderService::deliver(
                $officer,
                'test:rollback',
                'increment_upcoming',
                'Due',
                'Reminder',
                '/employees/1',
            ),
        );
    }

    public function test_cancelled_handover_never_changes_scope(): void
    {
        $source = $this->officer();
        $recipient = $this->officer();
        $admin = $this->officer('super_admin');
        $assignment = $this->assign($source);
        $handover = HrResponsibilityService::requestHandover($admin, $assignment, [
            'to_user_id' => $recipient->id,
            'effective_on' => '2026-09-18',
            'notes' => 'Transfer',
            'outstanding_actions' => 'None',
        ]);
        HrResponsibilityService::cancel($admin, $handover, 'Officer remains in post');
        $this->assertSame('cancelled', $handover->refresh()->status);
        $this->assertEquals([1], $source->refresh()->effectiveHrPositionIds()->all());
        $this->assertCount(0, $recipient->refresh()->effectiveHrPositionIds());
        $this->expectException(ValidationException::class);
        HrResponsibilityService::accept($recipient, $handover);
    }

    public function test_employee_hr_helper_denies_unlinked_and_other_position_records(): void
    {
        $officer = $this->officer();
        $this->assign($officer);
        $officer->refresh();
        $this->assertTrue($officer->canManageEmployeeHrRecord(new Employee(['position_id' => 1])));
        $this->assertFalse($officer->canManageEmployeeHrRecord(new Employee(['position_id' => 2])));
        $this->assertFalse($officer->canManageEmployeeHrRecord(null));
    }

    public function test_healthy_baseline_does_not_create_queue_and_repeated_observation_is_idempotent(): void
    {
        $officer = $this->officer();
        $this->assign($officer);
        $employee = Employee::create(['name' => 'Test Employee', 'position_id' => 1, 'is_active' => true]);
        $map = HrResponsibilityService::recipientMap();
        $this->assertTrue(HrIntelligenceService::observe($employee->id, $map));
        $this->assertFalse(HrIntelligenceService::observe($employee->id, $map));
        $this->assertSame(1, DB::table('hr_ownership_events')->count());
        $this->assertSame(0, HrReassignmentCase::count());
    }

    private function ownershipChange(): array
    {
        $outgoing = $this->officer();
        $incoming = $this->officer();
        $assignment = $this->assign($outgoing);
        $employee = Employee::create(['name' => 'Test Employee', 'position_id' => 1, 'is_active' => true]);
        HrIntelligenceService::observe($employee->id, HrResponsibilityService::recipientMap());
        HrResponsibilityService::end($this->officer('super_admin'), $assignment, 'Transfer');
        $this->assign($incoming);
        HrIntelligenceService::observe($employee->id, HrResponsibilityService::recipientMap());

        return [$employee, $outgoing, $incoming, HrReassignmentCase::firstOrFail()];
    }

    public function test_acceptance_moves_open_work_and_preserves_completed_work(): void
    {
        [$employee, $outgoing, $incoming, $case] = $this->ownershipChange();
        foreach (['assigned', 'verified'] as $status) {
            DB::table('data_quality_issues')->insert([
                'employee_id' => $employee->id,
                'status' => $status,
                'assigned_to' => $outgoing->id,
            ]);
        }
        foreach (['due', 'granted'] as $status) {
            DB::table('employee_increments')->insert([
                'employee_id' => $employee->id,
                'workflow_status' => $status,
                'responsible_user_id' => $outgoing->id,
            ]);
        }
        foreach (['documentation', 'completed'] as $status) {
            DB::table('retirement_projects')->insert([
                'employee_id' => $employee->id,
                'status' => $status,
                'responsible_user_id' => $outgoing->id,
            ]);
        }
        HrIntelligenceService::accept($incoming, $case, 'Reviewed the pending work');
        $this->assertSame('accepted', $case->refresh()->status);
        $this->assertEquals(
            $incoming->id,
            DB::table('data_quality_issues')->where('status', 'assigned')->value('assigned_to'),
        );
        $this->assertEquals(
            $outgoing->id,
            DB::table('data_quality_issues')->where('status', 'verified')->value('assigned_to'),
        );
        $this->assertEquals(
            $incoming->id,
            DB::table('employee_increments')->where('workflow_status', 'due')->value('responsible_user_id'),
        );
        $this->assertEquals(
            $outgoing->id,
            DB::table('employee_increments')->where('workflow_status', 'granted')->value('responsible_user_id'),
        );
        $this->assertEquals(
            $incoming->id,
            DB::table('retirement_projects')->where('status', 'documentation')->value('responsible_user_id'),
        );
        $this->assertEquals(
            $outgoing->id,
            DB::table('retirement_projects')->where('status', 'completed')->value('responsible_user_id'),
        );
        $firstAcceptance = $case->accepted_at;
        HrIntelligenceService::accept($incoming, $case, 'Retry');
        $this->assertEquals($firstAcceptance, $case->refresh()->accepted_at);
    }

    public function test_stale_employee_position_prevents_acceptance(): void
    {
        [$employee, $outgoing, $incoming, $case] = $this->ownershipChange();
        $employee->update(['position_id' => 2]);
        try {
            HrIntelligenceService::accept($incoming, $case, 'Stale request');
            $this->fail('Expected access denial.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $this->assertSame('awaiting_acceptance', $case->refresh()->status);
        HrIntelligenceService::observe($employee->id, HrResponsibilityService::recipientMap());
        $this->assertSame('superseded', $case->refresh()->status);
        $this->assertSame('unassigned', HrReassignmentCase::latest('id')->first()->status);
    }

    public function test_ordinary_officer_cannot_propose_case_owner(): void
    {
        [$employee, $outgoing, $incoming, $case] = $this->ownershipChange();
        try {
            HrIntelligenceService::propose($incoming, $case, $incoming->id, 'Self approval');
            $this->fail('Expected access denial.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_repeated_job_does_not_duplicate_alert_notifications_or_cases(): void
    {
        $this->officer('super_admin');
        Employee::create(['name' => 'Unassigned Employee', 'position_id' => 1, 'is_active' => true]);
        HrIntelligenceService::synchronize();
        $notifications = DB::table('notifications')->count();
        $this->assertGreaterThan(0, $notifications);
        HrIntelligenceService::synchronize();
        $this->assertSame($notifications, DB::table('notifications')->count());
        $this->assertSame(1, HrReassignmentCase::count());
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }
}
