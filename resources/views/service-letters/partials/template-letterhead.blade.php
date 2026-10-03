        <div class="workforce-panel" style="margin-top: 16px;">
            <div class="panel-title-row">
                <div>
                    <div class="panel-title">
                        2. Template & official header
                    </div>

                    <div class="panel-subtitle">
                        Templates are filtered by the selected letter language.
                    </div>
                </div>
            </div>

            <div class="md-form-row" style="margin-top: 12px;">
                <div class="md-form-group">
                    <label class="md-label">
                        {{ __('ui.template') }}
                    </label>

                    <select class="md-select" name="template_id" id="template_select">
                        <option value="">
                            — Write from scratch / AI-assisted —
                        </option>

                        @foreach ($templates as $template)
                            <option value="{{ $template->id }}" data-language="{{ $template->language }}"
                                {{ old('template_id', $draft?->template_id) == $template->id ? 'selected' : '' }}>
                                {{ $template->name }}
                                ({{ \App\Models\ServiceLetterTemplate::LANGUAGES[$template->language] ?? $template->language }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md-form-group">
                    <label class="md-label">
                        {{ __('ui.letterhead') }} *
                    </label>

                    <select class="md-select" name="letterhead_id" required>
                        @foreach ($letterheads as $letterhead)
                            <option value="{{ $letterhead->id }}"
                                {{ old('letterhead_id', $draft?->letterhead_id ?? optional($letterheads->firstWhere('is_default', true))->id) ==
                                $letterhead->id
                                    ? 'selected'
                                    : '' }}>
                                {{ $letterhead->name }}
                                {{ $letterhead->is_default ? ' — Default' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md-form-group">
                    <label class="md-label">
                        Copy / marking
                    </label>

                    <select class="md-select" name="copy_type">
                        @foreach (\App\Models\ServiceLetter::COPY_TYPES as $value => $label)
                            <option value="{{ $value }}"
                                {{ old('copy_type', $draft?->copy_type ?? 'original') === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div
                style="
                    display: flex;
                    gap: 8px;
                    flex-wrap: wrap;
                ">
                <button type="button" id="generateBtn" class="md-btn md-btn--outlined">
                    ⚡ {{ __('ui.generate_template') }}
                </button>

                @if (\App\Services\FeatureToggleService::enabled('ai_service_letter_assistant'))
                    <button type="button" id="aiBtn" class="md-btn md-btn--tonal">
                        ✨ {{ __('ui.ai_assist') }}
                    </button>
                @endif

                <span id="genStatus" class="md-body-sm" style="align-self: center;"></span>
            </div>
        </div>
