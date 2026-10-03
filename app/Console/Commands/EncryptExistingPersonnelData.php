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
use Illuminate\Support\Facades\DB;
use Throwable;

class EncryptExistingPersonnelData extends Command
{
    protected $signature = 'pii:encrypt-existing {--commit : Persist changes. Without this flag the command is a dry-run.} {--chunk=100 : Records per transaction chunk}';

    protected $description = 'Encrypt existing Employee and Intern PII and build exact-search HMAC blind indexes safely.';

    public function handle(PiiCryptographyService $crypto): int
    {
        if (! $crypto->enabled()) {
            $this->error('PII_ENCRYPTION_ENABLED is false. Configure keys and enable PII encryption first.');
            return self::FAILURE;
        }

        $commit = (bool) $this->option('commit');
        $chunk = max(1, min(1000, (int) $this->option('chunk')));

        $this->warn($commit
            ? 'COMMIT MODE: personnel PII will be encrypted in place. Ensure a verified PostgreSQL backup exists first.'
            : 'DRY RUN: no database values will be changed.');

        try {
            $employeeCount = $this->processModel(new Employee, $chunk, $commit, $crypto);
            $internCount = $this->processModel(new Intern, $chunk, $commit, $crypto);
            $auxCount = 0;
            $auxCount += $this->processAuxiliary(TransferRecord::class, ['employee_name'], $chunk, $commit, $crypto);
            $auxCount += $this->processAuxiliary(CarPassRequest::class, ['employee_name_snapshot', 'pay_no_snapshot'], $chunk, $commit, $crypto);
            $auxCount += $this->processAuxiliary(EmployeeChangeRequest::class, ['old_value', 'requested_value'], $chunk, $commit, $crypto);
            $auxCount += $this->processAuxiliary(ExternalHrReconciliationItem::class, ['external_identifier', 'local_value', 'external_value'], $chunk, $commit, $crypto);
            $auxCount += $this->processAuxiliary(EmployeeFieldProvenance::class, ['field_value'], $chunk, $commit, $crypto);
        } catch (Throwable $e) {
            $this->error('PII migration stopped safely: '.$e->getMessage());
            return self::FAILURE;
        }

        $this->info("Employees checked: {$employeeCount}; Interns checked: {$internCount}; linked PII rows checked: {$auxCount}.");
        $this->info($commit
            ? 'Migration completed. Run `php artisan pii:verify` before relying on the encrypted dataset.'
            : 'Dry-run completed. Re-run with --commit only after backup and maintenance-window checks.');

        return self::SUCCESS;
    }

    private function processAuxiliary(string $class, array $fields, int $chunk, bool $commit, PiiCryptographyService $crypto): int
    {
        if (! class_exists($class)) {
            return 0;
        }

        $prototype = new $class;
        $table = $prototype->getTable();
        if (! \Illuminate\Support\Facades\Schema::hasTable($table)) {
            return 0;
        }

        $count = 0;
        $class::query()->orderBy('id')->chunkById($chunk, function ($rows) use (&$count, $fields, $commit, $crypto, $class, $table) {
            DB::transaction(function () use ($rows, &$count, $fields, $commit, $crypto, $class, $table) {
                foreach ($rows as $row) {
                    $count++;
                    $updates = [];
                    $expected = [];
                    foreach ($fields as $field) {
                        $raw = $row->getRawOriginal($field);
                        if ($raw === null || $raw === '') {
                            continue;
                        }
                        $plain = $crypto->decrypt((string) $raw);
                        $expected[$field] = $plain;
                        $updates[$field] = $crypto->encrypt($plain);
                    }
                    if (! $commit || empty($updates)) {
                        continue;
                    }
                    DB::table($table)->where('id', $row->getKey())->update($updates);
                    $fresh = $class::query()->findOrFail($row->getKey());
                    foreach ($expected as $field => $plain) {
                        if ((string) $fresh->{$field} !== (string) $plain) {
                            throw new \RuntimeException("Verification failed for {$table}.{$field} on ID {$row->getKey()}; transaction rolled back.");
                        }
                    }
                }
            });
        });

        return $count;
    }

    private function processModel(Employee|Intern $prototype, int $chunk, bool $commit, PiiCryptographyService $crypto): int
    {
        $class = $prototype::class;
        $table = $prototype->getTable();
        $fields = $prototype instanceof Employee
            ? ['name','pay_no','service_file_no','nic_number','wop_number','professional_registration_no','email','whatsapp_mobile','permanent_address','current_address','emergency_contact_name','emergency_contact_relationship','emergency_contact_mobile','confirmation_reference_no','notes']
            : ['name','nic_number','mobile_number'];

        $searchable = $prototype instanceof Employee
            ? ['name','pay_no','service_file_no','nic_number','wop_number','professional_registration_no','email','whatsapp_mobile']
            : ['name','nic_number','mobile_number'];

        $normalizers = $prototype instanceof Employee
            ? ['name'=>'name','pay_no'=>'pay_no','service_file_no'=>'service_file_no','nic_number'=>'nic','wop_number'=>'wop_number','professional_registration_no'=>'professional_registration_no','email'=>'email','whatsapp_mobile'=>'phone']
            : ['name'=>'name','nic_number'=>'nic','mobile_number'=>'phone'];

        $count = 0;
        $class::query()->orderBy('id')->chunkById($chunk, function ($rows) use (&$count, $fields, $searchable, $normalizers, $crypto, $commit, $class, $table) {
            DB::transaction(function () use ($rows, &$count, $fields, $searchable, $normalizers, $crypto, $commit, $class, $table) {
                foreach ($rows as $row) {
                    $count++;
                    $updates = [];
                    $expected = [];

                    foreach ($fields as $field) {
                        $raw = $row->getRawOriginal($field);
                        if ($raw === null || $raw === '') {
                            if (in_array($field, $searchable, true)) {
                                $updates[$field.'_hmac'] = null;
                            }
                            continue;
                        }

                        $plain = $crypto->decrypt((string) $raw);
                        $expected[$field] = $plain;
                        $updates[$field] = $crypto->encrypt($plain);

                        if (in_array($field, $searchable, true)) {
                            $updates[$field.'_hmac'] = $crypto->blindIndex($plain, $normalizers[$field]);
                        }
                    }

                    if (! $commit) {
                        continue;
                    }

                    DB::table($table)->where('id', $row->getKey())->update($updates);

                    /** @var Employee|Intern $fresh */
                    $fresh = $class::query()->findOrFail($row->getKey());
                    foreach ($expected as $field => $plain) {
                        if ((string) $fresh->{$field} !== (string) $plain) {
                            throw new \RuntimeException("Verification failed for {$table}.{$field} on ID {$row->getKey()}; transaction rolled back.");
                        }
                    }
                }
            });
        });

        return $count;
    }
}
