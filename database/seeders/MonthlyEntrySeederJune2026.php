<?php

namespace Database\Seeders;

use App\Models\CarderMonthlyEntry;
use App\Models\Position;
use App\Models\SubjectCode;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * MonthlyEntrySeederJune2026
 * ─────────────────────────────────────────────────────────────────────────
 * Seeds carder_monthly_entries for June 2026 (year=2026, month=6) using
 * actual headcount figures sourced from 2st_Quarerly.xlsx (report dated
 * 2026/06/30 — the file's own column header reads "2026/06/31," a typo
 * in the source spreadsheet carried through here for traceability, not
 * a real date).
 *
 * ALL 57 positions are seeded explicitly here — every position gets its
 * own row for June regardless of whether its figures changed from March,
 * matching MonthlyEntrySeeder's own complete-per-month pattern rather
 * than relying on forPeriodWithCarryForward() to infer unchanged months.
 * A separate class from MonthlyEntrySeeder (March 2026) — deliberately
 * not merged into or overwriting it. Both files coexist.
 *
 * Columns from the source file:
 *   In Position  — total headcount in post on that date
 *   No Pay Leave — staff on no-pay leave (included in the above total)
 *   Males        — male headcount, reported "without no-pay-leave"
 *   Females      — female headcount, reported "without no-pay-leave"
 *
 * Internal consistency, verified before this seeder was written: for 56
 * of 57 positions, males + females + no_pay_leave = in_position exactly.
 * The one exception, Sewing Machine Operator (in_position=3, but
 * males/females/no_pay_leave all blank/0 in the source spreadsheet), is
 * seeded exactly as supplied — a gap in the source data, not inferred.
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
 * Safe to re-run — uses firstOrCreate/updateOrCreate throughout. Running
 * this does NOT affect March data — different (subject_code_id, year,
 * month) key, so June rows are added alongside March, never replacing it.
 *
 * Usage:
 *   php artisan db:seed --class=MonthlyEntrySeederJune2026
 */
class MonthlyEntrySeederJune2026 extends Seeder
{
    private const YEAR  = 2026;
    private const MONTH = 6;

