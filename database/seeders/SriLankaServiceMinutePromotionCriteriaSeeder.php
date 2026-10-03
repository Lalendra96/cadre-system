<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Position;
use App\Models\PositionGrade;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * SriLankaServiceMinutePromotionCriteriaSeeder
 *
 * Seeds promotion-eligibility criteria ONLY where the rule is supported by an
 * official Sri Lankan Service Minute / Scheme of Recruitment / Ministry circular.
 *
 * IMPORTANT
 * =========
 * `PositionGrade::minYearsInGrade()` is operational in Carder: when
 * `criteria.min_years_in_grade` exists, the promotion reminder engine may notify
 * users automatically. Therefore this seeder only sets that key for rules which
 * are sufficiently verified.
 *
 * Where the current rule cannot be safely confirmed, this seeder either:
 *  - adds descriptive metadata WITHOUT `min_years_in_grade`, or
 *  - leaves the grade untouched and records the position in MANUAL_REVIEW_POSITIONS.
 *
 * It is safe to re-run. No existing grade record is deleted.
 */
final class SriLankaServiceMinutePromotionCriteriaSeeder extends Seeder
{
    /**
     * Positions present in the Carder establishment for which automatic
     * min-years based eligibility is intentionally NOT enabled by this seeder.
     *
     * The reason is that the applicable promotion route is governed by a
     * service-specific minute / recruitment scheme / professional pathway that
     * must be verified before Carder should generate legal/HR eligibility alerts.
     */
    private const MANUAL_REVIEW_POSITIONS = [
        'POS-MDASN' => 'Medical Administration senior-grade pathway; verify current Medical Service Minute / specialist-administration provisions.',
        'POS-MDADP' => 'Medical Administration deputy-grade pathway; verify current Medical Service Minute.',
        'POS-MDCON' => 'Consultant/Specialist Medical Officer pathway is qualification and board/appointment based, not a simple years-in-grade rule.',
        'POS-DTSRG' => 'Dental Surgeon promotion governed by Medical/Dental Service provisions; verify current service minute.',
        'POS-MEDOF' => 'Medical Officer progression requires current Medical Service Minute and examinations/appointments; do not infer solely from years.',
        'POS-MATRN' => 'Matron is a nursing-management appointment pathway; verify current Nursing Service Minute appointment criteria.',
        'POS-NRSMS' => 'Nursing Sister/Master is an appointment/selection pathway; verify current Nursing Service Minute.',
        'POS-SGMDW' => 'Special Grade Midwife promotion is vacancy/seniority/service-minute dependent; verify current special-grade rules.',
        'POS-SGMLT' => 'Special Grade MLT promotion is vacancy/seniority/service-minute dependent; verify current special-grade rules.',
        'POS-SGPHM' => 'Special Grade Pharmacist promotion is vacancy/seniority/service-minute dependent; verify current special-grade rules.',
        'POS-SGRDG' => 'Special Grade Radiographer promotion is vacancy/seniority/service-minute dependent; verify current special-grade rules.',
        'POS-BIOME' => 'Bio Medical Engineer is an executive/technical service pathway; verify applicable Scheme of Recruitment.',
        'POS-NUTRI' => 'Nutritionist promotion route requires the specific approved Scheme of Recruitment.',
        'POS-PSYSW' => 'Psychiatric Social Worker is an MN service category; verify approved departmental Scheme of Recruitment.',
        'POS-ORTHT' => 'Orthopaedic Technician: verify current Paramedical/departmental Scheme of Recruitment.',
        'POS-BMEAS' => 'Bio Medical Engineering Assistant: verify current departmental technical Scheme of Recruitment.',
        'POS-ACCNT' => 'Accountant may fall under Sri Lanka Accountants Service/departmental structure; verify appointment/service mapping before automation.',
        'POS-ADMOF' => 'Administrative Officer promotion route depends on applicable service; verify Service Minute/Scheme.',
        'POS-MDRCD' => 'Medical Record Assistant was brought into related officer category; verify current approved promotion scheme before automation.',
        'POS-DVAST' => 'Development Assistant is not automatically equivalent to Development Officers Service; verify current departmental scheme.',
        'POS-ICTSA' => 'ICT Assistant requires mapping to current Sri Lanka ICT Service class/grade before automatic promotion rules.',
        'POS-PHMAS' => 'Public Health Management Assistant requires current approved service/recruitment scheme; salary category alone is insufficient.',
        'POS-WDCLK' => 'Ward Clerk: EB category is known, but exact promotion years must come from approved departmental Scheme of Recruitment.',
        'POS-BKBND' => 'Book Binder: verify current primary-level Scheme of Recruitment.',
        'POS-TLOPR' => 'Telephone Operator: verify current approved Scheme of Recruitment.',
        'POS-PHFOF' => 'Public Health Field Officer: verify exact current service category and Scheme of Recruitment.',
        'POS-ELCTN' => 'Electrician: EB category known (PL-3), exact grade-promotion criteria require departmental Scheme of Recruitment.',
        'POS-BOLRO' => 'Boiler Operator: verify approved primary technical Scheme of Recruitment.',
        'POS-CARP' => 'Carpenter: verify approved primary skilled/technical Scheme of Recruitment.',
        'POS-HPMO' => 'High Pressure Machine Operator: verify approved primary technical Scheme of Recruitment.',
        'POS-PLMPR' => 'Plumber/Pump Machine Operator: verify approved primary technical Scheme of Recruitment.',
        'POS-SWGMO' => 'Sewing Machine Operator: verify approved primary skilled Scheme of Recruitment.',
        'POS-DIETS' => 'Diet Steward: EB category known (MN-1), exact promotion years require departmental Scheme of Recruitment.',
        'POS-HWRDN' => 'House Warden: EB category known (MN-1), exact promotion years require departmental Scheme of Recruitment.',
        'POS-HLDVR' => 'Health Driver: EB category known (PL-3), exact promotion years require approved service/recruitment scheme.',
        'POS-HSOVR' => 'Hospital Overseer: recruitment category known, exact promotion ladder requires approved departmental scheme.',
        'POS-COOK' => 'Cook: verify current primary-level Scheme of Recruitment.',
        'POS-KKS' => 'KKS: verify current service designation and approved Scheme of Recruitment.',
        'POS-LIFTO' => 'Lift Operator: verify current primary technical Scheme of Recruitment.',
        'POS-SKSOD' => 'SKS Ordinary: verify current service designation and approved Scheme of Recruitment.',
        'POS-SKSJN' => 'SKS Junior: verify current service designation and approved Scheme of Recruitment.',
        'POS-SKSCS' => 'SKS Casual: casual designation should not be given automatic grade-promotion eligibility without an approved permanent-service mapping.',
        'POS-DATA-ENTRY' => 'Data Entry Operator: verify current service/class mapping before promotion automation.',
        'POS-ACT-CON' => 'Acting Consultant is an acting assignment, not a substantive promotion ladder.',
        'POS-ATNDNT' => 'Caregiver/Attendant: verify current primary semi-skilled Scheme of Recruitment and current permanent designation.',
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedDevelopmentOfficerCriteria();
            $this->seedNursingOfficerCriteria();
            $this->seedParamedicalCriteria();
            $this->seedPsmDescriptiveSafeguards();
            $this->reportManualReviewPositions();
        });
    }

    /**
     * DEVELOPMENT OFFICERS' SERVICE
     *
     * Official source:
     * Gazette Extraordinary No. 1745/11 dated 14.02.2012
     * (Minute of the Programme Officers' Service, effective 01.08.2011),
     * the service minute used by the Development Officers' Service page.
     *
     * Standard-performance route:
     * Grade III -> II : 10 active years in Grade III + 10 increments,
     *                    satisfactory performance, 5 satisfactory years,
     *                    language proficiency and relevant EB.
     * Grade II  -> I  : 10 active/satisfactory years in Grade II + 10 increments,
     *                    satisfactory performance, 5 satisfactory years,
     *                    relevant EB.
     *
     * Exceptional-performance alternatives (6 years / 9 years) are stored
     * descriptively but NOT used as Carder's automatic threshold. This keeps
     * auto-notifications on the conservative standard route.
     */
    private function seedDevelopmentOfficerCriteria(): void
    {
        $position = Position::query()->where('code', 'POS-DVOFF')->first();
        if (! $position) {
            return;
        }

        $this->upsertGrade($position->id, 'Grade III', 10, [
            'service' => "Development Officers' Service / Programme Officers' Service",
            'source' => 'Gazette Extraordinary No. 1745/11 dated 14.02.2012',
            'source_section' => 'Sections 10 and 12',
            'efficiency_bar_deadline_years' => 3,
            'automation_note' => 'Entry grade; no promotion threshold applies to this grade itself.',
        ]);

        $this->upsertGrade($position->id, 'Grade II', 20, [
            'min_years_in_grade' => 10,
            'required_previous_grade' => 'Grade III',
            'confirmed_in_appointment' => true,
            'required_salary_increments' => 10,
            'performance_period_years' => 10,
            'minimum_immediately_preceding_satisfactory_service_years' => 5,
            'other_official_language_required' => true,
            'relevant_efficiency_bar_required' => true,
            'exceptional_performance_path_available' => true,
            'exceptional_path_min_years_in_grade' => 6,
            'exceptional_path_salary_increments' => 6,
            'exceptional_path_test' => 'Written aptitude test; at least 60%',
            'source' => 'Gazette Extraordinary No. 1745/11 dated 14.02.2012',
            'source_section' => '12.1',
            'automation_basis' => 'Standard performance route only',
        ]);

        $this->upsertGrade($position->id, 'Grade I', 30, [
            'min_years_in_grade' => 10,
            'required_previous_grade' => 'Grade II',
            'required_salary_increments' => 10,
            'performance_period_years' => 10,
            'minimum_immediately_preceding_satisfactory_service_years' => 5,
            'relevant_efficiency_bar_required' => true,
            'exceptional_performance_path_available' => true,
            'exceptional_path_min_years_in_grade' => 9,
            'exceptional_path_salary_increments' => 9,
            'exceptional_path_method' => 'Structured interview; at least 50%',
            'source' => 'Gazette Extraordinary No. 1745/11 dated 14.02.2012',
            'source_section' => '12.2',
            'automation_basis' => 'Standard performance route only',
        ]);
    }

    /**
     * SRI LANKA NURSING SERVICE - Nursing Officer
     *
     * Sources:
     * - MoH General Circular 01-21/2020: Grade III -> Grade II application /
     *   implementation of new Nursing Service Minute: 5 active years in Grade III,
     *   earned salary increments, 5 satisfactory years, plus prescribed requirements.
     * - MoH General Circular 01-15/2023 and amendment 01-15/2023(i):
     *   Grade II -> Grade I application records completion of 5 years in Grade III,
     *   12 years total Nursing Service and salary increments during the 7 years
     *   immediately prior to Grade I promotion.
     * - Circular 01-15/2023 also addresses Grade I -> Supra Grade; published text
     *   references 7 active/satisfactory years in Grade I for the applicable route.
     *
     * NOTE:
     * Special Grade appointment/promotion is not automated here because it is
     * vacancy/seniority/selection dependent.
     */
    private function seedNursingOfficerCriteria(): void
    {
        $position = Position::query()->where('code', 'POS-NRSOF')->first();
        if (! $position) {
            return;
        }

        $this->upsertGrade($position->id, 'Grade III', 10, [
            'service' => 'Sri Lanka Nursing Service',
            'source' => 'MoH General Circular 01-21/2020',
            'automation_note' => 'Entry grade.',
        ]);

        $this->upsertGrade($position->id, 'Grade II', 20, [
            'min_years_in_grade' => 5,
            'required_previous_grade' => 'Grade III',
            'confirmed_in_appointment' => true,
            'salary_increments_for_grade_period_required' => true,
            'minimum_immediately_preceding_satisfactory_service_years' => 5,
            'other_official_language_requirement_applies' => true,
            'efficiency_bar_requirement_applies' => true,
            'disciplinary_clearance_required' => true,
            'source' => 'MoH General Circular 01-21/2020',
            'source_basis' => 'Application/implementation provisions for promotion Grade III -> Grade II',
        ]);

        $this->upsertGrade($position->id, 'Grade I', 30, [
            'min_years_in_grade' => 7,
            'required_previous_grade' => 'Grade II',
            'minimum_total_nursing_service_years' => 12,
            'minimum_grade_iii_service_years' => 5,
            'salary_increments_required_during_preceding_years' => 7,
            'second_efficiency_bar_required' => true,
            'disciplinary_clearance_required' => true,
            'source' => 'MoH General Circular 01-15/2023 + 01-15/2023(i)',
            'source_basis' => 'Form 2 Grade II -> Grade I: 5 years Grade III + 12 years total service; 7-year increment/service review',
            'automation_note' => 'min_years_in_grade=7 is used together with total-service metadata; final approval must verify both.',
        ]);

        $this->upsertGrade($position->id, 'Supra Grade', 40, [
            'min_years_in_grade' => 7,
            'required_previous_grade' => 'Grade I',
            'active_and_satisfactory_service_required' => true,
            'source' => 'MoH General Circular 01-15/2023',
            'source_basis' => 'Published provisions refer to 7 years continuous active and satisfactory service in Grade I for the applicable Supra Grade route.',
            'automation_note' => 'Special/transitional clauses may apply depending on appointment history; verify before final approval.',
        ]);

        // Do not add min_years_in_grade to Special Grade: it is not a simple
        // automatic time-based progression.
        $this->upsertGrade($position->id, 'Special Grade', 50, [
            'automation_disabled' => true,
            'selection_or_vacancy_dependent' => true,
            'source' => 'MoH Nursing Service promotion notices',
            'manual_verification_required' => true,
        ]);
    }

    /**
     * PARAMEDICAL SERVICE
     *
     * Current amended period source:
     * MoH General Circular Letter 02-81/2022, effective 05.07.2021.
     *
     * Grade III -> II:
     * - confirmed
     * - minimum 8 years active/satisfactory service INCLUDING training
     * - 5 salary increments
     * - satisfactory performance for preceding 5 years
     * - no disqualifying disciplinary punishment (PSC Circular 01/2020)
     * - other official language proficiency
     * - relevant EB
     *
     * Grade II -> I:
     * - confirmed
     * - for officers recruited on GCE A/L results: minimum 7 years active /
     *   satisfactory service and 7 increments
     * - satisfactory performance for preceding 7 years
     * - no disqualifying disciplinary punishment
     * - relevant EB
     *
     * Positions below are mapped by MoH circulars/exam notices to the
     * Paramedical Service.
     */
    private function seedParamedicalCriteria(): void
    {
        $positionCodes = [
            'POS-MIDWF', // Public Health Midwife
            'POS-PHINS', // Public Health Inspector
            'POS-DSPNS', // Dispenser
            'POS-ECGRC', // ECG/Cardiographer
            'POS-EEGRC', // EEG Recordist
        ];

        foreach ($positionCodes as $code) {
            $position = Position::query()->where('code', $code)->first();
            if (! $position) {
                continue;
            }

            $this->upsertGrade($position->id, 'Grade III', 10, [
                'service' => 'Paramedical Service',
                'source' => 'MoH General Circular Letter 02-81/2022',
                'effective_from' => '2021-07-05',
                'automation_note' => 'Entry grade.',
            ]);

            $this->upsertGrade($position->id, 'Grade II', 20, [
                'min_years_in_grade' => 8,
                'service_period_includes_training_period' => true,
                'required_previous_grade' => 'Grade III',
                'confirmed_in_appointment' => true,
                'required_salary_increments' => 5,
                'performance_period_years' => 5,
                'disciplinary_clearance_rule' => 'Public Service Commission Circular 01/2020',
                'other_official_language_required' => true,
                'relevant_efficiency_bar_required' => true,
                'source' => 'MoH General Circular Letter 02-81/2022',
                'effective_from' => '2021-07-05',
            ]);

            $this->upsertGrade($position->id, 'Grade I', 30, [
                'min_years_in_grade' => 7,
                'required_previous_grade' => 'Grade II',
                'confirmed_in_appointment' => true,
                'required_salary_increments' => 7,
                'performance_period_years' => 7,
                'disciplinary_clearance_rule' => 'Public Service Commission Circular 01/2020',
                'relevant_efficiency_bar_required' => true,
                'source' => 'MoH General Circular Letter 02-81/2022',
                'effective_from' => '2021-07-05',
                'scope_note' => 'The published clause explicitly describes officers recruited on G.C.E. (A/L) results; verify route for atypical entry streams.',
            ]);

            // Special Grade is not automatically time-driven.
            $this->upsertGrade($position->id, 'Special Grade', 50, [
                'automation_disabled' => true,
                'vacancy_or_seniority_selection_required' => true,
                'manual_verification_required' => true,
                'source' => 'MoH special-grade promotion notices for the relevant Paramedical post',
            ]);
        }
    }

    /**
     * SERVICE OF PROFESSIONS SUPPLEMENTARY TO MEDICINE (PSM)
     *
     * MoH General Circular 01-39/2018 verifies the service/post mapping and the
     * 10-year Grade III -> II / 10-year Grade II -> I scheme under sections
     * 10.1/10.2 of the new scheme. However, MoH published a later 2022 circular
     * specifically amending periods for PSM promotions.
     *
     * Because the exact English text of that later amendment is not reliably
     * available in the sources used to generate this seeder, this method
     * deliberately DOES NOT set `min_years_in_grade` for PSM. It stores the
     * verified service mapping and requires current amendment verification.
     */
    private function seedPsmDescriptiveSafeguards(): void
    {
        $positionCodes = [
            'POS-MLTEC',
            'POS-PHRMC',
            'POS-RADGP',
            'POS-PHYST',
            'POS-OCCTH',
            'POS-SPPTH',
        ];

        foreach ($positionCodes as $code) {
            $position = Position::query()->where('code', $code)->first();
            if (! $position) {
                continue;
            }

            foreach ([
                ['Grade III', 10],
                ['Grade II', 20],
                ['Grade I', 30],
                ['Supra Grade', 40],
                ['Special Grade', 50],
            ] as [$gradeName, $sortOrder]) {
                $criteria = [
                    'service' => 'Service of Professions Supplementary to Medicine',
                    'service_mapping_verified_by' => 'MoH General Circular 01-39/2018',
                    'historical_scheme_grade_iii_to_ii_years' => 10,
                    'historical_scheme_grade_ii_to_i_years' => 10,
                    'later_amendment_exists' => true,
                    'later_amendment_reference' => 'MoH promotion notice: Amending the Period Considered for Grade Promotions of PSM (02.09.2022)',
                    'automation_disabled' => true,
                    'manual_verification_required' => true,
                    'warning' => 'Do not add min_years_in_grade until the 2022 PSM amendment is verified from the authoritative text.',
                ];

                if ($gradeName === 'Grade II') {
                    $criteria['historical_requirements'] = [
                        'confirmed_in_appointment',
                        '10 years active/satisfactory Grade III',
                        '10 increments',
                        '10-year satisfactory performance',
                        '5 years immediately preceding satisfactory service',
                        'other official language',
                        'relevant efficiency bar',
                    ];
                } elseif ($gradeName === 'Grade I') {
                    $criteria['historical_requirements'] = [
                        '10 years active/satisfactory Grade II',
                        '10 increments',
                        '10-year satisfactory performance',
                        '5 years immediately preceding satisfactory service',
                        'relevant efficiency bar',
                    ];
                }

                $this->upsertGrade($position->id, $gradeName, $sortOrder, $criteria);
            }
        }
    }

    private function upsertGrade(
        int $positionId,
        string $gradeName,
        int $sortOrder,
        array $criteria
    ): void {
        $existing = PositionGrade::query()
            ->where('position_id', $positionId)
            ->where('name', $gradeName)
            ->first();

        $mergedCriteria = array_merge($existing?->criteria ?? [], $criteria);

        PositionGrade::updateOrCreate(
            [
                'position_id' => $positionId,
                'name' => $gradeName,
            ],
            [
                'sort_order' => $sortOrder,
                'criteria' => $mergedCriteria,
                'description' => $this->descriptionFor($criteria),
                'is_active' => true,
            ]
        );
    }

    private function descriptionFor(array $criteria): string
    {
        if (($criteria['automation_disabled'] ?? false) === true) {
            return 'Official service mapping recorded. Automatic promotion eligibility is disabled until the current Service Minute / Scheme of Recruitment is fully verified.';
        }

        return 'Promotion criteria seeded from the cited Sri Lankan Service Minute / Ministry of Health circular. Final promotion remains subject to HR verification, disciplinary clearance and the currently applicable legal/service provisions.';
    }

    private function reportManualReviewPositions(): void
    {
        $found = Position::query()
            ->whereIn('code', array_keys(self::MANUAL_REVIEW_POSITIONS))
            ->pluck('code')
            ->all();

        if ($found === []) {
            return;
        }

        $this->command?->warn(
            count($found).
            ' establishment position(s) remain intentionally excluded from automatic min-years promotion alerts pending service-minute verification.'
        );

        foreach ($found as $code) {
            $this->command?->line(
                " - {$code}: ".self::MANUAL_REVIEW_POSITIONS[$code]
            );
        }
    }
}
