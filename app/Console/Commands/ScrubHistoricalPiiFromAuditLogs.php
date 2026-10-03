<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ScrubHistoricalPiiFromAuditLogs extends Command
{
    protected $signature = 'pii:scrub-audit-history {--commit : Persist redaction. Without this flag the command is a dry-run.}';
    protected $description = 'Redact historical personnel PII duplicated inside audit old/new JSON values.';

    private array $sensitive = [
        'password', 'remember_token', 'mfa_secret',
        'name', 'pay_no', 'service_file_no', 'nic_number', 'wop_number',
        'professional_registration_no', 'email', 'whatsapp_mobile',
        'permanent_address', 'current_address', 'emergency_contact_name',
        'emergency_contact_relationship', 'emergency_contact_mobile',
        'confirmation_reference_no', 'notes', 'mobile_number',
        'old_value', 'requested_value', 'employee_name', 'employee_name_snapshot', 'pay_no_snapshot',
    ];

    public function handle(): int
    {
        $commit = (bool) $this->option('commit');
        $changed = 0;

        AuditLog::query()->orderBy('id')->chunkById(250, function ($logs) use ($commit, &$changed) {
            DB::transaction(function () use ($logs, $commit, &$changed) {
                foreach ($logs as $log) {
                    $old = $this->redact((array) ($log->old_values ?? []), $oldChanged);
                    $new = $this->redact((array) ($log->new_values ?? []), $newChanged);
                    if (! $oldChanged && ! $newChanged) {
                        continue;
                    }
                    $changed++;
                    if ($commit) {
                        DB::table('audit_logs')->where('id', $log->id)->update([
                            'old_values' => empty($old) ? null : json_encode($old, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                            'new_values' => empty($new) ? null : json_encode($new, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        ]);
                    }
                }
            });
        });

        $this->info(($commit ? 'Redacted' : 'Would redact')." {$changed} historical audit row(s).");
        return self::SUCCESS;
    }

    private function redact(array $values, ?bool &$changed = false): array
    {
        $changed = false;
        foreach ($values as $key => $value) {
            if (in_array((string) $key, $this->sensitive, true) || str_ends_with((string) $key, '_hmac')) {
                if ($value !== '[REDACTED]') {
                    $values[$key] = '[REDACTED]';
                    $changed = true;
                }
            }
        }
        return $values;
    }
}
