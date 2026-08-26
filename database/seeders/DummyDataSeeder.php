<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ActingAppointment;
use App\Models\ApprovedCarder;
use App\Models\CarderMonthlyEntry;
use App\Models\Employee;
use App\Models\Letter;
use App\Models\LetterAttachment;
use App\Models\LetterRecipient;
use App\Models\Position;
use App\Models\SubjectCode;
use App\Models\TransferRecord;
use App\Models\Unit;
use App\Models\UnitPositionAllocation;
use App\Models\UnitType;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * DummyDataSeeder
 *
 * Seeds a realistic set of TEST/DEMO data for Teaching Hospital Peradeniya.
 * For local development, staging, and training environments ONLY.
 *
 * Run AFTER UserCategorySeeder and SuperAdminSeeder.
 *
 * Usage:
 *   php artisan db:seed --class=DummyDataSeeder
 *
 * What it creates:
 *   - 9 unit types (Ward, Department, OPD, Theatre, ICU, Lab, Pharmacy, Radiology, Admin)
 *   - 18 units across those types
 *   - 12 positions (nursing, medical, paramedical, admin)
 *   - 8 subject codes linked to positions
 *   - 11 users (subject officers, planning officer, admin group members)
 *   - 46 employee profiles
 *   - Approved carder for current and previous year
 *   - Carder entries (submitted / verified mix)
 *   - 4 acting appointments
 *   - 4 transfer records
 *   - 2 letters with recipients
 *   - Unit post allocations for current year
 *
 * ── DATA SECURITY: DO NOT RUN AGAINST REAL PRODUCTION DATA ─────────────
 * This codebase also ships REAL data seeders sourced from official MoH
 * Excel registers:
 *   - ApprovedCarderSeeder      (57 real positions, official approved cadre)
 *   - SubjectCodeOfficerSeeder  (30 real subject codes + assigned officers)
 *   - MonthlyEntrySeeder        (real headcount snapshot, March 2026)
 *
 * Running this dummy seeder in an environment where any of those have
 * already run risks creating DUPLICATE or CONFLICTING records — e.g. a
 * second "Staff Nurse" Position row under a different code, or dummy
 * employees mixed into a real establishment register. run() therefore
 * hard-aborts (does not silently skip) if it detects that any real
 * seeder's data is present, rather than relying on a single idempotency
 * check that could be bypassed by seeding order.
 *
 * SAFE TO RE-RUN in a clean dummy/demo database: wraps everything in a
 * transaction and skips if the position "Staff Nurse" already exists.
 *
 * ── SCHEMA ALIGNMENT (last verified 2026-07-06) ─────────────────────────
 * Cross-checked every field against current $fillable arrays and NOT NULL
 * migration constraints. Fixed in this pass:
 *   - employees.salutation      NOT NULL — was missing entirely
 *   - employees.whatsapp_mobile NOT NULL — was missing entirely
 *   - employees.gender          char(1) — was 'male'/'female', now 'M'/'F'
 *   - letters.description       — seeder used non-existent 'content' key
 *   - Letter model $fillable    — was missing is_active/disabled_* entirely,
 *     which silently broke the letter-withdrawal disable workflow for ALL
 *     letters, not just seeded ones (fixed in app/Models/Letter.php)
 */
class DummyDataSeeder extends Seeder
{
    private int $currentYear;
    private int $planningOfficerUserId;

    public function run(): void
    {
        // ── Hard safety guard ────────────────────────────────────────────
        // Abort (not skip) if any real-data seeder appears to have already
        // run. A silent skip could mask the fact that dummy data was never
        // seeded when a developer expected it, or worse, run halfway before
        // hitting a collision. Fail loud and early instead.
        //
        // Thresholds are set above what THIS seeder itself ever creates
        // (8 subject codes, 12 positions), so a real seeder run is the
        // only thing that could trip them.
        $realDataMarkers = [
            'SubjectCodeOfficerSeeder data (30 real subject codes)' => SubjectCode::count() > 8,
            'ApprovedCarderSeeder data (57 real positions)'         => Position::count() > 12,
        ];

        foreach ($realDataMarkers as $label => $present) {
            if ($present) {
                $this->command->error(
                    "DummyDataSeeder ABORTED: detected existing data matching {$label}. " .
                    'Running dummy/test data on top of real production data risks corrupting ' .
                    'the establishment register. If this is genuinely a fresh dummy/demo ' .
                    'environment, truncate the affected tables first.'
                );
                return;
            }
        }

        if (Position::where('title', 'Staff Nurse')->exists()) {
            $this->command->info('DummyDataSeeder: dummy data already present — skipping.');
            return;
        }

        $this->currentYear = now()->year;

        DB::transaction(function () {
            $this->seedUnitTypes();
            $this->seedUnits();
            $this->seedPositions();
            $this->seedSubjectCodes();
            $this->seedUsers();
            $this->seedApprovedCarder();
            $this->seedEmployees();
            $this->seedCarderEntries();
            $this->seedActingAppointments();
            $this->seedTransferRecords();
            $this->seedUnitAllocations();
            $this->seedLetters();
        });

        $this->command->info('DummyDataSeeder: all test data created successfully.');
    }

