<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">

    <title>{{ $serviceLetter->subject }}</title>

    <style>
        @page {
            size: A4;
            margin: 15mm 18mm;
        }

        body {
            font-family:
                "Noto Sans Sinhala",
                "Noto Sans Tamil",
                "Nirmala UI",
                "DejaVu Sans",
                Arial,
                sans-serif;
            color: #111;
            font-size: 12pt;
            line-height: 1.55;
            margin: 0;
        }

        .toolbar {
            position: fixed;
            right: 12px;
            top: 12px;
        }

        .letterhead {
            text-align: center;
            border-bottom: 2px solid #111;
            padding-bottom: 10px;
            margin-bottom: 18px;
        }

        .emblem {
            font-size: 28px;
        }

        .institution {
            font-size: 18px;
            font-weight: 700;
        }

        .ministry {
            font-size: 11px;
        }

        .contacts {
            font-size: 9.5px;
            margin-top: 5px;
        }

        .meta {
            display: flex;
            justify-content: space-between;
            margin: 12px 0;
            font-size: 10.5pt;
        }

        .recipient {
            white-space: pre-line;
            margin-bottom: 14px;
        }

        .subject {
            font-weight: 700;
            text-decoration: underline;
            margin: 14px 0;
        }

        .body {
            white-space: pre-wrap;
        }

        .signature {
            margin-top: 36px;
        }

        .copy-mark {
            position: absolute;
            right: 18mm;
            top: 12mm;
            font-weight: 700;
        }

        .draft {
            position: fixed;
            inset: 45% 0;
            text-align: center;
            font-size: 54px;
            opacity: 0.08;
            transform: rotate(-25deg);
        }

        @media print {
            .toolbar {
                display: none;
            }
        }
    </style>
</head>

<body>
    @php
        $letterhead = $serviceLetter->letterhead;
    @endphp

    <button
        class="toolbar"
        onclick="window.print()"
    >
        🖨 Print
    </button>

    @if ($serviceLetter->status !== 'approved')
        <div class="draft">
            DRAFT — NOT APPROVED
        </div>
    @endif

    <div class="copy-mark">
        {{
            strtoupper(
                \App\Models\ServiceLetter::COPY_TYPES[$serviceLetter->copy_type]
                    ?? $serviceLetter->copy_type
            )
        }}
    </div>

    <div class="letterhead">
        @if ($letterhead?->logo_path)
            <img
                src="{{ asset('storage/' . $letterhead->logo_path) }}"
                alt="Letterhead logo"
                style="
                    max-height: 64px;
                    max-width: 110px;
                "
            >
        @elseif ($letterhead?->show_national_emblem)
            <div class="emblem">
                ⚜
            </div>
        @endif

        <div class="institution">
            {{ $letterhead?->institution_name ?? 'Teaching Hospital Peradeniya' }}
        </div>

        @if ($letterhead?->department_name)
            <div class="ministry">
                {{ $letterhead->department_name }}
            </div>
        @endif

        @if ($letterhead?->ministry_name)
            <div class="ministry">
                {{ $letterhead->ministry_name }}
            </div>
        @endif

        <div class="contacts">
            {{
                collect([
                    $letterhead?->address_line_1,
                    $letterhead?->address_line_2,
                    $letterhead?->telephone
                        ? 'Tel: ' . $letterhead->telephone
                        : null,
                    $letterhead?->fax
                        ? 'Fax: ' . $letterhead->fax
                        : null,
                    $letterhead?->email,
                ])
                    ->filter()
                    ->join(' · ')
            }}
        </div>

        @if ($letterhead?->header_note)
            <div class="contacts">
                {{ $letterhead->header_note }}
            </div>
        @endif
    </div>

    <div class="meta">
        <div>
            My No:
            {{
                $serviceLetter->reference_no
                    ?: (
                        ($letterhead?->reference_prefix ?? '')
                        . $serviceLetter->id
                    )
            }}
        </div>

        <div>
            Date:
            {{
                ($serviceLetter->approved_at ?? $serviceLetter->created_at)
                    ->format('d M Y')
            }}
        </div>
    </div>

    @if (
        $serviceLetter->recipient_name
        || $serviceLetter->recipient_address
    )
        <div class="recipient">
            {{ $serviceLetter->recipient_name }}

            @if ($serviceLetter->recipient_address)
                <br>
                {{ $serviceLetter->recipient_address }}
            @endif
        </div>
    @endif

    <div class="subject">
        {{ $serviceLetter->subject }}
    </div>

    <div class="body">
        {{ $serviceLetter->rendered_body }}
    </div>

    <div class="signature">
        @if ($serviceLetter->status === 'approved')
            <strong>
                {{ $serviceLetter->approvedBy?->name }}
            </strong>

            <br>

            {{
                $letterhead?->signatory_designation
                    ?: 'Administrative Officer / Authorised Signatory'
            }}

            <br>

            <small>
                Approved {{ $serviceLetter->approved_at?->format('d M Y H:i') }}
            </small>
        @else
            <strong>
                Draft — approval required
            </strong>
        @endif
    </div>

    @if ($letterhead?->footer_note)
        <div
            style="
                margin-top: 30px;
                border-top: 1px solid #bbb;
                padding-top: 7px;
                font-size: 9px;
                text-align: center;
            "
        >
            {{ $letterhead->footer_note }}
        </div>
    @endif
</body>
</html>
