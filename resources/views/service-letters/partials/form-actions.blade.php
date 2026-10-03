        <div
            style="
                display: flex;
                justify-content: flex-end;
                gap: 10px;
                margin-top: 16px;
            ">
            <a href="{{ route('service-letters.index') }}" class="md-btn md-btn--outlined">
                Cancel
            </a>

            <button class="md-btn md-btn--filled">
                💾 {{ $draft ? 'Update Draft' : __('ui.save_draft') }}
            </button>
        </div>
