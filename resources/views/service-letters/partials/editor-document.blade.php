    <main>
        <div class="sec-toolbar" aria-label="Editor tools">
            <button class="sec-tool" type="button" data-insert="• ">• Bullet</button>
            <button class="sec-tool" type="button" data-insert="\n    ">Indent</button>
            <button class="sec-tool" type="button" id="insertDate">Insert date</button>
            <button class="sec-tool" type="button" data-insert="\n--- Page break ---\n">Page break</button>
            <button class="sec-tool" type="button"
                data-insert="\nYours faithfully,\n\n\n........................................\n">Signature
                block</button>
            <span class="sec-muted" style="margin-left:auto;">Constrained official-text formatting reduces unsafe/hidden
                content.</span>
        </div>

        <div class="sec-page-wrap">
            <section class="sec-page" aria-label="Editable A4 letter">
                <div class="sec-letterhead">
                    <h2>{{ $serviceLetter->letterhead?->institution_name ?? 'TEACHING HOSPITAL PERADENIYA' }}</h2>
                    <div style="font-size:11px;margin-top:5px;">Official Service Correspondence</div>
                </div>

                <div class="sec-meta-grid">
                    <strong>My Ref.</strong>
                    <input id="reference_no" class="sec-inline-input" maxlength="100"
                        value="{{ $serviceLetter->reference_no }}" placeholder="Required before review"
                        @readonly($readonly)>
                    <strong>Date</strong><span>{{ now()->format('d F Y') }}</span>
                    <strong>To</strong>
                    <div>
                        <input id="recipient_name" class="sec-inline-input" maxlength="180"
                            value="{{ $serviceLetter->recipient_name }}" placeholder="Recipient name / designation"
                            @readonly($readonly)>
                        <input id="recipient_address" class="sec-inline-input" maxlength="300"
                            value="{{ $serviceLetter->recipient_address }}" placeholder="Recipient address"
                            style="margin-top:4px;" @readonly($readonly)>
                    </div>
                </div>

                <input id="subject" class="sec-subject" maxlength="200" value="{{ $serviceLetter->subject }}"
                    placeholder="Subject" @readonly($readonly)>
                <textarea id="rendered_body" class="sec-body" spellcheck="true" @readonly($readonly)>{{ $serviceLetter->rendered_body }}</textarea>

                <div class="sec-page-footer"><span>Classification: <strong
                            id="footerClassification">{{ strtoupper($classification) }}</strong></span><span>Page 1 ·
                        System-controlled record</span></div>
            </section>
        </div>
    </main>
