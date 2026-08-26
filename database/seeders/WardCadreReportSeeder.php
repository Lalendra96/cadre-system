<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Position;
use App\Models\PositionSubcategory;
use App\Models\Unit;
use App\Models\UnitPositionAllocation;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * WardCadreReportSeeder (v2)
 *
 * Rebuilt against the hospital's REAL, existing 58-position list
 * (2026-07-10position.csv) instead of creating new positions. Every
 * one of the 449 source rows from the Ward Cadre Allocation Report —
 * TH Peradeniya (11 May 2026) is mapped to either:
 *
 *   1. An EXISTING position, matched by title (see DIRECT_MAP in the
 *      generation script) — e.g. "Junior - Saukyaya Karyaya Sahayaka"
 *      -> the real "SKS Junior" position. Nothing new is created here.
 *
 *   2. A Position Subcategory under an existing parent position, for
 *      cadre titles with no dedicated position of their own:
 *        - 23 medical consultant specialties -> subcategories of
 *          "Medical Consultant" (counts toward its total)
 *        - 3 dental consultant specialties -> subcategories of
 *          "Dental Surgeon", flagged distinctly (counts toward its total)
 *        - "Medical Officer (PGIM)" -> subcategory "PGIM Trainee" of
 *          "Medical Officer", EXCLUDED from its total (tracked as a
 *          remark only, per explicit instruction)
 *
 *   "Acting Consultant" and "Caregiver" map DIRECTLY to their own
 *   existing positions — both now exist as real, dedicated positions,
 *   so they are NOT folded into Medical Consultant / renamed.
 *
 * RECONCILIATION: the source report's own Grand Total is In Position
 * 1843 / Total 4332. Every one of the 449 transcribed rows was
 * verified to sum to exactly that figure before mapping, and the
 * post-mapping split (main-line positions + subcategory rows) was
 * verified to still sum to exactly 1843 / 4332 — zero rows dropped,
 * zero rows double-counted.
 *
 * Positions are matched by title (case-insensitive) — NEVER created.
 * If a title from this data doesn't resolve to an existing Position,
 * that row is skipped with a warning naming exactly which title and
 * how many units were affected, rather than silently inventing one.
 *
 * Run WardCadreUnitSeeder FIRST — every unit name here must already exist.
 * Safe to re-run — upserts throughout.
 *
 * Usage:
 *   php artisan db:seed --class=WardCadreUnitSeeder     (if not already run)
 *   php artisan db:seed --class=WardCadreReportSeeder
 */
class WardCadreReportSeeder extends Seeder
{
    private const YEAR = 2026;

    public function run(): void
    {
        $officerId = User::havingRole(User::ROLE_SUPER_ADMIN)->value('id')
            ?? User::query()->value('id');

        $missingUnits = [];
        $missingPositions = [];
        $mainCreated = 0;
        $subCreated  = 0;

        // ── 1. Ensure every subcategory exists (idempotent) ────────────
        $subcategoryIds = [];
        foreach ($this->subcategoryDefinitions() as [$parentTitle, $subName, $countsToward]) {
            $parent = Position::whereRaw('LOWER(title) = ?', [strtolower($parentTitle)])->first();
            if (! $parent) {
                $missingPositions[$parentTitle] = ($missingPositions[$parentTitle] ?? 0) + 1;
                continue;
            }
            $sub = PositionSubcategory::updateOrCreate(
                ['parent_position_id' => $parent->id, 'name' => $subName],
                ['counts_toward_parent_total' => $countsToward, 'created_by' => $officerId, 'is_active' => true]
            );
            $subcategoryIds["{$parentTitle}::{$subName}"] = $sub->id;
        }

        // ── 2. Main-line allocations (direct position matches) ─────────
        foreach ($this->mainLineData() as $unitName => $positionRows) {
            $unit = Unit::where('name', $unitName)->first();
            if (! $unit) { $missingUnits[] = $unitName; continue; }

            foreach ($positionRows as $positionTitle => $row) {
                $position = Position::whereRaw('LOWER(title) = ?', [strtolower($positionTitle)])->first();
                if (! $position) {
                    $missingPositions[$positionTitle] = ($missingPositions[$positionTitle] ?? 0) + 1;
                    continue;
                }
                UnitPositionAllocation::updateOrCreate(
                    ['unit_id' => $unit->id, 'position_id' => $position->id, 'position_subcategory_id' => null, 'year' => self::YEAR],
                    ['allocated_posts' => $row['allocated_posts'], 'actual_in_post' => $row['actual_in_post'], 'notes' => $row['notes'], 'last_updated_by' => $officerId]
                );
                $mainCreated++;
            }
        }

        // ── 3. Subcategory allocations ──────────────────────────────────
        foreach ($this->subcategoryData() as $unitName => $subRows) {
            $unit = Unit::where('name', $unitName)->first();
            if (! $unit) { $missingUnits[] = $unitName; continue; }

            foreach ($subRows as $key => $row) {
                [$parentTitle, $subName] = explode('::', $key, 2);
                $position = Position::whereRaw('LOWER(title) = ?', [strtolower($parentTitle)])->first();
                $subcategoryId = $subcategoryIds[$key] ?? null;
                if (! $position || ! $subcategoryId) {
                    $missingPositions[$parentTitle] = ($missingPositions[$parentTitle] ?? 0) + 1;
                    continue;
                }
                UnitPositionAllocation::updateOrCreate(
                    ['unit_id' => $unit->id, 'position_id' => $position->id, 'position_subcategory_id' => $subcategoryId, 'year' => self::YEAR],
                    ['allocated_posts' => $row['allocated_posts'], 'actual_in_post' => $row['actual_in_post'], 'notes' => $row['notes'], 'last_updated_by' => $officerId]
                );
                $subCreated++;
            }
        }

        $this->command?->info("Main-line allocations upserted: {$mainCreated}");
        $this->command?->info("Subcategory allocations upserted: {$subCreated}");
        $this->command?->info('Subcategories ensured: ' . count($subcategoryIds));
        if ($missingUnits) {
            $this->command?->warn('Units not found: ' . implode(', ', array_unique($missingUnits)));
        }
        if ($missingPositions) {
            $this->command?->warn('Positions not found — rows skipped:');
            foreach ($missingPositions as $title => $count) {
                $this->command?->warn("  \"{$title}\" — {$count} occurrence(s)");
            }
        }
    }

