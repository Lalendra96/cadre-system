<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\EncryptsPersonnelData;
use Illuminate\Database\Eloquent\Model;

class ExternalHrReconciliationItem extends Model
{
    use EncryptsPersonnelData;

    protected $table = 'external_hr_reconciliation_items';

    protected array $encryptedPii = [
        'external_identifier' => 'generic',
        'local_value' => 'generic',
        'external_value' => 'generic',
    ];

    protected array $searchablePii = [];

    protected $guarded = [];
}