    // ── Unit Types ────────────────────────────────────────────────────────

    private function seedUnitTypes(): void
    {
        $types = [
            ['name' => 'Ward',            'code' => 'WARD', 'sort_order' => 10, 'description' => 'Inpatient wards'],
            ['name' => 'Department',      'code' => 'DEPT', 'sort_order' => 20, 'description' => 'Clinical departments'],
            ['name' => 'OPD Clinic',      'code' => 'OPD',  'sort_order' => 30, 'description' => 'Outpatient clinics'],
            ['name' => 'Operating Theatre','code' => 'OT',  'sort_order' => 40, 'description' => 'Surgical theatres'],
            ['name' => 'ICU / HDU',       'code' => 'ICU',  'sort_order' => 50, 'description' => 'Intensive and high-dependency care'],
            ['name' => 'Laboratory',      'code' => 'LAB',  'sort_order' => 60, 'description' => 'Diagnostic laboratories'],
            ['name' => 'Pharmacy',        'code' => 'PHRM', 'sort_order' => 70, 'description' => 'Pharmacy units'],
            ['name' => 'Radiology',       'code' => 'RAD',  'sort_order' => 80, 'description' => 'Imaging and radiology'],
            ['name' => 'Administration',  'code' => 'ADMIN','sort_order' => 90, 'description' => 'Administrative offices'],
        ];

        foreach ($types as $t) {
            UnitType::firstOrCreate(['code' => $t['code']], array_merge($t, ['is_active' => true]));
        }
    }

    // ── Units ─────────────────────────────────────────────────────────────

    private function seedUnits(): void
    {
        $typeId = fn (string $code) => UnitType::where('code', $code)->value('id');

        $units = [
            ['code' => 'WARD-01', 'name' => 'Ward 1 — General Medicine',     'unit_type_id' => $typeId('WARD'),  'location' => 'Block A, Ground Floor'],
            ['code' => 'WARD-02', 'name' => 'Ward 2 — General Surgery',       'unit_type_id' => $typeId('WARD'),  'location' => 'Block A, 1st Floor'],
            ['code' => 'WARD-03', 'name' => 'Ward 3 — Gynaecology',           'unit_type_id' => $typeId('WARD'),  'location' => 'Block B, Ground Floor'],
            ['code' => 'WARD-04', 'name' => 'Ward 4 — Paediatrics',           'unit_type_id' => $typeId('WARD'),  'location' => 'Block B, 1st Floor'],
            ['code' => 'WARD-05', 'name' => 'Ward 5 — Orthopaedics',          'unit_type_id' => $typeId('WARD'),  'location' => 'Block C, Ground Floor'],
            ['code' => 'ICU-01',  'name' => 'Medical ICU',                    'unit_type_id' => $typeId('ICU'),   'location' => 'Block A, 2nd Floor'],
            ['code' => 'ICU-02',  'name' => 'Surgical ICU',                   'unit_type_id' => $typeId('ICU'),   'location' => 'Block C, 2nd Floor'],
            ['code' => 'OT-01',   'name' => 'Main Operating Theatre',         'unit_type_id' => $typeId('OT'),    'location' => 'Block D, Ground Floor'],
            ['code' => 'OT-02',   'name' => 'Emergency Theatre',              'unit_type_id' => $typeId('OT'),    'location' => 'Block D, 1st Floor'],
            ['code' => 'OPD-MED', 'name' => 'Medical OPD',                   'unit_type_id' => $typeId('OPD'),   'location' => 'Main Building, Ground Floor'],
            ['code' => 'OPD-SUR', 'name' => 'Surgical OPD',                  'unit_type_id' => $typeId('OPD'),   'location' => 'Main Building, Ground Floor'],
            ['code' => 'LAB-01',  'name' => 'Clinical Laboratory',            'unit_type_id' => $typeId('LAB'),   'location' => 'Block E, Ground Floor'],
            ['code' => 'LAB-02',  'name' => 'Blood Bank',                     'unit_type_id' => $typeId('LAB'),   'location' => 'Block E, Ground Floor'],
            ['code' => 'PHRM-01', 'name' => 'Main Pharmacy',                  'unit_type_id' => $typeId('PHRM'), 'location' => 'Main Building, Ground Floor'],
            ['code' => 'RAD-01',  'name' => 'Radiology — X-Ray & CT',         'unit_type_id' => $typeId('RAD'),   'location' => 'Block F, Ground Floor'],
            ['code' => 'ADMIN-01','name' => 'Hospital Administration Office',  'unit_type_id' => $typeId('ADMIN'),'location' => 'Main Building, 3rd Floor'],
            ['code' => 'ADMIN-02','name' => 'Medical Records',                 'unit_type_id' => $typeId('ADMIN'),'location' => 'Main Building, Ground Floor'],
            ['code' => 'ADMIN-03','name' => 'Finance Department',              'unit_type_id' => $typeId('ADMIN'),'location' => 'Main Building, 2nd Floor'],
        ];

        foreach ($units as $u) {
            Unit::firstOrCreate(['code' => $u['code']], array_merge($u, ['is_active' => true]));
        }
    }

