<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDashboardLayout extends Model
{
    protected $fillable = [
        'user_id',
        'role_key',
        'layout',
        'quick_links',
    ];

    protected $casts = [
        'layout' => 'array',
        'quick_links' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
