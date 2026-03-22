<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt {{ $payment->receipt_number }}</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,sans-serif;color:#111827;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f4f6;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:680px;background:#ffffff;border-radius:14px;overflow:hidden;">
                <tr>
                    <td style="padding:24px 28px;background:linear-gradient(135deg,#000928,#381998);color:#ffffff;">
                        <p style="margin:0 0 8px 0;font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#a5f3fc;">Payment Receipt</p>
                        <h1 style="margin:0;font-size:24px;line-height:1.25;">{{ $siteName }}</h1>
                        <p style="margin:8px 0 0 0;font-size:14px;color:#d1d5db;">Receipt #{{ $payment->receipt_number }}</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:24px 28px 6px 28px;">
                        <p style="margin:0 0 12px 0;font-size:16px;line-height:1.7;color:#111827;">Hello {{ $notifiableName }},</p>
                        <p style="margin:0 0 16px 0;font-size:15px;line-height:1.7;color:#1f2937;">
                            We've successfully received your payment for <strong>{{ $program?->title }}</strong>. Here is your receipt summary.
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 28px 18px 28px;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;">
                            <tr><td style="padding:12px 14px;background:#f9fafb;font-weight:700;">Transaction Summary</td></tr>
                            <tr><td style="padding:14px;">
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="font-size:14px;color:#374151;">
                                    <tr>
                                        <td style="padding:8px 0;">Amount Paid</td>
                                        <td style="padding:8px 0;text-align:right;font-weight:700;color:#111827;">{{ number_format((float) $payment->amount, 0, '.', ',') }} {{ $payment->currency }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:8px 0;">Reference</td>
                                        <td style="padding:8px 0;text-align:right;">{{ $payment->reference }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:8px 0;">Program</td>
                                        <td style="padding:8px 0;text-align:right;">{{ $program?->title }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:8px 0;">Payment Method</td>
                                        <td style="padding:8px 0;text-align:right;">{{ $payment->payment_channel ? $payment->payment_channel.' - '.$payment->provider : $payment->provider.' Mobile Money' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:8px 0;">Installment</td>
                                        <td style="padding:8px 0;text-align:right;">{{ $payment->installment_number }} / {{ $payment->total_installments }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:8px 0;">Paid On</td>
                                        <td style="padding:8px 0;text-align:right;">{{ optional($payment->paid_at)->format('M d, Y H:i') }}</td>
                                    </tr>
                                </table>
                            </td></tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 28px 24px 28px;">
                        <a href="{{ $receiptUrl }}" style="display:inline-block;padding:12px 20px;background:#42b6c5;color:#ffffff;text-decoration:none;font-weight:700;border-radius:8px;">
                            View Printable Receipt
                        </a>
                        <a href="{{ $receiptDownloadUrl }}" style="display:inline-block;padding:12px 20px;background:#4f46e5;color:#ffffff;text-decoration:none;font-weight:700;border-radius:8px;margin-left:8px;">
                            Download Receipt PDF
                        </a>
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 28px 28px 28px;">
                        <p style="margin:0;font-size:14px;line-height:1.7;color:#4b5563;">Thank you for your trust.<br>{{ $siteName }}</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
