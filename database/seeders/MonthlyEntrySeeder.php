<?php

namespace Database\Seeders;

use App\Models\CarderMonthlyEntry;
use App\Models\Position;
use App\Models\SubjectCode;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * MonthlyEntrySeeder
 * ─────────────────────────────────────────────────────────────────────────
 * Seeds carder_monthly_entries for March 2026 (year=2026, month=3) using
 * actual headcount figures sourced from In_Position.xlsx (snapshot date:
 * 2026/03/31).
 *
 * Columns from the source file:
 *   In Position  — total headcount in post on that date
 *   No Pay Leave — staff on no-pay leave (included in the above total)
 *   Males        — male headcount (blank cells treated as 0)
 *   Females      — female headcount (blank cells treated as 0)
 *
 * Note: Males + Females may be less than "In Position" for some rows —
 * this is because gender data was not recorded in the source file for
 * those entries. The figures are seeded exactly as supplied.
 *
 * Subject code strategy — every position gets its own DEDICATED code:
 *   Each position is always filed under "SC-" + position code (e.g.
 *   "SC-POS-MEDOF"), created if it doesn't exist yet. Existing subject
 *   codes already linked to a position are never reused or shared —
 *   every position always resolves to exactly one dedicated code.
 *
 * Prerequisites:
 *   Run ApprovedCarderSeeder first so positions (POS-xxx codes) exist.
 *
 * Safe to re-run — uses firstOrCreate/updateOrCreate throughout.
 *
 * Usage:
 *   php artisan db:seed --class=MonthlyEntrySeeder
 */
class MonthlyEntrySeeder extends Seeder
{
    private const YEAR  = 2026;
    private const MONTH = 3;

    /**
     * Source data keyed by the Position code from ApprovedCarderSeeder.
     * [ pos_code => [males, females, no_pay_leave] ]
     */
    private function data(): array
    {
        return [
            'POS-MDASN' => ['m' =>   1, 'f' =>   0, 'np' =>  0],
            'POS-MDADP' => ['m' =>   2, 'f' =>   0, 'np' =>  0],
            'POS-MDCON' => ['m' =>  17, 'f' =>   9, 'np' =>  2],
            'POS-DTSRG' => ['m' =>  31, 'f' =>  14, 'np' =>  0],
            'POS-MEDOF' => ['m' => 105, 'f' => 116, 'np' => 13],
            'POS-ACCNT' => ['m' =>   0, 'f' =>   1, 'np' =>  0],
            'POS-BIOME' => ['m' =>   0, 'f' =>   0, 'np' =>  0],
            'POS-MATRN' => ['m' =>   0, 'f' =>   2, 'np' =>  0],
            'POS-SGMLT' => ['m' =>   0, 'f' =>   0, 'np' =>  0],
            'POS-SGPHM' => ['m' =>   0, 'f' =>   1, 'np' =>  0],
            'POS-SGRDG' => ['m' =>   0, 'f' =>   0, 'np' =>  0],
            'POS-SGMDW' => ['m' =>   0, 'f' =>   0, 'np' =>  0],
            'POS-ADMOF' => ['m' =>   0, 'f' =>   1, 'np' =>  0],
            'POS-NUTRI' => ['m' =>   0, 'f' =>   1, 'np' =>  0],
            'POS-PSYSW' => ['m' =>   1, 'f' =>   1, 'np' =>  0],
            'POS-NRSMS' => ['m' =>   1, 'f' =>  11, 'np' =>  0],
            'POS-NRSOF' => ['m' =>  28, 'f' => 631, 'np' => 20],
            'POS-MLTEC' => ['m' =>  12, 'f' =>  16, 'np' =>  0],
            'POS-OCCTH' => ['m' =>   2, 'f' =>   3, 'np' =>  0],
            'POS-PHRMC' => ['m' =>  10, 'f' =>  23, 'np' =>  1],
            'POS-PHYST' => ['m' =>   4, 'f' =>   3, 'np' =>  1],
            'POS-RADGP' => ['m' =>   8, 'f' =>   9, 'np' =>  0],
            'POS-SPPTH' => ['m' =>   0, 'f' =>   0, 'np' =>  0],
            'POS-MIDWF' => ['m' =>   0, 'f' =>  33, 'np' =>  0],
            'POS-PHINS' => ['m' =>   1, 'f' =>   0, 'np' =>  0],
            'POS-BMEAS' => ['m' =>   0, 'f' =>   0, 'np' =>  0],
            'POS-MDRCD' => ['m' =>   0, 'f' =>   2, 'np' =>  0],
            'POS-DVAST' => ['m' =>   0, 'f' =>   0, 'np' =>  0],
            'POS-DVOFF' => ['m' =>   5, 'f' =>  15, 'np' =>  1],
            'POS-DSPNS' => ['m' =>   0, 'f' =>   0, 'np' =>  0],
            'POS-ECGRC' => ['m' =>   3, 'f' =>   5, 'np' =>  0],
            'POS-EEGRC' => ['m' =>   0, 'f' =>   2, 'np' =>  0],
            'POS-ORTHT' => ['m' =>   0, 'f' =>   0, 'np' =>  0],
            'POS-PHFOF' => ['m' =>   1, 'f' =>   0, 'np' =>  0],
            'POS-ICTSA' => ['m' =>   1, 'f' =>   0, 'np' =>  0],
            'POS-PHMAS' => ['m' =>  12, 'f' =>  28, 'np' =>  1],
            'POS-DIETS' => ['m' =>   0, 'f' =>   1, 'np' =>  0],
            'POS-HWRDN' => ['m' =>   1, 'f' =>   2, 'np' =>  0],
            'POS-WDCLK' => ['m' =>   0, 'f' =>   5, 'np' =>  0],
            'POS-ELCTN' => ['m' =>   1, 'f' =>   0, 'np' =>  0],
            'POS-HLDVR' => ['m' =>  18, 'f' =>   0, 'np' =>  0],
            'POS-HSOVR' => ['m' =>   2, 'f' =>   3, 'np' =>  0],
            'POS-ATNDNT'=> ['m' =>   5, 'f' =>  32, 'np' =>  1],
            'POS-BOLRO' => ['m' =>   1, 'f' =>   0, 'np' =>  0],
            'POS-CARP'  => ['m' =>   1, 'f' =>   0, 'np' =>  0],
            'POS-COOK'  => ['m' =>   5, 'f' =>   0, 'np' =>  0],
            'POS-HLTAD' => ['m' =>   5, 'f' =>   5, 'np' =>  0],
            'POS-HPMO'  => ['m' =>   0, 'f' =>   0, 'np' =>  0],
            'POS-PLMPR' => ['m' =>   0, 'f' =>   0, 'np' =>  0],
            'POS-SWGMO' => ['m' =>   0, 'f' =>   3, 'np' =>  0],
            'POS-TLOPR' => ['m' =>   1, 'f' =>   2, 'np' =>  0],
            'POS-BKBND' => ['m' =>   0, 'f' =>   0, 'np' =>  0],
            'POS-KKS'   => ['m' =>   0, 'f' =>   2, 'np' =>  0],
            'POS-LIFTO' => ['m' =>   4, 'f' =>   0, 'np' =>  0],
            'POS-SKSOD' => ['m' =>  88, 'f' => 138, 'np' =>  4],
            'POS-SKSJN' => ['m' =>  81, 'f' => 164, 'np' =>  5],
            'POS-SKSCS' => ['m' =>   0, 'f' =>   1, 'np' =>  0],
        ];
    }