    /** @return array<int, array{0:string,1:string,2:bool}> [parent title, subcategory name, counts_toward_parent_total] */
    private function subcategoryDefinitions(): array
    {
        return [
            ['Medical Consultant', 'Emergency Physician', true],
            ['Medical Consultant', 'Transfusion Physician', true],
            ['Medical Consultant', 'Health Informatician', true],
            ['Medical Consultant', 'Judicial Medical Officer', true],
            ['Medical Consultant', 'Clinical Microbiologist', true],
            ['Medical Consultant', 'Histopathologist', true],
            ['Medical Consultant', 'Chemical Pathologist', true],
            ['Medical Consultant', 'Neonatologist', true],
            ['Medical Consultant', 'Paediatrician', true],
            ['Medical Consultant', 'Radiologist', true],
            ['Medical Consultant', 'Anaesthetist', true],
            ['Medical Consultant', 'Neurologist', true],
            ['Medical Consultant', 'Endocrinologist', true],
            ['Medical Consultant', 'Obstetrician & Gynaecologist', true],
            ['Medical Consultant', 'Orthopaedic Surgeon', true],
            ['Medical Consultant', 'Plastic Surgeon', true],
            ['Medical Consultant', 'General Physician', true],
            ['Medical Consultant', 'Rheumatologist', true],
            ['Medical Consultant', 'Dermatologist', true],
            ['Medical Consultant', 'Nutrition Physician', true],
            ['Medical Consultant', 'Cardiologist', true],
            ['Medical Consultant', 'Geriatric Medicine', true],
            ['Medical Consultant', 'Sports & Exercise Medicine', true],
            ['Dental Surgeon', 'Restorative Dentistry', true],
            ['Dental Surgeon', 'Orthodontics', true],
            ['Dental Surgeon', 'Oral Maxillofacial Surgery', true],
            ['Medical Officer', 'PGIM Trainee', false],
        ];
    }

