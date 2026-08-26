<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Unit;
use App\Models\UnitType;
use Illuminate\Database\Seeder;

/**
 * WardCadreUnitSeeder
 * Seeds Unit Types and Units from the "Ward Cadre Allocation Report" —
 * Teaching Hospital Peradeniya (report generated 11 May 2026 by Dr. Dimini,
 * https://carder.slinfo.cloud/ward/ward_export.php).
 *
 * SOURCE STRUCTURE vs THIS SCHEMA
 * ────────────────────────────────
 * The source report groups units under section headers (A & E, ADMIN,
 * BLOOD BANK, …) — these become UnitType rows. Within several sections the
 * report shows a "Shared Cadre" parent unit with one or more "Sub-unit"
 * children (e.g. Dental → Dental ICU, Dental Wd, Dental Emergency Unit …).
 *
 * The `units` table is FLAT — there is no parent_unit_id column, so this
 * seeder does NOT alter the schema to add one. Instead, each sub-unit's
 * `name` is prefixed with its parent's name using an em-dash, e.g.
 * "Dental — ICU", so the relationship is preserved as readable text
 * without inventing hierarchy support that doesn't exist. A future
 * migration could add real parent/child support later without this
 * seeder needing to change — see the $structure array below, which keeps
 * parent/child grouping explicit in code even though it's flattened on
 * write.
 *
 * Several "Shared Cadre" parent units show 0/0/0 in the source report
 * (e.g. "Other", "Hospital Maintainance Unit", "Clinic (not attached to
 * the ward)") — no cadre is CURRENTLY allocated directly at that parent
 * level, only within its sub-units. These are still seeded as their own
 * Unit row: a 0/0/0 shared total is a real, currently-unused allocation
 * point, not a placeholder to discard — every "Shared Cadre" parent is
 * treated the same way regardless of its current total, which is more
 * defensible than selectively omitting only the empty ones.
 *
 * UNIT CODES
 * ──────────
 * The source report has no per-unit code of its own, and `units.code` is
 * required + unique (max 30 chars). Codes here are generated
 * deterministically as `{UnitTypeCode}-{sequence}` (e.g. WARD-014) rather
 * than hand-assigned, guaranteeing uniqueness without 90+ manually-typed
 * values that would be easy to duplicate by mistake.
 *
 * WHAT IS DELIBERATELY NOT IMPORTED
 * ──────────────────────────────────
 * This seeder creates Units and Unit Types ONLY — not cadre figures
 * (In Position / Additional Required / Total per position), not
 * ApprovedCarder records, not Employee records. Those are a separate,
 * much larger data-entry task (this single report has 90+ units × several
 * positions each) best handled through the Approved Carder / Unit Post
 * Allocation screens directly, or a dedicated import built specifically
 * for that once the unit structure below exists to attach it to.
 *
 * "Unallocated / Shared Cadre" (the report's final section, a hospital-
 * wide pool not tied to a physical location) is seeded as its own
 * UnitType with a single Unit, rather than forced into one of the 24
 * location-based categories it doesn't actually belong to.
 *
 * Safe to re-run — UnitType keyed on name, Unit keyed on code.
 *
 * Usage:
 *   php artisan db:seed --class=WardCadreUnitSeeder
 */
class WardCadreUnitSeeder extends Seeder
{
    public function run(): void
    {
        $sortOrder = 0;

        foreach ($this->structure() as $typeName => $type) {
            $sortOrder += 10;

            $unitType = UnitType::updateOrCreate(
                ['name' => $typeName],
                [
                    'code'       => $type['code'],
                    'description'=> $type['description'] ?? null,
                    'is_active'  => true,
                    'sort_order' => $sortOrder,
                ]
            );

            $sequence = 0;
            foreach ($type['units'] as $unitName) {
                $sequence++;
                $code = sprintf('%s-%03d', $type['code'], $sequence);

                Unit::updateOrCreate(
                    ['code' => $code],
                    [
                        'name'         => $unitName,
                        'unit_type_id' => $unitType->id,
                        'is_active'    => true,
                    ]
                );
            }
        }

        $this->command?->info('Ward Cadre unit structure seeded: '
            . UnitType::count() . ' unit type(s), '
            . Unit::count() . ' unit(s).');
    }

