<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeImportBatch;
use App\Models\Position;
use App\Models\SalaryScale;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

/**
 * Applies an EmployeeImportBatch's column_mapping to each data row and
 * creates Employee records — used identically by both the preview
 * (dry-run, no writes) and the real import (writes, one row at a time,
 * each independently try/caught so one bad row never aborts the whole
 * batch).
 *
 * FIELD RESOLUTION
 * ────────────────
 * position / unit / salary_scale are mapped from free-text spreadsheet
 * columns (e.g. "Staff Nurse", "Ward 1") and resolved against the
 * existing master tables by exact title/name OR code match
 * (case-insensitive). A row whose text doesn't match anything existing
 * is rejected with a clear error rather than silently creating a
 * dangling/guessed reference — see resolveLookup().
 *
 * VALIDATION
 * ──────────
 * Mirrors EmployeeRequest's rules (NIC format, gender enum, uniqueness)
 * applied per-row via Laravel's Validator facade directly, since a
 * FormRequest isn't available for a plain array in a loop.
 */
class EmployeeImportProcessor
{
    public const IMPORTABLE_FIELDS = [
        'salutation'             => 'Salutation (Mr./Mrs./Ms./Dr. etc.)',
        'name'                   => 'Full Name',
        'pay_no'                 => 'Pay Number',
        'nic_number'             => 'NIC Number',
        'wop_number'             => 'W&OP Number',
        'gender'                 => 'Gender (M/F/O or Male/Female)',
        'email'                  => 'Email',
        'whatsapp_mobile'        => 'WhatsApp / Mobile',
        'position'               => 'Position (title or code)',
        'unit'                   => 'Unit (name or code)',
        'salary_scale'           => 'Salary Scale (code)',
        'date_of_birth'          => 'Date of Birth',
        'date_of_appointment'    => 'Date of Appointment',
        'date_reported_for_duty' => 'Date Reported for Duty',
        'retirement_age'         => 'Retirement Age',
        'notes'                  => 'Notes',
    ];

    /**
     * Apply the mapping to one raw row, returning ['data' => [...], 'errors' => [...]].
     * Does NOT write to the database — pure transform + validate.
     */
    public static function mapRow(array $rawRow, array $columnMapping): array
    {
        $mapped = [];
        foreach ($columnMapping as $columnIndex => $field) {
            if ($field === '' || $field === null) {
                continue; // column intentionally skipped
            }
            $mapped[$field] = trim((string) ($rawRow[(int) $columnIndex] ?? ''));
        }

        return $mapped;
    }

