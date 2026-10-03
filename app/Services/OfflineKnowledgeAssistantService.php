<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AiKnowledgeSource;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class OfflineKnowledgeAssistantService
{
    /**
     * The assistant is deliberately retrieval-first. It does not invent policy.
     * A local LLM can later summarise the retrieved passages, but citations and
     * access control remain authoritative in this service.
     */
    public function answer(User $user, string $question, int $phase, ?string $contextRoute = null): array
    {
        $sources = $this->retrieve($user, $question, $phase, $contextRoute);

        if ($sources->isEmpty()) {
            return [
                'answer' => 'I could not find a verified local source that supports an answer. Please consult the relevant authorised officer or add/verify the applicable manual, circular or policy in the Knowledge Base.',
                'mode' => 'no-supported-source',
                'citations' => [],
                'context' => $this->contextHelp($contextRoute),
            ];
        }

        $lead = match ($phase) {
            1 => 'Based on the local application help content:',
            2 => 'Based on verified local knowledge-base sources:',
            3 => 'For the page you are currently viewing, the local guidance indicates:',
            4 => 'For planning decision support, the authorised local sources indicate:',
            default => 'Based on local sources:',
        };

        $snippets = $sources
            ->take(3)
            ->map(fn (AiKnowledgeSource $source) => $this->extractRelevantSnippet($source->content, $question))
            ->filter()
            ->values();

        return [
            'answer' => $lead."\n\n".$snippets->map(fn ($text, $index) => ($index + 1).'. '.$text)->implode("\n\n"),
            'mode' => 'offline-retrieval',
            'citations' => $sources->take(5)->map(fn (AiKnowledgeSource $source) => [
                'id' => $source->id,
                'title' => $source->title,
                'reference_no' => $source->reference_no,
                'authority' => $source->issuing_authority,
                'language' => $source->language,
                'effective_date' => optional($source->effective_date)->format('Y-m-d'),
                'expiry_date' => optional($source->expiry_date)->format('Y-m-d'),
                'source_location' => $source->source_location,
            ])->values()->all(),
            'context' => $this->contextHelp($contextRoute),
        ];
    }

    public function contextHelp(?string $routeName): array
    {
        $map = [
            'planning.assessments.create' => [
                'title' => 'Hospital Planning Assessment',
                'purpose' => 'Document evidence, assumptions, alternatives, risks and an advisory planning recommendation for later authorised human review.',
                'boundary' => 'Saving or referring this assessment does not approve recruitment, procurement, expenditure, establishment changes or service reconfiguration.',
                'steps' => ['Define the planning question', 'Record evidence and assumptions', 'Compare reasonable alternatives', 'Document impacts and risks', 'Confirm safeguards', 'Refer for authorised decision'],
            ],
            'planning.assessments.index' => [
                'title' => 'Hospital Planning Register',
                'purpose' => 'Track planning assessments by area and workflow status while preserving the distinction between analysis and formal approval.',
                'boundary' => 'Referred assessments remain advisory records until the competent authority acts through the applicable official process.',
                'steps' => ['Review draft work', 'Check readiness', 'Open evidence trail', 'Follow referred items through official processes'],
            ],
            'workforce.planning-intelligence' => [
                'title' => 'Hospital Planning Intelligence',
                'purpose' => 'Explore workforce trends and scenarios using aggregate administrative data.',
                'boundary' => 'Scenario outputs are analytical estimates and must not be treated as appointments, cadre approvals or expenditure authority.',
                'steps' => ['Select period', 'Review assumptions', 'Compare scenarios', 'Check data quality', 'Record a planning assessment if action is proposed'],
            ],
            'utility-bills.index' => [
                'title' => 'Utility Bill Monitoring',
                'purpose' => 'Monitor utility obligations, due dates, recorded payments and expenditure trends.',
                'boundary' => 'The screen records and monitors transactions; it does not itself authorise expenditure or payment.',
                'steps' => ['Verify bill source', 'Check account and billing period', 'Record payment evidence', 'Review overdue/due-soon indicators', 'Use reports for oversight'],
            ],
        ];

        return $map[$routeName] ?? [
            'title' => 'Current Carder Management page',
            'purpose' => 'Use the page according to the role permissions and on-screen guidance.',
            'boundary' => 'The assistant explains the interface and approved knowledge only. It does not grant authority or make administrative decisions.',
            'steps' => ['Read the page guidance', 'Verify source data', 'Use only permitted actions', 'Escalate consequential decisions through the authorised workflow'],
        ];
    }

    private function retrieve(User $user, string $question, int $phase, ?string $contextRoute): Collection
    {
        $query = AiKnowledgeSource::query()
            ->where('is_active', true)
            ->where('is_verified', true)
            ->where(function ($builder) {
                $builder->whereNull('effective_date')
                    ->orWhereDate('effective_date', '<=', today());
            })
            ->where(function ($builder) {
                $builder->whereNull('expiry_date')
                    ->orWhereDate('expiry_date', '>=', today());
            });

        if ($phase === 1) {
            $query->whereIn('source_type', ['application_help', 'user_manual']);
        }

        // Confidential material is never exposed through general chat.
        // A future policy-specific permission may permit controlled access.
        $query->whereIn('classification', ['public', 'internal']);

        $sources = $query->limit(500)->get();
        $tokens = $this->tokens($question.' '.$contextRoute);

        return $sources
            ->map(function (AiKnowledgeSource $source) use ($tokens) {
                $haystack = Str::lower(implode(' ', [
                    $source->title,
                    $source->reference_no,
                    $source->issuing_authority,
                    $source->content,
                ]));

                $score = collect($tokens)->sum(fn (string $token) => substr_count($haystack, $token));
                $source->setAttribute('_retrieval_score', $score);

                return $source;
            })
            ->filter(fn (AiKnowledgeSource $source) => (int) $source->getAttribute('_retrieval_score') > 0)
            ->sortByDesc('_retrieval_score')
            ->values();
    }

    private function tokens(string $text): array
    {
        return collect(preg_split('/[^\pL\pN]+/u', Str::lower($text)) ?: [])
            ->filter(fn ($token) => mb_strlen($token) >= 3)
            ->reject(fn ($token) => in_array($token, ['the', 'and', 'for', 'with', 'this', 'that', 'how', 'what', 'where', 'from', 'your'], true))
            ->unique()
            ->values()
            ->all();
    }

    private function extractRelevantSnippet(string $content, string $question): string
    {
        $paragraphs = collect(preg_split('/\R{2,}/u', trim($content)) ?: [trim($content)])
            ->filter();
        $tokens = $this->tokens($question);

        $best = $paragraphs
            ->mapWithKeys(function (string $paragraph, int $index) use ($tokens) {
                $lower = Str::lower($paragraph);
                $score = collect($tokens)->sum(fn (string $token) => substr_count($lower, $token));

                return [$index => ['score' => $score, 'text' => $paragraph]];
            })
            ->sortByDesc('score')
            ->first();

        return Str::limit((string) ($best['text'] ?? $content), 900);
    }
}