    /**
     * @return array<string, array{code: string, description?: string, units: array<int, string>}>
     */
    private function structure(): array
    {
        return [
            'A & E' => [
                'code' => 'AE',
                'units' => [
                    'Accident and Emergency Unit',
                ],
            ],

            'ADMIN' => [
                'code' => 'ADMIN',
                'units' => [
                    'Overseer Office',
                    'Administration Unit',
                    'Matron Office',
                ],
            ],

            'BLOOD BANK' => [
                'code' => 'BLOOD',
                'units' => [
                    'Blood Bank',
                ],
            ],

            'DENTAL' => [
                'code' => 'DENT',
                'units' => [
                    'Dental',                          // shared cadre, parent
                    'Dental — ICU',
                    'Dental — Wd',
                    'Dental — Emergency Unit',
                    'Dental — Office',
                    'Dental — Operation Theater',
                    'Dental — OMF Units',
                    'Dental OPD',
                ],
            ],

            'DIALYSIS' => [
                'code' => 'DIAL',
                'units' => [
                    'Hemodialysis Unit',
                ],
            ],

            'ECG' => [
                'code' => 'ECG',
                'units' => [
                    'ECG Unit',
                ],
            ],

            'EEG' => [
                'code' => 'EEG',
                'units' => [
                    'EEG Unit',
                ],
            ],

            'FINANCE' => [
                'code' => 'FIN',
                'units' => [
                    'Finance Unit',
                ],
            ],

            'ICU' => [
                'code' => 'ICU',
                'units' => [
                    'General ICU',
                ],
            ],

            'IT' => [
                'code' => 'IT',
                'units' => [
                    'Health Information Unit (IT)',
                ],
            ],

            'JMO' => [
                'code' => 'JMO',
                'units' => [
                    'JMO Unit',
                ],
            ],

            'LAB' => [
                'code' => 'LAB',
                'units' => [
                    'Cytology Lab',
                    'Microbiology Laboratory',
                    'Histopathology Lab',
                    'Chemical Pathology / Bio Chemistry Laboratory',
                    'General Laboratory',
                    'Hematology Laboratory',
                ],
            ],

            'MAINTAINANCE' => [
                'code' => 'MAINT',
                'units' => [
                    'Hospital Maintainance Unit',        // shared cadre, parent
                    'Hospital Maintainance — Electrical Technical Unit',
                    'Hospital Maintainance — Painting Unit',
                    'Hospital Maintainance — Oxygen Unit',
                    'Hospital Maintainance — Carpentry Unit',
                    'Hospital Maintainance — Plumber',
                ],
            ],

            'OTHER' => [
                'code' => 'OTH',
                'units' => [
                    'Quality Management Unit',
                    'Planning Unit',
                    'Other',                            // shared cadre, parent (0/0/0 in source — still a real allocation point, see class docblock)
                    'Other — LMC',
                    'Other — Lift Operator',
                    'Other — B.M.E Units',
                    'Other — House Warden',
                    'Public Health Unit',
                    'Infection Control Unit',
                    'Health Education Unit',
                    'Medical Records Room',               // shared cadre, parent
                    'Medical Records Room — Office Records Room',
                    'Telephone Exchange',
                ],
            ],

            'OUT PATIENT' => [
                'code' => 'OPD',
                'units' => [
                    'General OPD',
                ],
            ],

            'PBU / SBU' => [
                'code' => 'PBU',
                'units' => [
                    'SBU (Sick Baby Unit)',
                ],
            ],

            'PHARMACY & DRUG STORES' => [
                'code' => 'PHARM',
                'units' => [
                    'Clinic Pharmacy',
                    'OPD Pharmacy',                       // shared cadre, parent
                    'OPD Pharmacy — Staff Pharmacy',
                    'OPD Pharmacy — General Pharmacy Store',
                    'OPD Pharmacy — Indoor Pharmacy',
                    'OPD Pharmacy — Dental Store',
                    'OPD Pharmacy — Dental Pharmacy',
                    'OPD Pharmacy — Drug Information',
                ],
            ],

            'PHYSIOTHERAPHY' => [
                'code' => 'PHYSIO',
                'units' => [
                    'Physiotheraphy Unit',
                ],
            ],

            'RADIOLOGY UNIT' => [
                'code' => 'RAD',
                'units' => [
                    'Xray',
                ],
            ],

            'SPEECH / OCCUPATION THERAPHY' => [
                'code' => 'SPCH',
                'units' => [
                    'Occupational Theraphy Unit',
                    'Speech Theraphy Unit',
                ],
            ],

            'SUPPORTIVE SERVICES' => [
                'code' => 'SUPP',
                'units' => [
                    'Laundry',
                    'Hospital Diet Branch',
                    'Hospital Kitchen',
                    'Sewing Unit',
                ],
            ],

            'THEATER' => [
                'code' => 'THTR',
                'units' => [
                    'General Operating Theater',          // shared cadre, parent
                    'General Operating Theater — EOT',
                    'General Operating Theater — New Labour Room OT',
                ],
            ],

            'TRANSPORT' => [
                'code' => 'TRNS',
                'units' => [
                    'Transport Unit',
                ],
            ],

            'UNIT (WITHOUT WARD)' => [
                'code' => 'NOWD',
                'units' => [
                    'Clinic (not attached to the ward)',  // shared cadre, parent
                    'Clinic — Surgical Clinic',
                    'Clinic — EMG Clinic',
                    'Clinic — Gyn & Obs Clinic',
                    'Clinic — Paediatrics Clinic',
                    'Clinic — Medical Clinic',
                    'Clinic — Dermatology Clinic',
                    'Clinic — Elderly Clinic',
                    'VP OPD Clinic',
                    'Psychiatry Clinic / Sithsuwa',
                    'Clinical Nutrition Clinic',
                    'Sports Medicine Clinic',
                    'Mithuru Piyasa',
                    'Pain Management Clinic',
                ],
            ],

            'WARD / UNIT' => [
                'code' => 'WARD',
                'units' => [
                    'Endocrinology Unit',
                    'Obstetrics and Gynecology (New) Unit',   // shared cadre, parent
                    'Obstetrics and Gynecology (New) — Wd 18 (Female)',
                    'Obstetrics and Gynecology (New) — Wd 19',
                    'Orthopedics',                            // shared cadre, parent
                    'Orthopedics — Wd 16',
                    'Orthopedics — Wd 15',
                    'Neurology',                               // shared cadre, parent
                    'Neurology — Wd 12 (Female)',
                    'Neurology — Wd 12 (Male)',
                    'General Pediatrics (Wd 4 & Wd 5)',
                    'Obstetrics and Gynecology (Prof) Unit',  // shared cadre, parent
                    'Obstetrics and Gynecology (Prof) — Wd 3 (Female)',
                    'Obstetrics and Gynecology (Prof) — Wd 11',
                    'Obstetrics and Gynecology (Prof) — Wd 10',
                    'Clinical Hematology Unit',
                    'General Surgery',                        // shared cadre, parent
                    'General Surgery — Wd 1',
                    'General Surgery — Wd 2',
                    'General Surgery — Wd 20',
                    'General Surgery — Wd 21',
                    'Plastic Surgery — Wd 22 (Male / Female)',
                    'General Medicine',                       // shared cadre, parent
                    'General Medicine — Medical Ward (New) Female',
                    'General Medicine — Wd 7 (Female)',
                    'General Medicine — Wd 8 (Male)',
                    'General Medicine — Medical Ward (New) Male',
                    'Psychiatry Unit',                        // shared cadre, parent
                    'Psychiatry Unit — Wd 6 (Male)',
                    'Psychiatry Unit — Wd 9 (Female)',
                    'Toxicology — Wd 17',
                    'Cardiology Unit',
                    'Nephrology Unit — Wd 23 (Male / Female)',
                    'Labour Room — Common (Not attached to a ward)',
                ],
            ],

            'UNALLOCATED / SHARED' => [
                'code'        => 'UNALL',
                'description' => 'Hospital-wide cadre pool not yet assigned to a specific physical unit.',
                'units' => [
                    'Unallocated / Shared Cadre',
                ],
            ],
        ];
    }
}