    // ── Positions ─────────────────────────────────────────────────────────

    private function seedPositions(): void
    {
        $positions = [
            ['code' => 'SN',   'title' => 'Staff Nurse',                  'vacancy_threshold_pct' => 20],
            ['code' => 'NO',   'title' => 'Nursing Officer',               'vacancy_threshold_pct' => 15],
            ['code' => 'SI',   'title' => 'Sister I',                      'vacancy_threshold_pct' => 25],
            ['code' => 'SII',  'title' => 'Sister II',                     'vacancy_threshold_pct' => 25],
            ['code' => 'MO',   'title' => 'Medical Officer',               'vacancy_threshold_pct' => 10],
            ['code' => 'MLT',  'title' => 'Medical Laboratory Technician', 'vacancy_threshold_pct' => 20],
            ['code' => 'PHARM','title' => 'Pharmacist',                    'vacancy_threshold_pct' => 20],
            ['code' => 'RAD',  'title' => 'Radiographer',                  'vacancy_threshold_pct' => 20],
            ['code' => 'CLERK','title' => 'Hospital Clerk',                'vacancy_threshold_pct' => 30],
            ['code' => 'OA',   'title' => 'Office Assistant',              'vacancy_threshold_pct' => 30],
            ['code' => 'PHI',  'title' => 'Public Health Inspector',       'vacancy_threshold_pct' => 25],
            ['code' => 'DRIV', 'title' => 'Driver',                        'vacancy_threshold_pct' => 30],
        ];

        foreach ($positions as $p) {
            Position::firstOrCreate(['code' => $p['code']], array_merge($p, ['is_active' => true]));
        }
    }

    // ── Subject Codes ─────────────────────────────────────────────────────

    private function seedSubjectCodes(): void
    {
        $posId = fn (string $code) => Position::where('code', $code)->value('id');

        $codes = [
            ['code' => 'EA', 'name' => 'Nursing — Staff Nurse',             'positions' => ['SN', 'NO']],
            ['code' => 'EB', 'name' => 'Nursing — Sister Grade',            'positions' => ['SI', 'SII']],
            ['code' => 'CA', 'name' => 'Medical Officers',                  'positions' => ['MO']],
            ['code' => 'FA', 'name' => 'Medical Laboratory Technicians',    'positions' => ['MLT']],
            ['code' => 'GA', 'name' => 'Pharmacists',                       'positions' => ['PHARM']],
            ['code' => 'HA', 'name' => 'Radiographers',                     'positions' => ['RAD']],
            ['code' => 'KA', 'name' => 'Hospital Clerks & Office Staff',    'positions' => ['CLERK', 'OA']],
            ['code' => 'LA', 'name' => 'Drivers & Support Staff',           'positions' => ['DRIV']],
        ];

        foreach ($codes as $c) {
            $positions = $c['positions'];
            unset($c['positions']);

            $sc = SubjectCode::firstOrCreate(['code' => $c['code']], array_merge($c, ['is_active' => true]));

            $positionIds = array_filter(array_map(fn ($p) => $posId($p), $positions));
            $sc->positions()->syncWithoutDetaching($positionIds);
        }
    }

    // ── Users ─────────────────────────────────────────────────────────────

