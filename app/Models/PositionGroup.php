<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasDisableWorkflow;
use Illuminate\Database\Eloquent\Model;

class PositionGroup extends Model
{
    use HasDisableWorkflow;

    protected $fillable = [
        'name', 'description', 'created_by',
        'is_active', 'disabled_by', 'disabled_at', 'disable_reason',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'disabled_at' => 'datetime',
    ];

    public function positions()
    {
        return $this->belongsToMany(Position::class, 'position_group_position');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
