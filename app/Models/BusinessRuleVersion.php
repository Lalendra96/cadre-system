<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessRuleVersion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'business_rule_id',
        'version_no',
        'change_type',
        'snapshot',
        'changed_by',
        'change_reason',
        'created_at',
    ];

    protected $casts = [
        'snapshot' => 'array',
        'created_at' => 'datetime',
    ];

    public function businessRule()
    {
        return $this->belongsTo(
            BusinessRule::class
        );
    }

    public function changedBy()
    {
        return $this->belongsTo(
            User::class,
            'changed_by'
        );
    }
}
