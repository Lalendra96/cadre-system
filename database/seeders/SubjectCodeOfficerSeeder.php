<?php

namespace Database\Seeders;

use App\Models\SubjectCode;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * SubjectCodeOfficerSeeder
 * ─────────────────────────────────────────────────────────────────────────
 * Seeds the 30 Institutional & Administrative Department subject codes and
 * their assigned officers from the official register:
 *   "ආයතන හා පාලන අංශයේ නිලධාරීන්ගේ රාජකාරී ලෑයිස්තු"
 *
 * Source columns used:
 *   Column 2 → subject_codes.code
 *   Column 3 → users.name  (Sinhala names transliterated; Sinhala
 *               originals stored in the seeder comments for traceability)
 *
 * User account defaults:
 *   - role           : subject_officer
 *   - email pattern  : sc.{normalized_code}@hims.local
 *   - temp password  : Force@2024  (force_password_change = true — user
 *                       MUST change on first login)
 *   - positions      : none linked (assign via Admin → Subject Codes → Edit)
 *   - can_view_employees / can_view_letters : false by default;
 *     Super Admin enables per officer as required.
 *
 * Idempotent — safe to re-run (firstOrCreate throughout).
 *
 * Run with:
 *   php artisan db:seed --class=SubjectCodeOfficerSeeder
 */
class SubjectCodeOfficerSeeder extends Seeder
{
    /**
     * Source data.
     * Format: [code, officer_name_en, officer_name_sinhala, salutation]
     *
     * Salutation codes: 'Ms' = මිය,  'Mr' = මයා,  '' = not specified
     *
     * NOTE: 'AF ii' is normalised to 'AFII' in code (no spaces allowed in
     * subject code values); original column-2 value is preserved in
     * the Sinhala name comment.
     */
    private function entries(): array
    {
        return [
            // code    English transliteration                       Sinhala original                              Sal
            ['EA',    'L.R.K. Nimalka',                             'එල්.ආර්.කේ.නිමල්කා',                          'Ms'],
            ['EAII',  'D.D. Naomi',                                 'ඩබ.ඩී .නයෝමී',                                'Ms'],   // AF ii → AFII
            ['EB',    'H.L.P. Kanchanamala',                        'එච්.එල්.පී. කාංචනමාලා',                       'Ms'],
            ['EC',    'H.K. Sanjeewa Jayakodi',                     'එච්.කේ. සංජීව ජයකෝදි',                        'Mr'],
            ['ED',    'N.G.N.M. Rathnayaka',                        'එන්.ජී.එන්.එම්.රත්නායක',                       'Ms'],
            ['EF',    'H.I. Vikumasinghe',                          'එච්.අයි.විකුමසිංහ',                            'Ms'],
            ['EG',    'K.M.M. Mallika',                             'කේ.එම්.එම්. මල්ලිකා',                          'Ms'],
            ['EH',    'Nayana Alwis',                               'නයනා අල්විස්',                                 'Ms'],
            ['EI',    'R.P.M. Dissanayake',                         'ආර්.පී.එම්.දිසානායක',                          'Ms'],
            ['EJ',    'S. Rathnadesha',                             'එස්.රත්නදේශිය',                                'Ms'],
            ['EL',    'P.V. Swarnakilana',                          'PV ස්වර්ණකිලාණා',                             ''],
            ['EM',    'M.M.R.M. Ekanayake',                         'එම්.එම්.ආර්.එම්.ඒකනායක',                      'Ms'],
            ['DEC',   'N.N.K. Rathnayaka',                          'එන්.එන්.කේ. රත්නායක',                          'Ms'],
            ['DEA',   'G.J.S.J. Manike',                            'ජී.ජේ.එස්.ජේ.මැණිකේ',                         'Ms'],
            ['AB',    'Rasmi Somarathna',                           'රස්මී සෝමරත්න',                                'Ms'],
            ['AC',    'K.K. Somalaka',                              'කේ.කේ.සෝමලකා',                                 'Ms'],
            ['AD',    'N.K. Bandara',                               'එන්.කේ.බංජාර',                                 'Mr'],
            ['AE',    'K.T.C. Jayarathna',                          'කේ.ටී.සී.ජයරත්න',                              'Ms'],
            ['AF',    'W.M.T.C.D. Vishesundara',                    'ඩබ.එම්.ටී.සී.ඩී.විශේසුන්දර',                  'Mr'],
            ['AFII',  'K.J.S.N. Rangasinghe',                       'කේ.ජේ.එස්.එන්.රංගසිංහ',                       'Mr'],   // AF ii → AFII
            ['AG',    'S.N.K. Dahanayake',                          'එස්.එන්.කේ.දහනායක',                            'Ms'],
            ['AH',    'Priyanka Samarakoon',                        'ප්‍රියංකා සමරකෝන්',                              'Ms'],
            ['AI',    'Udayasiri Weerasinghe',                      'උදයසිරි වීරසිංහ',                              'Mr'],
            ['AJ',    'Kanchana Ehelamalge',                        'කාංචනා ඇහැලමල්ජේ',                             'Ms'],
            ['AK',    'Pudiij Walagadara',                          'පුදිජ් වලගෙදර',                                'Mr'],
            ['AL',    'Kilina Rathnadesha',                         'කිලිණ රත්දේශිය',                               'Ms'],
            ['AM',    'Naomi Gunarathna',                           'නායෝමී ගුණරත්න',                               'Ms'],
            ['HA',    'I.J.M.K. Ohlan',                             'අයි.ජේ.එම්.කේ.ඔහ්ලාන',                        'Ms'],
            ['HC',    'H.J.N. Wijayakilaka',                        'එච්.ජේ.එන්.විජයකිලාක',                         'Ms'],
            ['HD',    'Sanjeewa Dharmaratna',                       'සංජීව ධර්මරත්න',                               'Mr'],
        ];
    }

