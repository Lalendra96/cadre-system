<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Position;
use App\Models\Unit;
use App\Models\UnitPositionAllocation;
use Illuminate\Database\Seeder;

/**
 * WardCadreUnitPositionAllocationSeeder
 * Seeds unit_position_allocations from the "Ward Cadre Allocation Report"
 * — Teaching Hospital Peradeniya (11 May 2026) — cross-referenced against
 * the hospital's In-Position export to resolve every cadre name to an
 * existing Position. Run WardCadreUnitSeeder FIRST — every unit name here
 * must already exist.
 *
 * MAPPING RULES APPLIED (confirmed with the user before this was built):
 *   - All 24 "Consultant X [Medical]" / "Acting Consultant" titles, plus
 *     Geriatric Medicine and Sports & Exercise Medicine (consultant-track
 *     specialties without the literal word "Consultant") -> Medical consultant.
 *   - The 3 "Consultant ... [Dental]" titles -> Dental Surgeon, flagged with
 *     a note naming the specific specialty (kept distinct from plain counts).
 *   - Medical Officer (PGIM) -> noted against Medical Officer in the same unit,
 *     NEVER added to allocated_posts/actual_in_post — a remark only.
 *   - Caregiver -> Attendant. Special Grade Nursing Officer -> Matron.
 *     Deputy Director -> Medical Administrator ( Deputy Grade). Public
 *     Management Assistant -> Public Health Management Assistant (same role).
 *   - Data Entry Operator(MN1) skipped entirely ("add later" per user).
 *
 * RECONCILIATION: source report's own Grand Total is In Position 1843 /
 * Add. Req. 2489 / Total 4332. The transcription this seeder is generated
 * from was verified to sum to exactly those three figures before mapping,
 * and the post-mapping split (main totals + PGIM remarks + skipped row)
 * was verified to still sum to the same three figures — see the generation
 * script's own reconciliation check.
 *
 * Positions are matched by title, case-insensitively. Any cadre that can't
 * be matched to an existing Position is SKIPPED with a warning printed to
 * the console, rather than silently creating a Position with a guessed code
 * — creating positions is a separate governance action, not something an
 * import should do implicitly.
 *
 * Safe to re-run — upserts on (unit_id, position_id, year).
 *
 * Usage:
 *   php artisan db:seed --class=WardCadreUnitSeeder              (run first)
 *   php artisan db:seed --class=WardCadreUnitPositionAllocationSeeder
 */
class WardCadreUnitPositionAllocationSeeder extends Seeder
{
    private const YEAR = 2026;

    public function run(): void
    {
        $created = 0;
        $missingUnits = [];
        $missingPositions = [];

        foreach ($this->data() as $unitName => $positions) {
            $unit = Unit::where('name', $unitName)->first();
            if (! $unit) {
                $missingUnits[] = $unitName;

                continue;
            }

            foreach ($positions as $positionTitle => $row) {
                $position = Position::whereRaw('LOWER(title) = ?', [strtolower($positionTitle)])->first();
                if (! $position) {
                    $missingPositions[$positionTitle] = ($missingPositions[$positionTitle] ?? 0) + 1;

                    continue;
                }

                UnitPositionAllocation::updateOrCreate(
                    ['unit_id' => $unit->id, 'position_id' => $position->id, 'year' => self::YEAR],
                    [
                        'allocated_posts' => $row['allocated_posts'],
                        'actual_in_post' => $row['actual_in_post'],
                        'notes' => $row['notes'],
                    ]
                );
                $created++;
            }
        }

        $this->command?->info("Unit Post Allocations upserted: {$created}");
        if ($missingUnits) {
            $this->command?->warn('Units not found (run WardCadreUnitSeeder first): '.implode(', ', array_unique($missingUnits)));
        }
        if ($missingPositions) {
            $this->command?->warn('Positions not found in this system — rows skipped:');
            foreach ($missingPositions as $title => $count) {
                $this->command?->warn("  \"{$title}\" — {$count} unit(s) affected");
            }
        }
    }

