@extends('layouts.app')

@section('title', 'Employee Documents')

@section('content')
    <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap">
        <div>
            <a href="{{ route('employees.show', $employee) }}">← Employee 360</a>
            <h1 class="md-headline-md">Documents · {{ $employee->display_name }}</h1>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:minmax(320px,.65fr) minmax(0,1.35fr);gap:16px;align-items:start">
        <section class="workforce-panel">
            <h2 class="md-title-lg">Upload Document</h2>

            <form method="POST" enctype="multipart/form-data" action="{{ route('employee-documents.store', $employee) }}"
                style="display:grid;gap:10px;margin-top:12px">
                @csrf

                <label>
                    Category
                    <select class="md-select" name="category" required>
                        @foreach (['appointment', 'transfer', 'promotion', 'increment', 'retirement', 'qualification', 'professional_registration', 'nic', 'wop', 'confirmation', 'training', 'administrative', 'other'] as $category)
                            <option value="{{ $category }}">{{ ucwords($category) }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    Title
                    <input class="md-input" name="title" required>
                </label>

                <label>
                    Reference
                    <input class="md-input" name="reference_no">
                </label>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                    <label>
                        Document date
                        <input type="date" class="md-input" name="document_date">
                    </label>

                    <label>
                        Expiry date
                        <input type="date" class="md-input" name="expiry_date">
                    </label>
                </div>

                <label>
                    File
                    <input class="md-input" type="file" name="file" required accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                </label>

                <label>
                    <input type="checkbox" name="is_confidential" value="1">
                    Confidential / management-only
                </label>

                <label>
                    Notes
                    <textarea class="md-input" name="notes" rows="3"></textarea>
                </label>

                <button type="submit" class="md-btn--primary">Upload</button>
            </form>
        </section>

        <section class="workforce-panel">
            <h2 class="md-title-lg">Document Repository</h2>

            <div style="display:grid;gap:8px;margin-top:12px">
                @forelse($documents as $document)
                    <div class="md-card" style="padding:12px">
                        <div style="display:flex;justify-content:space-between;gap:10px">
                            <div>
                                <strong>{{ $document->title }}</strong>

                                <div class="md-body-sm">
                                    {{ ucwords($document->category) }}
                                    @if ($document->reference_no)
                                        · {{ $document->reference_no }}
                                    @endif
                                </div>

                                <div class="md-body-sm">
                                    {{ $document->document_date?->format('d M Y') ?? 'No document date' }}

                                    @if ($document->expiry_date)
                                        · expires {{ $document->expiry_date->format('d M Y') }}
                                    @endif

                                    @if ($document->is_confidential)
                                        · Confidential
                                    @endif
                                </div>
                            </div>

                            <a class="md-btn md-btn--ghost"
                                href="{{ route('employee-documents.download', [$employee, $document]) }}">
                                Download
                            </a>
                        </div>
                    </div>
                @empty
                    <p>No documents uploaded.</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection
