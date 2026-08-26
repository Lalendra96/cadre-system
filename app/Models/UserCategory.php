<?php

namespace App\Models;

use App\Traits\HasDisableWorkflow;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserCategory extends Model
{
    use HasDisableWorkflow, HasFactory;

    protected $table = 'user_categories';

    protected $fillable = [
        'name', 'description', 'is_active', 'can_receive_letters', 'sort_order',
        'disabled_by', 'disabled_at', 'disable_reason',
    ];

    protected $casts = [
        'is_active'           => 'boolean',
        'can_receive_letters' => 'boolean',
        'sort_order'          => 'integer',
        'disabled_at'         => 'datetime',
    ];

    // ── Relations ──────────────────────────────────────────────────────────

    public function users()
    {
        return $this->hasMany(User::class, 'category_id');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeLetterRecipients($query)
    {
        return $query->where('can_receive_letters', true)
            ->orderBy('sort_order')
            ->orderBy('name');
    }
}