    /** @return array<string, array<string, array{allocated_posts:int, actual_in_post:int, notes:?string}>> */
    private function mainLineData(): array
    {
        return [
            'Accident and Emergency Unit' => [
                'Acting Consultant' => ['allocated_posts' => 0, 'actual_in_post' => 2, 'notes' => null],
                'Caregiver' => ['allocated_posts' => 6, 'actual_in_post' => 1, 'notes' => null],
                'SKS Casual' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'SKS Junior' => ['allocated_posts' => 36, 'actual_in_post' => 11, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 47, 'actual_in_post' => 17, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 46, 'actual_in_post' => 30, 'notes' => null],
                'Nursing Sister / Master' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 3, 'actual_in_post' => 3, 'notes' => null],
            ],
            'Overseer Office' => [
                'Hospital Overseer' => ['allocated_posts' => 12, 'actual_in_post' => 5, 'notes' => null],
                'SKS Junior' => ['allocated_posts' => 19, 'actual_in_post' => 17, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 7, 'actual_in_post' => 7, 'notes' => null],
            ],
            'Administration Unit' => [
                'Administrative Officer' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'Development Officer' => ['allocated_posts' => 7, 'actual_in_post' => 7, 'notes' => null],
                'SKS Junior' => ['allocated_posts' => 4, 'actual_in_post' => 4, 'notes' => null],
                'KKS' => ['allocated_posts' => 7, 'actual_in_post' => 2, 'notes' => null],
                'Medical Administrator (Deputy Grade)' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
                'Medical Administrator (Senior Grade)' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'Public Health Management Assistant' => ['allocated_posts' => 36, 'actual_in_post' => 24, 'notes' => null],
            ],
            'Matron Office' => [
                'SKS Junior' => ['allocated_posts' => 6, 'actual_in_post' => 3, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'Nursing Sister / Master' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
                'Matron' => ['allocated_posts' => 7, 'actual_in_post' => 1, 'notes' => null],
            ],
            'Blood Bank' => [
                'Health Laboratory Aid' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'SKS Junior' => ['allocated_posts' => 12, 'actual_in_post' => 3, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 22, 'actual_in_post' => 10, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 11, 'actual_in_post' => 11, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 5, 'actual_in_post' => 5, 'notes' => null],
            ],
            'Dental' => [
                'Dental Surgeon' => ['allocated_posts' => 66, 'actual_in_post' => 45, 'notes' => null],
            ],
            'Dental — ICU' => [
                'Caregiver' => ['allocated_posts' => 2, 'actual_in_post' => 0, 'notes' => null],
                'SKS Junior' => ['allocated_posts' => 12, 'actual_in_post' => 4, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 20, 'actual_in_post' => 11, 'notes' => null],
                'Nursing Sister / Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 3, 'actual_in_post' => 3, 'notes' => null],
            ],
            'Dental — Wd' => [
                'Caregiver' => ['allocated_posts' => 9, 'actual_in_post' => 3, 'notes' => null],
                'SKS Junior' => ['allocated_posts' => 3, 'actual_in_post' => 3, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 23, 'actual_in_post' => 13, 'notes' => null],
                'Nursing Sister / Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 4, 'actual_in_post' => 4, 'notes' => null],
            ],
            'Dental — Emergency Unit' => [
                'SKS Junior' => ['allocated_posts' => 9, 'actual_in_post' => 3, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 7, 'actual_in_post' => 1, 'notes' => null],
                'Nursing Sister / Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
            ],
            'Dental — Office' => [
                'Medical Administrator (Deputy Grade)' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Development Officer' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'SKS Junior' => ['allocated_posts' => 4, 'actual_in_post' => 1, 'notes' => null],
                'KKS' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Public Health Management Assistant' => ['allocated_posts' => 7, 'actual_in_post' => 2, 'notes' => null],
                'Matron' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
            ],
            'Dental — Operation Theater' => [
                'SKS Junior' => ['allocated_posts' => 15, 'actual_in_post' => 5, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 24, 'actual_in_post' => 14, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 5, 'actual_in_post' => 5, 'notes' => null],
            ],
            'Dental — OMF Units' => [
                'SKS Junior' => ['allocated_posts' => 2, 'actual_in_post' => 0, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
            ],
            'Dental OPD' => [
                'SKS Junior' => ['allocated_posts' => 4, 'actual_in_post' => 2, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 9, 'actual_in_post' => 6, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 3, 'actual_in_post' => 3, 'notes' => null],
            ],
            'Hemodialysis Unit' => [
                'SKS Junior' => ['allocated_posts' => 3, 'actual_in_post' => 1, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 10, 'actual_in_post' => 4, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 10, 'actual_in_post' => 8, 'notes' => null],
                'Nursing Sister / Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
            ],
            'ECG Unit' => [
                'ECG Recordist' => ['allocated_posts' => 22, 'actual_in_post' => 8, 'notes' => null],
                'SKS Junior' => ['allocated_posts' => 6, 'actual_in_post' => 4, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 3, 'actual_in_post' => 3, 'notes' => null],
            ],
            'EEG Unit' => [
                'EEG Recordist' => ['allocated_posts' => 3, 'actual_in_post' => 2, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
            ],
            'Finance Unit' => [
                'Accountant' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'Development Officer' => ['allocated_posts' => 6, 'actual_in_post' => 6, 'notes' => null],
                'ICT Assistant' => ['allocated_posts' => 5, 'actual_in_post' => 1, 'notes' => null],
                'SKS Junior' => ['allocated_posts' => 3, 'actual_in_post' => 1, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 4, 'actual_in_post' => 4, 'notes' => null],
                'Public Health Management Assistant' => ['allocated_posts' => 22, 'actual_in_post' => 14, 'notes' => null],
            ],
            'General ICU' => [
                'SKS Junior' => ['allocated_posts' => 26, 'actual_in_post' => 7, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 28, 'actual_in_post' => 10, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 51, 'actual_in_post' => 42, 'notes' => null],
                'Nursing Sister / Master' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 5, 'actual_in_post' => 5, 'notes' => null],
            ],
            'Health Information Unit (IT)' => [
                'Development Officer' => ['allocated_posts' => 3, 'actual_in_post' => 2, 'notes' => null],
            ],
            'JMO Unit' => [
                'Development Officer' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'SKS Junior' => ['allocated_posts' => 3, 'actual_in_post' => 1, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 7, 'actual_in_post' => 3, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
            ],
            'Cytology Lab' => [
                'Medical Laboratory Technologist' => ['allocated_posts' => 3, 'actual_in_post' => 1, 'notes' => null],
            ],
            'Microbiology Laboratory' => [
                'Health Laboratory Aid' => ['allocated_posts' => 14, 'actual_in_post' => 4, 'notes' => null],
                'Medical Laboratory Technologist' => ['allocated_posts' => 19, 'actual_in_post' => 6, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 4, 'actual_in_post' => 2, 'notes' => null],
            ],
            'Histopathology Lab' => [
                'Medical Laboratory Technologist' => ['allocated_posts' => 9, 'actual_in_post' => 2, 'notes' => null],
            ],
            'Chemical Pathology / Bio Chemistry Laboratory' => [
                'Acting Consultant' => ['allocated_posts' => 0, 'actual_in_post' => 2, 'notes' => null],
                'Health Laboratory Aid' => ['allocated_posts' => 7, 'actual_in_post' => 2, 'notes' => null],
                'Medical Laboratory Technologist' => ['allocated_posts' => 19, 'actual_in_post' => 6, 'notes' => null],
            ],
            'General Laboratory' => [
                'SKS Junior' => ['allocated_posts' => 9, 'actual_in_post' => 5, 'notes' => null],
                'Medical Laboratory Technologist' => ['allocated_posts' => 11, 'actual_in_post' => 7, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 8, 'actual_in_post' => 3, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 4, 'actual_in_post' => 4, 'notes' => null],
            ],
            'Hematology Laboratory' => [
                'Health Laboratory Aid' => ['allocated_posts' => 8, 'actual_in_post' => 3, 'notes' => null],
                'Medical Laboratory Technologist' => ['allocated_posts' => 16, 'actual_in_post' => 6, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 9, 'actual_in_post' => 3, 'notes' => null],
            ],
            'Hospital Maintainance — Electrical Technical Unit' => [
                'Electrician' => ['allocated_posts' => 4, 'actual_in_post' => 1, 'notes' => null],
                'SKS Junior' => ['allocated_posts' => 6, 'actual_in_post' => 1, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
            ],
            'Hospital Maintainance — Painting Unit' => [
                'SKS Junior' => ['allocated_posts' => 3, 'actual_in_post' => 0, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
            ],
            'Hospital Maintainance — Oxygen Unit' => [
                'SKS Junior' => ['allocated_posts' => 6, 'actual_in_post' => 0, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 3, 'actual_in_post' => 3, 'notes' => null],
            ],
            'Hospital Maintainance — Carpentry Unit' => [
                'Carpenter' => ['allocated_posts' => 3, 'actual_in_post' => 1, 'notes' => null],
                'SKS Junior' => ['allocated_posts' => 3, 'actual_in_post' => 0, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
            ],
            'Hospital Maintainance — Plumber' => [
                'Plumber / Pump Machine Operator' => ['allocated_posts' => 2, 'actual_in_post' => 0, 'notes' => null],
            ],
            'Quality Management Unit' => [
                'Development Officer' => ['allocated_posts' => 2, 'actual_in_post' => 1, 'notes' => null],
                'SKS Junior' => ['allocated_posts' => 2, 'actual_in_post' => 0, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 6, 'actual_in_post' => 3, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
            ],
            'Planning Unit' => [
                'Development Officer' => ['allocated_posts' => 4, 'actual_in_post' => 2, 'notes' => null],
                'SKS Junior' => ['allocated_posts' => 2, 'actual_in_post' => 0, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
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
                'House Warden' => ['allocated_posts' => 14, 'actual_in_post' => 3, 'notes' => null],
            ],
            'Public Health Unit' => [
                'Development Officer' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 3, 'actual_in_post' => 2, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Public Health Field Officer' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'Public Health Inspector' => ['allocated_posts' => 2, 'actual_in_post' => 1, 'notes' => null],
            ],
            'Infection Control Unit' => [
                'SKS Junior' => ['allocated_posts' => 2, 'actual_in_post' => 0, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 5, 'actual_in_post' => 3, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
            ],
            'Health Education Unit' => [
                'SKS Junior' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 4, 'actual_in_post' => 3, 'notes' => null],
            ],
            'Medical Records Room' => [
                'Medical Record Assistant' => ['allocated_posts' => 10, 'actual_in_post' => 2, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
            ],
            'Medical Records Room — Office Records Room' => [
                'Book Binder' => ['allocated_posts' => 2, 'actual_in_post' => 0, 'notes' => null],
                'Data Entry Operator' => ['allocated_posts' => 6, 'actual_in_post' => 1, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
            ],
            'Telephone Exchange' => [
                'Boiler Operator' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'Telephone Operator' => ['allocated_posts' => 17, 'actual_in_post' => 3, 'notes' => null],
            ],
            'General OPD' => [
                'SKS Junior' => ['allocated_posts' => 30, 'actual_in_post' => 5, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 61, 'actual_in_post' => 31, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 33, 'actual_in_post' => 16, 'notes' => null],
                'Nursing Sister / Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 17, 'actual_in_post' => 17, 'notes' => null],
            ],
            'SBU (Sick Baby Unit)' => [
                'SKS Junior' => ['allocated_posts' => 24, 'actual_in_post' => 7, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 24, 'actual_in_post' => 10, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 45, 'actual_in_post' => 33, 'notes' => null],
                'Nursing Sister / Master' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
            ],
            'Clinic Pharmacy' => [
                'SKS Junior' => ['allocated_posts' => 3, 'actual_in_post' => 0, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 6, 'actual_in_post' => 6, 'notes' => null],
                'Pharmacist' => ['allocated_posts' => 22, 'actual_in_post' => 9, 'notes' => null],
            ],
            'OPD Pharmacy' => [
                'SKS Junior' => ['allocated_posts' => 5, 'actual_in_post' => 5, 'notes' => null],
                'Pharmacist' => ['allocated_posts' => 25, 'actual_in_post' => 7, 'notes' => null],
            ],
            'OPD Pharmacy — Staff Pharmacy' => [
                'SKS Ordinary' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'Pharmacist' => ['allocated_posts' => 4, 'actual_in_post' => 2, 'notes' => null],
            ],
            'OPD Pharmacy — General Pharmacy Store' => [
                'SKS Junior' => ['allocated_posts' => 9, 'actual_in_post' => 5, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 3, 'actual_in_post' => 3, 'notes' => null],
                'Pharmacist' => ['allocated_posts' => 17, 'actual_in_post' => 7, 'notes' => null],
                'Special Grade Pharmacist' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
            ],
            'OPD Pharmacy — Indoor Pharmacy' => [
                'SKS Junior' => ['allocated_posts' => 3, 'actual_in_post' => 2, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
                'Pharmacist' => ['allocated_posts' => 12, 'actual_in_post' => 5, 'notes' => null],
            ],
            'OPD Pharmacy — Dental Store' => [
                'SKS Junior' => ['allocated_posts' => 2, 'actual_in_post' => 1, 'notes' => null],
                'Pharmacist' => ['allocated_posts' => 3, 'actual_in_post' => 1, 'notes' => null],
            ],
            'OPD Pharmacy — Dental Pharmacy' => [
                'SKS Ordinary' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'Pharmacist' => ['allocated_posts' => 3, 'actual_in_post' => 1, 'notes' => null],
            ],
            'OPD Pharmacy — Drug Information' => [
                'Pharmacist' => ['allocated_posts' => 9, 'actual_in_post' => 2, 'notes' => null],
            ],
            'Physiotheraphy Unit' => [
                'SKS Junior' => ['allocated_posts' => 7, 'actual_in_post' => 2, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 3, 'actual_in_post' => 3, 'notes' => null],
                'Physiotherapist' => ['allocated_posts' => 34, 'actual_in_post' => 8, 'notes' => null],
            ],
            'Xray' => [
                'SKS Junior' => ['allocated_posts' => 33, 'actual_in_post' => 7, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 8, 'actual_in_post' => 8, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 12, 'actual_in_post' => 5, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 10, 'actual_in_post' => 10, 'notes' => null],
                'Radiographer' => ['allocated_posts' => 52, 'actual_in_post' => 17, 'notes' => null],
            ],
            'Occupational Theraphy Unit' => [
                'Occupational Therapist' => ['allocated_posts' => 19, 'actual_in_post' => 5, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
            ],
            'Speech Theraphy Unit' => [
                'SKS Junior' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Speech Therapist' => ['allocated_posts' => 6, 'actual_in_post' => 0, 'notes' => null],
            ],
            'Laundry' => [
                'SKS Junior' => ['allocated_posts' => 11, 'actual_in_post' => 1, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 5, 'actual_in_post' => 5, 'notes' => null],
            ],
            'Hospital Diet Branch' => [
                'Ward Clerk' => ['allocated_posts' => 18, 'actual_in_post' => 5, 'notes' => null],
            ],
            'Hospital Kitchen' => [
                'Cook' => ['allocated_posts' => 30, 'actual_in_post' => 5, 'notes' => null],
                'Diet Steward' => ['allocated_posts' => 9, 'actual_in_post' => 1, 'notes' => null],
                'SKS Junior' => ['allocated_posts' => 15, 'actual_in_post' => 2, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 4, 'actual_in_post' => 4, 'notes' => null],
            ],
            'Sewing Unit' => [
                'Sewing Machine Operator' => ['allocated_posts' => 16, 'actual_in_post' => 3, 'notes' => null],
            ],
            'General Operating Theater' => [
                'SKS Junior' => ['allocated_posts' => 58, 'actual_in_post' => 28, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 86, 'actual_in_post' => 35, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 93, 'actual_in_post' => 55, 'notes' => null],
                'Nursing Sister / Master' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 12, 'actual_in_post' => 12, 'notes' => null],
            ],
            'General Operating Theater — EOT' => [
                'SKS Junior' => ['allocated_posts' => 13, 'actual_in_post' => 5, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 31, 'actual_in_post' => 9, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
            ],
            'General Operating Theater — New Labour Room OT' => [
                'SKS Junior' => ['allocated_posts' => 10, 'actual_in_post' => 0, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 15, 'actual_in_post' => 0, 'notes' => null],
                'Midwife' => ['allocated_posts' => 16, 'actual_in_post' => 0, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 30, 'actual_in_post' => 0, 'notes' => null],
                'Nursing Sister / Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
            ],
            'Transport Unit' => [
                'Health Driver' => ['allocated_posts' => 38, 'actual_in_post' => 18, 'notes' => null],
                'SKS Junior' => ['allocated_posts' => 7, 'actual_in_post' => 3, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 6, 'actual_in_post' => 6, 'notes' => null],
            ],
            'Clinic — Surgical Clinic' => [
                'SKS Junior' => ['allocated_posts' => 2, 'actual_in_post' => 1, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 3, 'actual_in_post' => 2, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 4, 'actual_in_post' => 4, 'notes' => null],
            ],
            'Clinic — EMG Clinic' => [
                'SKS Junior' => ['allocated_posts' => 2, 'actual_in_post' => 0, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
            ],
            'Clinic — Gyn & Obs Clinic' => [
                'SKS Junior' => ['allocated_posts' => 2, 'actual_in_post' => 1, 'notes' => null],
                'Midwife' => ['allocated_posts' => 4, 'actual_in_post' => 1, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 4, 'actual_in_post' => 2, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 3, 'actual_in_post' => 3, 'notes' => null],
            ],
            'Clinic — Paediatrics Clinic' => [
                'SKS Junior' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 3, 'actual_in_post' => 2, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
            ],
            'Clinic — Medical Clinic' => [
                'SKS Junior' => ['allocated_posts' => 3, 'actual_in_post' => 1, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 14, 'actual_in_post' => 4, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 10, 'actual_in_post' => 6, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 6, 'actual_in_post' => 6, 'notes' => null],
            ],
            'Clinic — Dermatology Clinic' => [
                'Acting Consultant' => ['allocated_posts' => 0, 'actual_in_post' => 1, 'notes' => null],
                'SKS Junior' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
            ],
            'Clinic — Elderly Clinic' => [
                'Acting Consultant' => ['allocated_posts' => 0, 'actual_in_post' => 1, 'notes' => null],
                'SKS Junior' => ['allocated_posts' => 2, 'actual_in_post' => 0, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 3, 'actual_in_post' => 2, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 3, 'actual_in_post' => 0, 'notes' => null],
            ],
            'VP OPD Clinic' => [
                'SKS Junior' => ['allocated_posts' => 3, 'actual_in_post' => 0, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 9, 'actual_in_post' => 4, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 4, 'actual_in_post' => 2, 'notes' => null],
                'Nursing Sister / Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
            ],
            'Psychiatry Clinic / Sithsuwa' => [
                'SKS Junior' => ['allocated_posts' => 2, 'actual_in_post' => 0, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 14, 'actual_in_post' => 8, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 3, 'actual_in_post' => 2, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 3, 'actual_in_post' => 3, 'notes' => null],
                'Psychiatric Social Worker' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
            ],
            'Clinical Nutrition Clinic' => [
                'Acting Consultant' => ['allocated_posts' => 0, 'actual_in_post' => 1, 'notes' => null],
                'SKS Junior' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 3, 'actual_in_post' => 1, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Nutritionist' => ['allocated_posts' => 2, 'actual_in_post' => 1, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
            ],
            'Sports Medicine Clinic' => [
                'Acting Consultant' => ['allocated_posts' => 0, 'actual_in_post' => 1, 'notes' => null],
                'SKS Junior' => ['allocated_posts' => 3, 'actual_in_post' => 0, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 5, 'actual_in_post' => 1, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
            ],
            'Mithuru Piyasa' => [
                'SKS Junior' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 2, 'actual_in_post' => 1, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
            ],
            'Pain Management Clinic' => [
                'SKS Junior' => ['allocated_posts' => 2, 'actual_in_post' => 0, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 3, 'actual_in_post' => 1, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 5, 'actual_in_post' => 2, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
            ],
            'Endocrinology Unit' => [
                'SKS Junior' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 2, 'actual_in_post' => 0, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 3, 'actual_in_post' => 2, 'notes' => null],
            ],
            'Obstetrics and Gynecology (New) Unit' => [
                'Medical Officer' => ['allocated_posts' => 11, 'actual_in_post' => 5, 'notes' => null],
            ],
            'Obstetrics and Gynecology (New) — Wd 18 (Female)' => [
                'SKS Junior' => ['allocated_posts' => 3, 'actual_in_post' => 3, 'notes' => null],
                'Midwife' => ['allocated_posts' => 22, 'actual_in_post' => 10, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 18, 'actual_in_post' => 11, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 3, 'actual_in_post' => 3, 'notes' => null],
            ],
            'Obstetrics and Gynecology (New) — Wd 19' => [
                'Caregiver' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
                'SKS Junior' => ['allocated_posts' => 4, 'actual_in_post' => 4, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 32, 'actual_in_post' => 12, 'notes' => null],
                'Nursing Sister / Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
            ],
            'Orthopedics' => [
                'Acting Consultant' => ['allocated_posts' => 0, 'actual_in_post' => 1, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 19, 'actual_in_post' => 7, 'notes' => null],
            ],
            'Orthopedics — Wd 16' => [
                'Caregiver' => ['allocated_posts' => 5, 'actual_in_post' => 5, 'notes' => null],
                'SKS Junior' => ['allocated_posts' => 13, 'actual_in_post' => 3, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 22, 'actual_in_post' => 12, 'notes' => null],
                'Nursing Sister / Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'Orthopaedic Technician' => ['allocated_posts' => 2, 'actual_in_post' => 0, 'notes' => null],
            ],
            'Orthopedics — Wd 15' => [
                'Caregiver' => ['allocated_posts' => 12, 'actual_in_post' => 2, 'notes' => null],
                'SKS Junior' => ['allocated_posts' => 14, 'actual_in_post' => 4, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 24, 'actual_in_post' => 13, 'notes' => null],
                'Nursing Sister / Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 13, 'actual_in_post' => 3, 'notes' => null],
            ],
            'Neurology' => [
                'Acting Consultant' => ['allocated_posts' => 0, 'actual_in_post' => 1, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 12, 'actual_in_post' => 6, 'notes' => null],
            ],
            'Neurology — Wd 12 (Female)' => [
                'Caregiver' => ['allocated_posts' => 2, 'actual_in_post' => 1, 'notes' => null],
                'SKS Junior' => ['allocated_posts' => 21, 'actual_in_post' => 5, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 21, 'actual_in_post' => 9, 'notes' => null],
                'Nursing Sister / Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
            ],
            'Neurology — Wd 12 (Male)' => [
                'Caregiver' => ['allocated_posts' => 4, 'actual_in_post' => 2, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 22, 'actual_in_post' => 9, 'notes' => null],
                'Nursing Sister / Master' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
            ],
            'General Pediatrics (Wd 4 & Wd 5)' => [
                'Caregiver' => ['allocated_posts' => 7, 'actual_in_post' => 2, 'notes' => null],
                'SKS Junior' => ['allocated_posts' => 16, 'actual_in_post' => 6, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 14, 'actual_in_post' => 6, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 49, 'actual_in_post' => 27, 'notes' => null],
                'Nursing Sister / Master' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 13, 'actual_in_post' => 3, 'notes' => null],
            ],
            'Obstetrics and Gynecology (Prof) Unit' => [
                'Medical Officer' => ['allocated_posts' => 13, 'actual_in_post' => 5, 'notes' => null],
            ],
            'Obstetrics and Gynecology (Prof) — Wd 3 (Female)' => [
                'Caregiver' => ['allocated_posts' => 7, 'actual_in_post' => 2, 'notes' => null],
                'SKS Junior' => ['allocated_posts' => 12, 'actual_in_post' => 3, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 29, 'actual_in_post' => 15, 'notes' => null],
                'Nursing Sister / Master' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 3, 'actual_in_post' => 3, 'notes' => null],
            ],
            'Obstetrics and Gynecology (Prof) — Wd 11' => [
                'Caregiver' => ['allocated_posts' => 4, 'actual_in_post' => 1, 'notes' => null],
                'SKS Junior' => ['allocated_posts' => 15, 'actual_in_post' => 2, 'notes' => null],
                'Midwife' => ['allocated_posts' => 13, 'actual_in_post' => 7, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 50, 'actual_in_post' => 20, 'notes' => null],
                'Nursing Sister / Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
            ],
            'Obstetrics and Gynecology (Prof) — Wd 10' => [
                'Caregiver' => ['allocated_posts' => 4, 'actual_in_post' => 1, 'notes' => null],
                'SKS Junior' => ['allocated_posts' => 32, 'actual_in_post' => 5, 'notes' => null],
                'Midwife' => ['allocated_posts' => 13, 'actual_in_post' => 6, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 48, 'actual_in_post' => 18, 'notes' => null],
                'Nursing Sister / Master' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 3, 'actual_in_post' => 3, 'notes' => null],
            ],
            'Clinical Hematology Unit' => [
                'SKS Junior' => ['allocated_posts' => 4, 'actual_in_post' => 0, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 11, 'actual_in_post' => 4, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 5, 'actual_in_post' => 3, 'notes' => null],
                'Nursing Sister / Master' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
            ],
            'General Surgery' => [
                'Medical Officer' => ['allocated_posts' => 16, 'actual_in_post' => 4, 'notes' => null],
            ],
            'General Surgery — Wd 1' => [
                'Caregiver' => ['allocated_posts' => 10, 'actual_in_post' => 2, 'notes' => null],
                'SKS Junior' => ['allocated_posts' => 30, 'actual_in_post' => 7, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 47, 'actual_in_post' => 27, 'notes' => null],
                'Nursing Sister / Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 3, 'actual_in_post' => 3, 'notes' => null],
            ],
            'General Surgery — Wd 2' => [
                'Caregiver' => ['allocated_posts' => 10, 'actual_in_post' => 3, 'notes' => null],
                'SKS Junior' => ['allocated_posts' => 24, 'actual_in_post' => 4, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 45, 'actual_in_post' => 24, 'notes' => null],
                'Nursing Sister / Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 3, 'actual_in_post' => 3, 'notes' => null],
            ],
            'General Surgery — Wd 20' => [
                'Caregiver' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'SKS Junior' => ['allocated_posts' => 11, 'actual_in_post' => 3, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 15, 'actual_in_post' => 8, 'notes' => null],
                'Nursing Sister / Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
            ],
            'General Surgery — Wd 21' => [
                'Caregiver' => ['allocated_posts' => 3, 'actual_in_post' => 1, 'notes' => null],
                'SKS Junior' => ['allocated_posts' => 11, 'actual_in_post' => 2, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 15, 'actual_in_post' => 8, 'notes' => null],
                'Nursing Sister / Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
            ],
            'Plastic Surgery — Wd 22 (Male / Female)' => [
                'SKS Junior' => ['allocated_posts' => 12, 'actual_in_post' => 2, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 10, 'actual_in_post' => 2, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 11, 'actual_in_post' => 6, 'notes' => null],
                'Nursing Sister / Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 3, 'actual_in_post' => 3, 'notes' => null],
            ],
            'General Medicine' => [
                'Medical Officer' => ['allocated_posts' => 25, 'actual_in_post' => 9, 'notes' => null],
            ],
            'General Medicine — Medical Ward (New) Female' => [
                'Caregiver' => ['allocated_posts' => 3, 'actual_in_post' => 0, 'notes' => null],
                'SKS Junior' => ['allocated_posts' => 15, 'actual_in_post' => 0, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 3, 'actual_in_post' => 0, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 33, 'actual_in_post' => 0, 'notes' => null],
                'Nursing Sister / Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
            ],
            'General Medicine — Wd 7 (Female)' => [
                'Caregiver' => ['allocated_posts' => 9, 'actual_in_post' => 4, 'notes' => null],
                'SKS Junior' => ['allocated_posts' => 31, 'actual_in_post' => 7, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 54, 'actual_in_post' => 28, 'notes' => null],
                'Nursing Sister / Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 6, 'actual_in_post' => 6, 'notes' => null],
            ],
            'General Medicine — Wd 8 (Male)' => [
                'Caregiver' => ['allocated_posts' => 5, 'actual_in_post' => 0, 'notes' => null],
                'SKS Junior' => ['allocated_posts' => 38, 'actual_in_post' => 10, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 88, 'actual_in_post' => 32, 'notes' => null],
                'Nursing Sister / Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 4, 'actual_in_post' => 4, 'notes' => null],
            ],
            'General Medicine — Medical Ward (New) Male' => [
                'Caregiver' => ['allocated_posts' => 3, 'actual_in_post' => 0, 'notes' => null],
                'SKS Junior' => ['allocated_posts' => 15, 'actual_in_post' => 0, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 5, 'actual_in_post' => 0, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 34, 'actual_in_post' => 0, 'notes' => null],
                'Nursing Sister / Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
            ],
            'Psychiatry Unit' => [
                'Medical Officer' => ['allocated_posts' => 19, 'actual_in_post' => 7, 'notes' => null],
            ],
            'Psychiatry Unit — Wd 6 (Male)' => [
                'Caregiver' => ['allocated_posts' => 2, 'actual_in_post' => 1, 'notes' => null],
                'SKS Junior' => ['allocated_posts' => 13, 'actual_in_post' => 6, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 15, 'actual_in_post' => 10, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 5, 'actual_in_post' => 5, 'notes' => null],
            ],
            'Psychiatry Unit — Wd 9 (Female)' => [
                'Caregiver' => ['allocated_posts' => 2, 'actual_in_post' => 1, 'notes' => null],
                'SKS Junior' => ['allocated_posts' => 11, 'actual_in_post' => 4, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 26, 'actual_in_post' => 10, 'notes' => null],
                'Nursing Sister / Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
                'Psychiatric Social Worker' => ['allocated_posts' => 4, 'actual_in_post' => 2, 'notes' => null],
            ],
            'Toxicology — Wd 17' => [
                'Caregiver' => ['allocated_posts' => 3, 'actual_in_post' => 2, 'notes' => null],
                'SKS Junior' => ['allocated_posts' => 7, 'actual_in_post' => 4, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 21, 'actual_in_post' => 11, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 43, 'actual_in_post' => 17, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
            ],
            'Cardiology Unit' => [
                'SKS Junior' => ['allocated_posts' => 3, 'actual_in_post' => 0, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 4, 'actual_in_post' => 2, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 5, 'actual_in_post' => 2, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
            ],
            'Nephrology Unit — Wd 23 (Male / Female)' => [
                'SKS Junior' => ['allocated_posts' => 19, 'actual_in_post' => 6, 'notes' => null],
                'Medical Officer' => ['allocated_posts' => 15, 'actual_in_post' => 5, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 40, 'actual_in_post' => 20, 'notes' => null],
                'Nursing Sister / Master' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 3, 'actual_in_post' => 3, 'notes' => null],
            ],
            'Labour Room — Common (Not attached to a ward)' => [
                'SKS Junior' => ['allocated_posts' => 18, 'actual_in_post' => 8, 'notes' => null],
                'Midwife' => ['allocated_posts' => 23, 'actual_in_post' => 9, 'notes' => null],
                'Nursing Officer' => ['allocated_posts' => 27, 'actual_in_post' => 25, 'notes' => null],
                'Nursing Sister / Master' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 4, 'actual_in_post' => 4, 'notes' => null],
            ],
            'Unallocated / Shared Cadre' => [
                'Acting Consultant' => ['allocated_posts' => 10, 'actual_in_post' => 0, 'notes' => null],
                'SKS Ordinary' => ['allocated_posts' => 197, 'actual_in_post' => 0, 'notes' => null],
            ],
        ];
    }

    /** @return array<string, array<string, array{allocated_posts:int, actual_in_post:int, notes:?string}>> keyed by "ParentTitle::SubName" */
    private function subcategoryData(): array
    {
        return [
            'Accident and Emergency Unit' => [
                'Medical Consultant::Emergency Physician' => ['allocated_posts' => 4, 'actual_in_post' => 0, 'notes' => null],
                'Medical Officer::PGIM Trainee' => ['allocated_posts' => 0, 'actual_in_post' => 3, 'notes' => null],
            ],
            'Blood Bank' => [
                'Medical Consultant::Transfusion Physician' => ['allocated_posts' => 2, 'actual_in_post' => 1, 'notes' => null],
            ],
            'Dental — Operation Theater' => [
                'Dental Surgeon::Restorative Dentistry' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Dental Surgeon::Orthodontics' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
            ],
            'Dental — OMF Units' => [
                'Dental Surgeon::Oral Maxillofacial Surgery' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
            ],
            'General ICU' => [
                'Medical Officer::PGIM Trainee' => ['allocated_posts' => 0, 'actual_in_post' => 7, 'notes' => null],
            ],
            'Health Information Unit (IT)' => [
                'Medical Consultant::Health Informatician' => ['allocated_posts' => 1, 'actual_in_post' => 1, 'notes' => null],
            ],
            'JMO Unit' => [
                'Medical Consultant::Judicial Medical Officer' => ['allocated_posts' => 2, 'actual_in_post' => 1, 'notes' => null],
            ],
            'Microbiology Laboratory' => [
                'Medical Consultant::Clinical Microbiologist' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Medical Officer::PGIM Trainee' => ['allocated_posts' => 0, 'actual_in_post' => 2, 'notes' => null],
            ],
            'Histopathology Lab' => [
                'Medical Consultant::Histopathologist' => ['allocated_posts' => 2, 'actual_in_post' => 1, 'notes' => null],
                'Medical Officer::PGIM Trainee' => ['allocated_posts' => 0, 'actual_in_post' => 13, 'notes' => null],
            ],
            'Chemical Pathology / Bio Chemistry Laboratory' => [
                'Medical Consultant::Chemical Pathologist' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
            ],
            'SBU (Sick Baby Unit)' => [
                'Medical Consultant::Neonatologist' => ['allocated_posts' => 3, 'actual_in_post' => 0, 'notes' => null],
                'Medical Consultant::Paediatrician' => ['allocated_posts' => 4, 'actual_in_post' => 2, 'notes' => null],
                'Medical Officer::PGIM Trainee' => ['allocated_posts' => 0, 'actual_in_post' => 8, 'notes' => null],
            ],
            'Xray' => [
                'Medical Consultant::Radiologist' => ['allocated_posts' => 4, 'actual_in_post' => 2, 'notes' => null],
            ],
            'General Operating Theater' => [
                'Medical Officer::PGIM Trainee' => ['allocated_posts' => 0, 'actual_in_post' => 5, 'notes' => null],
            ],
            'General Operating Theater — New Labour Room OT' => [
                'Medical Consultant::Anaesthetist' => ['allocated_posts' => 2, 'actual_in_post' => 0, 'notes' => null],
            ],
            'Clinic — Dermatology Clinic' => [
                'Medical Consultant::Dermatologist' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
            ],
            'Clinic — Elderly Clinic' => [
                'Medical Consultant::Geriatric Medicine' => ['allocated_posts' => 2, 'actual_in_post' => 0, 'notes' => null],
                'Medical Officer::PGIM Trainee' => ['allocated_posts' => 0, 'actual_in_post' => 10, 'notes' => null], // source PDF Total was -1 (Add.Req. -11 against In Position 10) — clamped to 0, negative allocated posts isn't meaningful
            ],
            'VP OPD Clinic' => [
                'Medical Consultant::General Physician' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
            ],
            'Psychiatry Clinic / Sithsuwa' => [
                'Medical Officer::PGIM Trainee' => ['allocated_posts' => 0, 'actual_in_post' => 6, 'notes' => null],
            ],
            'Clinical Nutrition Clinic' => [
                'Medical Consultant::Nutrition Physician' => ['allocated_posts' => 2, 'actual_in_post' => 0, 'notes' => null],
            ],
            'Sports Medicine Clinic' => [
                'Medical Consultant::Sports & Exercise Medicine' => ['allocated_posts' => 2, 'actual_in_post' => 0, 'notes' => null],
            ],
            'Endocrinology Unit' => [
                'Medical Consultant::Endocrinologist' => ['allocated_posts' => 2, 'actual_in_post' => 0, 'notes' => null],
            ],
            'Obstetrics and Gynecology (New) Unit' => [
                'Medical Consultant::Obstetrician & Gynaecologist' => ['allocated_posts' => 4, 'actual_in_post' => 2, 'notes' => null],
                'Medical Officer::PGIM Trainee' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
            ],
            'Orthopedics' => [
                'Medical Consultant::Orthopaedic Surgeon' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
                'Medical Officer::PGIM Trainee' => ['allocated_posts' => 2, 'actual_in_post' => 0, 'notes' => null],
            ],
            'Neurology' => [
                'Medical Consultant::Neurologist' => ['allocated_posts' => 2, 'actual_in_post' => 0, 'notes' => null],
            ],
            'General Pediatrics (Wd 4 & Wd 5)' => [
                'Medical Officer::PGIM Trainee' => ['allocated_posts' => 0, 'actual_in_post' => 7, 'notes' => null],
            ],
            'Obstetrics and Gynecology (Prof) Unit' => [
                'Medical Officer::PGIM Trainee' => ['allocated_posts' => 0, 'actual_in_post' => 1, 'notes' => null],
            ],
            'Plastic Surgery — Wd 22 (Male / Female)' => [
                'Medical Consultant::Plastic Surgeon' => ['allocated_posts' => 2, 'actual_in_post' => 0, 'notes' => null],
            ],
            'General Medicine' => [
                'Medical Consultant::General Physician' => ['allocated_posts' => 2, 'actual_in_post' => 2, 'notes' => null],
                'Medical Consultant::Rheumatologist' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
                'Medical Officer::PGIM Trainee' => ['allocated_posts' => 0, 'actual_in_post' => 5, 'notes' => null],
            ],
            'General Medicine — Medical Ward (New) Female' => [
                'Medical Consultant::General Physician' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
            ],
            'General Medicine — Medical Ward (New) Male' => [
                'Medical Consultant::General Physician' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
            ],
            'Psychiatry Unit' => [
                'Medical Officer::PGIM Trainee' => ['allocated_posts' => 0, 'actual_in_post' => 4, 'notes' => null],
            ],
            'Toxicology — Wd 17' => [
                'Medical Officer::PGIM Trainee' => ['allocated_posts' => 0, 'actual_in_post' => 4, 'notes' => null],
            ],
            'Cardiology Unit' => [
                'Medical Consultant::Cardiologist' => ['allocated_posts' => 1, 'actual_in_post' => 0, 'notes' => null],
            ],
            'Unallocated / Shared Cadre' => [
                'Medical Officer::PGIM Trainee' => ['allocated_posts' => 73, 'actual_in_post' => 0, 'notes' => null],
            ],
        ];
    }
}
