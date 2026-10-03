@php
    $showAdministrativeNotice = session('administrative_notice_required', false)
        && auth()->check()
        && ! auth()->user()->force_password_change
        && ! auth()->user()->isPasswordExpired();

    $administrativeNoticeVersion = (string) \App\Models\SystemSetting::get('administrative_notice_version', '1.0');

    $noticeLanguages = [
        'en' => [
            'tab' => 'English',
            'title' => 'Important Notice',
            'subtitle' => 'Please read and acknowledge before using the system.',
            'items' => [
                'Carder Management is an internal administrative support and decision-support system for authorised institutional use.',
                'System-calculated indicators, alerts, forecasts, summaries ' .
                'and recommendations assist administrative review only. ' .
                'They do not themselves constitute a final HR decision ' .
                'or official Government of Sri Lanka authority.',
                'Before taking consequential administrative action, verify ' .
                'the underlying employee record and the applicable Act, ' .
                'regulation, Establishments Code provision, circular, ' .
                'Ministry instruction, service minute, PSC decision or ' .
                'other official authority.',
                'Use your own authorised account, protect your login ' .
                'credentials, and access information only for approved ' .
                'official purposes. Activities may be logged and audited.',
                'If you notice incorrect data, a calculation issue, ' .
                'unexpected system behaviour, a report error, or a ' .
                'privacy/security concern, report it through the ' .
                'Incident & Correction Register.',
            ],
            'acknowledgement' => 'I have read and understood the above notice.',
            'continue' => 'Continue to System',
        ],
        'si' => [
            'tab' => 'සිංහල',
            'title' => 'වැදගත් දැනුම්දීම',
            'subtitle' => 'පද්ධතිය භාවිත කිරීමට පෙර කරුණාකර මෙම දැනුම්දීම කියවා පිළිගන්න.',
            'items' => [
                'Carder Management යනු බලයලත් ආයතනික භාවිතය සඳහා ඇති අභ්‍යන්තර පරිපාලන සහ තීරණ සහාය පද්ධතියකි.',
                'පද්ධතිය මගින් ගණනය කරන දර්ශක, අනතුරු ඇඟවීම්, ' .
                'පුරෝකථන, සාරාංශ සහ යෝජනා පරිපාලන සමාලෝචනයට ' .
                'සහාය පමණක් ලබා දෙයි. ඒවා තනිවම අවසාන මානව ' .
                'සම්පත් තීරණයක් හෝ ශ්‍රී ලංකා රජයේ නිල බලධාරීත්වයක් නොවේ.',
                'වැදගත් පරිපාලන ක්‍රියාවක් ගැනීමට පෙර අදාළ සේවක ' .
                'වාර්තාව සහ පනත්, රෙගුලාසි, ආයතන සංග්‍රහය, ' .
                'චක්‍රලේඛ, අමාත්‍යාංශ උපදෙස්, සේවා මිනිත්තු, ' .
                'රාජ්‍ය සේවා කොමිෂන් සභා තීරණ හෝ වෙනත් නිල ' .
                'මූලාශ්‍ර සත්‍යාපනය කරන්න.',
                'ඔබට ලබා දී ඇති බලයලත් ගිණුම පමණක් භාවිත කර ' .
                'පිවිසුම් තොරතුරු ආරක්ෂා කරන්න. තොරතුරු භාවිතය ' .
                'අනුමත නිල කාර්යයන් සඳහා පමණක් විය යුතු අතර ' .
                'ක්‍රියාකාරකම් ලොග් කර විගණනය කළ හැක.',
                'වැරදි දත්ත, ගණනය කිරීමේ දෝෂයක්, අනපේක්ෂිත පද්ධති ' .
                'හැසිරීමක්, වාර්තා දෝෂයක් හෝ පෞද්ගලිකත්ව/ආරක්ෂක ' .
                'ගැටලුවක් දැකිය හැකි නම් Incident & Correction Register ' .
                'හරහා වාර්තා කරන්න.',
            ],
            'acknowledgement' => 'ඉහත දැනුම්දීම කියවා තේරුම් ගත් බව මම පිළිගනිමි.',
            'continue' => 'පද්ධතියට ඉදිරියට යන්න',
        ],
        'ta' => [
            'tab' => 'தமிழ்',
            'title' => 'முக்கிய அறிவிப்பு',
            'subtitle' => 'கணினி முறைமையைப் பயன்படுத்துவதற்கு முன் இந்த அறிவிப்பைப் படித்து ஒப்புக்கொள்ளவும்.',
            'items' => [
                'Carder Management என்பது அங்கீகரிக்கப்பட்ட நிறுவனப் பயன்பாட்டிற்கான உள்நாட்டு நிர்வாக மற்றும் தீர்மான-ஆதரவு முறைமையாகும்.',
                'முறைமை கணக்கிடும் குறியீடுகள், எச்சரிக்கைகள், ' .
                'முன்னறிவிப்புகள், சுருக்கங்கள் மற்றும் பரிந்துரைகள் ' .
                'நிர்வாக பரிசீலனைக்கு உதவுவதற்காக மட்டுமே. அவை ' .
                'தனியாக இறுதி மனிதவளத் தீர்மானமாகவோ இலங்கை அரசின் ' .
                'அதிகாரப்பூர்வ ஆணையாகவோ அமையாது.',
                'முக்கியமான நிர்வாக நடவடிக்கைக்கு முன் சம்பந்தப்பட்ட ' .
                'பணியாளர் பதிவையும் பொருந்தும் சட்டம், விதிமுறை, ' .
                'Establishments Code, சுற்றறிக்கை, அமைச்சு அறிவுறுத்தல், ' .
                'சேவை நிமிடம், PSC தீர்மானம் அல்லது வேறு அதிகாரப்பூர்வ ' .
                'ஆதாரத்தையும் சரிபார்க்கவும்.',
                'உங்களுக்கு அங்கீகரிக்கப்பட்ட கணக்கை மட்டுமே பயன்படுத்தி ' .
                'உள்நுழைவு தகவலை பாதுகாக்கவும். தகவல்கள் அங்கீகரிக்கப்பட்ட ' .
                'உத்தியோகபூர்வ நோக்கங்களுக்காக மட்டுமே அணுகப்பட வேண்டும். ' .
                'செயல்பாடுகள் பதிவு செய்யப்பட்டு கணக்காய்வு செய்யப்படலாம்.',
                'தவறான தரவு, கணக்கீட்டு பிழை, எதிர்பாராத முறைமை ' .
                'நடத்தை, அறிக்கை பிழை அல்லது தனியுரிமை/பாதுகாப்பு ' .
                'பிரச்சினையை கண்டால் Incident & Correction Register ' .
                'மூலம் அறிவிக்கவும்.',
            ],
            'acknowledgement' => 'மேலுள்ள அறிவிப்பைப் படித்து புரிந்துகொண்டேன்.',
            'continue' => 'முறைமைக்கு தொடரவும்',
        ],
    ];