    public function run(): void
    {
        $year  = self::YEAR;
        $month = self::MONTH;

        // Use a real system user as the submitter (planning officer or super admin).
        $submitter = User::havingRole('planning_officer')->where('is_active', true)->first()
            ?? User::havingRole('super_admin')->first();

        if (! $submitter) {
            $this->command?->warn('No planning_officer or super_admin user found. Run SuperAdminSeeder first.');
            return;
        }

        $data  = $this->data();
        $seeded = 0;
        $autoCreated = 0;

        DB::transaction(function () use ($data, $year, $month, $submitter, &$seeded, &$autoCreated) {
            foreach ($data as $posCode => $figures) {
                $position = Position::where('code', $posCode)->first();

                if (! $position) {
                    $this->command?->warn("  Position {$posCode} not found — run ApprovedCarderSeeder first.");
                    continue;
                }

                // Always use/create a dedicated subject code for this
                // position — never reuse or share an existing linked
                // code, and never split a position's figures across
                // several codes. Every position gets exactly one code
                // (e.g. "SC-POS-MEDOF"), so entries never need manual
                // redistribution after the fact.
                $wasExisting = SubjectCode::where('code', 'SC-' . $posCode)->exists();
                $subjectCode = SubjectCode::firstOrCreate(
                    ['code' => 'SC-' . $posCode],
                    [
                        'name'      => $position->title . ' (General)',
                        'is_active' => true,
                    ]
                );
                $subjectCode->positions()->syncWithoutDetaching([$position->id]);
                if (! $wasExisting) {
                    $autoCreated++;
                }

                CarderMonthlyEntry::updateOrCreate(
                    [
                        'subject_code_id' => $subjectCode->id,
                        'year'            => $year,
                        'month'           => $month,
                    ],
                    [
                        'position_id'     => $position->id,
                        'males'           => $figures['m'],
                        'females'         => $figures['f'],
                        'transferred_in'  => 0,
                        'transferred_out' => 0,
                        'no_pay_leave'    => $figures['np'],
                        'submitted_by'    => $submitter->id,
                        'submitted_at'    => now(),
                    ]
                );

                $seeded++;
            }
        });

        $this->command?->info(
            "Seeded {$seeded} monthly entries for {$year}/{$month}."
        );

        if ($autoCreated > 0) {
            $this->command?->line(
                "  <comment>Auto-created {$autoCreated} dedicated subject code(s) for positions that had none.</comment>"
            );
        }
    }
}
