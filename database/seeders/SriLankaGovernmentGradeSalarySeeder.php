<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Position;
use App\Models\PositionGrade;
use App\Models\SalaryScale;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * SriLankaGovernmentGradeSalarySeeder
 *
 * PURPOSE
 * -------
 * Seeds:
 *  1. Sri Lanka Public Service salary-scale master data relevant to the Carder module
 *     from Public Administration Circular 10/2025 (Salary Structure for the Public Service - 2025).
 *  2. Position grade ladders for hospital positions where the salary/service mapping can be
 *     supported from official Public Administration / Ministry of Health material.
 *  3. Promotion criteria metadata based on the Establishments Code.
 *
 * IMPORTANT LEGAL / DATA-GOVERNANCE NOTE
 * --------------------------------------
 * Public Administration Circular 10/2025 DOES NOT replace service-specific Service Minutes /
 * Schemes of Recruitment for promotion. Paragraph 14 of PAC 10/2025 states that methods of
 * promotion remain unchanged. Establishments Code Chapter II also requires promotion to be in
 * accordance with the approved Scheme of Recruitment / Service Minute.
 *
 * Therefore this seeder intentionally DOES NOT invent `min_years_in_grade` values.
 * Add exact service-duration / qualification / examination requirements only after checking the
 * currently applicable Service Minute / Scheme of Recruitment / Gazette for the relevant service.
 *
 * The application currently treats `min_years_in_grade` specially for automated promotion
 * eligibility reminders. Omitting that key is deliberate: it prevents a false legal eligibility
 * alert from being generated from an assumed number of years.
 *
 * PRIMARY SOURCES
 * ---------------
 * - Public Administration Circular 10/2025, 25.03.2025
 *   "Revision of Salaries of the Public Service as per Budget Proposals 2025"
 *   Schedule I: service/post -> salary code
 *   Schedule II: Salary Structure for the Public Service - 2025
 *   Paras 12-14: Efficiency Bars, increment date, methods of promotion
 *
 * - Establishments Code, Volume I, Chapter II
 *   s.5.5 / 5.6 : promotions according to approved Scheme of Recruitment
 *   s.6.1.5     : appointment/promotion must accord with approved Scheme of Recruitment
 *   s.15        : Efficiency Bars
 *
 * - Establishments Code, Volume I, Chapter VII s.5
 *   Salary on Promotion
 *
 * - Ministry of Health examination / promotion notices (used only as corroboration that
 *   particular hospital service categories use Grade III / II / I and relevant EB examinations).
 *
 * SAFE TO RE-RUN
 * --------------
 * Uses updateOrCreate() and does not delete or disable any existing master data.
 */
