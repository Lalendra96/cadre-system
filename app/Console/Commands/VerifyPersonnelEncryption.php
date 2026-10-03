<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\CarPassRequest;
use App\Models\Employee;
use App\Models\EmployeeChangeRequest;
use App\Models\EmployeeFieldProvenance;
use App\Models\ExternalHrReconciliationItem;
use App\Models\Intern;
use App\Models\TransferRecord;
use App\Services\PiiCryptographyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class VerifyPersonnelEncryption extends Command
{
    protected $signature = 'pii:verify';
    protected $description = 'Verify that protected Employee and Intern fields are encrypted and searchable blind indexes are present.';

    public function handle(PiiCryptographyService $crypto): int
    {
        if (! $crypto->enabled()) {
            $this->error('PII encryption is not enabled.');
            return self::FAILURE;
        }

        $failures = 0;
        $failures += $this->verifyModel(Employee::class,
            ['name','pay_no','service_file_no','nic_number','wop_number','professional_registration_no','email','whatsapp_mobile','permanent_address','current_address','emergency_contact_name','emergency_contact_relationship','emergency_contact_mobile','confirmation_reference_no','notes'],
            ['name','pay_no','service_file_no','nic_number','wop_number','professional_registration_no','email','whatsapp_mobile'], $crypto);
        $failures += $this->verifyModel(Intern::class,
            ['name','nic_number','mobile_number'], ['name','nic_number','mobile_number'], $crypto);
        $failures += $this->verifyModel(TransferRecord::class, ['employee_name'], [], $crypto);
        $failures += $this->verifyModel(CarPassRequest::class, ['employee_name_snapshot','pay_no_snapshot'], [], $crypto);
        $failures += $this->verifyModel(EmployeeChangeRequest::class, ['old_value','requested_value'], [], $crypto);
        $failures += $this->verifyModel(ExternalHrReconciliationItem::class, ['external_identifier','local_value','external_value'], [], $crypto);
        $failures += $this->verifyModel(EmployeeFieldProvenance::class, ['field_value'], [], $crypto);

        if ($failures > 0) {
            $this->error("Verification failed: {$failures} protected value(s) still need attention.");
            return self::FAILURE;
        }

        $this->info('Verification successful: protected personnel fields are encrypted and exact-search HMAC indexes are populated.');
        return self::SUCCESS;
    }

    private function verifyModel(string $class, array $fields, array $searchable, PiiCryptographyService $crypto): int
    {
        $prototype = new $class;
        if (! Schema::hasTable($prototype->getTable())) {
            return 0;
        }

        $failures = 0;
        $class::query()->orderBy('id')->chunkById(250, function ($rows) use (&$failures, $fields, $searchable, $crypto) {
            foreach ($rows as $row) {
                foreach ($fields as $field) {
                    $raw = $row->getRawOriginal($field);
                    if ($raw === null || $raw === '') {
                        continue;
                    }
                    if (! $crypto->isEncrypted((string) $raw)) {
                        $this->warn("{$row->getTable()} ID {$row->getKey()}: {$field} is still plaintext.");
                        $failures++;
                    }
                    if (in_array($field, $searchable, true) && empty($row->getRawOriginal($field.'_hmac'))) {
                        $this->warn("{$row->getTable()} ID {$row->getKey()}: {$field}_hmac is missing.");
                        $failures++;
                    }
                }
            }
        });
        return $failures;
    }
}