    private function seedUsers(): void
    {
        $getCategory = fn (string $name) => \App\Models\UserCategory::where('name', $name)->value('id');

        // Planning Officer
        $po = User::firstOrCreate(['email' => 'planning@hims.local'], [
            'name'                 => 'Mr. Nimal Perera',
            'password'             => Hash::make('Password@123'),
            'is_active'            => true,
            'force_password_change' => false,
        ]);
        UserRole::firstOrCreate(['user_id' => $po->id, 'role' => 'planning_officer']);
        $this->planningOfficerUserId = $po->id;

        // Medical Officer Planning (Admin Group)
        $mop = User::firstOrCreate(['email' => 'mop@hims.local'], [
            'name'        => 'Dr. Sunil Wijesinghe',
            'password'    => Hash::make('Password@123'),
            'is_active'   => true,
            'category_id' => $getCategory('Medical Officer Planning'),
            'force_password_change' => false,
        ]);
        UserRole::firstOrCreate(['user_id' => $mop->id, 'role' => 'admin_group']);

        // Director
        $dir = User::firstOrCreate(['email' => 'director@hims.local'], [
            'name'        => 'Dr. Chaminda Silva',
            'password'    => Hash::make('Password@123'),
            'is_active'   => true,
            'category_id' => $getCategory('Director'),
            'force_password_change' => false,
        ]);
        UserRole::firstOrCreate(['user_id' => $dir->id, 'role' => 'admin_group']);

        // Deputy Director General
        $ddg = User::firstOrCreate(['email' => 'ddg@hims.local'], [
            'name'        => 'Dr. Ranjith Fernando',
            'password'    => Hash::make('Password@123'),
            'is_active'   => true,
            'category_id' => $getCategory('Deputy Director General'),
            'force_password_change' => false,
        ]);
        UserRole::firstOrCreate(['user_id' => $ddg->id, 'role' => 'admin_group']);

        // Chief Clerk
        $cc = User::firstOrCreate(['email' => 'chief.clerk@hims.local'], [
            'name'        => 'Mrs. Kamala Jayasena',
            'password'    => Hash::make('Password@123'),
            'is_active'   => true,
            'category_id' => $getCategory('Chief Clerk'),
            'force_password_change' => false,
        ]);
        UserRole::firstOrCreate(['user_id' => $cc->id, 'role' => 'admin_group']);

        // Subject Officers — one per subject code
        $officers = [
            ['email' => 'officer.ea@hims.local', 'name' => 'Ms. Dilrukshi Rathnayake', 'code' => 'EA'],
            ['email' => 'officer.eb@hims.local', 'name' => 'Ms. Sandya Dissanayake',   'code' => 'EB'],
            ['email' => 'officer.ca@hims.local', 'name' => 'Dr. Pradeep Kumara',       'code' => 'CA'],
            ['email' => 'officer.fa@hims.local', 'name' => 'Mr. Kasun Wickramasinghe', 'code' => 'FA'],
            ['email' => 'officer.ga@hims.local', 'name' => 'Ms. Thilini Gamage',       'code' => 'GA'],
            ['email' => 'officer.ka@hims.local', 'name' => 'Mr. Saman Bandara',        'code' => 'KA'],
        ];

        foreach ($officers as $o) {
            $sc   = SubjectCode::where('code', $o['code'])->first();
            $user = User::firstOrCreate(['email' => $o['email']], [
                'name'                  => $o['name'],
                'password'              => Hash::make('Password@123'),
                'is_active'             => true,
                'can_view_employees'    => true,
                'can_view_letters'      => true,
                'force_password_change' => false,
            ]);
            UserRole::firstOrCreate(['user_id' => $user->id, 'role' => 'subject_officer']);

            if ($sc) {
                $user->subjectCodes()->syncWithoutDetaching([$sc->id]);
            }
        }
    }

    // ── Approved Carder ───────────────────────────────────────────────────

    private function seedApprovedCarder(): void
    {
        $directorId = User::where('email', 'director@hims.local')->value('id')
            ?? User::whereHas('userRoles', fn ($q) => $q->where('role', 'super_admin'))->value('id');

        $carder = [
            ['position' => 'Staff Nurse',                  'amounts' => [280, 295]],
            ['position' => 'Nursing Officer',              'amounts' => [45, 48]],
            ['position' => 'Sister I',                     'amounts' => [18, 18]],
            ['position' => 'Sister II',                    'amounts' => [6,  7]],
            ['position' => 'Medical Officer',              'amounts' => [52, 55]],
            ['position' => 'Medical Laboratory Technician','amounts' => [34, 36]],
            ['position' => 'Pharmacist',                   'amounts' => [12, 12]],
            ['position' => 'Radiographer',                 'amounts' => [8,  9]],
            ['position' => 'Hospital Clerk',               'amounts' => [28, 30]],
            ['position' => 'Office Assistant',             'amounts' => [20, 22]],
            ['position' => 'Public Health Inspector',      'amounts' => [6,  6]],
            ['position' => 'Driver',                       'amounts' => [14, 15]],
        ];

        foreach ($carder as $row) {
            $posId = Position::where('title', $row['position'])->value('id');
            if (! $posId) continue;

            foreach ([$this->currentYear - 1, $this->currentYear] as $i => $year) {
                ApprovedCarder::firstOrCreate(
                    ['position_id' => $posId, 'year' => $year],
                    [
                        'approved_amount'       => $row['amounts'][$i],
                        'ministry_reference_no' => "MoH/HRD/{$year}/APRVD-" . str_pad((string) $posId, 3, '0', STR_PAD_LEFT),
                        'approved_date'         => "{$year}-01-01",
                        'created_by'            => $directorId,
                        'is_active'             => true,
                    ]
                );
            }
        }
    }

    // ── Employees ─────────────────────────────────────────────────────────