    /**
     * Resolve + validate a mapped row into Employee::create()-ready data,
     * or return errors if it can't be resolved/validated.
     *
     * @return array{data: array|null, errors: array<string>}
     */
    public static function resolveAndValidate(array $mapped, int $subjectCodeId, int $userId, ?int $defaultPositionId = null): array
    {
        $errors = [];
        $data   = [
            'subject_code_id' => $subjectCodeId,
            'created_by'      => $userId,
            'updated_by'      => $userId,
            'is_active'       => true,
        ];

        // ── Direct-copy fields ──────────────────────────────────────────
        foreach (['salutation', 'name', 'pay_no', 'nic_number', 'wop_number', 'email',
                  'whatsapp_mobile', 'notes'] as $field) {
            if (isset($mapped[$field]) && $mapped[$field] !== '') {
                $data[$field] = $mapped[$field];
            }
        }

        // ── Gender normalisation ────────────────────────────────────────
        if (! empty($mapped['gender'])) {
            $g = strtoupper(trim($mapped['gender']));
            $data['gender'] = match (true) {
                in_array($g, ['M', 'MALE'], true)   => 'M',
                in_array($g, ['F', 'FEMALE'], true) => 'F',
                default                              => 'O',
            };
        }

        // ── NIC normalisation (matches EmployeeRequest behaviour) ───────
        if (! empty($data['nic_number'])) {
            $data['nic_number'] = strtoupper($data['nic_number']);
        }

        // ── Date fields ──────────────────────────────────────────────────
        foreach (['date_of_birth', 'date_of_appointment', 'date_reported_for_duty'] as $field) {
            if (! empty($mapped[$field])) {
                $parsed = self::parseDate($mapped[$field]);
                if ($parsed === null) {
                    $errors[] = "Could not parse {$field}: \"{$mapped[$field]}\" (expected YYYY-MM-DD or DD/MM/YYYY)";
                } else {
                    $data[$field] = $parsed;
                }
            }
        }

        if (! empty($mapped['retirement_age'])) {
            $data['retirement_age'] = (int) $mapped['retirement_age'];
        }

        // ── Foreign-key text lookups ─────────────────────────────────────
        // ── Position: per-row mapping takes priority; falls back to the
        // batch's single default position when the file has no Position
        // column at all (e.g. a position-specific import file where every
        // row is the same position, so mapping a column would be redundant).
        if (! empty($mapped['position'])) {
            $position = self::resolveLookup(Position::class, $mapped['position'], 'title', 'code');
            if (! $position) {
                $errors[] = "Position not found: \"{$mapped['position']}\"";
            } else {
                $data['position_id'] = $position->id;
            }
        } elseif ($defaultPositionId) {
            $data['position_id'] = $defaultPositionId;
        } else {
            $errors[] = 'Position is required — either map a Position column, or select a default position for this batch.';
        }

        // ── Unit: optional. An unmatched or absent unit no longer blocks
        // the row — the employee imports with unit_id left unassigned
        // (unit_id is nullable on Employee), rather than being rejected
        // over a field that can reasonably be filled in later.
        if (! empty($mapped['unit'])) {
            $unit = self::resolveLookup(Unit::class, $mapped['unit'], 'name', 'code');
            if ($unit) {
                $data['unit_id'] = $unit->id;
            }
            // An unmatched unit value is silently left unassigned rather
            // than rejecting the row — same treatment as salary_scale below.
        }

        if (! empty($mapped['salary_scale'])) {
            $scale = self::resolveLookup(SalaryScale::class, $mapped['salary_scale'], 'code', 'code');
            if ($scale) {
                $data['salary_scale_id'] = $scale->id;
            }
            // Salary scale is optional — an unmatched value is silently
            // skipped rather than rejecting the whole row, since it's not
            // a required Employee field.
        }

        if (! empty($errors)) {
            return ['data' => null, 'errors' => $errors];
        }

        // ── Full validation pass, mirroring EmployeeRequest ─────────────
        $validator = Validator::make($data, [
            'salutation' => ['nullable', 'string', 'in:Mr.,Mrs.,Ms.,Miss,Dr.,Rev.,Prof.,Eng.'],
            'name'       => ['required', 'string', 'max:150'],
            'pay_no'     => ['nullable', 'string', 'max:50', 'unique:employees,pay_no'],
            'nic_number' => ['nullable', 'string', 'max:12', 'regex:/^(\d{9}[VvXx]|\d{12})$/', 'unique:employees,nic_number'],
            'wop_number' => ['nullable', 'string', 'max:20', 'unique:employees,wop_number'],
            'gender'     => ['required', 'in:M,F,O'],
            'email'      => ['nullable', 'email', 'max:150'],
        ]);

        if ($validator->fails()) {
            return ['data' => null, 'errors' => $validator->errors()->all()];
        }

        return ['data' => $data, 'errors' => []];
    }

    /**
     * @return array{position:int,unit:int}|null placeholder for type hints; not used
     */
    private static function resolveLookup(string $modelClass, string $text, string $primaryColumn, string $codeColumn)
    {
        $text = trim($text);

        return $modelClass::active()
            ->where(function ($q) use ($text, $primaryColumn, $codeColumn) {
                $q->whereRaw("LOWER({$primaryColumn}) = ?", [strtolower($text)])
                  ->orWhereRaw("LOWER({$codeColumn}) = ?", [strtolower($text)]);
            })
            ->first();
    }

    private static function parseDate(string $value): ?string
    {
        $value = trim($value);
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y'] as $format) {
            $d = \DateTime::createFromFormat($format, $value);
            if ($d !== false && $d->format($format) === $value) {
                return $d->format('Y-m-d');
            }
        }
        // Fall back to PHP's general parser for anything reasonably standard.
        $ts = strtotime($value);
        return $ts !== false ? date('Y-m-d', $ts) : null;
    }
}
