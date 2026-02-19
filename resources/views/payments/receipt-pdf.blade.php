<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt {{ $payment->receipt_number ?? $payment->reference }}</title>
    <style>
        @page { margin: 24px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #111827; font-size: 12px; line-height: 1.5; }
        .header { background: linear-gradient(135deg, #000928, #381998); color: #fff; padding: 18px 20px; border-radius: 10px; }
        .subtle { color: #6b7280; font-size: 11px; }
        .title { font-size: 24px; font-weight: 700; margin: 0; }
        .receipt-no { margin-top: 4px; font-size: 12px; }
        .grid { width: 100%; margin-top: 16px; border-collapse: collapse; }
        .panel { width: 48%; vertical-align: top; }
        .card { border: 1px solid #e5e7eb; border-radius: 10px; padding: 14px; min-height: 210px; }
        .card h3 { margin: 0 0 10px 0; font-size: 12px; text-transform: uppercase; letter-spacing: .05em; color: #4b5563; }
        .row { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .row td:first-child { color: #6b7280; width: 45%; }
        .amount { color: #0f766e; font-weight: 700; }
        .status { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 10px; text-transform: uppercase; font-weight: 700; }
        .status-successful { background: #dcfce7; color: #166534; }
        .status-pending { background: #fef9c3; color: #854d0e; }
        .status-failed { background: #fee2e2; color: #991b1b; }
        .verification { margin-top: 18px; border: 1px solid #e5e7eb; border-radius: 10px; padding: 14px; }
        .verification-grid { width: 100%; border-collapse: collapse; }
        .verification-grid td { vertical-align: top; }
        .qr-wrap { text-align: right; }
        .qr-wrap img { width: 120px; height: 120px; }
        .signature-section { margin-top: 36px; width: 100%; border-collapse: collapse; }
        .signature-cell { width: 48%; vertical-align: top; padding-top: 26px; }
        .line { border-top: 1px solid #111827; height: 24px; }
        .label { font-size: 11px; color: #374151; font-weight: 700; }
        .footer-note { margin-top: 22px; color: #6b7280; font-size: 10px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <p class="subtle" style="color:#a5f3fc;margin:0;text-transform:uppercase;letter-spacing:.08em;">Traitz Academy • Official Receipt</p>
        <h1 class="title">Payment Receipt</h1>
        <p class="receipt-no">Receipt #{{ $payment->receipt_number ?? 'Pending' }}</p>
    </div>

    <table class="grid">
        <tr>
            <td class="panel" style="padding-right: 2%;">
                <div class="card">
                    <h3>Payment Details</h3>
                    <table class="row"><tr><td>Status</td><td>
                        <span class="status status-{{ $payment->status }}">{{ $payment->status }}</span>
                    </td></tr></table>
                    <table class="row"><tr><td>Reference</td><td>{{ $payment->reference }}</td></tr></table>
                    <table class="row"><tr><td>Receipt Number</td><td>{{ $payment->receipt_number ?? 'N/A' }}</td></tr></table>
                    <table class="row"><tr><td>Provider</td><td>{{ $payment->provider }}</td></tr></table>
                    <table class="row"><tr><td>Payment Channel</td><td>{{ $payment->payment_channel ?? 'ONLINE' }}</td></tr></table>
                    <table class="row"><tr><td>Phone</td><td>{{ $payment->payer_phone }}</td></tr></table>
                    <table class="row"><tr><td>Amount</td><td class="amount">{{ number_format((float) $payment->amount, 0, '.', ',') }} {{ $payment->currency }}</td></tr></table>
                    <table class="row"><tr><td>Paid On</td><td>{{ optional($payment->paid_at ?? $payment->created_at)->format('d M Y, H:i') }}</td></tr></table>
                    @if ($payment->manual_entry)
                        @php
                            $collector = $payment->recordedBy ?? $payment->updatedBy;
                            $collectorRole = $collector?->role === 'admin'
                                ? 'CTO (Legacy)'
                                : ($collector?->role
                                    ? str((string) $collector->role)->replace('_', ' ')->title()->toString()
                                    : 'Unknown');
                        @endphp
                        <table class="row"><tr><td>Collected By</td><td>{{ $collector?->name ?? 'Unknown' }}</td></tr></table>
                        <table class="row"><tr><td>Collector Role</td><td>{{ $collectorRole }}</td></tr></table>
                    @endif
                </div>
            </td>
            <td class="panel" style="padding-left: 2%;">
                <div class="card">
                    <h3>Applicant & Program</h3>
                    <table class="row"><tr><td>Applicant</td><td>{{ $payment->application?->first_name }} {{ $payment->application?->last_name }}</td></tr></table>
                    <table class="row"><tr><td>Email</td><td>{{ $payment->application?->email }}</td></tr></table>
                    <table class="row"><tr><td>Program</td><td>{{ $payment->application?->program?->title }}</td></tr></table>
                    <table class="row"><tr><td>Payment Type</td><td>{{ ucfirst((string) $payment->payment_type) }}</td></tr></table>
                    <table class="row"><tr><td>Installment</td><td>{{ $payment->installment_number }} / {{ $payment->total_installments }}</td></tr></table>
                    <table class="row"><tr><td>Transaction ID</td><td>{{ $payment->mesomb_transaction_id ?? 'N/A' }}</td></tr></table>
                </div>
            </td>
        </tr>
    </table>

    <div class="verification">
        <table class="verification-grid">
            <tr>
                <td>
                    <h3 style="margin:0 0 8px 0;font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#4b5563;">Validation</h3>
                    <p style="margin:0 0 8px 0;">Scan the QR code to cross-check this receipt in the system.</p>
                    <p class="subtle" style="margin:0;">Verification URL: {{ $receiptUrl }}</p>
                </td>
                <td class="qr-wrap">
                    @if (!empty($qrCodeDataUri))
                        <img src="{{ $qrCodeDataUri }}" alt="Receipt QR code">
                    @else
                        <span class="subtle">QR unavailable</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <table class="signature-section">
        <tr>
            <td class="signature-cell" style="padding-right: 2%;">
                <div class="line"></div>
                <div class="label">Manager/Administrator Signature</div>
                <div class="subtle">Name, signature, and stamp</div>
            </td>
            <td class="signature-cell" style="padding-left: 2%;">
                <div class="line"></div>
                <div class="label">Student Signature</div>
                <div class="subtle">
                    {{ trim(($payment->application?->first_name ?? '').' '.($payment->application?->last_name ?? '')) ?: 'Applicant Name' }}
                </div>
            </td>
        </tr>
    </table>

    <p class="footer-note">
        This receipt is generated by Traitz Academy and can be used for internal validation and official stamping.
    </p>
</body>
</html>