@endphp

@if ($showAdministrativeNotice)
    <div id="administrativeNoticeOverlay" role="dialog" aria-modal="true" aria-labelledby="administrativeNoticeTitle"
        style="
            position: fixed;
            inset: 0;
            z-index: 5000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: rgba(5, 24, 45, 0.72);
            backdrop-filter: blur(4px);
        ">
        <div
            style="
                width: min(780px, 100%);
                max-height: calc(100vh - 48px);
                overflow-y: auto;
                border: 1px solid var(--md-outline-variant);
                border-radius: 22px;
                background: var(--md-surface-container-low);
                box-shadow: var(--md-elevation-5);
            ">
            <div
                style="
                    padding: 24px 28px;
                    background: linear-gradient(
                        135deg,
                        #0d3b66,
                        #175f9e
                    );
                    color: #ffffff;
                ">
                <div
                    style="
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        gap: 18px;
                    ">
                    <div>
                        <div
                            style="
                                font-size: 25px;
                                font-weight: 800;
                                letter-spacing: -0.4px;
                            ">
                            Carder Management
                        </div>

                        <div
                            style="
                                margin-top: 3px;
                                font-size: 15px;
                                font-weight: 600;
                            ">
                            Administrative Support System
                        </div>

                        <div
                            style="
                                margin-top: 3px;
                                font-size: 12px;
                                opacity: 0.9;
                            ">
                            Internal Administrative Decision Support
                        </div>
                    </div>

                    <div
                        style="
                            max-width: 240px;
                            text-align: right;
                            font-size: 11px;
                            line-height: 1.45;
                            opacity: 0.92;
                        ">
                        Transparent · Accountable · Auditable<br>
                        Decision support, not decisions.
                    </div>
                </div>
            </div>

            <div style="padding: 24px 28px 28px;">
                <div
                    style="
                        margin-bottom: 16px;
                        padding: 12px 14px;
                        border-left: 4px solid var(--md-primary);
                        border-radius: var(--md-shape-sm);
                        background: color-mix(
                            in srgb,
                            var(--md-primary) 7%,
                            var(--md-surface-container)
                        );
                    ">
                    <strong>
                        Administrative Support System Notice
                    </strong>

                    <div class="md-body-sm"
                        style="
                            margin-top: 4px;
                            color: var(--md-on-surface-variant);
                        ">
                        Notice version:
                        {{ $administrativeNoticeVersion }}
                        ·
                        This notice is displayed after every successful login.
                    </div>
                </div>

                <div role="tablist" aria-label="Administrative notice language"
                    style="
                        display: grid;
                        grid-template-columns: repeat(3, 1fr);
                        gap: 8px;
                        margin-bottom: 16px;
                    ">
                    @foreach ($noticeLanguages as $locale => $notice)
                        <button type="button" class="administrative-notice-tab" data-notice-tab="{{ $locale }}"
                            aria-selected="{{ $locale === 'en' ? 'true' : 'false' }}"
                            style="
                                padding: 11px 12px;
                                border: 1px solid var(--md-outline-variant);
                                border-radius: var(--md-shape-sm);
                                background: {{ $locale === 'en' ? 'var(--md-primary-container)' : 'var(--md-surface-container)' }};
                                color: var(--md-on-surface);
                                font-weight: 700;
                                cursor: pointer;
                            ">
                            {{ $notice['tab'] }}
                        </button>
                    @endforeach
                </div>

                @foreach ($noticeLanguages as $locale => $notice)
                    <section class="administrative-notice-panel" data-notice-panel="{{ $locale }}"
                        style="{{ $locale === 'en' ? '' : 'display: none;' }}">
                        <h2 id="{{ $locale === 'en' ? 'administrativeNoticeTitle' : null }}"
                            style="
                                margin: 0;
                                font-size: 22px;
                                color: var(--md-on-surface);
                            ">
                            {{ $notice['title'] }}
                        </h2>

                        <p class="md-body-sm"
                            style="
                                margin-top: 5px;
                                color: var(--md-on-surface-variant);
                            ">
                            {{ $notice['subtitle'] }}
                        </p>

                        <div
                            style="
                                margin-top: 15px;
                                padding: 16px 18px;
                                border-radius: var(--md-shape-md);
                                background: var(--md-surface-container);
                            ">
                            <ol
                                style="
                                    margin: 0;
                                    padding-left: 22px;
                                    display: flex;
                                    flex-direction: column;
                                    gap: 10px;
                                    line-height: 1.5;
                                ">
                                @foreach ($notice['items'] as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ol>
                        </div>
                    </section>
                @endforeach

                <form method="POST" action="{{ route('administrative-notice.acknowledge') }}"
                    style="margin-top: 18px;">
                    @csrf

                    <label
                        style="
                            display: flex;
                            gap: 12px;
                            align-items: flex-start;
                            padding: 14px;
                            border: 1px solid var(--md-outline-variant);
                            border-radius: var(--md-shape-sm);
                            background: var(--md-surface-container);
                            cursor: pointer;
                        ">
                        <input id="administrativeNoticeAcknowledged" type="checkbox" name="notice_acknowledged"
                            value="1" required
                            style="
                                width: 20px;
                                height: 20px;
                                margin-top: 1px;
                            ">

                        <span>
                            <strong>
                                Acknowledgement / පිළිගැනීම / ஒப்புதல்
                            </strong>

                            <span class="md-body-sm"
                                style="
                                    display: block;
                                    margin-top: 4px;
                                    color: var(--md-on-surface-variant);
                                ">
                                English: I have read and understood the above notice.<br>
                                සිංහල: ඉහත දැනුම්දීම කියවා තේරුම් ගත් බව මම පිළිගනිමි.<br>
                                தமிழ்: மேலுள்ள அறிவிப்பைப் படித்து புரிந்துகொண்டேன்.
                            </span>
                        </span>
                    </label>

                    @error('notice_acknowledged')
                        <div class="md-field-error" style="margin-top: 8px;">
                            {{ $message }}
                        </div>
                    @enderror

                    <div
                        style="
                            display: flex;
                            justify-content: flex-end;
                            margin-top: 16px;
                        ">
                        <button id="administrativeNoticeContinue" type="submit" class="md-btn md-btn--filled" disabled
                            style="
                                min-width: 220px;
                                opacity: 0.55;
                            ">
                            ✓ Continue / ඉදිරියට / தொடரவும்
                        </button>
                    </div>
                </form>

                <div class="md-body-sm"
                    style="
                        margin-top: 14px;
                        text-align: center;
                        color: var(--md-on-surface-variant);
                    ">
                    Your acknowledgement, notice version and timestamp are
                    retained for administrative audit purposes.
                </div>
            </div>
        </div>
    </div>

    <script>
        (function() {
            'use strict';

            const overlay = document.getElementById(
                'administrativeNoticeOverlay'
            );

            const checkbox = document.getElementById(
                'administrativeNoticeAcknowledged'
            );

            const continueButton = document.getElementById(
                'administrativeNoticeContinue'
            );

            const tabs = document.querySelectorAll(
                '[data-notice-tab]'
            );

            const panels = document.querySelectorAll(
                '[data-notice-panel]'
            );

            if (!overlay || !checkbox || !continueButton) {
                return;
            }

            document.body.style.overflow = 'hidden';

            checkbox.addEventListener(
                'change',
                function() {
                    continueButton.disabled = !checkbox.checked;
                    continueButton.style.opacity = checkbox.checked ?
                        '1' :
                        '0.55';
                }
            );

            tabs.forEach(
                function(tab) {
                    tab.addEventListener(
                        'click',
                        function() {
                            const locale = tab.getAttribute(
                                'data-notice-tab'
                            );

                            tabs.forEach(
                                function(item) {
                                    const active = item === tab;

                                    item.setAttribute(
                                        'aria-selected',
                                        active ? 'true' : 'false'
                                    );

                                    item.style.background = active ?
                                        'var(--md-primary-container)' :
                                        'var(--md-surface-container)';
                                }
                            );

                            panels.forEach(
                                function(panel) {
                                    panel.style.display =
                                        panel.getAttribute(
                                            'data-notice-panel'
                                        ) === locale ?
                                        '' :
                                        'none';
                                }
                            );
                        }
                    );
                }
            );

            overlay.addEventListener(
                'keydown',
                function(event) {
                    if (event.key === 'Escape') {
                        event.preventDefault();
                    }
                }
            );
        })();
    </script>
@endif