    public function run(): void
    {
        // Single temp password for ALL accounts — every user is forced to
        // change it on first login (force_password_change = true).
        $tempPlain    = 'Force@2024';
        $tempHashed   = Hash::make($tempPlain);

        DB::transaction(function () use ($tempHashed) {
            $created    = 0;
            $skipped    = 0;
            $userCount  = 0;

            foreach ($this->entries() as [$code, $nameEn, $nameSinhala, $sal]) {

                // ── 1. Subject Code ──────────────────────────────────────────
                // position_id is deliberately left null — this register does not
                // specify which Position each code maps to; link via Positions
                // screen after seeding.
                [$sc, $scNew] = [
                    SubjectCode::firstOrCreate(
                        ['code' => $code],
                        [
                            'name'        => $nameEn . ' (' . $code . ')',
                            'is_active'   => true,
                            // Positions are linked via the position_subject_code pivot.
                            // Use Admin → Subject Codes → Edit to assign positions.
                        ]
                    ),
                    false,
                ];

                if ($sc->wasRecentlyCreated) {
                    $scNew = true;
                    $created++;
                } else {
                    $skipped++;
                }

                // ── 2. User account ──────────────────────────────────────────
                // Email: sc.{lowercase_code}@hims.local
                $email = 'sc.' . strtolower($code) . '@hims.local';

                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name'                   => trim(($sal ? $sal . '. ' : '') . $nameEn),
                        'password'               => $tempHashed,
                        'is_active'              => true,
                        'force_password_change'  => true,  // must change on first login
                        'can_view_employees'     => false, // Super Admin enables as needed
                        'can_view_letters'       => false, // Super Admin enables as needed
                    ]
                );

                if ($user->wasRecentlyCreated) {
                    $userCount++;
                }

                // ── 3. Role — subject_officer ────────────────────────────────
                UserRole::firstOrCreate([
                    'user_id' => $user->id,
                    'role'    => User::ROLE_SUBJECT_OFFICER,
                ]);

                // ── 4. Pivot: user ↔ subject code ────────────────────────────
                // syncWithoutDetaching preserves any existing assignments.
                $user->subjectCodes()->syncWithoutDetaching([
                    $sc->id => ['assigned_at' => now()],
                ]);
            }

            $this->command?->info(
                sprintf(
                    'Subject codes: %d created, %d already existed. User accounts: %d created.',
                    $created, $skipped, $userCount
                )
            );
        });

        $this->command?->newLine();
        $this->command?->line('  <comment>Temporary password for ALL seeded accounts:</comment> Force@2024');
        $this->command?->line('  <comment>All accounts have force_password_change = true.</comment>');
        $this->command?->line('  <comment>Users MUST set a new password on first login.</comment>');
        $this->command?->newLine();
        $this->command?->line('  <comment>NOTE: no positions are linked to these codes yet.</comment>');
        $this->command?->line('  <comment>Assign positions via Admin → Subject Codes → Edit (multi-select).</comment>');
    }
}
