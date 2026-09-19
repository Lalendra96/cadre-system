<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'incident_report_id',
        'event_type',
        'from_status',
        'to_status',
        'comment',
        'metadata',
        'actor_id',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function incidentReport()
    {
        return $this->belongsTo(
            IncidentReport::class
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
