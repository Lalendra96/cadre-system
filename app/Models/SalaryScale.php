<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasDisableWorkflow;
use Illuminate\Database\Eloquent\Model;

class SalaryScale extends Model
{
    use HasDisableWorkflow;

    protected $fillable = [
        'code', 'name', 'min_salary', 'max_salary', 'increment_amount', 'notes',
        'is_active', 'disabled_by', 'disabled_at', 'disable_reason',
    ];

    protected $casts = [
        'min_salary'        => 'decimal:2',
        'max_salary'        => 'decimal:2',
        'increment_amount'  => 'decimal:2',
        'is_active'         => 'boolean',
        'disabled_at'       => 'datetime',
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class, 'salary_scale_id');
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->whereRaw('code ILIKE ?', ["%{$term}%"])
              ->orWhereRaw('name ILIKE ?', ["%{$term}%"]);
        });
    }
}
