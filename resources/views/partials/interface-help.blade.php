@php
    $screenHelp = \App\Services\ScreenHelpService::forRoute(request()->route()?->getName());
@endphp
@if ($screenHelp)
    @include('partials.section-help', ['helpTopic' => $screenHelp])
@endif
