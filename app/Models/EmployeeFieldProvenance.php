<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\EncryptsPersonnelData;
use Illuminate\Database\Eloquent\Model;

class EmployeeFieldProvenance extends Model
{
    use EncryptsPersonnelData;

    protected $table = 'employee_field_provenance';

    protected array $encryptedPii = [
        'field_value' => 'generic',
    ];

    protected array $searchablePii = [];

    protected $guarded = [];
}
