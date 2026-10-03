<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\EncryptsPersonnelData;
use Illuminate\Database\Eloquent\Model;

class Intern extends Model
{
    use EncryptsPersonnelData;

    protected array $encryptedPii = [
        'name' => 'name',
        'nic_number' => 'nic',
        'mobile_number' => 'phone',
    ];

    protected array $searchablePii = ['name', 'nic_number', 'mobile_number'];

    protected $fillable = [
        'intern_batch_id',
        'name',
        'nic_number',
        'mobile_number',
        'is_active',
        'disabled_by',
        'disabled_at',
        'disable_reason',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'disabled_at' => 'datetime',
    ];

    public function batch()
    {
        return $this->belongsTo(InternBatch::class, 'intern_batch_id');
    }

    public function assignments()
    {
        return $this->hasMany(InternAssignment::class);
    }

    public function rhoPlacement()
    {
        return $this->hasOne(InternRhoPlacement::class);
    }

    public function firstAppointment()
    {
        return $this->assignments()->where('appointment_number', 1)->first();
    }

    public function secondAppointment()
    {
        return $this->assignments()->where('appointment_number', 2)->first();
    }
}
