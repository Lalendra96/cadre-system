@extends('layouts.app')
@section('title', 'Secure Letter Editor — ' . $serviceLetter->subject)

@push('head')
    <link rel="stylesheet" href="{{ asset('css/service-letter-editor.css') }}">
@endpush

@section('content')
    @php
        $readonly = !$canEdit;
        $openComments = $serviceLetter->comments->where('is_resolved', false);
        $classification = $serviceLetter->document_classification ?: 'internal';
    @endphp

    @include('service-letters.partials.editor-header')

    <div id="secureLetterEditorConfig" data-can-edit="{{ $canEdit ? '1' : '0' }}"
        data-lock-version="{{ (int) $serviceLetter->editor_lock_version }}"
        data-autosave-url="{{ route('service-letters.autosave', $serviceLetter) }}"
        data-heartbeat-url="{{ route('service-letters.heartbeat', $serviceLetter) }}"
        data-comment-url="{{ route('service-letters.comments.store', $serviceLetter) }}"
        data-resolve-url-template="{{ route('service-letters.comments.resolve', [$serviceLetter, '__COMMENT__']) }}"
        data-csrf="{{ csrf_token() }}" hidden></div>

    <div class="sec-editor-shell">
        @include('service-letters.partials.editor-document')

        @include('service-letters.partials.editor-sidebar')
    </div>

    @push('scripts')
        <script src="{{ asset('js/service-letter-editor.js') }}" defer></script>
    @endpush
@endsection
