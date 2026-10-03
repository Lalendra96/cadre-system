<?php

declare(strict_types=1);

// Dependency-free checks: php tests/hr-responsibility/reminder-bands.php
require __DIR__.'/../../app/Services/HrReminderService.php';

use App\Services\HrReminderService;

$cases = [
    ['valid markers', [7, 30, 90], HrReminderService::markers([90, 30, 7, 30])],
    ['invalid markers ignored', [0, 30], HrReminderService::markers(['bad', null, -1, 4000, 30, 0])],
    ['numeric strings', [7, 30], HrReminderService::markers(['30', '7'])],
    ['empty configuration', [], HrReminderService::markers([])],
    ['boundary', 30, HrReminderService::markerFor(30, [7, 30, 90])],
    ['missed run', 30, HrReminderService::markerFor(29, [7, 30, 90])],
    ['outer band', 90, HrReminderService::markerFor(31, [7, 30, 90])],
    ['due today', 7, HrReminderService::markerFor(0, [7, 30, 90])],
    ['past due', null, HrReminderService::markerFor(-1, [7, 30, 90])],
    ['outside window', null, HrReminderService::markerFor(91, [7, 30, 90])],
];

foreach ($cases as [$label, $expected, $actual]) {
    if ($actual !== $expected) {
        throw new RuntimeException('Failed: '.$label);
    }
}

echo count($cases)." reminder-band checks passed.\n";
