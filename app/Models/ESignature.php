<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasDisableWorkflow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * A registered e-signature image for one Admin Group user.
 *
 * DATA SECURITY: file_path is always on the PRIVATE disk. Use
 * ESignatureController::show() (auth + ownership/legitimate-use checked)
 * to serve the image — never link to storage/app directly, and never
 * put this on the `public` disk.
 */
class ESignature extends Model
{
    use HasDisableWorkflow;

    protected $table = 'e_signatures';

    protected $fillable = [
        'user_id', 'file_path', 'mime_type', 'file_size',
        'is_active', 'disabled_by', 'disabled_at', 'disable_reason',
    ];

    protected $casts = [
        'file_size'   => 'integer',
        'is_active'   => 'boolean',
        'disabled_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function fileExists(): bool
    {
        return Storage::disk('local')->exists($this->file_path);
    }
}
