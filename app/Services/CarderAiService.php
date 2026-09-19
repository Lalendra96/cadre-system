<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Employee;
use App\Models\ServiceLetterTemplate;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CarderAiService
{
    public function employeeSummary(Employee $employee): array
    {
        $employee->loadMissing([
            'position',
            'unit',
            'subjectCode',
            'salaryScale',
            'gradeRecords.positionGrade',
            'servicePeriods.position',
            'servicePeriods.positionGrade',
            'documents',
        ]);

        $grade = $employee->current_grade;
        $periods = $employee->servicePeriods
            ->where('is_active', true)
            ->sortBy('start_date');

        $missing = [];

        foreach ([
            'date_joined_public_service' => 'Date joined public service',
            'date_reported_for_duty' => 'Date reported for duty to this institute',
            'date_current_grade' => 'Current grade start date',
        ] as $field => $label) {
            $hasGradeDate = $field === 'date_current_grade'
                && $grade?->effective_date;

            if (! $employee->{$field} && ! $hasGradeDate) {
                $missing[] = $label;
            }
        }

        if ($periods->isEmpty()) {
            $missing[] = 'Structured service history';
        }

        $facts = [
            'employee' => $employee->display_name,
            'position' => $employee->position?->title,
            'grade' => $grade?->positionGrade?->name,
            'grade_since' => $grade?->effective_date?->format('d M Y')
                ?: $employee->date_current_grade?->format('d M Y'),
            'unit' => $employee->unit?->name,
            'public_service_since' => $employee->date_joined_public_service?->format('d M Y'),
            'reported_here' => $employee->date_reported_for_duty?->format('d M Y'),
            'combined_service' => $employee->combined_service_name,
            'service_period_count' => $periods->count(),
            'verified_service_periods' => $periods
                ->where('verification_status', 'verified')
                ->count(),
            'missing' => $missing,
        ];

        $localSummary = $this->localSummary(
            $employee,
            $facts,
            $periods
        );

        $aiSummary = $this->callLanModel(
            'Summarise this employee service record for an HR Subject Officer. '
            . 'Use only supplied facts. Never infer or invent missing dates. '
            . 'Return concise plain text.',
            $facts
        );

        return [
            'summary' => $aiSummary ?: $localSummary,
            'facts' => $facts,
            'mode' => $aiSummary ? 'lan_ai' : 'offline_rules',
        ];
    }

    public function draftServiceLetter(
        Employee $employee,
        ?ServiceLetterTemplate $template,
        string $purpose,
        string $language,
        string $instructions = ''
    ): array {
        $base = $template
            ? ServiceLetterService::render($template, $employee)
            : $this->fallbackLetter($employee, $purpose, $language);

        $context = [
            'purpose' => $purpose,
            'language' => $language,
            'employee' => $employee->display_name,
            'position' => $employee->position?->title,
            'grade' => $employee->current_grade?->positionGrade?->name,
            'unit' => $employee->unit?->name,
            'date_joined_public_service' => $employee->date_joined_public_service?->format('d M Y'),
            'date_reported_for_duty' => $employee->date_reported_for_duty?->format('d M Y'),
            'base_template' => $base,
            'instructions' => Str::limit($instructions, 500),
        ];

        $aiDraft = $this->callLanModel(
            'Draft an official public-sector service letter using only supplied '
            . 'verified facts. Preserve any [EDIT:] markers for facts not supplied. '
            . 'Do not invent salary, disciplinary clearance, approvals, or dates. '
            . 'Return body text only.',
            $context
        );

        return [
            'body' => $aiDraft ?: $base,
            'mode' => $aiDraft ? 'lan_ai' : 'offline_template',
        ];
    }

    private function localSummary(
        Employee $employee,
        array $facts,
        $periods
    ): string {
        $lines = [];

        $lines[] = $employee->display_name
            . ' is recorded as '
            . ($facts['position'] ?: 'position not recorded')
            . ($facts['grade'] ? ' — ' . $facts['grade'] : '')
            . '.';

        if ($facts['public_service_since']) {
            $lines[] = 'Public service is recorded from '
                . $facts['public_service_since']
                . '.';
        }

        if ($facts['grade_since']) {
            $lines[] = 'Current grade service is recorded from '
                . $facts['grade_since']
                . '.';
        }

        if ($facts['reported_here']) {
            $lines[] = 'Reported for duty to this institute on '
                . $facts['reported_here']
                . '.';
        }

        if ($periods->count()) {
            $lines[] = 'Structured service history contains '
                . $periods->count()
                . ' period(s), of which '
                . $facts['verified_service_periods']
                . ' are verified from official records.';
        }

        $lines[] = $facts['missing']
            ? 'Needs attention: ' . implode('; ', $facts['missing']) . '.'
            : 'No core chronology gaps were detected by the offline assistant.';

        return implode("\n", $lines);
    }

    private function fallbackLetter(
        Employee $employee,
        string $purpose,
        string $language
    ): string {
        $name = $employee->display_name;
        $position = $employee->position?->title ?? '[EDIT: position]';

        if ($language === 'si') {
            return "අදාළ පාර්ශ්ව වෙත,\n\n{$purpose}\n\n"
                . "{$name} මහතා/මහත්මිය මෙම ආයතනයේ {$position} ලෙස සේවය කරන බව "
                . "නිල සේවා වාර්තා අනුව සනාථ කරනු ලැබේ. "
                . "[සංස්කරණය කරන්න: ලිපියේ විශේෂ අරමුණ/ලබන්නා/අවශ්‍ය අමතර සත්‍යාපිත තොරතුරු]\n\n"
                . 'මෙම කෙටුම්පත නිලධාරියා විසින් සමාලෝචනය කර අනුමත කළ යුතුය.';
        }

        if ($language === 'ta') {
            return "சம்பந்தப்பட்டவர்களுக்கு,\n\n{$purpose}\n\n"
                . "அதிகாரப்பூர்வ சேவைப் பதிவுகளின்படி {$name} அவர்கள் "
                . "இந்நிறுவனத்தில் {$position} ஆக பணியாற்றுகிறார்/பணியாற்றியுள்ளார் என்பதை உறுதிப்படுத்துகிறோம். "
                . "[திருத்துக: குறிப்பிட்ட நோக்கம்/பெறுநர்/தேவையான உறுதிப்படுத்தப்பட்ட தகவல்]\n\n"
                . 'இந்த வரைவு அதிகாரியால் பரிசீலித்து அங்கீகரிக்கப்பட வேண்டும்.';
        }

        return "TO WHOM IT MAY CONCERN\n\n"
            . strtoupper($purpose)
            . "\n\nThis is to certify, on the basis of the official service record, "
            . "that {$name} is/was serving at this institution as {$position}. "
            . '[EDIT: specific purpose / recipient / additional verified facts required]'
            . "\n\nThis assisted draft must be reviewed by the responsible officer before approval.";
    }

    private function callLanModel(
        string $instruction,
        array $context
    ): ?string {
        $endpoint = trim(
            (string) SystemSetting::get('ai_local_endpoint', '')
        );

        if ($endpoint === '' || ! $this->isPrivateEndpoint($endpoint)) {
            return null;
        }

        try {
            $model = (string) SystemSetting::get(
                'ai_local_model',
                'carder-local'
            );

            $response = Http::timeout(15)->post(
                rtrim($endpoint, '/') . '/v1/chat/completions',
                [
                    'model' => $model,
                    'temperature' => 0.1,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are Carder Management local HR assistant. Never invent facts.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $instruction
                                . "\n\nDATA:\n"
                                . json_encode(
                                    $context,
                                    JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
                                ),
                        ],
                    ],
                ]
            );

            if (! $response->successful()) {
                return null;
            }

            $text = data_get(
                $response->json(),
                'choices.0.message.content'
            );

            return is_string($text) && trim($text) !== ''
                ? trim($text)
                : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function isPrivateEndpoint(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! $host) {
            return false;
        }

        if (in_array(
            $host,
            ['localhost', '127.0.0.1', '::1'],
            true
        )) {
            return true;
        }

        $ip = gethostbyname($host);

        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }
}
