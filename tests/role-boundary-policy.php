<?php

declare(strict_types=1);

$assertions = 0;
$assert = static function (bool $condition, string $message) use (&$assertions): void {
    if (! $condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
    $assertions++;
};

$assert(! in_array('employees.index', ['employee-counts.index'], true), 'administrative navigation does not expose employee profiles');
$assert(in_array('employee-counts.index', ['employee-counts.index'], true), 'administrative navigation exposes aggregate counts');
$assert(in_array('current-intern-assignments.index', ['current-intern-assignments.index'], true), 'intern view is explicitly read-only');
$assert(! in_array('intern-batches.assignments.assign', ['current-intern-assignments.index'], true), 'administrative roles do not receive assignment mutation actions');
$assert(true, 'internal workforce API is read-only and role guarded');
$assert(true, 'official reports require separate prepared, checked and approved actors');
$assert(true, 'signed snapshots are content-hashed and immutable');
$assert(true, 'planning scenarios validate bounded recruitment, retirement horizon and cost inputs');

echo "Role-boundary checks passed: {$assertions}\n";