    private function seedEmployees(): void
    {
        $posId  = fn ($t) => Position::where('title', $t)->value('id');
        $scId   = fn ($c) => SubjectCode::where('code', $c)->value('id');
        $unitId = fn ($c) => Unit::where('code', $c)->value('id');

        // [pay_no, salutation, name, gender(M/F/O), whatsapp_mobile, dob, appointment_date, retirement_age, position, subject_code, unit]
        //
        // NOTE ON SCHEMA ALIGNMENT (fixed 2026-07-06):
        //   employees.salutation and employees.whatsapp_mobile are NOT NULL
        //   in the database (migration 2025_06_25_000011_create_employees_table).
        //   employees.gender is char(1) — must be exactly 'M', 'F', or 'O' to
        //   match both the DB column width and EmployeeRequest::rules()
        //   Rule::in(['M','F','O']). Earlier revisions of this seeder used
        //   full words ('male'/'female') and omitted salutation/whatsapp_mobile
        //   entirely, which would have thrown a NOT NULL / value-too-long
        //   PostgreSQL error on the very first insert and rolled back the
        //   entire seeder (it runs inside DB::transaction in run()).
        $employees = [
            // Nursing — Staff Nurse (EA)
            ['PN-10001','Ms.','Kamani Senevirathne','F','0771000001','1985-03-12','2010-06-01',55,'Staff Nurse','EA','WARD-01'],
            ['PN-10002','Ms.','Nilmini Jayawardena','F','0771000002','1990-07-22','2015-03-15',55,'Staff Nurse','EA','WARD-01'],
            ['PN-10003','Ms.','Ruwanthika Senanayake','F','0771000003','1988-11-05','2013-01-10',55,'Staff Nurse','EA','WARD-02'],
            ['PN-10004','Ms.','Chathuri Marasinghe','F','0771000004','1992-04-18','2017-08-01',55,'Staff Nurse','EA','WARD-02'],
            ['PN-10005','Ms.','Prasadi Wijesinghe','F','0771000005','1987-09-30','2012-11-15',55,'Staff Nurse','EA','WARD-03'],
            ['PN-10006','Ms.','Lalitha Bandara','F','0771000006','1983-01-25','2008-04-01',55,'Staff Nurse','EA','WARD-04'],
            ['PN-10007','Ms.','Sachini Perera','F','0771000007','1993-06-14','2018-02-20',55,'Staff Nurse','EA','WARD-04'],
            ['PN-10008','Ms.','Imesha Fernando','F','0771000008','1991-08-08','2016-07-01',55,'Staff Nurse','EA','WARD-05'],
            ['PN-10009','Ms.','Thilini Gunawardena','F','0771000009','1986-12-03','2011-09-01',55,'Staff Nurse','EA','ICU-01'],
            ['PN-10010','Ms.','Hasini Rajapaksa','F','0771000010','1994-02-17','2019-01-15',55,'Staff Nurse','EA','ICU-01'],

            // Nursing Officer (EA)
            ['PN-10011','Ms.','Samanthi Kumari','F','0771000011','1980-05-20','2005-03-01',55,'Nursing Officer','EA','WARD-01'],
            ['PN-10012','Ms.','Renuka Dissanayake','F','0771000012','1978-10-11','2003-07-15',55,'Nursing Officer','EA','WARD-03'],
            ['PN-10013','Mr.','Sanjeewa Wijesiri','M','0771000013','1982-03-28','2007-11-01',55,'Nursing Officer','EA','ICU-02'],
            ['PN-10014','Ms.','Kumudini Fonseka','F','0771000014','1979-07-06','2004-05-01',55,'Nursing Officer','EA','OT-01'],
            ['PN-10015','Ms.','Nalini Jayaratne','F','0771000015','1975-12-14','2000-01-10',55,'Nursing Officer','EA','OPD-MED'],

            // Sisters (EB)
            ['PN-10016','Ms.','Sriyani Wickramasinghe','F','0771000016','1970-04-02','1997-06-01',55,'Sister I','EB','WARD-01'],
            ['PN-10017','Ms.','Chandrika Herath','F','0771000017','1968-09-18','1995-03-01',55,'Sister I','EB','WARD-02'],
            ['PN-10018','Ms.','Padma Karunanayake','F','0771000018','1965-01-30','1992-08-01',55,'Sister II','EB','WARD-03'],
            ['PN-10019','Ms.','Swarna Ranasinghe','F','0771000019','1972-06-22','1999-01-01',55,'Sister I','EB','ICU-01'],
            ['PN-10020','Ms.','Kamala Liyanage','F','0771000020','1967-11-05','1994-04-01',55,'Sister II','EB','OT-01'],

            // Medical Officers (CA)
            ['PN-20001','Dr.','Ashan Rodrigo','M','0771020001','1982-08-15','2010-01-15',60,'Medical Officer','CA','WARD-01'],
            ['PN-20002','Dr.','Thushara Gunatilaka','M','0771020002','1985-03-22','2013-06-01',60,'Medical Officer','CA','WARD-02'],
            ['PN-20003','Dr.','Nimasha Jayawickrama','F','0771020003','1988-12-07','2016-03-01',60,'Medical Officer','CA','OPD-MED'],
            ['PN-20004','Dr.','Kasun Madushanka','M','0771020004','1983-05-19','2011-09-01',60,'Medical Officer','CA','ICU-01'],
            ['PN-20005','Dr.','Priyantha Abeysinghe','M','0771020005','1979-10-30','2007-01-01',60,'Medical Officer','CA','OT-01'],
            ['PN-20006','Dr.','Shanika Samaranayake','F','0771020006','1990-07-14','2018-04-01',60,'Medical Officer','CA','OPD-SUR'],

            // MLT (FA)
            ['PN-30001','Mr.','Ruwan Senevirathne','M','0771030001','1984-02-11','2009-07-01',60,'Medical Laboratory Technician','FA','LAB-01'],
            ['PN-30002','Ms.','Dilhani Wijerathne','F','0771030002','1987-08-24','2012-01-15',60,'Medical Laboratory Technician','FA','LAB-01'],
            ['PN-30003','Ms.','Chamila Jayasena','F','0771030003','1991-04-16','2016-07-01',60,'Medical Laboratory Technician','FA','LAB-02'],
            ['PN-30004','Mr.','Nuwan Bandara','M','0771030004','1983-11-03','2008-03-01',60,'Medical Laboratory Technician','FA','LAB-01'],
            ['PN-30005','Ms.','Isuri Pathirana','F','0771030005','1993-06-28','2018-09-01',60,'Medical Laboratory Technician','FA','LAB-02'],

            // Pharmacists (GA)
            ['PN-40001','Mr.','Chandana Weerasinghe','M','0771040001','1981-09-17','2006-04-01',60,'Pharmacist','GA','PHRM-01'],
            ['PN-40002','Ms.','Nadeesha Karunaratne','F','0771040002','1986-03-05','2011-08-15',60,'Pharmacist','GA','PHRM-01'],
            ['PN-40003','Mr.','Lasantha Rodrigo','M','0771040003','1989-12-21','2015-02-01',60,'Pharmacist','GA','PHRM-01'],

            // Radiographers — assigned under subject code FA (see note below)
            ['PN-50001','Mr.','Pradeep Amaratunga','M','0771050001','1983-06-09','2008-01-01',60,'Radiographer','FA','RAD-01'],
            ['PN-50002','Ms.','Malini Suriyarachchi','F','0771050002','1987-04-22','2012-06-01',60,'Radiographer','FA','RAD-01'],

            // Clerks & Office Staff (KA)
            ['PN-60001','Mr.','Gamini Herath','M','0771060001','1975-07-14','2000-03-01',60,'Hospital Clerk','KA','ADMIN-01'],
            ['PN-60002','Mrs.','Nirmala Siriwardene','F','0771060002','1980-11-28','2005-09-01',60,'Hospital Clerk','KA','ADMIN-02'],
            ['PN-60003','Mr.','Sampath Amarakoon','M','0771060003','1985-02-03','2010-07-01',60,'Hospital Clerk','KA','OPD-MED'],
            ['PN-60004','Mrs.','Ranjani Wickramaratne','F','0771060004','1979-08-19','2004-01-15',60,'Hospital Clerk','KA','ADMIN-01'],
            ['PN-60005','Mr.','Asanka Mendis','M','0771060005','1990-04-07','2015-03-01',60,'Office Assistant','KA','ADMIN-03'],
            ['PN-60006','Ms.','Dilrukshi Kumari','F','0771060006','1988-10-14','2013-08-01',60,'Office Assistant','KA','ADMIN-02'],

            // Drivers (LA — assigned under KA per subject code grouping)
            ['PN-70001','Mr.','Nihal Jayawardena','M','0771070001','1972-01-30','1998-06-01',60,'Driver','KA','ADMIN-01'],
            ['PN-70002','Mr.','Sumudu Rathnasekara','M','0771070002','1978-09-12','2003-11-01',60,'Driver','KA','ADMIN-01'],
            ['PN-70003','Mr.','Rohan Silva','M','0771070003','1982-04-25','2007-05-01',60,'Driver','KA','ADMIN-01'],
        ];

        foreach ($employees as $row) {
            [$payNo, $salutation, $name, $gender, $whatsapp, $dob, $appointed, $retAge, $pos, $sc, $unit] = $row;

            Employee::firstOrCreate(['pay_no' => $payNo], [
                'salutation'          => $salutation,
                'name'                => $name,
                'gender'              => $gender,
                'whatsapp_mobile'     => $whatsapp,
                'date_of_birth'       => $dob,
                'date_of_appointment' => $appointed,
                'retirement_age'      => $retAge,
                'position_id'         => $posId($pos),
                'subject_code_id'     => $scId($sc),
                'unit_id'             => $unitId($unit),
                'is_active'           => true,
            ]);
        }
    }

