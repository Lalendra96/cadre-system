<?php

declare(strict_types=1);

require __DIR__.'/../../app/Services/HrOwnershipPolicy.php';

use App\Services\HrOwnershipPolicy as Policy;

$single = Policy::state(1, true, [7]);
$shared = Policy::state(1, true, [7, 8]);
$empty = Policy::state(1, true, []);
$inactive = Policy::state(1, false, [7]);
$cases = [
    ['normalize order', [7, 8], Policy::state(1, true, [8, 7])['owner_ids']],
    ['deduplicate owners', [7, 8], Policy::state(1, true, [7, 8, 7])['owner_ids']],
    ['integer IDs', [7], Policy::state(1, true, ['7'])['owner_ids']],
    ['discard invalid IDs', [7], Policy::state(1, true, [0, -1, 7])['owner_ids']],
    ['inactive clears owners', [], $inactive['owner_ids']],
    ['no position clears owners', [], Policy::state(null, true, [7])['owner_ids']],
    ['invalid position is absent', null, Policy::state(0, true, [7])['position_id']],
    ['single owner status', 'awaiting_acceptance', Policy::status($single)],
    ['shared owner status', 'needs_review', Policy::status($shared)],
    ['no owner status', 'unassigned', Policy::status($empty)],
    ['inactive status', 'inactive', Policy::status($inactive)],
    ['healthy baseline no queue', false, Policy::needsCase(null, $single)],
    ['orphan baseline queue', true, Policy::needsCase(null, $empty)],
    ['shared baseline queue', true, Policy::needsCase(null, $shared)],
    ['inactive baseline no queue', false, Policy::needsCase(null, $inactive)],
    ['repeat no queue', false, Policy::needsCase($single, $single)],
    ['owner change queue', true, Policy::needsCase($single, Policy::state(1, true, [8]))],
    ['position change queue', true, Policy::needsCase($single, Policy::state(2, true, [7]))],
    ['expiry queue', true, Policy::needsCase($single, $empty)],
    ['inactive employee no queue', false, Policy::needsCase($single, $inactive)],
    ['reactivation queue', true, Policy::needsCase($inactive, $single)],
    [
        'owner order fingerprint stable',
        Policy::fingerprint($shared),
        Policy::fingerprint(Policy::state(1, true, [8, 7])),
    ],
    [
        'fingerprint detects position',
        false,
        hash_equals(Policy::fingerprint($single), Policy::fingerprint(Policy::state(2, true, [7]))),
    ],
    ['fingerprint detects activity', false, hash_equals(Policy::fingerprint($single), Policy::fingerprint($inactive))],
];

foreach ($cases as [$label, $expected, $actual]) {
    if ($expected !== $actual) {
        throw new RuntimeException('Failed: '.$label);
    }
}

echo count($cases)." HR ownership policy checks passed.\n";
