@extends('layouts.app')

@section('title', 'Offline Help & Knowledge Assistant')

@section('content')
    @php
        $phaseLabels = [
            1 => ['Application Help', 'System help and user manuals only'],
            2 => ['Controlled KB', 'Verified policies, circulars, manuals and governance sources'],
            3 => ['Context Help', 'Explains the current Carder Management page and workflow'],
            4 => ['Decision Support', 'Authorised planning analytics with governed knowledge'],
        ];
    @endphp

    <div class="assistant-shell" data-assistant-phase="{{ $phase }}">
        <section class="assistant-hero assistant-reveal">
            <div>
                <div class="assistant-eyebrow">OFFLINE · GOVERNED · CITATION-FIRST</div>
                <h1>Help & Knowledge Assistant</h1>
                <p>
                    A local support assistant for Carder Management. It explains the application and retrieves approved local knowledge;
                    it does not replace authorised administrative, legal, financial, HR or clinical decisions.
                </p>
            </div>

            <div class="assistant-status-card">
                <span class="assistant-status-dot"></span>
                <strong>Offline mode</strong>
                <small>{{ number_format($verifiedSourceCount) }} verified local sources</small>
            </div>
        </section>

        <section class="assistant-boundary assistant-reveal" aria-label="Decision support safeguard">
            <div class="assistant-boundary__icon">⚖</div>
            <div>
                <strong>Support system only</strong>
                <p>
                    Answers are guidance generated from local application help and authorised knowledge sources. Verify cited material and follow the
                    applicable official process before taking consequential action. The assistant cannot approve recruitment, transfer, promotion,
                    discipline, retirement, procurement, payment, establishment changes or service reconfiguration.
                </p>
            </div>
        </section>

        <nav class="assistant-phase-nav assistant-reveal" aria-label="Assistant phases">
            @foreach ($phaseLabels as $number => [$label, $description])
                @if ($number !== 4 || $planningAccess)
                    <a href="{{ route('offline-assistant.index', ['phase' => $number, 'context_route' => $contextRoute]) }}"
                        class="assistant-phase-card {{ $phase === $number ? 'is-active' : '' }}">
                        <span class="assistant-phase-card__number">{{ $number }}</span>
                        <span>
                            <strong>{{ $label }}</strong>
                            <small>{{ $description }}</small>
                        </span>
                    </a>
                @endif
            @endforeach
        </nav>

        <div class="assistant-grid">
            <main class="assistant-chat-card assistant-reveal">
                <header class="assistant-chat-card__header">
                    <div>
                        <span class="assistant-kicker">Phase {{ $phase }}</span>
                        <h2>{{ $phaseLabels[$phase][0] }}</h2>
                        <p>{{ $phaseLabels[$phase][1] }}</p>
                    </div>
                    <div class="assistant-language-pills" aria-label="Available content languages">
                        <span>English</span><span>සිංහල</span><span>தமிழ்</span>
                    </div>
                </header>

                @if ($phase === 3)
                    <div class="assistant-context-card">
                        <span class="assistant-context-card__label">Current-page guidance</span>
                        <h3>{{ $contextHelp['title'] }}</h3>
                        <p>{{ $contextHelp['purpose'] }}</p>
                        <div class="assistant-context-boundary">{{ $contextHelp['boundary'] }}</div>
                        <ol>
                            @foreach ($contextHelp['steps'] as $step)
                                <li>{{ $step }}</li>
                            @endforeach
                        </ol>
                    </div>
                @endif

                @if ($phase === 4)
                    <div class="assistant-phase-four-notice">
                        <strong>Analytical Decision Support Only</strong>
                        <p>
                            Phase 4 may combine authorised aggregate application data with verified local knowledge. Its output is a planning aid,
                            never an automated decision, approval or instruction to act.
                        </p>
                    </div>

                    @if ($planningSnapshot)
                        <div class="assistant-metric-row">
                            <article>
                                <span>Approved establishment</span>
                                <strong>{{ number_format((int) data_get($planningSnapshot, 'approved_establishment', 0)) }}</strong>
                            </article>
                            <article>
                                <span>Current in post</span>
                                <strong>{{ number_format((int) data_get($planningSnapshot, 'active_employees', 0)) }}</strong>
                            </article>
                            <article>
                                <span>Current gap</span>
                                <strong>{{ number_format((int) data_get($planningSnapshot, 'current_establishment_gap', 0)) }}</strong>
                            </article>
                            <article>
                                <span>Open governance signals</span>
                                <strong>{{ number_format((int) data_get($planningSnapshot, 'open_hr_escalations', 0)) }}</strong>
                            </article>
                        </div>
                    @endif
                @endif

                <div id="assistant-thread" class="assistant-thread" aria-live="polite">
                    <div class="assistant-message assistant-message--system">
                        <div class="assistant-avatar">AI</div>
                        <div>
                            <strong>Local Assistant</strong>
                            <p>
                                @if ($phase === 1)
                                    Ask how to use Carder Management. I will use only local help content and user manuals.
                                @elseif ($phase === 2)
                                    Ask about an approved policy, circular, manual, governance requirement or planning reference. Unsupported answers are not invented.
                                @elseif ($phase === 3)
                                    Ask what this page does, what a field means, what evidence is required, or what happens next in the workflow.
                                @else
                                    Ask a planning question. I will keep analysis advisory, identify assumptions and cite the local knowledge used.
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <form id="assistant-form" class="assistant-composer">
                    @csrf
                    <input type="hidden" name="phase" value="{{ $phase }}">
                    <input type="hidden" name="context_route" value="{{ $contextRoute }}">

                    @if ($phase === 4)
                        <label class="assistant-acknowledgement">
                            <input type="checkbox" name="support_only_acknowledged" value="1" required>
                            <span>
                                I understand this is decision support only and I will verify evidence and use the authorised institutional process for any consequential decision.
                            </span>
                        </label>
                    @endif

                    <div class="assistant-composer__row">
                        <textarea name="question" rows="2" maxlength="1200" required
                            placeholder="Ask a question about this phase…"></textarea>
                        <button type="submit" class="assistant-send-button">
                            <span>Ask</span>
                            <span aria-hidden="true">→</span>
                        </button>
                    </div>
                    <small>No internet fallback. Questions and sources used are audit logged.</small>
                </form>
            </main>

            <aside class="assistant-side-panel assistant-reveal">
                <section>
                    <span class="assistant-kicker">Try asking</span>
                    <div class="assistant-prompt-list">
                        @if ($phase === 1)
                            <button type="button">How do I prepare a retirement projection?</button>
                            <button type="button">Where do I monitor Utility Bills?</button>
                            <button type="button">What does Ready for Review mean?</button>
                        @elseif ($phase === 2)
                            <button type="button">What sources should support a workforce planning recommendation?</button>
                            <button type="button">What governance checks should happen before referral?</button>
                            <button type="button">Show the source used for this answer.</button>
                        @elseif ($phase === 3)
                            <button type="button">What is the purpose of this page?</button>
                            <button type="button">What evidence should I include?</button>
                            <button type="button">What happens after this workflow step?</button>
                        @else
                            <button type="button">What should I consider when analysing a workforce gap?</button>
                            <button type="button">Which assumptions must I document in a planning scenario?</button>
                            <button type="button">What governance checks should happen before a planning recommendation is referred?</button>
                        @endif
                    </div>
                </section>

                <section>
                    <span class="assistant-kicker">Safeguards</span>
                    <ul class="assistant-safeguard-list">
                        <li>Verified-source preference</li>
                        <li>Role-aware access</li>
                        <li>No internet fallback</li>
                        <li>No autonomous administrative actions</li>
                        <li>Question/source audit trail</li>
                        <li>Expired or unsupported guidance must not be treated as authority</li>
                    </ul>
                </section>

                <section id="assistant-citations" hidden>
                    <span class="assistant-kicker">Sources used</span>
                    <div id="assistant-citation-list"></div>
                </section>
            </aside>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .assistant-shell {
            --assistant-blue: #155c8a;
            --assistant-blue-deep: #0b3d60;
            --assistant-soft: color-mix(in srgb, var(--assistant-blue) 8%, var(--md-surface));
            max-width: 1500px;
            margin: 0 auto;
        }

        .assistant-reveal {
            animation: assistantReveal 520ms cubic-bezier(.2, .8, .2, 1) both;
        }

        .assistant-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            padding: 22px 24px;
            border: 1px solid var(--md-outline-variant);
            border-radius: 18px;
            background: linear-gradient(135deg, var(--assistant-soft), var(--md-surface));
            box-shadow: var(--md-elevation-1);
        }

        .assistant-eyebrow,
        .assistant-kicker {
            color: var(--assistant-blue);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .09em;
            text-transform: uppercase;
        }

        .assistant-hero h1 {
            margin: 6px 0;
            font-size: clamp(26px, 3vw, 38px);
        }

        .assistant-hero p,
        .assistant-boundary p,
        .assistant-chat-card__header p {
            margin: 0;
            color: var(--md-on-surface-variant);
            line-height: 1.55;
        }

        .assistant-status-card {
            min-width: 190px;
            padding: 14px 16px;
            border-radius: 14px;
            border: 1px solid var(--md-outline-variant);
            background: var(--md-surface-container-low);
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 2px 9px;
            align-items: center;
        }

        .assistant-status-card small {
            grid-column: 2;
            color: var(--md-on-surface-variant);
        }

        .assistant-status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #3c8d40;
            box-shadow: 0 0 0 5px rgba(60, 141, 64, .12);
            animation: assistantPulse 2.4s ease-in-out infinite;
        }

        .assistant-boundary {
            display: flex;
            gap: 14px;
            margin-top: 16px;
            padding: 16px 18px;
            border: 1px solid #d4a72c;
            border-radius: 14px;
            background: color-mix(in srgb, #f6c453 12%, var(--md-surface));
            animation-delay: 80ms;
        }

        .assistant-boundary__icon {
            font-size: 25px;
        }

        .assistant-phase-nav {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin: 18px 0;
            animation-delay: 130ms;
        }

        .assistant-phase-card {
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 82px;
            padding: 13px;
            color: inherit;
            text-decoration: none;
            border: 1px solid var(--md-outline-variant);
            border-radius: 14px;
            background: var(--md-surface-container-low);
            transition: transform 180ms ease, box-shadow 180ms ease, border-color 180ms ease;
        }

        .assistant-phase-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--md-elevation-2);
        }

        .assistant-phase-card.is-active {
            border-color: var(--assistant-blue);
            background: var(--assistant-soft);
        }

        .assistant-phase-card__number {
            display: grid;
            place-items: center;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            color: #fff;
            background: var(--assistant-blue);
            font-weight: 800;
        }

        .assistant-phase-card span:last-child {
            display: grid;
            gap: 4px;
        }

        .assistant-phase-card small {
            color: var(--md-on-surface-variant);
            line-height: 1.3;
        }

        .assistant-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 330px;
            gap: 18px;
        }

        .assistant-chat-card,
        .assistant-side-panel {
            border: 1px solid var(--md-outline-variant);
            border-radius: 18px;
            background: var(--md-surface-container-lowest, var(--md-surface));
            box-shadow: var(--md-elevation-1);
        }

        .assistant-chat-card {
            overflow: hidden;
            animation-delay: 180ms;
        }

        .assistant-chat-card__header {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            padding: 20px;
            border-bottom: 1px solid var(--md-outline-variant);
        }

        .assistant-chat-card__header h2 {
            margin: 4px 0;
        }

        .assistant-language-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            align-content: flex-start;
        }

        .assistant-language-pills span {
            padding: 5px 9px;
            border-radius: 999px;
            background: var(--assistant-soft);
            color: var(--assistant-blue-deep);
            font-size: 11px;
            font-weight: 700;
        }

        .assistant-context-card,
        .assistant-phase-four-notice {
            margin: 16px 18px 0;
            padding: 16px;
            border-radius: 14px;
            background: var(--assistant-soft);
            border: 1px solid color-mix(in srgb, var(--assistant-blue) 28%, var(--md-outline-variant));
        }

        .assistant-context-card h3 {
            margin: 5px 0 8px;
        }

        .assistant-context-card__label {
            color: var(--assistant-blue);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .assistant-context-boundary {
            margin-top: 10px;
            padding: 9px 11px;
            border-left: 4px solid #d4a72c;
            background: color-mix(in srgb, #f6c453 10%, transparent);
        }

        .assistant-metric-row {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            padding: 16px 18px 0;
        }

        .assistant-metric-row article {
            padding: 12px;
            border: 1px solid var(--md-outline-variant);
            border-radius: 12px;
            background: var(--md-surface-container-low);
        }

        .assistant-metric-row span {
            display: block;
            color: var(--md-on-surface-variant);
            font-size: 11px;
        }

        .assistant-metric-row strong {
            display: block;
            margin-top: 4px;
            font-size: 22px;
        }

        .assistant-thread {
            min-height: 310px;
            max-height: 560px;
            overflow-y: auto;
            padding: 18px;
        }

        .assistant-message {
            display: flex;
            gap: 11px;
            max-width: 86%;
            margin-bottom: 14px;
            animation: assistantMessageIn 280ms ease both;
        }

        .assistant-message--user {
            margin-left: auto;
            flex-direction: row-reverse;
        }

        .assistant-message > div:last-child {
            padding: 11px 13px;
            border-radius: 14px;
            background: var(--md-surface-container);
            border: 1px solid var(--md-outline-variant);
        }

        .assistant-message--user > div:last-child {
            color: #fff;
            background: var(--assistant-blue);
            border-color: var(--assistant-blue);
        }

        .assistant-message p {
            white-space: pre-wrap;
            margin: 4px 0 0;
            line-height: 1.55;
        }

        .assistant-avatar {
            flex: 0 0 auto;
            display: grid;
            place-items: center;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: var(--assistant-soft);
            color: var(--assistant-blue);
            font-size: 10px;
            font-weight: 900;
        }

        .assistant-composer {
            padding: 14px 18px 18px;
            border-top: 1px solid var(--md-outline-variant);
        }

        .assistant-composer__row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 10px;
        }

        .assistant-composer textarea {
            width: 100%;
            resize: vertical;
            min-height: 58px;
            padding: 12px;
            border-radius: 12px;
            border: 1px solid var(--md-outline-variant);
            color: var(--md-on-surface);
            background: var(--md-surface);
            font: inherit;
        }

        .assistant-send-button {
            min-width: 100px;
            padding: 0 16px;
            border: 0;
            border-radius: 12px;
            color: #fff;
            background: var(--assistant-blue);
            font-weight: 800;
            cursor: pointer;
            transition: transform 160ms ease, filter 160ms ease;
        }

        .assistant-send-button:hover {
            transform: translateY(-1px);
            filter: brightness(1.06);
        }

        .assistant-send-button:disabled {
            opacity: .6;
            cursor: wait;
        }

        .assistant-acknowledgement {
            display: flex;
            gap: 9px;
            margin-bottom: 11px;
            padding: 10px 12px;
            border-radius: 10px;
            background: color-mix(in srgb, #f6c453 9%, var(--md-surface));
            font-size: 12px;
            line-height: 1.4;
        }

        .assistant-composer > small {
            display: block;
            margin-top: 8px;
            color: var(--md-on-surface-variant);
        }

        .assistant-side-panel {
            padding: 17px;
            animation-delay: 230ms;
        }

        .assistant-side-panel section + section {
            margin-top: 22px;
            padding-top: 18px;
            border-top: 1px solid var(--md-outline-variant);
        }

        .assistant-prompt-list {
            display: grid;
            gap: 8px;
            margin-top: 10px;
        }

        .assistant-prompt-list button {
            padding: 10px 11px;
            text-align: left;
            color: var(--md-on-surface);
            background: var(--md-surface-container);
            border: 1px solid var(--md-outline-variant);
            border-radius: 10px;
            cursor: pointer;
            transition: transform 150ms ease, border-color 150ms ease;
        }

        .assistant-prompt-list button:hover {
            transform: translateX(3px);
            border-color: var(--assistant-blue);
        }

        .assistant-safeguard-list {
            margin: 10px 0 0;
            padding-left: 19px;
            color: var(--md-on-surface-variant);
            line-height: 1.65;
        }

        .assistant-citation {
            margin-top: 9px;
            padding: 10px;
            border: 1px solid var(--md-outline-variant);
            border-radius: 10px;
            background: var(--md-surface-container-low);
        }

        .assistant-citation small {
            display: block;
            margin-top: 3px;
            color: var(--md-on-surface-variant);
        }

        @keyframes assistantReveal {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes assistantMessageIn {
            from {
                opacity: 0;
                transform: translateY(6px) scale(.99);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes assistantPulse {
            0%, 100% { box-shadow: 0 0 0 4px rgba(60, 141, 64, .10); }
            50% { box-shadow: 0 0 0 8px rgba(60, 141, 64, .04); }
        }

        @media (prefers-reduced-motion: reduce) {
            .assistant-reveal,
            .assistant-message,
            .assistant-status-dot {
                animation: none !important;
            }

            .assistant-phase-card,
            .assistant-send-button,
            .assistant-prompt-list button {
                transition: none !important;
            }
        }

        @media (max-width: 1050px) {
            .assistant-phase-nav {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .assistant-grid {
                grid-template-columns: 1fr;
            }

            .assistant-metric-row {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 650px) {
            .assistant-hero,
            .assistant-chat-card__header {
                align-items: stretch;
                flex-direction: column;
            }

            .assistant-phase-nav,
            .assistant-metric-row {
                grid-template-columns: 1fr;
            }

            .assistant-composer__row {
                grid-template-columns: 1fr;
            }

            .assistant-send-button {
                min-height: 44px;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        (() => {
            const form = document.getElementById('assistant-form');
            const thread = document.getElementById('assistant-thread');
            const citationSection = document.getElementById('assistant-citations');
            const citationList = document.getElementById('assistant-citation-list');
            const promptButtons = document.querySelectorAll('.assistant-prompt-list button');

            if (!form || !thread) {
                return;
            }

            const addMessage = (kind, text) => {
                const message = document.createElement('div');
                message.className = `assistant-message assistant-message--${kind}`;

                const avatar = document.createElement('div');
                avatar.className = 'assistant-avatar';
                avatar.textContent = kind === 'user' ? 'YOU' : 'AI';

                const body = document.createElement('div');
                const title = document.createElement('strong');
                title.textContent = kind === 'user' ? 'You' : 'Local Assistant';
                const paragraph = document.createElement('p');
                paragraph.textContent = text;

                body.append(title, paragraph);
                message.append(avatar, body);
                thread.appendChild(message);
                thread.scrollTop = thread.scrollHeight;
            };

            const renderCitations = (citations) => {
                citationList.innerHTML = '';

                if (!Array.isArray(citations) || citations.length === 0) {
                    citationSection.hidden = true;
                    return;
                }

                citations.forEach((citation, index) => {
                    const item = document.createElement('div');
                    item.className = 'assistant-citation';

                    const title = document.createElement('strong');
                    title.textContent = `${index + 1}. ${citation.title}`;
                    const meta = document.createElement('small');
                    meta.textContent = [
                        citation.reference_no,
                        citation.authority,
                        citation.effective_date ? `Effective ${citation.effective_date}` : null,
                    ].filter(Boolean).join(' · ');

                    item.append(title, meta);
                    citationList.appendChild(item);
                });

                citationSection.hidden = false;
            };

            promptButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    form.elements.question.value = button.textContent.trim();
                    form.elements.question.focus();
                });
            });

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                const question = form.elements.question.value.trim();

                if (!question) {
                    return;
                }

                const submitButton = form.querySelector('button[type="submit"]');
                submitButton.disabled = true;
                addMessage('user', question);

                const payload = {
                    question,
                    phase: Number(form.elements.phase.value),
                    context_route: form.elements.context_route.value || null,
                    support_only_acknowledged: form.elements.support_only_acknowledged
                        ? form.elements.support_only_acknowledged.checked
                        : false,
                };

                try {
                    const response = await fetch(@json(route('offline-assistant.ask')), {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                        },
                        body: JSON.stringify(payload),
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        const message = data.message || Object.values(data.errors || {}).flat()[0] || 'The request could not be completed.';
                        throw new Error(message);
                    }

                    addMessage('system', data.answer);
                    renderCitations(data.citations || []);
                    form.elements.question.value = '';
                } catch (error) {
                    addMessage('system', error.message || 'The local assistant could not complete the request.');
                } finally {
                    submitButton.disabled = false;
                }
            });
        })();
    </script>
@endpush
