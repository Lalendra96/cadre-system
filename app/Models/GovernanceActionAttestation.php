<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GovernanceActionAttestation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'evidence_reviewed' => 'boolean',
        'accuracy_confirmed' => 'boolean',
        'minimum_necessary_confirmed' => 'boolean',
        'no_conflict_confirmed' => 'boolean',
        'attested_at' => 'datetime',
    ];
}
