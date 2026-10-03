<section class="cadre-dashboard-panel">
    <div class="cadre-dashboard-panel__head">
        <div>
            <h3>Governance & Legal-Safeguard Status</h3>
            <p>Controls that support accountable use of workforce information.</p>
        </div>
        @if (Route::has('governance.index'))
            <a class="cadre-panel-link" href="{{ route('governance.index') }}">View governance record →</a>
        @endif
    </div>

    <div class="cadre-control-list">
        <div class="cadre-control-row">
            <span class="cadre-control-row__mark cadre-control-row__mark--good">✓</span>
            <div><strong>Business Rule Register</strong><small>{{ number_format($activeRules) }} active rules</small>
            </div>
            <span>{{ $ruleVerificationPct }}% source-verified</span>
        </div>
        <div class="cadre-control-row">
            <span
                class="cadre-control-row__mark {{ $rulesDueForReview > 0 ? 'cadre-control-row__mark--warn' : 'cadre-control-row__mark--good' }}">{{ $rulesDueForReview > 0 ? '!' : '✓' }}</span>
            <div><strong>Institutional Rule Review</strong><small>Rules requiring review in the next 3 months</small>
            </div>
            <span>{{ number_format($rulesDueForReview) }}</span>
        </div>
        <div class="cadre-control-row">
            <span class="cadre-control-row__mark cadre-control-row__mark--good">✓</span>
            <div><strong>Human Approval Control</strong><small>Consequential HR records use the administrative decision
                    workflow</small></div>
            <span>Enabled</span>
        </div>
        <div class="cadre-control-row">
            <span class="cadre-control-row__mark cadre-control-row__mark--good">✓</span>
            <div><strong>AI Advisory-Only Guard</strong><small>AI drafting and summaries cannot approve final HR
                    decisions</small></div>
            <span>Active</span>
        </div>
        <div class="cadre-control-row">
            <span class="cadre-control-row__mark cadre-control-row__mark--good">✓</span>
            <div><strong>Audit Evidence</strong><small>Recorded audit events during the current month</small></div>
            <span>{{ number_format($auditThisMonth) }}</span>
        </div>
    </div>
</section>