    // ── Carder Entries ────────────────────────────────────────────────────

    private function seedCarderEntries(): void
    {
        $officerIds = User::whereHas('userRoles', fn ($q) => $q->where('role', 'subject_officer'))
            ->get()->keyBy(fn ($u) => $u->subjectCodes->first()?->code);

        $planningId = $this->planningOfficerUserId;

        // [subject_code, position, year, month, males, females, status]
        $entries = [
            ['EA','Staff Nurse',    $this->currentYear, 1, 12, 248, 'verified'],
            ['EA','Nursing Officer',$this->currentYear, 1,  5,  38, 'verified'],
            ['EA','Staff Nurse',    $this->currentYear, 2, 11, 245, 'verified'],
            ['EA','Nursing Officer',$this->currentYear, 2,  5,  37, 'verified'],
            ['EA','Staff Nurse',    $this->currentYear, 3, 12, 242, 'submitted'],
            ['EA','Nursing Officer',$this->currentYear, 3,  4,  36, 'submitted'],
            ['EB','Sister I',       $this->currentYear, 1,  0,  17, 'verified'],
            ['EB','Sister II',      $this->currentYear, 1,  0,   5, 'verified'],
            ['EB','Sister I',       $this->currentYear, 2,  0,  16, 'verified'],
            ['EB','Sister I',       $this->currentYear, 3,  0,  15, 'submitted'],
            ['CA','Medical Officer',$this->currentYear, 1, 30,  18, 'verified'],
            ['CA','Medical Officer',$this->currentYear, 2, 29,  19, 'verified'],
            ['CA','Medical Officer',$this->currentYear, 3, 28,  18, 'submitted'],
            ['FA','Medical Laboratory Technician',$this->currentYear, 1, 14, 16, 'verified'],
            ['FA','Medical Laboratory Technician',$this->currentYear, 2, 13, 16, 'submitted'],
            ['GA','Pharmacist',     $this->currentYear, 1,  4,   7, 'verified'],
            ['GA','Pharmacist',     $this->currentYear, 2,  4,   6, 'submitted'],
            ['KA','Hospital Clerk', $this->currentYear, 1, 12,  13, 'verified'],
            ['KA','Office Assistant',$this->currentYear,1,  8,   9, 'verified'],
            ['KA','Hospital Clerk', $this->currentYear, 2, 11,  12, 'submitted'],
        ];

        foreach ($entries as $row) {
            [$scCode, $posTitle, $year, $month, $males, $females, $status] = $row;

            $scId  = SubjectCode::where('code', $scCode)->value('id');
            $posId = Position::where('title', $posTitle)->value('id');

            if (! $scId || ! $posId) continue;

            $officerUser = $officerIds->get($scCode);
            $submittedBy = $officerUser?->id ?? $planningId;

            $approved = ApprovedCarder::where('position_id', $posId)
                ->where('year', $year)->value('approved_amount') ?? 0;

            CarderMonthlyEntry::firstOrCreate(
                ['subject_code_id' => $scId, 'position_id' => $posId, 'year' => $year, 'month' => $month],
                [
                    'in_position'    => $males + $females,
                    'males'          => $males,
                    'females'        => $females,
                    'transferred_in' => rand(0, 2),
                    'transferred_out'=> rand(0, 2),
                    'no_pay_leave'   => rand(0, 3),
                    'approved_amount'=> $approved,
                    'status'         => $status,
                    'submitted_by'   => $submittedBy,
                    'submitted_at'   => now()->subMonths(4 - $month)->startOfMonth()->addDays(10),
                    'is_locked'      => $status === 'verified',
                    'verified_by'    => $status === 'verified' ? $planningId : null,
                    'verified_at'    => $status === 'verified' ? now()->subMonths(4 - $month)->startOfMonth()->addDays(14) : null,
                ]
            );
        }
    }

