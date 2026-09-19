<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdministrativeDecisionEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'administrative_decision_id',
        'event',
        'actor_id',
        'reason',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function decision()
    {
        return $this->belongsTo(
            AdministrativeDecision::class,
            'administrative_decision_id'
        );
    }

    public function actor()
    {
        return $this->belongsTo(
            User::class,
            'actor_id'
        );
    }
}