final class SriLankaGovernmentGradeSalarySeeder extends Seeder
{
    /**
     * Salary scales used by the hospital/Carder positions.
     *
     * `increment_amount` is intentionally NULL because PAC 10/2025 scales contain multiple
     * incremental slabs. The existing Carder salary_scales table has only one increment_amount
     * column; storing one slab there would be misleading. The complete structure is retained
     * in `notes`.
     */
    private function salaryScales(): array
    {
        return [
            ['PL 1 - 2025', 'Primary Level Unskilled',              40000,  61880, null, '10x450; 10x490; 10x540; 12x590. PAC 10/2025 Schedule II.'],
            ['PL 2 - 2025', 'Primary Level Semi-skilled',           41800,  65560, null, '10x490; 10x540; 10x590; 12x630. PAC 10/2025 Schedule II.'],
            ['PL 3 - 2025', 'Primary Level Skilled',                42780,  66540, null, '10x490; 10x540; 10x590; 12x630. PAC 10/2025 Schedule II.'],

            ['MN 1 - 2025', 'Management Assistants Non-tech Segment 2',            45230,  78360, null, '10x540; 11x630; 10x890; 10x1190. PAC 10/2025 Schedule II.'],
            ['MN 2 - 2025', 'Management Assistants Non-tech Multi duty - Segment 1', 48470,  82800, null, '10x540; 11x630; 10x1010; 10x1190. PAC 10/2025 Schedule II.'],
            ['MN 3 - 2025', 'MA Supervisory Non-tech / Tech',                      52250, 100040, null, '10x800; 11x1190; 10x1320; 10x1350. PAC 10/2025 Schedule II.'],
            ['MN 4 - 2025', 'Associate Officer',                                  53060,  94100, null, '10x800; 11x1190; 10x1320; 5x1350. PAC 10/2025 Schedule II.'],
            ['MN 5 - 2025', 'Field / Office Based Officer Segment 2',             58660, 110570, null, '10x1190; 11x1360; 15x1670. PAC 10/2025 Schedule II.'],
            ['MN 6 - 2025', 'Field / Office Based Officer Segment 1',             62230, 114140, null, '10x1190; 11x1360; 15x1670. PAC 10/2025 Schedule II.'],
            ['MN 7 - 2025', 'Management Assistants Supra',                        71240, 119500, null, '11x1360; 18x1850. PAC 10/2025 Schedule II.'],

            ['MT 1 - 2025', 'MA Technical Segment 3',                 50090,  84420, null, '10x540; 11x630; 10x1010; 10x1190. PAC 10/2025 Schedule II.'],
            ['MT 2 - 2025', 'MA Technical Segment 2',                 50630,  86300, null, '10x630; 11x670; 10x1010; 10x1190. PAC 10/2025 Schedule II.'],
            ['MT 3 - 2025', 'MA Technical Segment 1',                 51890,  87560, null, '10x630; 11x670; 10x1010; 10x1190. PAC 10/2025 Schedule II.'],
            ['MT 4 - 2025', 'Para Medical Services Segment 3',        52520, 100310, null, '10x800; 11x1190; 10x1320; 10x1350. PAC 10/2025 Schedule II.'],
            ['MT 5 - 2025', 'Para Medical Services Segment 2',        53320, 101110, null, '10x800; 11x1190; 10x1320; 10x1350. PAC 10/2025 Schedule II.'],
            ['MT 6 - 2025', 'PSM / Para Medical Services Segment 1',  54120, 101910, null, '10x800; 11x1190; 10x1320; 10x1350. PAC 10/2025 Schedule II.'],
            ['MT 7 - 2025', 'Nurses',                                54920, 102710, null, '10x800; 11x1190; 10x1320; 10x1350. PAC 10/2025 Schedule II.'],
            ['MT 8 - 2025', 'Nurses / PSM / PMS Special Grade',      86800, 134520, null, '10x2420; 8x2940. PAC 10/2025 Schedule II.'],

            ['SL 1 - 2025', 'Executive',                     82150, 195970, null, '10x2400; 8x2940; 17x3900. PAC 10/2025 Schedule II.'],
            ['SL 2 - 2025', 'Medical Officers',              91750, 184170, null, '3x2400; 7x2420; 2x2940; 16x3900. PAC 10/2025 Schedule II.'],
            ['SL 3 - 2025', 'Senior Executive / MO Specialists', 156000, 214200, null, '12x4850. PAC 10/2025 Schedule II.'],
        ];
    }

    /**
     * Common Establishments Code criteria metadata.
     *
     * NOTE:
     * - These are governance conditions, not a substitute for a Service Minute.
     * - `min_years_in_grade` is NOT set here.
     */
    private function basePromotionCriteria(string $salaryCode, string $sourceNote): array
    {
        return [
            'salary_code' => $salaryCode,
            'salary_revision' => 'PAC 10/2025',
            'promotion_governed_by' => 'Approved Service Minute / Scheme of Recruitment',
            'requires_approved_scheme_of_recruitment' => true,
            'efficiency_bar_if_prescribed' => true,
            'departmental_exam_if_prescribed' => true,
            'fitness_certificate_for_efficiency_bar' => true,
            'salary_on_promotion_rule' => 'Establishments Code Vol I, Chapter VII, Section 5',
            'promotion_procedure_rule' => 'Establishments Code Vol I, Chapter II',
            'no_pay_leave_excluded_from_minimum_service_when_scheme_requires_minimum_service' => true,
            'exact_minimum_service_must_be_verified_from_service_minute' => true,
            'source_note' => $sourceNote,
        ];
    }

