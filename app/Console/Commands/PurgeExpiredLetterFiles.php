<?php

namespace App\Console\Commands;

use App\Models\LetterAttachment;
use App\Services\AuditLogService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Deletes the physical file for any letter attachment older than 30 days.
 * The attachment record itself, its original filename, and the parent
 * letter's full review history are kept — only the underlying file is
 * removed, to control storage growth while preserving the audit trail.
 *
 * Schedule this in app/Console/Kernel.php:
 *   $schedule->command('letters:purge-expired')->daily();
 * (and make sure your server's cron runs `php artisan schedule:run` every minute)
 */
class PurgeExpiredLetterFiles extends Command
{
    protected $signature = 'letters:purge-expired';

    protected $description = 'Delete uploaded letter attachment files older than 30 days (metadata is retained)';

    public function handle(): int
    {
        $cutoff = now()->subDays(30);

        $attachments = LetterAttachment::whereNotNull('file_path')
            ->where('created_at', '<=', $cutoff)
            ->get();

        if ($attachments->isEmpty()) {
            $this->info('No letter attachment files are due for removal.');
            return self::SUCCESS;
        }

        $removed = 0;

        foreach ($attachments as $attachment) {
            if (Storage::disk('local')->exists($attachment->file_path)) {
                Storage::disk('local')->delete($attachment->file_path);
            }

            $attachment->update([
                'file_path'       => null,
                'file_removed_at' => now(),
            ]);

            AuditLogService::updated(
                $attachment,
                ['file_path' => 'present'],
                "Auto-removed file \"{$attachment->original_filename}\" (30-day retention policy)"
            );

            $removed++;
        }

        $this->info("Removed {$removed} letter attachment file(s) older than 30 days.");
        return self::SUCCESS;
    }
}
