<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ApprovedCarder;
use App\Models\Position;
use Illuminate\Database\Seeder;

/**
 * Quarterly2ApprovedCarderSeeder
 *
 * Updates the hospital-wide Approved Carder baseline (position + year,
 * no subject-code or unit dimension) from the Q2 quarterly return
 * (2st_Quarerly.xlsx), a report dated 2026/06/30 covering 57 positions.
 *
 * ONLY the 'Approved in 2022' column is written here — see
 * resources/data/2026-06-quarterly-reference.csv for the rest of this
 * report (in-position, males, females, no-pay-leave) as a reference
 * dataset. Those columns are DELIBERATELY NOT written to any table:
 * carder_monthly_entries requires a subject_code_id this report has
 * no way to supply (it's a hospital-wide aggregate with no subject-code
 * breakdown), and guessing an attribution risked misattributing real
 * headcount data to the wrong subject officer's account — a decision
 * made explicitly with the user rather than assumed.
 *
 * Every position title was cross-checked against the live system's
 * position list before mapping — 17 of 57 needed a cosmetic rename
 * (casing, spacing, singular/plural, or a known typo in the source
 * spreadsheet, e.g. 'Developmrnt Assistant' -> 'Development Assistant').
 * Zero were left unmatched.
 *
 * Internal consistency check on the source data: for 56 of 57 rows,
 * males + females + no_pay_leave = in_position exactly. The one
 * exception, 'Sewing Machine Operator' (in_position=3, but
 * males/females/no_pay_leave all blank/0 in the source spreadsheet),
 * is a gap in the source data itself, not something inferred here.
 *
 * Positions are matched by title, case-insensitive — NEVER created.
 * Safe to re-run — updateOrCreate on (position_id, year).
 *
 * Usage:
 *   php artisan db:seed --class=Quarterly2ApprovedCarderSeeder
 */
class Quarterly2ApprovedCarderSeeder extends Seeder
{
    private const YEAR = 2026;

    public function run(): void
    {
        $updated = 0;
        $missingPositions = [];

        foreach ($this->data() as $title => $approvedAmount) {
            $position = Position::whereRaw('LOWER(title) = ?', [strtolower($title)])->first();
            if (! $position) {
                $missingPositions[] = $title;
                continue;
            }

            ApprovedCarder::updateOrCreate(
                ['position_id' => $position->id, 'year' => self::YEAR],
                ['approved_amount' => $approvedAmount]
            );
            $updated++;
        }

        $this->command?->info("Approved Carder rows updated: {$updated}");
        if ($missingPositions) {
            $this->command?->warn('Positions not found — skipped: ' . implode(', ', $missingPositions));
        }
    }

    /** @return array<string, int> position title => approved amount */
    private function data(): array
    {
        return [
            'Medical Administrator (Senior Grade)' => 1,
            'Medical Administrator (Deputy Grade)' => 2,
            'Medical Consultant' => 16,
            'Dental Surgeon' => 50,
            'Medical Officer' => 253,
            'Accountant' => 1,
            'Bio Medical Engineer' => 1,
            'Matron' => 4,
            'Special Grade MLT' => 1,
            'Special Grade Pharmacist' => 1,
            'Special Grade Radiographer' => 1,
            'Special Grade Midwife' => 1,
            'Administrative Officer' => 1,
            'Nutritionist' => 1,
            'Psychiatric Social Worker' => 2,
            'Nursing Sister / Master' => 20,
            'Nursing Officer' => 682,
            'Medical Laboratory Technologist' => 32,
            'Occupational Therapist' => 5,
            'Pharmacist' => 36,
            'Physiotherapist' => 10,
            'Radiographer' => 21,
            'Speech Therapist' => 1,
            'Midwife' => 45,
            'Public Health Inspector' => 1,
            'Bio Medical Engineering Assistant' => 2,
            'Medical Record Assistant' => 4,
            'Development Assistant' => 1,
            'Development Officer' => 21,
            'Dispenser' => 1,
            'ECG Recordist' => 10,
            'EEG Recordist' => 2,
            'Orthopaedic Technician' => 1,
            'Public Health Field Officer' => 0,
            'ICT Assistant' => 2,
            'Public Health Management Assistant' => 46,
            'Diet Steward' => 4,
            'House Warden' => 6,
            'Ward Clerk' => 8,
            'Electrician' => 1,
            'Health Driver' => 18,
            'Hospital Overseer' => 9,
            'Caregiver' => 50,
            'Boiler Operator' => 2,
            'Carpenter' => 2,
            'Cook' => 10,
            'Health Laboratory Aid' => 11,
            'High Pressure Machine Operator' => 3,
            'Plumber / Pump Machine Operator' => 2,
            'Sewing Machine Operator' => 8,
            'Telephone Operator' => 7,
            'Book Binder' => 1,
            'KKS' => 6,
            'Lift Operator' => 6,
            'SKS Ordinary' => 450,
            'SKS Junior' => 150,
            'SKS Casual' => 0,
        ];
    }
}
