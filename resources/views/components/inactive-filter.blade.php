{{--
  Show/hide inactive records toggle for index pages.
  Add to the top of any index that supports $showInactive / $canSeeInactive.

  Props:
    $showInactive   — bool from controller
    $canSeeInactive — bool (Super Admin / elevated role)
    $route          — named route for the current index
    $year           — optional year param to preserve
--}}
@props(['showInactive' => false, 'canSeeInactive' => false, 'route', 'year' => null])

@if($canSeeInactive)
    @php
        $params = array_filter(['show_inactive' => !$showInactive ? 1 : 0, 'year' => $year]);
    @endphp
    <a href="{{ route($route, $params) }}"
       class="md-btn {{ $showInactive ? 'md-btn--tonal' : 'md-btn--outlined' }}"
       style="font-size:12px;">
        {{ $showInactive ? '👁 Hiding disabled' : '👁 Show disabled' }}
    </a>
@endif
