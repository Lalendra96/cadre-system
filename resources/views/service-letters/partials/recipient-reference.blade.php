        <div class="workforce-panel" style="margin-top: 16px;">
            <div class="panel-title-row">
                <div>
                    <div class="panel-title">
                        3. Recipient & reference
                    </div>

                    <div class="panel-subtitle">
                        Optional. Use exactly as shown on the official
                        correspondence/file.
                    </div>
                </div>
            </div>

            <div class="md-form-row" style="margin-top: 12px;">
                <div class="md-form-group">
                    <label class="md-label">
                        {{ __('ui.recipient') }}
                    </label>

                    <input class="md-input" name="recipient_name"
                        value="{{ old('recipient_name', $draft?->recipient_name) }}"
                        placeholder="e.g. Visa Officer / Director">
                </div>

                <div class="md-form-group">
                    <label class="md-label">
                        Recipient address / institution
                    </label>

                    <input class="md-input" name="recipient_address"
                        value="{{ old('recipient_address', $draft?->recipient_address) }}">
                </div>

                <div class="md-form-group">
                    <label class="md-label">
                        {{ __('ui.reference') }}
                    </label>

                    <input class="md-input" name="reference_no" value="{{ old('reference_no', $draft?->reference_no) }}"
                        placeholder="Official file/reference number">
                </div>
            </div>
        </div>