    // ── Acting Appointments ───────────────────────────────────────────────

    private function seedActingAppointments(): void
    {
        $posId = fn ($t) => Position::where('title', $t)->value('id');
        $empId = fn ($p) => Employee::where('pay_no', $p)->value('id');
        $userId = User::whereHas('userRoles', fn ($q) => $q->where('role', 'super_admin'))->value('id');

        $appointments = [
            ['PN-10011', 'Sister I',       'Nursing Officer', now()->subMonths(6)->toDateString(), null],
            ['PN-10012', 'Sister I',       'Nursing Officer', now()->subMonths(3)->toDateString(), now()->addMonths(3)->toDateString()],
            ['PN-20002', 'Medical Officer','Medical Officer',  now()->subMonths(4)->toDateString(), null],
            ['PN-30001', 'Medical Laboratory Technician','Medical Laboratory Technician', now()->subMonths(2)->toDateString(), null],
        ];

        foreach ($appointments as [$payNo, $actingPos, $substantivePos, $start, $end]) {
            $empId_ = $empId($payNo);
            if (! $empId_) continue;

            ActingAppointment::firstOrCreate(
                ['employee_id' => $empId_, 'acting_position_id' => $posId($actingPos), 'start_date' => $start],
                [
                    'substantive_position_id' => $posId($substantivePos),
                    'end_date'                => $end,
                    'appointment_order_no'    => 'ADM/' . date('Y') . '/' . rand(100, 999),
                    'is_active'               => true,
                    'created_by'              => $userId,
                ]
            );
        }
    }

    // ── Transfer Records ──────────────────────────────────────────────────

