<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InternRhoPlacement extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PLACED = 'placed';

    public const STATUS_DEFERRED = 'deferred';

    public const STATUS_NOT_PLACED = 'not_placed';

    protected $fillable = [
        'intern_batch_id',
        'intern_id',
        'status',
        'placement_institution',
        'placement_unit',
        'effective_date',
        'reference_no',
        'notes',
        'recorded_by',
        'updated_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
    ];

    public function batch()
    {
        return $this->belongsTo(InternBatch::class, 'intern_batch_id');
    }

    public function intern()
    {
        return $this->belongsTo(Intern::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
