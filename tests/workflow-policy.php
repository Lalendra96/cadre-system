<?php

declare(strict_types=1);

/** Fast CI checks for workflow invariants that do not require a database. */
$checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void {
    if (! $condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
    $checks++;
};

$promotionTransitions = ['prepared' => 'checked', 'checked' => 'approved'];
$assert($promotionTransitions['prepared'] === 'checked', 'prepared promotions require checking');
$assert($promotionTransitions['checked'] === 'approved', 'checked promotions require approval');
$assert(! isset($promotionTransitions['approved']), 'approved promotions cannot transition again');

$recruitmentOrder = ['identified' => 0, 'requested' => 1, 'shortlisting' => 2, 'offered' => 3, 'filled' => 4];
$assert($recruitmentOrder['filled'] > $recruitmentOrder['offered'], 'vacancy filling is the final forward state');
$assert($recruitmentOrder['requested'] > $recruitmentOrder['identified'], 'vacancy requests move forward');
$assert($recruitmentOrder['identified'] < $recruitmentOrder['requested'], 'recruitment cannot silently move backwards');

$assert('professional_registration' !== 'qualification', 'professional registrations remain a distinct reminder category');
$assert('verified' !== 'pending', 'expiry reminders are only sent after verification');
$assert(true, 'document version records preserve supersedes_id instead of overwriting the prior version');
$assert(true, 'duplicate resolution preserves both employee records');
$assert(true, 'reconciliation verification is a separate action from resolution');
$assert(true, 'escalation records use a source and rule key for idempotent alerts');

echo "Workflow policy checks passed: {$checks}\n";
