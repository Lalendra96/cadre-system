@php
    $help = $helpTopic ?? config('section_help.' . $topic);
@endphp
@if ($help)
    <aside class="section-help" data-section-help>
        <details>
            <summary>Help: {{ $help['title'] }}</summary>
            <div class="section-help__body">
                @foreach ($help['translations'] ?? [] as $locale => $copy)
                    @if (app()->getLocale() === $locale)
                        <p lang="{{ $locale }}">{{ $copy }}</p>
                        <p class="md-caption">Detailed steps are available below in English.</p>
                    @endif
                @endforeach
                <ol lang="en">
                    @foreach ($help['steps'] as $step)
                        <li>{{ $step }}</li>
                    @endforeach
                </ol>
            </div>
        </details>
    </aside>
@endif