    /**
     * Confirmed / supportable position grade ladders in the current Carder establishment list.
     *
     * Grade order is junior -> senior.
     *
     * Salary code may change at Special / Executive grade, therefore it is stored per grade
     * inside criteria JSON instead of assuming one salary code for the whole Position.
     */
    private function gradeMappings(): array
    {
        return [
            // Medical Service
            'POS-MEDOF' => [
                ['Grade II', 10, 'SL 2 - 2025', 'PAC 10/2025 Schedule I: Medical Officer Grade II -> SL 2-2025.'],
                ['Grade I',  20, 'SL 2 - 2025', 'PAC 10/2025 Schedule I: Medical Officer Grade I -> SL 2-2025.'],
            ],
            'POS-MDCON' => [
                ['Specialist Medical Officer Grade', 10, 'SL 3 - 2025', 'PAC 10/2025 Schedule I: Specialist Medical Officer Grade -> SL 3-2025.'],
            ],

            // Sri Lanka Nursing Service
            'POS-NRSOF' => [
                ['Grade III',     10, 'MT 7 - 2025', 'PAC 10/2025 Schedule I: Nursing Service Grade III -> MT 7-2025.'],
                ['Grade II',      20, 'MT 7 - 2025', 'PAC 10/2025 Schedule I: Nursing Service Grade II -> MT 7-2025.'],
                ['Grade I',       30, 'MT 7 - 2025', 'PAC 10/2025 Schedule I: Nursing Service Grade I -> MT 7-2025.'],
                ['Supra Grade',   40, 'MT 7 - 2025', 'PAC 10/2025 Schedule I: Nursing Service Supra Grade -> MT 7-2025.'],
                ['Special Grade', 50, 'MT 8 - 2025', 'PAC 10/2025 Schedule I: Nursing Service Special Grade -> MT 8-2025.'],
            ],

            // Service of Professions Supplementary to Medicine
            'POS-MLTEC' => $this->psmLadder('Medical Laboratory Technologist'),
            'POS-PHRMC' => $this->psmLadder('Pharmacist'),
            'POS-RADGP' => $this->psmLadder('Radiographer'),
            'POS-PHYST' => $this->psmLadder('Physiotherapist'),
            'POS-OCCTH' => $this->psmLadder('Occupational Therapist'),
            'POS-SPPTH' => $this->psmLadder('Speech Therapist'),

            // Para Medical Services - Segment 2
            'POS-MIDWF' => $this->paraLadder('Public Health Midwife', 'MT 5 - 2025'),
            'POS-PHINS' => $this->paraLadder('Public Health Inspector', 'MT 5 - 2025'),

            // Para Medical Services - Segment 3
            'POS-ECGRC' => $this->paraLadder('ECG Recordist', 'MT 4 - 2025'),
            'POS-EEGRC' => $this->paraLadder('EEG Recordist', 'MT 4 - 2025'),
            'POS-DSPNS' => $this->paraLadder('Dispenser', 'MT 4 - 2025'),

            // Public Health Management Assistants' Service
            'POS-PHMAS' => [
                ['Grade III',   10, 'MN 2 - 2025', 'PAC 10/2025 Schedule I: PHMA Grade III -> MN 2-2025.'],
                ['Grade II',    20, 'MN 2 - 2025', 'PAC 10/2025 Schedule I: PHMA Grade II -> MN 2-2025.'],
                ['Grade I',     30, 'MN 2 - 2025', 'PAC 10/2025 Schedule I: PHMA Grade I -> MN 2-2025.'],
                ['Supra Grade', 40, 'MN 7 - 2025', 'PAC 10/2025 Schedule I: PHMA Supra Grade -> MN 7-2025.'],
            ],

            // Development Officers' Service
            'POS-DVOFF' => [
                ['Grade III', 10, 'MN 4 - 2025', 'PAC 10/2025 Schedule I: Development Officers Service Grades I/II/III -> MN 4-2025.'],
                ['Grade II',  20, 'MN 4 - 2025', 'PAC 10/2025 Schedule I: Development Officers Service Grades I/II/III -> MN 4-2025.'],
                ['Grade I',   30, 'MN 4 - 2025', 'PAC 10/2025 Schedule I: Development Officers Service Grades I/II/III -> MN 4-2025.'],
            ],

            // Health departmental posts for which Ministry of Health currently conducts
            // EB exams under Management Assistant Non-technical Segment 2.
            'POS-WDCLK' => $this->genericThreeGradeLadder(
                'MN 1 - 2025',
                'Ministry of Health EB notices identify Ward Clerk under Management Assistant Non-technical Segment 2.'
            ),
            'POS-DIETS' => $this->genericThreeGradeLadder(
                'MN 1 - 2025',
                'Ministry of Health EB notices identify Diet Steward under Management Assistant Non-technical Segment 2.'
            ),
            'POS-HWRDN' => $this->genericThreeGradeLadder(
                'MN 1 - 2025',
                'Ministry of Health EB notices identify House Warden under Management Assistant Non-technical Segment 2.'
            ),

            // Primary skilled technical posts: Ministry of Health EB notices explicitly place
            // Health Driver / Electrician etc. under PL-03 category.
            'POS-HLDVR' => $this->genericThreeGradeLadder(
                'PL 3 - 2025',
                'Ministry of Health EB notices identify Health Driver under Primary Level Skilled / PL-03 category.'
            ),
            'POS-ELCTN' => $this->genericThreeGradeLadder(
                'PL 3 - 2025',
                'Ministry of Health EB notices identify Electrician under Primary Technical / PL-03 category.'
            ),
        ];
    }

