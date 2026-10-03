<?php

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeGradeRecord;
use App\Models\SystemSetting;
use App\Services\AdvancedGovernanceService;
use App\Services\AuditLogService;
use App\Services\CarderAiService;
use App\Services\ScreenHelpService;
use Illuminate\Config\Repository;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Http;

/** Standalone SQLite tests; no production database or external AI service is used. */
$root = dirname(__DIR__);
require getenv('CARDER_TEST_AUTOLOAD') ?: $root.'/vendor/autoload.php';
spl_autoload_register(function ($class) use ($root) {
    if (str_starts_with($class, 'App\\')) {
        $path = $root.'/app/'.str_replace('\\', '/', substr($class, 4)).'.php';
        if (is_file($path)) {
            require_once $path;
        }
    }
}, true, true);
$app = new Application($root);
Facade::setFacadeApplication($app);
$app->instance('config', new Repository(['section_help' => require $root.'/config/section_help.php']));
$app->instance('request', Request::create('/test', 'POST', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']));
$app->instance('auth', new class
{
    public function id()
    {
        return 7;
    }
});
$capsule = new Manager($app);
$capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
$capsule->setAsGlobal();
$capsule->bootEloquent();
$app->instance('db', $capsule->getDatabaseManager());
$app->instance('db.schema', $capsule->schema());
$capsule->schema()->create('audit_logs', function ($t) {
    $t->increments('id');
    $t->integer('user_id')->nullable();
    $t->string('action');
    $t->string('auditable_type');
    $t->integer('auditable_id')->nullable();
    $t->text('description')->nullable();
    $t->text('old_values')->nullable();
    $t->text('new_values')->nullable();
    $t->string('ip_address')->nullable();
    $t->timestamp('created_at')->nullable();
});
$capsule->schema()->create('system_settings', function ($t) {
    $t->string('key')->primary();
    $t->text('value');
    $t->string('type')->default('string');
});
class ExperienceTestEmployee extends Employee
{
    public function getCurrentGradeAttribute(): ?EmployeeGradeRecord
    {
        return null;
    }
}
$employee = new ExperienceTestEmployee(['name' => 'PRIVATE EMPLOYEE FACT']);
$employee->id = 77;
foreach (['position', 'unit', 'subjectCode', 'salaryScale'] as $relation) {
    $employee->setRelation($relation, null);
}
foreach (['gradeRecords', 'servicePeriods', 'documents'] as $relation) {
    $employee->setRelation($relation, new Collection);
}
$assertions = 0;
$assert = function ($ok, $message) use (&$assertions) {
    if (! $ok) {
        throw new RuntimeException($message);
    } $assertions++;
};
$settings = function ($endpoint) use ($capsule) {
    $capsule->table('system_settings')->delete();
    $capsule->table('system_settings')->insert(['key' => 'ai_local_endpoint', 'value' => $endpoint, 'type' => 'string']);
    $cache = new ReflectionProperty(SystemSetting::class, 'runtimeCache');
    $cache->setValue(null, []);
};
$fake = function ($response) {
    Http::swap(new Factory);
    Http::fake(['*' => $response]);
};
$ai = new CarderAiService;
$settings('');
$fake(Http::response([], 200));
$result = $ai->employeeSummary($employee);
$assert($result['mode'] === 'offline_rules', 'Offline summary mode');
$assert((bool) preg_match('/^[a-f0-9-]{36}$/', $result['audit_reference']), 'Reference provided');
$assert(AuditLog::pluck('action')->all() === ['ai_started', 'ai_fallback'], 'Start and fallback events');
Http::assertNothingSent();
$settings('http://127.0.0.1:11434');
$fake(Http::response(['choices' => [['message' => ['content' => 'PRIVATE GENERATED OUTPUT']]]], 200));
$result = $ai->draftServiceLetter($employee, null, 'Service confirmation', 'en', 'PRIVATE PROMPT');
$assert($result['mode'] === 'lan_ai' && $result['body'] === 'PRIVATE GENERATED OUTPUT', 'LAN result');
$last = AuditLog::orderByDesc('id')->first();
$assert($last->action === 'ai_completed' && $last->user_id === 7 && $last->auditable_id === 77, 'Actor and employee logged');
$assert($last->new_values['reference'] === $result['audit_reference'], 'Reference correlated');
$assert($last->new_values['duration_ms'] >= 0, 'Duration logged');
$fake(Http::response([], 503));
$result = $ai->draftServiceLetter($employee, null, 'Service confirmation', 'en');
$assert($result['mode'] === 'offline_template', 'HTTP failure fallback');
$assert(AuditLog::orderByDesc('id')->first()->new_values['provider_status'] === 'http_error', 'HTTP failure reason');
$fake(Http::response(['choices' => []], 200));
$ai->employeeSummary($employee);
$assert(AuditLog::orderByDesc('id')->first()->new_values['provider_status'] === 'empty_response', 'Empty response recorded');
$settings('https://8.8.8.8');
$fake(Http::response([], 200));
$ai->employeeSummary($employee);
Http::assertNothingSent();
$assert(AuditLog::orderByDesc('id')->first()->new_values['provider_status'] === 'endpoint_not_allowed', 'Disallowed endpoint recorded');
$badEmployee = new class extends ExperienceTestEmployee
{
    public function loadMissing($relations)
    {
        throw new RuntimeException('PRIVATE EXCEPTION');
    }
};
$badEmployee->id = 88;
try {
    $ai->employeeSummary($badEmployee);
    throw new LogicException('Expected failure');
} catch (RuntimeException $exception) {
    $assert($exception->getMessage() === 'PRIVATE EXCEPTION', 'Original failure rethrown');
}
$assert(AuditLog::orderByDesc('id')->first()->action === 'ai_failed', 'Failure logged');
$all = AuditLog::all()->toJson();
foreach (['PRIVATE EMPLOYEE FACT', 'PRIVATE GENERATED OUTPUT', 'PRIVATE PROMPT', 'PRIVATE EXCEPTION', '127.0.0.1:11434'] as $secret) {
    $assert(! str_contains($all, $secret), 'Sensitive content excluded: '.$secret);
}
AuditLogService::aiUsage($employee, 'ai_applied', ['reference' => 'test', 'capability' => 'letter_draft', 'prompt' => 'PRIVATE PROMPT']);
$assert(! array_key_exists('prompt', AuditLog::orderByDesc('id')->first()->new_values), 'Metadata allowlist');
$assert(ScreenHelpService::forRoute('advanced-governance.temporal')['key'] === 'temporal', 'Specific route before broad help');
$assert(ScreenHelpService::forRoute('audit-logs.index')['key'] === 'audit', 'Audit-specific help');
$assert(ScreenHelpService::forRoute('not-mapped.index') === null, 'No unrelated generic help');
$assert(ScreenHelpService::forRoute('service-letters.create')['key'] === 'letters', 'Letter-specific help');
$capsule->schema()->create('establishment_temporal_events', function ($t) {
    $t->increments('id');
    $t->date('effective_date');
    $t->string('event_type');
    $t->string('entity_type');
    $t->integer('entity_id');
    $t->text('after_state');
});
for ($day = 1; $day <= 15; $day++) {
    $capsule->table('establishment_temporal_events')->insert([
        'effective_date' => sprintf('2026-09-%02d', $day), 'event_type' => 'unit_change',
        'entity_type' => 'employee', 'entity_id' => 77, 'after_state' => json_encode(['unit_id' => $day]),
    ]);
}
$capsule->table('establishment_temporal_events')->insert([
    'effective_date' => '2026-09-30', 'event_type' => 'unit_change', 'entity_type' => 'employee',
    'entity_id' => 77, 'after_state' => json_encode(['unit_id' => 99]),
]);
$temporal = new AdvancedGovernanceService;
$snapshot = $temporal->temporalSnapshot(Carbon\Carbon::parse('2026-09-15'));
$assert($snapshot['event_count'] === 15, 'Future events excluded');
$assert(count($snapshot['recent_events']) === 12, 'Latest twelve display events');
$assert($snapshot['recent_events'][0]['effective_date'] === '2026-09-04', 'Display chronology preserved');
$assert($snapshot['entities']['employee:77']['unit_id'] === 15, 'Snapshot state unchanged by display limit');
$assert(! array_key_exists('after_state', $snapshot['recent_events'][0]), 'Timeline does not expose state payload');
$empty = $temporal->temporalSnapshot(Carbon\Carbon::parse('2026-08-01'));
$assert($empty['event_count'] === 0 && $empty['recent_events'] === [], 'Empty history is honest');
$capsule->schema()->drop('audit_logs');
$fake(Http::response([], 200));
try {
    $ai->employeeSummary($employee);
    throw new LogicException('Expected unavailable audit store');
} catch (QueryException) {
    $assert(true, 'Audit store failure blocks AI');
}
Http::assertNothingSent();
echo "PASS: {$assertions} AI audit/privacy, contextual-help and temporal assertions.\n";
