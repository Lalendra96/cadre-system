<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background:#f2f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f2f4f5;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width:520px;background:#ffffff;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="background:#0B3D42;padding:24px 32px;">
                            <span style="color:#02C39A;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;">
                                {{ \App\Models\Circular::CATEGORY_LABELS[$circular->category] ?? 'Notice' }}
                            </span>
                            <h1 style="color:#ffffff;font-size:19px;margin:8px 0 0;">{{ $circular->title }}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 32px;">
                            <p style="font-size:14px;color:#333;margin:0 0 16px;">Dear {{ $recipientName }},</p>
                            <p style="font-size:14px;color:#333;line-height:1.6;margin:0 0 20px;">
                                A new {{ strtolower(\App\Models\Circular::CATEGORY_LABELS[$circular->category] ?? 'notice') }}
                                has been issued and applies to your position.
                            </p>
                            @if($circular->description)
                            <p style="font-size:13.5px;color:#555;line-height:1.6;background:#f6f8f8;padding:14px 16px;border-radius:8px;margin:0 0 20px;">
                                {{ $circular->description }}
                            </p>
                            @endif
                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="background:#02C39A;border-radius:8px;">
                                        <a href="{{ $publicUrl }}" style="display:inline-block;padding:12px 24px;color:#06201A;font-weight:600;font-size:13.5px;text-decoration:none;">
                                            View &amp; Download Document
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="font-size:11.5px;color:#888;margin:24px 0 0;">
                                This link does not require a login and may be shared with others who need this document.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 32px;background:#f6f8f8;text-align:center;">
                            <p style="font-size:11px;color:#999;margin:0;">Teaching Hospital Peradeniya · HIMS PARIKSHA</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
