{{-- Reusable active/disabled status badge --}}
@props(['record'])

@if($record->is_active)
    <span class="md-badge md-badge--success">Active</span>
@else
    <span class="md-badge md-badge--critical"
          title="{{ $record->disableSummary() }}"
          style="cursor:help;">⊘ Disabled</span>
@endif