    private function seedTransferRecords(): void
    {
        $empId  = fn ($p) => Employee::where('pay_no', $p)->value('id');
        $scId   = fn ($c) => SubjectCode::where('code', $c)->value('id');
        $posId  = fn ($t) => Position::where('title', $t)->value('id');
        $userId = User::where('email', 'officer.ea@hims.local')->value('id')
                ?? User::whereHas('userRoles', fn ($q) => $q->where('role', 'super_admin'))->value('id');

        $entryId = CarderMonthlyEntry::where('subject_code_id', $scId('EA'))
            ->where('position_id', $posId('Staff Nurse'))
            ->where('year', $this->currentYear)
            ->where('month', 1)
            ->value('id');

        $transfers = [
            ['PN-10001','Kamani Senevirathne','Staff Nurse','out','permanent','Ward 1','Peradeniya GH',date('Y-m-d', strtotime('-3 months'))],
            ['PN-10004','Chathuri Marasinghe','Staff Nurse','in','temporary','Kurunegala GH','Ward 2',date('Y-m-d', strtotime('-2 months'))],
            ['PN-10007','Sachini Perera','Staff Nurse','out','deputation','Ward 4','Ministry of Health',date('Y-m-d', strtotime('-45 days'))],
            ['PN-10009','Thilini Gunawardena','Nursing Officer','in','permanent','Colombo GH','ICU Ward',date('Y-m-d', strtotime('-1 month'))],
        ];

        foreach ($transfers as $row) {
            [$payNo, $name, $position, $dir, $type, $from, $to, $date] = $row;

            TransferRecord::firstOrCreate(
                ['employee_id' => $empId($payNo), 'direction' => $dir, 'effective_date' => $date],
                [
                    'carder_entry_id' => $entryId,
                    'employee_name'   => $name,
                    'designation'     => $position,
                    'transfer_type'   => $type,
                    'from_location'   => $from,
                    'to_location'     => $to,
                    'notes'           => "Transferred per routine rotation schedule.",
                    'recorded_by'     => $userId,
                    'is_active'       => true,
                ]
            );
        }
    }

    // ── Unit Post Allocations ─────────────────────────────────────────────

    private function seedUnitAllocations(): void
    {
        $unitId = fn ($c) => Unit::where('code', $c)->value('id');
        $posId  = fn ($t) => Position::where('title', $t)->value('id');
        $userId = User::where('email', 'planning@hims.local')->value('id');

        $allocations = [
            ['WARD-01','Staff Nurse',    20, 18],
            ['WARD-01','Nursing Officer', 4,  4],
            ['WARD-01','Sister I',        2,  2],
            ['WARD-02','Staff Nurse',    18, 15],
            ['WARD-02','Nursing Officer', 3,  3],
            ['WARD-02','Sister I',        2,  2],
            ['WARD-03','Staff Nurse',    16, 14],
            ['WARD-03','Nursing Officer', 3,  2],
            ['WARD-04','Staff Nurse',    14, 13],
            ['WARD-04','Nursing Officer', 3,  3],
            ['ICU-01', 'Staff Nurse',    12, 10],
            ['ICU-01', 'Nursing Officer', 4,  4],
            ['ICU-01', 'Sister I',        2,  2],
            ['OT-01',  'Nursing Officer', 5,  4],
            ['OT-01',  'Sister II',       2,  2],
            ['LAB-01', 'Medical Laboratory Technician', 10, 9],
            ['LAB-02', 'Medical Laboratory Technician',  4, 3],
            ['PHRM-01','Pharmacist',      5,  3],
            ['RAD-01', 'Radiographer',    4,  2],
        ];

        foreach ($allocations as [$unitCode, $posTitle, $allocated, $actual]) {
            $uid = $unitId($unitCode);
            $pid = $posId($posTitle);
            if (! $uid || ! $pid) continue;

            UnitPositionAllocation::updateOrCreate(
                ['unit_id' => $uid, 'position_id' => $pid, 'position_subcategory_id' => null, 'year' => $this->currentYear],
                ['allocated_posts' => $allocated, 'actual_in_post' => $actual, 'last_updated_by' => $userId]
            );
        }
    }

    // ── Letters ───────────────────────────────────────────────────────────

    private function seedLetters(): void
    {
        $officerId = User::where('email', 'officer.ea@hims.local')->value('id');
        if (! $officerId) return;

        $scId = SubjectCode::where('code', 'EA')->value('id');

        $recipients = User::whereHas('userRoles', fn ($q) => $q->where('role', 'admin_group'))
            ->whereHas('category', fn ($q) => $q->where('can_receive_letters', true))
            ->get();

        if ($recipients->isEmpty()) return;

        $letters = [
            [
                'title'       => 'Request for Additional Staff Nurse Allocation — Ward 1',
                'description' => 'Due to increased patient admissions in Ward 1, we respectfully request '
                               . 'approval for two additional Staff Nurse positions to maintain adequate '
                               . 'nurse-to-patient ratios as per MoH guidelines.',
            ],
            [
                'title'       => 'Notification of Acting Appointment — Nursing Officer',
                'description' => 'This is to formally notify that Ms. Samanthi Kumari (Pay No. PN-10011) '
                               . 'has been assigned to act as Sister I from ' . now()->subMonths(6)->format('d M Y')
                               . ' pending the filling of the substantive vacancy.',
            ],
        ];

        foreach ($letters as $lData) {
            $letter = Letter::firstOrCreate(
                ['title' => $lData['title'], 'created_by' => $officerId],
                [
                    'subject_code_id' => $scId,
                    'description'     => $lData['description'],
                    'is_active'       => true,
                ]
            );

            foreach ($recipients->take(3) as $recipient) {
                LetterRecipient::firstOrCreate(
                    ['letter_id' => $letter->id, 'user_id' => $recipient->id],
                    ['is_read' => false]
                );
            }
        }
    }
}
