<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedEmployeeView extends Model
{
    protected $fillable = ['user_id', 'name', 'filters', 'is_default'];

    protected $casts = ['filters' => 'array', 'is_default' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
