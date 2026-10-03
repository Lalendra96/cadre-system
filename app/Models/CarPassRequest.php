<?php

namespace App\Models;

use App\Traits\EncryptsPersonnelData;
use Illuminate\Database\Eloquent\Model;

class CarPassRequest extends Model
{
    use EncryptsPersonnelData;

    protected array $encryptedPii = [
        'employee_name_snapshot' => 'name',
        'pay_no_snapshot' => 'pay_no',
    ];
    protected array $searchablePii = [];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending_approval';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_ISSUED = 'issued';

    public const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'uuid',
        'reference_no',
        'employee_id',
        'template_id',
        'employee_name_snapshot',
        'pay_no_snapshot',
        'position_snapshot',
        'unit_snapshot',
        'template_name_snapshot',
        'template_code_snapshot',
        'template_version_snapshot',
        'template_image_snapshot_path',
        'vehicle_registration_no',
        'vehicle_type',
        'valid_from',
        'valid_to',
        'purpose',
        'notes',
        'status',
        'prepared_by',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'decision_note',
        'approved_at',
        'issued_at',
        'issued_by',
        'revoked_at',
        'revoked_by',
        'revocation_reason',
        'verification_token_hash',
        'content_hash',
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_to' => 'date',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'issued_at' => 'datetime',
        'revoked_at' => 'datetime',
        'template_version_snapshot' => 'integer',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function template()
    {
        return $this->belongsTo(CarPassTemplate::class, 'template_id');
    }

    public function preparer()
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function issuer()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function isMutable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED], true);
    }
}
