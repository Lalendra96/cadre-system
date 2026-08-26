<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Position;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * UnitPositionBindingSeeder
 *
 * Seeds unit_position (see that migration's docblock for what this scopes)
 * directly from the same Ward Cadre Allocation Report data already used by
 * WardCadreReportSeeder — NOT a separate judgement call. For every unit, a
 * position is bound if and only if the source report actually shows real
 * data for that position in that unit (either directly, or via one of its
 * subcategories — e.g. "Emergency Physician" data under Accident & Emergency
 * binds the parent "Medical Consultant" position there, not a subcategory).
 *
 * 113 of the 116 units get at least one binding. The remaining 3 are the
 * "shared cadre" parent placeholders with a 0/0/0 total in the source report
 * ("Hospital Maintainance Unit", "Other", "Clinic (not attached to the ward)")
 * — no position data belongs directly to them (only their sub-units), so they
 * correctly get zero bindings and fall back to showing every position, exactly
 * as UnitAllocationController's fallback logic intends for an unconfigured unit.
 *
 * Run WardCadreUnitSeeder and WardCadreReportSeeder FIRST.
 * Safe to re-run — sync() per unit is idempotent.
 *
 * Usage:
 *   php artisan db:seed --class=UnitPositionBindingSeeder
 */
class UnitPositionBindingSeeder extends Seeder
{
    public function run(): void
    {
        $officerId = User::havingRole(User::ROLE_SUPER_ADMIN)->value('id')
            ?? User::query()->value('id');

        $missingUnits = [];
        $missingPositions = [];
        $bound = 0;

        foreach ($this->data() as $unitName => $positionTitles) {
            $unit = Unit::where('name', $unitName)->first();
            if (! $unit) { $missingUnits[] = $unitName; continue; }

            $positionIds = [];
            foreach ($positionTitles as $title) {
                $position = Position::whereRaw('LOWER(title) = ?', [strtolower($title)])->first();
                if (! $position) {
                    $missingPositions[$title] = ($missingPositions[$title] ?? 0) + 1;
                    continue;
                }
                $positionIds[$position->id] = ['created_by' => $officerId];
            }

            $unit->boundPositions()->syncWithoutDetaching($positionIds);
            $bound += count($positionIds);
        }

        $this->command?->info("Unit-position bindings created: {$bound}");
        if ($missingUnits) {
            $this->command?->warn('Units not found: ' . implode(', ', array_unique($missingUnits)));
        }
        if ($missingPositions) {
            $this->command?->warn('Positions not found — bindings skipped:');
            foreach ($missingPositions as $title => $count) {
                $this->command?->warn("  \"{$title}\" — {$count} unit(s)");
            }
        }
    }

    /** @return array<string, array<int, string>> unit name => list of position titles */
    private function data(): array
    {
        return [
            'Accident and Emergency Unit' => ['Acting Consultant', 'Caregiver', 'Medical Consultant', 'Medical Officer', 'Nursing Officer', 'Nursing Sister / Master', 'SKS Casual', 'SKS Junior', 'SKS Ordinary'],
            'Administration Unit' => ['Administrative Officer', 'Development Officer', 'KKS', 'Medical Administrator (Deputy Grade)', 'Medical Administrator (Senior Grade)', 'Public Health Management Assistant', 'SKS Junior'],
            'Blood Bank' => ['Health Laboratory Aid', 'Medical Consultant', 'Medical Officer', 'Nursing Officer', 'SKS Junior', 'SKS Ordinary'],
            'Cardiology Unit' => ['Medical Consultant', 'Medical Officer', 'Nursing Officer', 'SKS Junior', 'SKS Ordinary'],
            'Chemical Pathology / Bio Chemistry Laboratory' => ['Acting Consultant', 'Health Laboratory Aid', 'Medical Consultant', 'Medical Laboratory Technologist'],
            'Clinic Pharmacy' => ['Pharmacist', 'SKS Junior', 'SKS Ordinary'],
            'Clinic — Dermatology Clinic' => ['Acting Consultant', 'Medical Consultant', 'Medical Officer', 'Nursing Officer', 'SKS Junior'],
            'Clinic — EMG Clinic' => ['SKS Junior', 'SKS Ordinary'],
            'Clinic — Elderly Clinic' => ['Acting Consultant', 'Medical Consultant', 'Medical Officer', 'Nursing Officer', 'SKS Junior', 'SKS Ordinary'],
            'Clinic — Gyn & Obs Clinic' => ['Midwife', 'Nursing Officer', 'SKS Junior', 'SKS Ordinary'],
            'Clinic — Medical Clinic' => ['Medical Officer', 'Nursing Officer', 'SKS Junior', 'SKS Ordinary'],
            'Clinic — Paediatrics Clinic' => ['Nursing Officer', 'SKS Junior', 'SKS Ordinary'],
            'Clinic — Surgical Clinic' => ['Nursing Officer', 'SKS Junior', 'SKS Ordinary'],
            'Clinical Hematology Unit' => ['Medical Officer', 'Nursing Officer', 'Nursing Sister / Master', 'SKS Junior', 'SKS Ordinary'],
            'Clinical Nutrition Clinic' => ['Acting Consultant', 'Medical Consultant', 'Medical Officer', 'Nursing Officer', 'Nutritionist', 'SKS Junior', 'SKS Ordinary'],
            'Cytology Lab' => ['Medical Laboratory Technologist'],
            'Dental' => ['Dental Surgeon'],
            'Dental OPD' => ['Nursing Officer', 'SKS Junior', 'SKS Ordinary'],
            'Dental — Emergency Unit' => ['Nursing Officer', 'Nursing Sister / Master', 'SKS Junior', 'SKS Ordinary'],
            'Dental — ICU' => ['Caregiver', 'Nursing Officer', 'Nursing Sister / Master', 'SKS Junior', 'SKS Ordinary'],
            'Dental — OMF Units' => ['Dental Surgeon', 'Nursing Officer', 'SKS Junior', 'SKS Ordinary'],
            'Dental — Office' => ['Development Officer', 'KKS', 'Matron', 'Medical Administrator (Deputy Grade)', 'Public Health Management Assistant', 'SKS Junior'],
            'Dental — Operation Theater' => ['Dental Surgeon', 'Nursing Officer', 'SKS Junior', 'SKS Ordinary'],
            'Dental — Wd' => ['Caregiver', 'Nursing Officer', 'Nursing Sister / Master', 'SKS Junior', 'SKS Ordinary'],
            'ECG Unit' => ['ECG Recordist', 'SKS Junior', 'SKS Ordinary'],
            'EEG Unit' => ['EEG Recordist', 'SKS Ordinary'],
            'Endocrinology Unit' => ['Medical Consultant', 'Medical Officer', 'Nursing Officer', 'SKS Junior'],
            'Finance Unit' => ['Accountant', 'Development Officer', 'ICT Assistant', 'Public Health Management Assistant', 'SKS Junior', 'SKS Ordinary'],
            'General ICU' => ['Medical Officer', 'Nursing Officer', 'Nursing Sister / Master', 'SKS Junior', 'SKS Ordinary'],
            'General Laboratory' => ['Medical Laboratory Technologist', 'Medical Officer', 'SKS Junior', 'SKS Ordinary'],
            'General Medicine' => ['Medical Consultant', 'Medical Officer'],
            'General Medicine — Medical Ward (New) Female' => ['Caregiver', 'Medical Consultant', 'Medical Officer', 'Nursing Officer', 'Nursing Sister / Master', 'SKS Junior'],
            'General Medicine — Medical Ward (New) Male' => ['Caregiver', 'Medical Consultant', 'Medical Officer', 'Nursing Officer', 'Nursing Sister / Master', 'SKS Junior'],
            'General Medicine — Wd 7 (Female)' => ['Caregiver', 'Nursing Officer', 'Nursing Sister / Master', 'SKS Junior', 'SKS Ordinary'],
            'General Medicine — Wd 8 (Male)' => ['Caregiver', 'Nursing Officer', 'Nursing Sister / Master', 'SKS Junior', 'SKS Ordinary'],
            'General OPD' => ['Medical Officer', 'Nursing Officer', 'Nursing Sister / Master', 'SKS Junior', 'SKS Ordinary'],
            'General Operating Theater' => ['Medical Officer', 'Nursing Officer', 'Nursing Sister / Master', 'SKS Junior', 'SKS Ordinary'],
            'General Operating Theater — EOT' => ['Nursing Officer', 'SKS Junior', 'SKS Ordinary'],
            'General Operating Theater — New Labour Room OT' => ['Medical Consultant', 'Medical Officer', 'Midwife', 'Nursing Officer', 'Nursing Sister / Master', 'SKS Junior'],
            'General Pediatrics (Wd 4 & Wd 5)' => ['Caregiver', 'Medical Officer', 'Nursing Officer', 'Nursing Sister / Master', 'SKS Junior', 'SKS Ordinary'],
            'General Surgery' => ['Medical Officer'],
            'General Surgery — Wd 1' => ['Caregiver', 'Nursing Officer', 'Nursing Sister / Master', 'SKS Junior', 'SKS Ordinary'],
            'General Surgery — Wd 2' => ['Caregiver', 'Nursing Officer', 'Nursing Sister / Master', 'SKS Junior', 'SKS Ordinary'],
            'General Surgery — Wd 20' => ['Caregiver', 'Nursing Officer', 'Nursing Sister / Master', 'SKS Junior', 'SKS Ordinary'],
            'General Surgery — Wd 21' => ['Caregiver', 'Nursing Officer', 'Nursing Sister / Master', 'SKS Junior', 'SKS Ordinary'],
            'Health Education Unit' => ['Medical Officer', 'Nursing Officer', 'SKS Junior'],
            'Health Information Unit (IT)' => ['Development Officer', 'Medical Consultant'],
            'Hematology Laboratory' => ['Health Laboratory Aid', 'Medical Laboratory Technologist', 'Medical Officer'],
            'Hemodialysis Unit' => ['Medical Officer', 'Nursing Officer', 'Nursing Sister / Master', 'SKS Junior', 'SKS Ordinary'],
            'Histopathology Lab' => ['Medical Consultant', 'Medical Laboratory Technologist', 'Medical Officer'],
            'Hospital Diet Branch' => ['Ward Clerk'],
            'Hospital Kitchen' => ['Cook', 'Diet Steward', 'SKS Junior', 'SKS Ordinary'],
            'Hospital Maintainance — Carpentry Unit' => ['Carpenter', 'SKS Junior', 'SKS Ordinary'],
            'Hospital Maintainance — Electrical Technical Unit' => ['Electrician', 'SKS Junior', 'SKS Ordinary'],
            'Hospital Maintainance — Oxygen Unit' => ['SKS Junior', 'SKS Ordinary'],
            'Hospital Maintainance — Painting Unit' => ['SKS Junior', 'SKS Ordinary'],
            'Hospital Maintainance — Plumber' => ['Plumber / Pump Machine Operator'],
            'Infection Control Unit' => ['Nursing Officer', 'SKS Junior', 'SKS Ordinary'],
            'JMO Unit' => ['Development Officer', 'Medical Consultant', 'Medical Officer', 'SKS Junior', 'SKS Ordinary'],
            'Labour Room — Common (Not attached to a ward)' => ['Midwife', 'Nursing Officer', 'Nursing Sister / Master', 'SKS Junior', 'SKS Ordinary'],
            'Laundry' => ['SKS Junior', 'SKS Ordinary'],
            'Matron Office' => ['Matron', 'Nursing Officer', 'Nursing Sister / Master', 'SKS Junior'],
            'Medical Records Room' => ['Medical Record Assistant', 'SKS Ordinary'],
            'Medical Records Room — Office Records Room' => ['Book Binder', 'Data Entry Operator', 'SKS Ordinary'],
            'Microbiology Laboratory' => ['Health Laboratory Aid', 'Medical Consultant', 'Medical Laboratory Technologist', 'Medical Officer'],
            'Mithuru Piyasa' => ['Medical Officer', 'Nursing Officer', 'SKS Junior'],
            'Nephrology Unit — Wd 23 (Male / Female)' => ['Medical Officer', 'Nursing Officer', 'Nursing Sister / Master', 'SKS Junior', 'SKS Ordinary'],
            'Neurology' => ['Acting Consultant', 'Medical Consultant', 'Medical Officer'],
            'Neurology — Wd 12 (Female)' => ['Caregiver', 'Nursing Officer', 'Nursing Sister / Master', 'SKS Junior', 'SKS Ordinary'],
            'Neurology — Wd 12 (Male)' => ['Caregiver', 'Nursing Officer', 'Nursing Sister / Master', 'SKS Ordinary'],
            'OPD Pharmacy' => ['Pharmacist', 'SKS Junior'],
            'OPD Pharmacy — Dental Pharmacy' => ['Pharmacist', 'SKS Ordinary'],
            'OPD Pharmacy — Dental Store' => ['Pharmacist', 'SKS Junior'],
            'OPD Pharmacy — Drug Information' => ['Pharmacist'],
            'OPD Pharmacy — General Pharmacy Store' => ['Pharmacist', 'SKS Junior', 'SKS Ordinary', 'Special Grade Pharmacist'],
            'OPD Pharmacy — Indoor Pharmacy' => ['Pharmacist', 'SKS Junior', 'SKS Ordinary'],
            'OPD Pharmacy — Staff Pharmacy' => ['Pharmacist', 'SKS Ordinary'],
            'Obstetrics and Gynecology (New) Unit' => ['Medical Consultant', 'Medical Officer'],
            'Obstetrics and Gynecology (New) — Wd 18 (Female)' => ['Midwife', 'Nursing Officer', 'SKS Junior', 'SKS Ordinary'],
            'Obstetrics and Gynecology (New) — Wd 19' => ['Caregiver', 'Nursing Officer', 'Nursing Sister / Master', 'SKS Junior', 'SKS Ordinary'],
            'Obstetrics and Gynecology (Prof) Unit' => ['Medical Officer'],
            'Obstetrics and Gynecology (Prof) — Wd 10' => ['Caregiver', 'Midwife', 'Nursing Officer', 'Nursing Sister / Master', 'SKS Junior', 'SKS Ordinary'],
            'Obstetrics and Gynecology (Prof) — Wd 11' => ['Caregiver', 'Midwife', 'Nursing Officer', 'Nursing Sister / Master', 'SKS Junior', 'SKS Ordinary'],
            'Obstetrics and Gynecology (Prof) — Wd 3 (Female)' => ['Caregiver', 'Nursing Officer', 'Nursing Sister / Master', 'SKS Junior', 'SKS Ordinary'],
            'Occupational Theraphy Unit' => ['Occupational Therapist', 'SKS Ordinary'],
            'Orthopedics' => ['Acting Consultant', 'Medical Consultant', 'Medical Officer'],
            'Orthopedics — Wd 15' => ['Caregiver', 'Nursing Officer', 'Nursing Sister / Master', 'SKS Junior', 'SKS Ordinary'],
            'Orthopedics — Wd 16' => ['Caregiver', 'Nursing Officer', 'Nursing Sister / Master', 'Orthopaedic Technician', 'SKS Junior', 'SKS Ordinary'],
            'Other — B.M.E Units' => ['Bio Medical Engineer', 'Bio Medical Engineering Assistant'],
            'Other — House Warden' => ['House Warden'],
            'Other — LMC' => ['Nursing Officer'],
            'Other — Lift Operator' => ['Lift Operator'],
            'Overseer Office' => ['Hospital Overseer', 'SKS Junior', 'SKS Ordinary'],
            'Pain Management Clinic' => ['Medical Officer', 'Nursing Officer', 'SKS Junior', 'SKS Ordinary'],
            'Physiotheraphy Unit' => ['Physiotherapist', 'SKS Junior', 'SKS Ordinary'],
            'Planning Unit' => ['Development Officer', 'Medical Officer', 'SKS Junior', 'SKS Ordinary'],
            'Plastic Surgery — Wd 22 (Male / Female)' => ['Medical Consultant', 'Medical Officer', 'Nursing Officer', 'Nursing Sister / Master', 'SKS Junior', 'SKS Ordinary'],
            'Psychiatry Clinic / Sithsuwa' => ['Medical Officer', 'Nursing Officer', 'Psychiatric Social Worker', 'SKS Junior', 'SKS Ordinary'],
            'Psychiatry Unit' => ['Medical Officer'],
            'Psychiatry Unit — Wd 6 (Male)' => ['Caregiver', 'Nursing Officer', 'SKS Junior', 'SKS Ordinary'],
            'Psychiatry Unit — Wd 9 (Female)' => ['Caregiver', 'Nursing Officer', 'Nursing Sister / Master', 'Psychiatric Social Worker', 'SKS Junior', 'SKS Ordinary'],
            'Public Health Unit' => ['Development Officer', 'Medical Officer', 'Nursing Officer', 'Public Health Field Officer', 'Public Health Inspector'],
            'Quality Management Unit' => ['Development Officer', 'Nursing Officer', 'SKS Junior', 'SKS Ordinary'],
            'SBU (Sick Baby Unit)' => ['Medical Consultant', 'Medical Officer', 'Nursing Officer', 'Nursing Sister / Master', 'SKS Junior', 'SKS Ordinary'],
            'Sewing Unit' => ['Sewing Machine Operator'],
            'Speech Theraphy Unit' => ['SKS Junior', 'Speech Therapist'],
            'Sports Medicine Clinic' => ['Acting Consultant', 'Medical Consultant', 'Medical Officer', 'Nursing Officer', 'SKS Junior', 'SKS Ordinary'],
            'Telephone Exchange' => ['Boiler Operator', 'SKS Ordinary', 'Telephone Operator'],
            'Toxicology — Wd 17' => ['Caregiver', 'Medical Officer', 'Nursing Officer', 'SKS Junior', 'SKS Ordinary'],
            'Transport Unit' => ['Health Driver', 'SKS Junior', 'SKS Ordinary'],
            'Unallocated / Shared Cadre' => ['Acting Consultant', 'Medical Officer', 'SKS Ordinary'],
            'VP OPD Clinic' => ['Medical Consultant', 'Medical Officer', 'Nursing Officer', 'Nursing Sister / Master', 'SKS Junior', 'SKS Ordinary'],
            'Xray' => ['Medical Consultant', 'Medical Officer', 'Nursing Officer', 'Radiographer', 'SKS Junior', 'SKS Ordinary'],
        ];
    }
}