    /**
     * @return array<string, array<string, array{allocated_posts:int, actual_in_post:int, notes:?string}>>
     */
    private function data(): array
    {
        return [
            'Accident and Emergency Unit' => [
                'Medical consultant' => ['allocated_posts' => 4, 'actual_in_post' => 2, 'notes' => 'Incl. Consultant Emergency Physicians: 0 in post, 4 req.'],
                'Attendant' => ['allocated_posts' => 6, 'actual_in_post' => 1, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Casual)' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 36, 'actual_in_post' => 11, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 47, 'actual_in_post' => 17, 'notes' => '+3 PGIM trainee(s) attached, not counted in total'],
                'Nursing Officer' => ['allocated_posts' => 46, 'actual_in_post' => 30, 'notes' => null],
                'Nursing Sister/Master' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 3, 'actual_in_post' => 3, 'notes' => null],
            ],
            'Overseer Office' => [
                'Hospital Overseer' => ['allocated_posts' => 12, 'actual_in_post' => 5, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 19, 'actual_in_post' => 17, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 7, 'actual_in_post' => 7, 'notes' => null],
            ],
            'Administration Unit' => [
                'Administrative officer' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'Development Officer' => ['allocated_posts' => 7, 'actual_in_post' => 7, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 4, 'actual_in_post' => 4, 'notes' => null],
                'KKS' => ['allocated_posts' => 7, 'actual_in_post' => 2, 'notes' => null],
                'Medical Administrator ( Deputy Grade)' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
                'Medical Administrator (Senior Grade)' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'Public Health Management Assistant' => ['allocated_posts' => 36, 'actual_in_post' => 24, 'notes' => null],
            ],
            'Matron Office' => [
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 6, 'actual_in_post' => 3, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'Nursing Sister/Master' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
                'Matron' => ['allocated_posts' => 7, 'actual_in_post' => 1, 'notes' => null],
            ],
            'Blood Bank' => [
                'Medical consultant' => ['allocated_posts' => 2, 'actual_in_post' => 1, 'notes' => 'Incl. Consultant Transfusion Physician: 1 in post, 1 req.'],
                'Health Laboratory Aid' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 12, 'actual_in_post' => 3, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 22, 'actual_in_post' => 10, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 11, 'actual_in_post' => 11, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 5, 'actual_in_post' => 5, 'notes' => null],
            ],
            'Dental' => [
                'Dental Surgeon' => ['allocated_posts' => 66, 'actual_in_post' => 45, 'notes' => null],
            ],
            'Dental — ICU' => [
                'Attendant' => ['allocated_posts' => 2, 'actual_in_post' => 0, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 12, 'actual_in_post' => 4, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 20, 'actual_in_post' => 11, 'notes' => null],
                'Nursing Sister/Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 3, 'actual_in_post' => 3, 'notes' => null],
            ],
            'Dental — Wd' => [
                'Attendant' => ['allocated_posts' => 9, 'actual_in_post' => 3, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 3, 'actual_in_post' => 3, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 23, 'actual_in_post' => 13, 'notes' => null],
                'Nursing Sister/Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 4, 'actual_in_post' => 4, 'notes' => null],
            ],
            'Dental — Emergency Unit' => [
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 9, 'actual_in_post' => 3, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 7, 'actual_in_post' => 1, 'notes' => null],
                'Nursing Sister/Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
            ],
            'Dental — Office' => [
                'Medical Administrator ( Deputy Grade)' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Development Officer' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 4, 'actual_in_post' => 1, 'notes' => null],
                'KKS' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Public Health Management Assistant' => ['allocated_posts' => 7, 'actual_in_post' => 2, 'notes' => null],
                'Matron' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
            ],
            'Dental — Operation Theater' => [
                'Dental Surgeon' => ['allocated_posts' => 2, 'actual_in_post' => 0, 'notes' => 'Includes Dental Consultant — Restorative Dentistry (0 in position, 1 required); Includes Dental Consultant — Orthodontics (0 in position, 1 required)'],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 15, 'actual_in_post' => 5, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 24, 'actual_in_post' => 14, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 5, 'actual_in_post' => 5, 'notes' => null],
            ],
            'Dental — OMF Units' => [
                'Dental Surgeon' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => 'Includes Dental Consultant — Oral Maxillofacial Surgery (0 in position, 1 required)'],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 2, 'actual_in_post' => 0, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
            ],
            'Dental OPD' => [
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 4, 'actual_in_post' => 2, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 9, 'actual_in_post' => 6, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 3, 'actual_in_post' => 3, 'notes' => null],
            ],
            'Hemodialysis Unit' => [
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 3, 'actual_in_post' => 1, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 10, 'actual_in_post' => 4, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 10, 'actual_in_post' => 8, 'notes' => null],
                'Nursing Sister/Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
            ],
            'ECG Unit' => [
                'ECG Recordist' => ['allocated_posts' => 22, 'actual_in_post' => 8, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 6, 'actual_in_post' => 4, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 3, 'actual_in_post' => 3, 'notes' => null],
            ],
            'EEG Unit' => [
                'EEG Recordist' => ['allocated_posts' => 3, 'actual_in_post' => 2, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
            ],
            'Finance Unit' => [
                'Accountant' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'Development Officer' => ['allocated_posts' => 6, 'actual_in_post' => 6, 'notes' => null],
                'ICT Assistant' => ['allocated_posts' => 5, 'actual_in_post' => 1, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 3, 'actual_in_post' => 1, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 4, 'actual_in_post' => 4, 'notes' => null],
                'Public Health Management Assistant' => ['allocated_posts' => 22, 'actual_in_post' => 14, 'notes' => null],
            ],
            'General ICU' => [
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 26, 'actual_in_post' => 7, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 28, 'actual_in_post' => 10, 'notes' => '+7 PGIM trainee(s) attached, not counted in total'],
                'Nursing Officer' => ['allocated_posts' => 51, 'actual_in_post' => 42, 'notes' => null],
                'Nursing Sister/Master' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 5, 'actual_in_post' => 5, 'notes' => null],
            ],
            'Health Information Unit (IT)' => [
                'Medical consultant' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => 'Incl. Consultants Health Informaticians: 1 in post, 0 req.'],
                'Development Officer' => ['allocated_posts' => 3, 'actual_in_post' => 2, 'notes' => null],
            ],
            'JMO Unit' => [
                'Medical consultant' => ['allocated_posts' => 2, 'actual_in_post' => 1, 'notes' => 'Incl. Consultant Judicial Medical Officers: 1 in post, 1 req.'],
                'Development Officer' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 3, 'actual_in_post' => 1, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 7, 'actual_in_post' => 3, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
            ],
            'Cytology Lab' => [
                'Medical Laboratory Technologist' => ['allocated_posts' => 3, 'actual_in_post' => 1, 'notes' => null],
            ],
            'Microbiology Laboratory' => [
                'Medical consultant' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => 'Incl. Consultant Clinical Microbiologists: 0 in post, 1 req.'],
                'Health Laboratory Aid' => ['allocated_posts' => 14, 'actual_in_post' => 4, 'notes' => null],
                'Medical Laboratory Technologist' => ['allocated_posts' => 19, 'actual_in_post' => 6, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 4, 'actual_in_post' => 2, 'notes' => '+2 PGIM trainee(s) attached, not counted in total'],
            ],
            'Histopathology Lab' => [
                'Medical consultant' => ['allocated_posts' => 2, 'actual_in_post' => 1, 'notes' => 'Incl. Consultant Histopathologists: 1 in post, 1 req.'],
                'Medical Laboratory Technologist' => ['allocated_posts' => 9, 'actual_in_post' => 2, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 0, 'actual_in_post' => 0, 'notes' => '+13 PGIM trainee(s) attached, not counted in total'],
            ],
            'Chemical Pathology / Bio Chemistry Laboratory' => [
                'Medical consultant' => ['allocated_posts' => 1, 'actual_in_post' => 2, 'notes' => 'Incl. Consultant Chemical Pathologists: 0 in post, 1 req.'],
                'Health Laboratory Aid' => ['allocated_posts' => 7, 'actual_in_post' => 2, 'notes' => null],
                'Medical Laboratory Technologist' => ['allocated_posts' => 19, 'actual_in_post' => 6, 'notes' => null],
            ],
            'General Laboratory' => [
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 9, 'actual_in_post' => 5, 'notes' => null],
                'Medical Laboratory Technologist' => ['allocated_posts' => 11, 'actual_in_post' => 7, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 8, 'actual_in_post' => 3, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 4, 'actual_in_post' => 4, 'notes' => null],
            ],
            'Hematology Laboratory' => [
                'Health Laboratory Aid' => ['allocated_posts' => 8, 'actual_in_post' => 3, 'notes' => null],
                'Medical Laboratory Technologist' => ['allocated_posts' => 16, 'actual_in_post' => 6, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 9, 'actual_in_post' => 3, 'notes' => null],
            ],
            'Hospital Maintainance — Electrical Technical Unit' => [
                'Electrician' => ['allocated_posts' => 4, 'actual_in_post' => 1, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 6, 'actual_in_post' => 1, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
            ],
            'Hospital Maintainance — Painting Unit' => [
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 3, 'actual_in_post' => 0, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
            ],
            'Hospital Maintainance — Oxygen Unit' => [
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 6, 'actual_in_post' => 0, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 3, 'actual_in_post' => 3, 'notes' => null],
            ],
            'Hospital Maintainance — Carpentry Unit' => [
                'Carpenter' => ['allocated_posts' => 3, 'actual_in_post' => 1, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 3, 'actual_in_post' => 0, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
            ],
            'Hospital Maintainance — Plumber' => [
                'Plumber/pump Machine Operator' => ['allocated_posts' => 2, 'actual_in_post' => 0, 'notes' => null],
            ],
            'Quality Management Unit' => [
                'Development Officer' => ['allocated_posts' => 2, 'actual_in_post' => 1, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 2, 'actual_in_post' => 0, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 6, 'actual_in_post' => 3, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
            ],
            'Planning Unit' => [
                'Development Officer' => ['allocated_posts' => 4, 'actual_in_post' => 2, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 2, 'actual_in_post' => 0, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
            ],
            'Other — LMC' => [
                'Nursing Officer' => ['allocated_posts' => 3, 'actual_in_post' => 2, 'notes' => null],
            ],
            'Other — Lift Operator' => [
                'Lift Operator' => ['allocated_posts' => 13, 'actual_in_post' => 4, 'notes' => null],
            ],
            'Other — B.M.E Units' => [
                'Bio Medical Engineer' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Bio Medical Engineering Assistant' => ['allocated_posts' => 4, 'actual_in_post' => 0, 'notes' => null],
            ],
            'Other — House Warden' => [
                'House warden' => ['allocated_posts' => 14, 'actual_in_post' => 3, 'notes' => null],
            ],
            'Public Health Unit' => [
                'Development Officer' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 3, 'actual_in_post' => 2, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Public Health Field Officer' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'Public Health Inspector' => ['allocated_posts' => 2, 'actual_in_post' => 1, 'notes' => null],
            ],
            'Infection Control Unit' => [
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 2, 'actual_in_post' => 0, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 5, 'actual_in_post' => 3, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
            ],
            'Health Education Unit' => [
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 4, 'actual_in_post' => 3, 'notes' => null],
            ],
            'Medical Records Room' => [
                'Medical Record Assistant' => ['allocated_posts' => 10, 'actual_in_post' => 2, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
            ],
            'Medical Records Room — Office Records Room' => [
                'Book Binder' => ['allocated_posts' => 2, 'actual_in_post' => 0, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
            ],
            'Telephone Exchange' => [
                'Boiler' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'Telephone Operator' => ['allocated_posts' => 17, 'actual_in_post' => 3, 'notes' => null],
            ],
            'General OPD' => [
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 30, 'actual_in_post' => 5, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 61, 'actual_in_post' => 31, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 33, 'actual_in_post' => 16, 'notes' => null],
                'Nursing Sister/Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 17, 'actual_in_post' => 17, 'notes' => null],
            ],
            'SBU (Sick Baby Unit)' => [
                'Medical consultant' => ['allocated_posts' => 7, 'actual_in_post' => 2, 'notes' => 'Incl. Consultant Neonatologists: 0 in post, 3 req.; Incl. Consultant Paediatricians: 2 in post, 2 req.'],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 24, 'actual_in_post' => 7, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 24, 'actual_in_post' => 10, 'notes' => '+8 PGIM trainee(s) attached, not counted in total'],
                'Nursing Officer' => ['allocated_posts' => 45, 'actual_in_post' => 33, 'notes' => null],
                'Nursing Sister/Master' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
            ],
            'Clinic Pharmacy' => [
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 3, 'actual_in_post' => 0, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 6, 'actual_in_post' => 6, 'notes' => null],
                'Pharmacist' => ['allocated_posts' => 22, 'actual_in_post' => 9, 'notes' => null],
            ],
            'OPD Pharmacy' => [
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 5, 'actual_in_post' => 5, 'notes' => null],
                'Pharmacist' => ['allocated_posts' => 25, 'actual_in_post' => 7, 'notes' => null],
            ],
            'OPD Pharmacy — Staff Pharmacy' => [
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'Pharmacist' => ['allocated_posts' => 4, 'actual_in_post' => 2, 'notes' => null],
            ],
            'OPD Pharmacy — General Pharmacy Store' => [
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 9, 'actual_in_post' => 5, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 3, 'actual_in_post' => 3, 'notes' => null],
                'Pharmacist' => ['allocated_posts' => 17, 'actual_in_post' => 7, 'notes' => null],
                'Special Grade Pharmacist' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
            ],
            'OPD Pharmacy — Indoor Pharmacy' => [
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 3, 'actual_in_post' => 2, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
                'Pharmacist' => ['allocated_posts' => 12, 'actual_in_post' => 5, 'notes' => null],
            ],
            'OPD Pharmacy — Dental Store' => [
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 2, 'actual_in_post' => 1, 'notes' => null],
                'Pharmacist' => ['allocated_posts' => 3, 'actual_in_post' => 1, 'notes' => null],
            ],
            'OPD Pharmacy — Dental Pharmacy' => [
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'Pharmacist' => ['allocated_posts' => 3, 'actual_in_post' => 1, 'notes' => null],
            ],
            'OPD Pharmacy — Drug Information' => [
                'Pharmacist' => ['allocated_posts' => 9, 'actual_in_post' => 2, 'notes' => null],
            ],
            'Physiotheraphy Unit' => [
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 7, 'actual_in_post' => 2, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 3, 'actual_in_post' => 3, 'notes' => null],
                'Physiotherapist' => ['allocated_posts' => 34, 'actual_in_post' => 8, 'notes' => null],
            ],
            'Xray' => [
                'Medical consultant' => ['allocated_posts' => 4, 'actual_in_post' => 2, 'notes' => 'Incl. Consultant Radiologists: 2 in post, 2 req.'],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 33, 'actual_in_post' => 7, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 8, 'actual_in_post' => 8, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 12, 'actual_in_post' => 5, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 10, 'actual_in_post' => 10, 'notes' => null],
                'Radiographer' => ['allocated_posts' => 52, 'actual_in_post' => 17, 'notes' => null],
            ],
            'Occupational Theraphy Unit' => [
                'Occupational Therapist' => ['allocated_posts' => 19, 'actual_in_post' => 5, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
            ],
            'Speech Theraphy Unit' => [
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Speech Therapist' => ['allocated_posts' => 6, 'actual_in_post' => 0, 'notes' => null],
            ],
            'Laundry' => [
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 11, 'actual_in_post' => 1, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 5, 'actual_in_post' => 5, 'notes' => null],
            ],
            'Hospital Diet Branch' => [
                'Ward Clerk' => ['allocated_posts' => 18, 'actual_in_post' => 5, 'notes' => null],
            ],
            'Hospital Kitchen' => [
                'cooks' => ['allocated_posts' => 30, 'actual_in_post' => 5, 'notes' => null],
                'Diet Stewards' => ['allocated_posts' => 9, 'actual_in_post' => 1, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 15, 'actual_in_post' => 2, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 4, 'actual_in_post' => 4, 'notes' => null],
            ],
            'Sewing Unit' => [
                'Sewing Operator' => ['allocated_posts' => 16, 'actual_in_post' => 3, 'notes' => null],
            ],
            'General Operating Theater' => [
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 58, 'actual_in_post' => 28, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 86, 'actual_in_post' => 35, 'notes' => '+5 PGIM trainee(s) attached, not counted in total'],
                'Nursing Officer' => ['allocated_posts' => 93, 'actual_in_post' => 55, 'notes' => null],
                'Nursing Sister/Master' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 12, 'actual_in_post' => 12, 'notes' => null],
            ],
            'General Operating Theater — EOT' => [
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 13, 'actual_in_post' => 5, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 31, 'actual_in_post' => 9, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
            ],
            'General Operating Theater — New Labour Room OT' => [
                'Medical consultant' => ['allocated_posts' => 2, 'actual_in_post' => 0, 'notes' => 'Incl. Consultant Anaesthetists: 0 in post, 2 req.'],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 10, 'actual_in_post' => 0, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 15, 'actual_in_post' => 0, 'notes' => null],
                'Midwife' => ['allocated_posts' => 16, 'actual_in_post' => 0, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 30, 'actual_in_post' => 0, 'notes' => null],
                'Nursing Sister/Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
            ],
            'Transport Unit' => [
                'Health Driver' => ['allocated_posts' => 38, 'actual_in_post' => 18, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 7, 'actual_in_post' => 3, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 6, 'actual_in_post' => 6, 'notes' => null],
            ],
            'Clinic — Surgical Clinic' => [
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 2, 'actual_in_post' => 1, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 3, 'actual_in_post' => 2, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 4, 'actual_in_post' => 4, 'notes' => null],
            ],
            'Clinic — EMG Clinic' => [
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 2, 'actual_in_post' => 0, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
            ],
            'Clinic — Gyn & Obs Clinic' => [
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 2, 'actual_in_post' => 1, 'notes' => null],
                'Midwife' => ['allocated_posts' => 4, 'actual_in_post' => 1, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 4, 'actual_in_post' => 2, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 3, 'actual_in_post' => 3, 'notes' => null],
            ],
            'Clinic — Paediatrics Clinic' => [
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 3, 'actual_in_post' => 2, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
            ],
            'Clinic — Medical Clinic' => [
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 3, 'actual_in_post' => 1, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 14, 'actual_in_post' => 4, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 10, 'actual_in_post' => 6, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 6, 'actual_in_post' => 6, 'notes' => null],
            ],
            'Clinic — Dermatology Clinic' => [
                'Medical consultant' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => 'Incl. Consultant Dermatologists: 0 in post, 1 req.'],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
            ],
            'Clinic — Elderly Clinic' => [
                'Medical consultant' => ['allocated_posts' => 2, 'actual_in_post' => 1, 'notes' => 'Incl. Geriatric Medicine: 0 in post, 2 req.'],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 2, 'actual_in_post' => 0, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => '+10 PGIM trainee(s) attached, not counted in total'],
                'Nursing Officer' => ['allocated_posts' => 3, 'actual_in_post' => 2, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 3, 'actual_in_post' => 0, 'notes' => null],
            ],
            'VP OPD Clinic' => [
                'Medical consultant' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => 'Incl. Consultant General Physicians: 2 in post, 0 req.'],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 3, 'actual_in_post' => 0, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 9, 'actual_in_post' => 4, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 4, 'actual_in_post' => 2, 'notes' => null],
                'Nursing Sister/Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
            ],
            'Psychiatry Clinic / Sithsuwa' => [
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 2, 'actual_in_post' => 0, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 14, 'actual_in_post' => 8, 'notes' => '+6 PGIM trainee(s) attached, not counted in total'],
                'Nursing Officer' => ['allocated_posts' => 3, 'actual_in_post' => 2, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 3, 'actual_in_post' => 3, 'notes' => null],
                'Psychiatric social Worker' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
            ],
            'Clinical Nutrition Clinic' => [
                'Medical consultant' => ['allocated_posts' => 2, 'actual_in_post' => 1, 'notes' => 'Incl. Consultant Nutrition Physician: 0 in post, 2 req.'],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 3, 'actual_in_post' => 1, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Nutritionist' => ['allocated_posts' => 2, 'actual_in_post' => 1, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
            ],
            'Sports Medicine Clinic' => [
                'Medical consultant' => ['allocated_posts' => 2, 'actual_in_post' => 1, 'notes' => 'Incl. Sports & Exercise Medicine: 0 in post, 2 req.'],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 3, 'actual_in_post' => 0, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 5, 'actual_in_post' => 1, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
            ],
            'Mithuru Piyasa' => [
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 2, 'actual_in_post' => 1, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
            ],
            'Pain Management Clinic' => [
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 2, 'actual_in_post' => 0, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 3, 'actual_in_post' => 1, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 5, 'actual_in_post' => 2, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
            ],
            'Endocrinology Unit' => [
                'Medical consultant' => ['allocated_posts' => 2, 'actual_in_post' => 0, 'notes' => 'Incl. Consultant Endocrinologist: 0 in post, 2 req.'],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 2, 'actual_in_post' => 0, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 3, 'actual_in_post' => 2, 'notes' => null],
            ],
            'Obstetrics and Gynecology (New) Unit' => [
                'Medical consultant' => ['allocated_posts' => 4, 'actual_in_post' => 2, 'notes' => 'Incl. Consultant Obstetricians & Gynaecologists: 2 in post, 2 req.'],
                'Medical Officer' => ['allocated_posts' => 11, 'actual_in_post' => 5, 'notes' => 'PGIM requirement noted (+1), none currently attached, not counted in total'],
            ],
            'Obstetrics and Gynecology (New) — Wd 18 (Female)' => [
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 3, 'actual_in_post' => 3, 'notes' => null],
                'Midwife' => ['allocated_posts' => 22, 'actual_in_post' => 10, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 18, 'actual_in_post' => 11, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 3, 'actual_in_post' => 3, 'notes' => null],
            ],
            'Obstetrics and Gynecology (New) — Wd 19' => [
                'Attendant' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 4, 'actual_in_post' => 4, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 32, 'actual_in_post' => 12, 'notes' => null],
                'Nursing Sister/Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
            ],
            'Orthopedics' => [
                'Medical consultant' => ['allocated_posts' => 2, 'actual_in_post' => 3, 'notes' => 'Incl. Consultant Orthopaedic Surgeons: 2 in post, 0 req.'],
                'Medical Officer' => ['allocated_posts' => 19, 'actual_in_post' => 7, 'notes' => 'PGIM requirement noted (+2), none currently attached, not counted in total'],
            ],
            'Orthopedics — Wd 16' => [
                'Attendant' => ['allocated_posts' => 5, 'actual_in_post' => 5, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 13, 'actual_in_post' => 3, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 22, 'actual_in_post' => 12, 'notes' => null],
                'Nursing Sister/Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'Orthopaedic Technician' => ['allocated_posts' => 2, 'actual_in_post' => 0, 'notes' => null],
            ],
            'Orthopedics — Wd 15' => [
                'Attendant' => ['allocated_posts' => 12, 'actual_in_post' => 2, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 14, 'actual_in_post' => 4, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 24, 'actual_in_post' => 13, 'notes' => null],
                'Nursing Sister/Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 13, 'actual_in_post' => 3, 'notes' => null],
            ],
            'Neurology' => [
                'Medical consultant' => ['allocated_posts' => 2, 'actual_in_post' => 1, 'notes' => 'Incl. Consultant Neurologists: 0 in post, 2 req.'],
                'Medical Officer' => ['allocated_posts' => 12, 'actual_in_post' => 6, 'notes' => null],
            ],
            'Neurology — Wd 12 (Female)' => [
                'Attendant' => ['allocated_posts' => 2, 'actual_in_post' => 1, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 21, 'actual_in_post' => 5, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 21, 'actual_in_post' => 9, 'notes' => null],
                'Nursing Sister/Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
            ],
            'Neurology — Wd 12 (Male)' => [
                'Attendant' => ['allocated_posts' => 4, 'actual_in_post' => 2, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 22, 'actual_in_post' => 9, 'notes' => null],
                'Nursing Sister/Master' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
            ],
            'General Pediatrics (Wd 4 & Wd 5)' => [
                'Attendant' => ['allocated_posts' => 7, 'actual_in_post' => 2, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 16, 'actual_in_post' => 6, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 14, 'actual_in_post' => 6, 'notes' => '+7 PGIM trainee(s) attached, not counted in total'],
                'Nursing Officer' => ['allocated_posts' => 49, 'actual_in_post' => 27, 'notes' => null],
                'Nursing Sister/Master' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 13, 'actual_in_post' => 3, 'notes' => null],
            ],
            'Obstetrics and Gynecology (Prof) Unit' => [
                'Medical Officer' => ['allocated_posts' => 13, 'actual_in_post' => 5, 'notes' => '+1 PGIM trainee(s) attached, not counted in total'],
            ],
            'Obstetrics and Gynecology (Prof) — Wd 3 (Female)' => [
                'Attendant' => ['allocated_posts' => 7, 'actual_in_post' => 2, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 12, 'actual_in_post' => 3, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 29, 'actual_in_post' => 15, 'notes' => null],
                'Nursing Sister/Master' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 3, 'actual_in_post' => 3, 'notes' => null],
            ],
            'Obstetrics and Gynecology (Prof) — Wd 11' => [
                'Attendant' => ['allocated_posts' => 4, 'actual_in_post' => 1, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 15, 'actual_in_post' => 2, 'notes' => null],
                'Midwife' => ['allocated_posts' => 13, 'actual_in_post' => 7, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 50, 'actual_in_post' => 20, 'notes' => null],
                'Nursing Sister/Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
            ],
            'Obstetrics and Gynecology (Prof) — Wd 10' => [
                'Attendant' => ['allocated_posts' => 4, 'actual_in_post' => 1, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 32, 'actual_in_post' => 5, 'notes' => null],
                'Midwife' => ['allocated_posts' => 13, 'actual_in_post' => 6, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 48, 'actual_in_post' => 18, 'notes' => null],
                'Nursing Sister/Master' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 3, 'actual_in_post' => 3, 'notes' => null],
            ],
            'Clinical Hematology Unit' => [
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 4, 'actual_in_post' => 0, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 11, 'actual_in_post' => 4, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 5, 'actual_in_post' => 3, 'notes' => null],
                'Nursing Sister/Master' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
            ],
            'General Surgery' => [
                'Medical Officer' => ['allocated_posts' => 16, 'actual_in_post' => 4, 'notes' => null],
            ],
            'General Surgery — Wd 1' => [
                'Attendant' => ['allocated_posts' => 10, 'actual_in_post' => 2, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 30, 'actual_in_post' => 7, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 47, 'actual_in_post' => 27, 'notes' => null],
                'Nursing Sister/Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 3, 'actual_in_post' => 3, 'notes' => null],
            ],
            'General Surgery — Wd 2' => [
                'Attendant' => ['allocated_posts' => 10, 'actual_in_post' => 3, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 24, 'actual_in_post' => 4, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 45, 'actual_in_post' => 24, 'notes' => null],
                'Nursing Sister/Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 3, 'actual_in_post' => 3, 'notes' => null],
            ],
            'General Surgery — Wd 20' => [
                'Attendant' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 11, 'actual_in_post' => 3, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 15, 'actual_in_post' => 8, 'notes' => null],
                'Nursing Sister/Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
            ],
            'General Surgery — Wd 21' => [
                'Attendant' => ['allocated_posts' => 3, 'actual_in_post' => 1, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 11, 'actual_in_post' => 2, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 15, 'actual_in_post' => 8, 'notes' => null],
                'Nursing Sister/Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
            ],
            'Plastic Surgery — Wd 22 (Male / Female)' => [
                'Medical consultant' => ['allocated_posts' => 2, 'actual_in_post' => 0, 'notes' => 'Incl. Consultant Plastic Surgeon: 0 in post, 2 req.'],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 12, 'actual_in_post' => 2, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 10, 'actual_in_post' => 2, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 11, 'actual_in_post' => 6, 'notes' => null],
                'Nursing Sister/Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 3, 'actual_in_post' => 3, 'notes' => null],
            ],
            'General Medicine' => [
                'Medical consultant' => ['allocated_posts' => 3, 'actual_in_post' => 2, 'notes' => 'Incl. Consultant General Physicians: 2 in post, 0 req.; Incl. Consultant Rheumatologists: 0 in post, 1 req.'],
                'Medical Officer' => ['allocated_posts' => 25, 'actual_in_post' => 9, 'notes' => '+5 PGIM trainee(s) attached, not counted in total'],
            ],
            'General Medicine — Medical Ward (New) Female' => [
                'Attendant' => ['allocated_posts' => 3, 'actual_in_post' => 0, 'notes' => null],
                'Medical consultant' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => 'Incl. Consultant General Physicians: 0 in post, 1 req.'],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 15, 'actual_in_post' => 0, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 3, 'actual_in_post' => 0, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 33, 'actual_in_post' => 0, 'notes' => null],
                'Nursing Sister/Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
            ],
            'General Medicine — Wd 7 (Female)' => [
                'Attendant' => ['allocated_posts' => 9, 'actual_in_post' => 4, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 31, 'actual_in_post' => 7, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 54, 'actual_in_post' => 28, 'notes' => null],
                'Nursing Sister/Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 6, 'actual_in_post' => 6, 'notes' => null],
            ],
            'General Medicine — Wd 8 (Male)' => [
                'Attendant' => ['allocated_posts' => 5, 'actual_in_post' => 0, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 38, 'actual_in_post' => 10, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 88, 'actual_in_post' => 32, 'notes' => null],
                'Nursing Sister/Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 4, 'actual_in_post' => 4, 'notes' => null],
            ],
            'General Medicine — Medical Ward (New) Male' => [
                'Attendant' => ['allocated_posts' => 3, 'actual_in_post' => 0, 'notes' => null],
                'Medical consultant' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => 'Incl. Consultant General Physicians: 0 in post, 1 req.'],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 15, 'actual_in_post' => 0, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 5, 'actual_in_post' => 0, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 34, 'actual_in_post' => 0, 'notes' => null],
                'Nursing Sister/Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
            ],
            'Psychiatry Unit' => [
                'Medical Officer' => ['allocated_posts' => 19, 'actual_in_post' => 7, 'notes' => '+4 PGIM trainee(s) attached, not counted in total'],
            ],
            'Psychiatry Unit — Wd 6 (Male)' => [
                'Attendant' => ['allocated_posts' => 2, 'actual_in_post' => 1, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 13, 'actual_in_post' => 6, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 15, 'actual_in_post' => 10, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 5, 'actual_in_post' => 5, 'notes' => null],
            ],
            'Psychiatry Unit — Wd 9 (Female)' => [
                'Attendant' => ['allocated_posts' => 2, 'actual_in_post' => 1, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 11, 'actual_in_post' => 4, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 26, 'actual_in_post' => 10, 'notes' => null],
                'Nursing Sister/Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
                'Psychiatric social Worker' => ['allocated_posts' => 4, 'actual_in_post' => 2, 'notes' => null],
            ],
            'Toxicology — Wd 17' => [
                'Attendant' => ['allocated_posts' => 3, 'actual_in_post' => 2, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 7, 'actual_in_post' => 4, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 21, 'actual_in_post' => 11, 'notes' => '+4 PGIM trainee(s) attached, not counted in total'],
                'Nursing Officer' => ['allocated_posts' => 43, 'actual_in_post' => 17, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
            ],
            'Cardiology Unit' => [
                'Medical consultant' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => 'Incl. Consultant Cardiologists: 0 in post, 1 req.'],
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 3, 'actual_in_post' => 0, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 4, 'actual_in_post' => 2, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 5, 'actual_in_post' => 2, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
            ],
            'Nephrology Unit — Wd 23 (Male / Female)' => [
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 19, 'actual_in_post' => 6, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 15, 'actual_in_post' => 5, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 40, 'actual_in_post' => 20, 'notes' => null],
                'Nursing Sister/Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 3, 'actual_in_post' => 3, 'notes' => null],
            ],
            'Labour Room — Common (Not attached to a ward)' => [
                'Saukyaya Karyaya Sahayaka(Junior)' => ['allocated_posts' => 18, 'actual_in_post' => 8, 'notes' => null],
                'Midwife' => ['allocated_posts' => 23, 'actual_in_post' => 9, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 27, 'actual_in_post' => 25, 'notes' => null],
                'Nursing Sister/Master' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 4, 'actual_in_post' => 4, 'notes' => null],
            ],
            'Unallocated / Shared Cadre' => [
                'Medical consultant' => ['allocated_posts' => 10, 'actual_in_post' => 0, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 0, 'actual_in_post' => 0, 'notes' => 'PGIM requirement noted (+73), none currently attached, not counted in total'],
                'Saukyaya Karyaya Sahayaka(Ordinary)' => ['allocated_posts' => 197, 'actual_in_post' => 0, 'notes' => null],
            ],
        ];
    }
}
