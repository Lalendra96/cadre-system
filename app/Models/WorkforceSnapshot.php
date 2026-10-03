<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkforceSnapshot extends Model
{
    public $timestamps = false;

    protected $fillable = ['official_report_id', 'payload', 'content_hash', 'signed_by', 'signed_at', 'archived_at'];

    protected $casts = ['payload' => 'array', 'signed_at' => 'datetime', 'archived_at' => 'datetime'];
}
