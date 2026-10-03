<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiAssistantInteraction extends Model
{
    protected $fillable = [
        'user_id',
        'phase',
        'question',
        'context_route',
        'answer_mode',
        'source_ids',
        'response_summary',
        'support_only_acknowledged',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'source_ids' => 'array',
        'support_only_acknowledged' => 'boolean',
    ];
}
