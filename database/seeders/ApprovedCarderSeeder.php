<?php

namespace Database\Seeders;

use App\Models\ApprovedCarder;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * ApprovedCarderSeeder
 * Seeds the full Position list and Ministry-approved carder totals sourced
 * from the official establishment register (Approved.xlsx).
 *
 * Hierarchy: Position -> Subject Code  (Designation removed)
 *
 * 57 Positions, total approved cadre = 2,033.
 *
 * Safe to re-run — idempotent (firstOrCreate for positions,
 * updateOrCreate for approved carder figures).
 *
 * Usage:
 *   php artisan db:seed --class=ApprovedCarderSeeder
 *
 * Optional env override for created_by user:
 *   CARDER_SEEDER_OFFICER=planning@hims.local php artisan db:seed --class=ApprovedCarderSeeder
 */
class ApprovedCarderSeeder extends Seeder
{
    private const YEAR = null; // null = current year

    /**
     * All 57 positions with their approved headcount.
     * Format: ['code', 'title', approved_amount]
     */
    private function positions(): array
    {
        return [
            ['POS-MDASN', 'Medical Administrator (Senior Grade)', 1],
            ['POS-MDADP', 'Medical Administrator (Deputy Grade)',  2],
            ['POS-MDCON', 'Medical Consultant',                    16],
            ['POS-DTSRG', 'Dental Surgeon',                       50],
            ['POS-MEDOF', 'Medical Officer',                       253],
            ['POS-MATRN', 'Matron',                               4],
            ['POS-NRSMS', 'Nursing Sister / Master',              20],
            ['POS-NRSOF', 'Nursing Officer',                      682],
            ['POS-SGMDW', 'Special Grade Midwife',                1],
            ['POS-MIDWF', 'Midwife',                              45],
            ['POS-SGMLT', 'Special Grade MLT',                    1],
            ['POS-MLTEC', 'Medical Laboratory Technologist',       32],
            ['POS-HLTAD', 'Health Laboratory Aid',                11],
            ['POS-SGPHM', 'Special Grade Pharmacist',             1],
            ['POS-PHRMC', 'Pharmacist',                           36],
            ['POS-DSPNS', 'Dispenser',                            1],
            ['POS-SGRDG', 'Special Grade Radiographer',           1],
            ['POS-RADGP', 'Radiographer',                         21],
            ['POS-BIOME', 'Bio Medical Engineer',                  1],
            ['POS-NUTRI', 'Nutritionist',                         1],
            ['POS-PSYSW', 'Psychiatric Social Worker',            2],
            ['POS-OCCTH', 'Occupational Therapist',               5],
            ['POS-PHYST', 'Physiotherapist',                      10],
            ['POS-SPPTH', 'Speech Therapist',                     1],
            ['POS-ECGRC', 'ECG Recordist',                        10],
            ['POS-EEGRC', 'EEG Recordist',                        2],
            ['POS-ORTHT', 'Orthopaedic Technician',               1],
            ['POS-BMEAS', 'Bio Medical Engineering Assistant',     2],
            ['POS-ACCNT', 'Accountant',                           1],
            ['POS-ADMOF', 'Administrative Officer',               1],
            ['POS-MDRCD', 'Medical Record Assistant',             4],
            ['POS-DVAST', 'Development Assistant',                1],
            ['POS-DVOFF', 'Development Officer',                  21],
            ['POS-ICTSA', 'ICT Assistant',                        2],
            ['POS-PHMAS', 'Public Health Management Assistant',    46],
            ['POS-WDCLK', 'Ward Clerk',                           8],
            ['POS-BKBND', 'Book Binder',                          1],
            ['POS-TLOPR', 'Telephone Operator',                   7],
            ['POS-PHINS', 'Public Health Inspector',              1],
            ['POS-PHFOF', 'Public Health Field Officer',          0],
            ['POS-ELCTN', 'Electrician',                          1],
            ['POS-BOLRO', 'Boiler Operator',                      2],
            ['POS-CARP',  'Carpenter',                            2],
            ['POS-HPMO',  'High Pressure Machine Operator',       3],
            ['POS-PLMPR', 'Plumber / Pump Machine Operator',      2],
            ['POS-SWGMO', 'Sewing Machine Operator',              8],
            ['POS-DIETS', 'Diet Steward',                         4],
            ['POS-HWRDN', 'House Warden',                         6],
            ['POS-HLDVR', 'Health Driver',                        18],
            ['POS-HSOVR', 'Hospital Overseer',                    9],
            ['POS-ATNDNT','Attendant',                            50],
            ['POS-COOK',  'Cook',                                 10],
            ['POS-KKS',   'KKS',                                  6],
            ['POS-LIFTO', 'Lift Operator',                        6],
            ['POS-SKSOD', 'SKS Ordinary',                        450],
            ['POS-SKSJN', 'SKS Junior',                          150],
            ['POS-SKSCS', 'SKS Casual',                           0],
        ];
    }

    public function run(): void
    {
        $year = self::YEAR ?? now()->year;

        $officerEmail = env('CARDER_SEEDER_OFFICER');

        $officer = $officerEmail
            ? User::where('email', $officerEmail)->first()
            : User::havingRole('planning_officer')->where('is_active', true)->first()
                ?? User::havingRole('super_admin')->first();

        if (! $officer) {
            $this->command?->warn('No planning_officer or super_admin user found. Run SuperAdminSeeder first.');
            return;
        }

        DB::transaction(function () use ($year, $officer) {
            $totalApproved = 0;

            foreach ($this->positions() as [$code, $title, $approved]) {
                $position = Position::firstOrCreate(
                    ['code' => $code],
                    ['title' => $title, 'is_active' => true]
                );

                ApprovedCarder::updateOrCreate(
                    ['position_id' => $position->id, 'year' => $year],
                    [
                        'approved_amount'       => $approved,
                        'ministry_reference_no' => 'MOH/CADRE/' . $year . '/' . $code,
                        'approved_date'         => now()->startOfYear()->addDays(14),
                        'created_by'            => $officer->id,
                    ]
                );

                $totalApproved += $approved;
            }

            $this->command?->info(
                'Done. ' . count($this->positions()) . ' positions, approved total = ' . $totalApproved . ' (year ' . $year . ')'
            );
        });
    }
}
