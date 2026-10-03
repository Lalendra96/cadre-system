        <div class="workforce-panel" style="margin-top: 16px;">
            <div class="panel-title-row">
                <div>
                    <div class="panel-title">
                        4. Review the letter
                    </div>

                    <div class="panel-subtitle">
                        Generated or AI-assisted text is only a draft.
                        Verify every factual statement and the applicable
                        official authority before approval. System/AI output
                        does not itself constitute Government policy, a circular,
                        regulation, Establishments Code provision, or an
                        administrative determination.
                    </div>
                </div>
            </div>

            <div class="md-form-group" style="margin-top: 12px;">
                <label class="md-label">
                    {{ __('ui.subject') }} *
                </label>

                <input class="md-input" name="subject" id="subject_input" maxlength="200" required
                    value="{{ old('subject', $draft?->subject) }}">
            </div>

            <div class="md-form-group">
                <label class="md-label">
                    {{ __('ui.letter_body') }} *
                </label>

                <textarea class="md-input" name="rendered_body" id="body_textarea" rows="18" required>{{ old('rendered_body', $draft?->rendered_body) }}</textarea>

                <div class="md-field-hint">
                    Do not approve placeholders such as [EDIT: …] until they
                    are completed or deliberately removed.
                </div>
            </div>

            <div class="md-form-group">
                <label class="md-label">
                    Optional AI drafting instruction
                </label>

                <input class="md-input" id="ai_instructions" maxlength="500"
                    placeholder="e.g. Address to the Australian High Commission; keep the wording concise">
            </div>
        </div>

        <div class="ai-draft-preview workforce-panel" id="aiDraftPreview" hidden>
            <h3 class="panel-title">Review assisted draft</h3>
            <p id="aiDraftNotice" class="panel-subtitle"></p>
            <pre id="aiDraftText" class="ai-draft-text"></pre>
            <div class="ai-draft-actions">
                <button type="button" class="md-btn md-btn--primary" id="applyAiDraft">Use this draft</button>
                <button type="button" class="md-btn md-btn--outlined" id="dismissAiDraft">Discard preview</button>
            </div>
            <small>This preview is not saved. Using it replaces the editable letter body; save the letter when
                ready.</small>
        </div>