    private function psmLadder(string $serviceName): array
    {
        $base = "PAC 10/2025 Schedule I: {$serviceName} belongs to Service of Professions Supplementary to Medicine; Grade III/II/I/Supra -> MT 6-2025, Special Grade -> MT 8-2025.";

        return [
            ['Grade III',     10, 'MT 6 - 2025', $base],
            ['Grade II',      20, 'MT 6 - 2025', $base],
            ['Grade I',       30, 'MT 6 - 2025', $base],
            ['Supra Grade',   40, 'MT 6 - 2025', $base],
            ['Special Grade', 50, 'MT 8 - 2025', $base],
        ];
    }

    private function paraLadder(string $serviceName, string $normalSalaryCode): array
    {
        $base = "PAC 10/2025 Schedule I: {$serviceName}; Grade III/II/I/Supra -> {$normalSalaryCode}, Special Grade -> MT 8-2025.";

        return [
            ['Grade III',     10, $normalSalaryCode, $base],
            ['Grade II',      20, $normalSalaryCode, $base],
            ['Grade I',       30, $normalSalaryCode, $base],
            ['Supra Grade',   40, $normalSalaryCode, $base],
            ['Special Grade', 50, 'MT 8 - 2025', $base],
        ];
    }

    private function genericThreeGradeLadder(string $salaryCode, string $source): array
    {
        return [
            ['Grade III', 10, $salaryCode, $source],
            ['Grade II',  20, $salaryCode, $source],
            ['Grade I',   30, $salaryCode, $source],
        ];
    }

    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedSalaryScales();
            $this->seedPositionGrades();
        });
    }

    private function seedSalaryScales(): void
    {
        foreach ($this->salaryScales() as [$code, $name, $min, $max, $increment, $notes]) {
            SalaryScale::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'min_salary' => $min,
                    'max_salary' => $max,
                    'increment_amount' => $increment,
                    'notes' => $notes,
                    'is_active' => true,
                ]
            );
        }

        $this->command?->info('Seeded/updated '.count($this->salaryScales()).' official 2025 salary scales relevant to Carder.');
    }

    private function seedPositionGrades(): void
    {
        $mapped = 0;
        $missing = [];

        foreach ($this->gradeMappings() as $positionCode => $grades) {
            $position = Position::query()->where('code', $positionCode)->first();

            if (! $position) {
                $missing[] = $positionCode;

                continue;
            }

            foreach ($grades as [$gradeName, $sortOrder, $salaryCode, $sourceNote]) {
                $criteria = $this->basePromotionCriteria($salaryCode, $sourceNote);

                // Ministry of Health examination notices corroborate EB examinations for
                // Nursing / PSM / Para Medical / PHMA and selected departmental grades.
                if ($this->isKnownEfficiencyBarService($positionCode)) {
                    $criteria['efficiency_bar_examinations_in_service'] = true;
                }

                PositionGrade::updateOrCreate(
                    [
                        'position_id' => $position->id,
                        'name' => $gradeName,
                    ],
                    [
                        'sort_order' => $sortOrder,
                        'criteria' => $criteria,
                        'description' => 'Official grade/salary baseline. Exact promotion eligibility must be checked against the currently applicable Service Minute / Scheme of Recruitment before approval.',
                        'is_active' => true,
                    ]
                );
            }

            $mapped++;
        }

        $this->command?->info("Seeded grade ladders for {$mapped} configured positions.");

        if ($missing !== []) {
            $this->command?->warn(
                'These mapped position codes were not found in the current database and were skipped: '.
                implode(', ', $missing)
            );
        }

        $this->command?->warn(
            'Exact min_years_in_grade has NOT been seeded. '.
            'Populate it only from the applicable Service Minute / Scheme of Recruitment / Gazette for each service.'
        );
    }

    private function isKnownEfficiencyBarService(string $positionCode): bool
    {
        return in_array($positionCode, [
            'POS-NRSOF',
            'POS-MLTEC',
            'POS-PHRMC',
            'POS-RADGP',
            'POS-PHYST',
            'POS-OCCTH',
            'POS-SPPTH',
            'POS-MIDWF',
            'POS-PHINS',
            'POS-ECGRC',
            'POS-EEGRC',
            'POS-DSPNS',
            'POS-PHMAS',
            'POS-WDCLK',
            'POS-DIETS',
            'POS-HWRDN',
            'POS-HLDVR',
            'POS-ELCTN',
        ], true);
    }
}