    /**
     * Source data keyed by the Position code from ApprovedCarderSeeder.
     * All 57 positions from the June quarterly return.
     * [ pos_code => [males, females, no_pay_leave] ]
     */
    private function data(): array
    {
        return [
            'POS-MDASN'  => ['m' =>   0, 'f' =>   0, 'np' =>  0],
            'POS-MDADP'  => ['m' =>   2, 'f' =>   0, 'np' =>  0],
            'POS-MDCON'  => ['m' =>  17, 'f' =>   9, 'np' =>  0],
            'POS-DTSRG'  => ['m' =>  14, 'f' =>  29, 'np' =>  1],
            'POS-MEDOF'  => ['m' => 109, 'f' => 124, 'np' =>  0],
            'POS-ACCNT'  => ['m' =>   0, 'f' =>   1, 'np' =>  0],
            'POS-BIOME'  => ['m' =>   0, 'f' =>   0, 'np' =>  0],
            'POS-MATRN'  => ['m' =>   0, 'f' =>   1, 'np' =>  0],
            'POS-SGMLT'  => ['m' =>   0, 'f' =>   0, 'np' =>  0],
            'POS-SGPHM'  => ['m' =>   0, 'f' =>   1, 'np' =>  0],
            'POS-SGRDG'  => ['m' =>   0, 'f' =>   0, 'np' =>  0],
            'POS-SGMDW'  => ['m' =>   0, 'f' =>   0, 'np' =>  0],
            'POS-ADMOF'  => ['m' =>   0, 'f' =>   1, 'np' =>  0],
            'POS-NUTRI'  => ['m' =>   0, 'f' =>   1, 'np' =>  0],
            'POS-PSYSW'  => ['m' =>   1, 'f' =>   1, 'np' =>  0],
            'POS-NRSMS'  => ['m' =>   1, 'f' =>  11, 'np' =>  0],
            'POS-NRSOF'  => ['m' =>  28, 'f' => 611, 'np' => 20],
            'POS-MLTEC'  => ['m' =>  12, 'f' =>  16, 'np' =>  0],
            'POS-OCCTH'  => ['m' =>   2, 'f' =>   3, 'np' =>  0],
            'POS-PHRMC'  => ['m' =>  10, 'f' =>  21, 'np' =>  1],
            'POS-PHYST'  => ['m' =>   4, 'f' =>   3, 'np' =>  0],
            'POS-RADGP'  => ['m' =>   8, 'f' =>   9, 'np' =>  0],
            'POS-SPPTH'  => ['m' =>   0, 'f' =>   0, 'np' =>  0],
            'POS-MIDWF'  => ['m' =>   0, 'f' =>  33, 'np' =>  0],
            'POS-PHINS'  => ['m' =>   1, 'f' =>   0, 'np' =>  0],
            'POS-BMEAS'  => ['m' =>   0, 'f' =>   0, 'np' =>  0],
            'POS-MDRCD'  => ['m' =>   0, 'f' =>   1, 'np' =>  0],
            'POS-DVAST'  => ['m' =>   0, 'f' =>   0, 'np' =>  0],
            'POS-DVOFF'  => ['m' =>   6, 'f' =>  14, 'np' =>  1],
            'POS-DSPNS'  => ['m' =>   0, 'f' =>   0, 'np' =>  0],
            'POS-ECGRC'  => ['m' =>   3, 'f' =>   5, 'np' =>  0],
            'POS-EEGRC'  => ['m' =>   0, 'f' =>   2, 'np' =>  0],
            'POS-ORTHT'  => ['m' =>   0, 'f' =>   0, 'np' =>  0],
            'POS-PHFOF'  => ['m' =>   1, 'f' =>   0, 'np' =>  0],
            'POS-ICTSA'  => ['m' =>   1, 'f' =>   0, 'np' =>  0],
            'POS-PHMAS'  => ['m' =>  12, 'f' =>  27, 'np' =>  1],
            'POS-DIETS'  => ['m' =>   0, 'f' =>   2, 'np' =>  0],
            'POS-HWRDN'  => ['m' =>   1, 'f' =>   2, 'np' =>  0],
            'POS-WDCLK'  => ['m' =>   0, 'f' =>   5, 'np' =>  0],
            'POS-ELCTN'  => ['m' =>   1, 'f' =>   0, 'np' =>  0],
            'POS-HLDVR'  => ['m' =>  18, 'f' =>   0, 'np' =>  0],
            'POS-HSOVR'  => ['m' =>   2, 'f' =>   3, 'np' =>  0],
            'POS-ATNDNT' => ['m' =>   5, 'f' =>  31, 'np' =>  1],
            'POS-BOLRO'  => ['m' =>   1, 'f' =>   0, 'np' =>  0],
            'POS-CARP'   => ['m' =>   1, 'f' =>   0, 'np' =>  0],
            'POS-COOK'   => ['m' =>   6, 'f' =>   0, 'np' =>  0],
            'POS-HLTAD'  => ['m' =>   6, 'f' =>   6, 'np' =>  0],
            'POS-HPMO'   => ['m' =>   0, 'f' =>   0, 'np' =>  0],
            'POS-PLMPR'  => ['m' =>   0, 'f' =>   0, 'np' =>  0],
            'POS-SWGMO'  => ['m' =>   0, 'f' =>   0, 'np' =>  0],
            'POS-TLOPR'  => ['m' =>   1, 'f' =>   2, 'np' =>  0],
            'POS-BKBND'  => ['m' =>   0, 'f' =>   0, 'np' =>  0],
            'POS-KKS'    => ['m' =>   0, 'f' =>   1, 'np' =>  0],
            'POS-LIFTO'  => ['m' =>   4, 'f' =>   0, 'np' =>  0],
            'POS-SKSOD'  => ['m' =>  80, 'f' => 158, 'np' =>  4],
            'POS-SKSJN'  => ['m' =>  85, 'f' => 131, 'np' =>  4],
            'POS-SKSCS'  => ['m' =>   0, 'f' =>   1, 'np' =>  0],
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
                // several codes.
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
