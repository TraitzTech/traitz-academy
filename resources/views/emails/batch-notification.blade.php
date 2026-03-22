<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,sans-serif;color:#111827;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f4f6;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border-radius:12px;overflow:hidden;">
                <tr>
                    <td style="padding:28px 28px 16px 28px;background:#ffffff;">
                        <h1 style="margin:0;font-size:22px;line-height:1.3;color:#111827;">{{ $subject }}</h1>
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 28px 18px 28px;">
                        <p style="margin:0 0 14px 0;font-size:16px;line-height:1.6;color:#1f2937;">
                            Hello {{ $recipientName }},
                        </p>
                        <div style="font-size:15px;line-height:1.7;color:#1f2937;">
                            {!! $messageHtml !!}
                        </div>
                    </td>
                </tr>
                @if(!empty($actionText) && !empty($actionUrl))
                    <tr>
                        <td style="padding:0 28px 24px 28px;">
                            <a href="{{ $actionUrl }}" style="display:inline-block;padding:12px 20px;background:#42b6c5;color:#ffffff;text-decoration:none;font-weight:600;border-radius:8px;">
                                {{ $actionText }}
                            </a>
                        </td>
                    </tr>
                @endif
                <tr>
                    <td style="padding:0 28px 28px 28px;">
                        <p style="margin:0 0 6px 0;font-size:15px;line-height:1.6;color:#1f2937;">
                            Thank you for being part of {{ $siteName }}!
                        </p>
                        <p style="margin:0;font-size:14px;line-height:1.6;color:#4b5563;">
                            Regards,<br>{{ $siteName }}
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
