<?php

declare(strict_types=1);

$count = 0;
$assert = static function (bool $value, string $message) use (&$count): void {
    if (! $value) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
    $count++;
};

// Offline OTP determinism: the same secret/time always yields the same code.
$secret = bin2hex(str_repeat('A', 20));
$counter = intdiv(1700000000, 30);
$key = hex2bin($secret);
$hash = hash_hmac('sha1', pack('N*', 0).pack('N*', $counter), $key, true);
$offset = ord($hash[19]) & 0xF;
$otp =
    ((ord($hash[$offset]) & 0x7F) << 24) |
    ((ord($hash[$offset + 1]) & 0xFF) << 16) |
    ((ord($hash[$offset + 2]) & 0xFF) << 8) |
    (ord($hash[$offset + 3]) & 0xFF);
$assert(strlen(str_pad((string) ($otp % 1000000), 6, '0', STR_PAD_LEFT)) === 6, 'offline OTP produces six digits');
$root = dirname(__DIR__);
$read = static fn (string $file): string => (string) file_get_contents($root.'/'.$file);
$assert(
    str_contains($read('app/Http/Controllers/Auth/LoginController.php'), 'mfa_pending_user'),
    'MFA challenge is required before an enabled user reaches the dashboard',
);
$assert(
    str_contains($read('app/Http/Controllers/EmployeeSelfServiceController.php'), '->employee()'),
    'employee self-service is scoped through the user employee link',
);
$assert(
    str_contains(
        $read('database/migrations/2026_09_18_000099_create_training_and_mfa_tables.php'),
        'employee_training_records',
    ),
    'training records retain expiry and certificate references',
);
$assert(
    file_exists($root.'/app/Http/Controllers/EmployeeCompetencyController.php'),
    'competencies support employee assessments',
);
$assert(
    str_contains($read('app/Http/Controllers/WorkforceHealthController.php'), 'failed_jobs'),
    'health dashboard checks failed jobs',
);
$assert(
    str_contains($read('app/Http/Controllers/EmployeeChangeRequestController.php'), "status !== 'pending'"),
    'maker-checker requests cannot be reviewed twice',
);
$assert(
    str_contains($read('routes/web.php'), 'employee-competencies.index'),
    'critical training routes are registered',
);
echo "v15 regression checks passed: {$count}\n";
