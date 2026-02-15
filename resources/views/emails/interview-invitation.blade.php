<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interview Invitation — {{ $programTitle }}</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:'Segoe UI',Arial,Helvetica,sans-serif;color:#111827;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f4f6;padding:32px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.06);">

                {{-- Header Banner --}}
                <tr>
                    <td style="background:linear-gradient(135deg,#000928 0%,#0a1a3f 100%);padding:40px 32px 32px 32px;text-align:center;">
                        <div style="width:56px;height:56px;background:#42b6c5;border-radius:50%;margin:0 auto 16px auto;text-align:center;line-height:56px;">
                            <span style="font-size:28px;color:#ffffff;">📋</span>
                        </div>
                        <h1 style="margin:0 0 8px 0;font-size:24px;font-weight:700;color:#ffffff;letter-spacing:-0.3px;">Interview Invitation</h1>
                        <p style="margin:0;font-size:15px;color:#94a3b8;">You've been selected for an interview</p>
                    </td>
                </tr>

                {{-- Greeting --}}
                <tr>
                    <td style="padding:32px 32px 0 32px;">
                        <p style="margin:0 0 16px 0;font-size:17px;line-height:1.6;color:#1f2937;">
                            Hello <strong>{{ $applicantName }}</strong>,
                        </p>
                        <p style="margin:0 0 20px 0;font-size:15px;line-height:1.7;color:#374151;">
                            Great news! After reviewing your application for <strong style="color:#42b6c5;">{{ $programTitle }}</strong>, we'd like to invite you to complete an interview assessment. This is an important step in the selection process.
                        </p>
                    </td>
                </tr>

                {{-- Interview Details Card --}}
                <tr>
                    <td style="padding:0 32px;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
                            <tr>
                                <td style="padding:20px 24px 12px 24px;border-bottom:1px solid #e2e8f0;">
                                    <p style="margin:0;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;">Interview Details</p>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:16px 24px;">
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                        <tr>
                                            <td style="padding:6px 0;">
                                                <span style="font-size:14px;color:#64748b;">Title:</span>
                                                <span style="font-size:15px;font-weight:600;color:#111827;margin-left:8px;">{{ $interviewTitle }}</span>
                                            </td>
                                        </tr>
                                        @if($interviewDescription)
                                        <tr>
                                            <td style="padding:6px 0;">
                                                <span style="font-size:14px;color:#64748b;">Description:</span>
                                                <span style="font-size:14px;color:#374151;margin-left:8px;">{{ $interviewDescription }}</span>
                                            </td>
                                        </tr>
                                        @endif
                                        <tr>
                                            <td style="padding:6px 0;">
                                                <span style="font-size:14px;color:#64748b;">Questions:</span>
                                                <span style="font-size:15px;font-weight:600;color:#111827;margin-left:8px;">{{ $questionCount }} question{{ $questionCount !== 1 ? 's' : '' }}</span>
                                            </td>
                                        </tr>
                                        @if($timeLimit)
                                        <tr>
                                            <td style="padding:6px 0;">
                                                <span style="font-size:14px;color:#64748b;">Time Limit:</span>
                                                <span style="font-size:15px;font-weight:600;color:#111827;margin-left:8px;">{{ $timeLimit }} minutes</span>
                                            </td>
                                        </tr>
                                        @endif
                                        <tr>
                                            <td style="padding:6px 0;">
                                                <span style="font-size:14px;color:#64748b;">Passing Score:</span>
                                                <span style="font-size:15px;font-weight:600;color:#42b6c5;margin-left:8px;">{{ $passingScore }}%</span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- CTA Button --}}
                <tr>
                    <td style="padding:28px 32px;text-align:center;">
                        <a href="{{ $interviewUrl }}" style="display:inline-block;padding:14px 36px;background:#42b6c5;color:#ffffff;text-decoration:none;font-weight:700;font-size:16px;border-radius:10px;letter-spacing:0.3px;">
                            Start Interview →
                        </a>
                    </td>
                </tr>

                {{-- Tips --}}
                <tr>
                    <td style="padding:0 32px 24px 32px;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eff6ff;border-radius:10px;border:1px solid #bfdbfe;">
                            <tr>
                                <td style="padding:16px 20px;">
                                    <p style="margin:0 0 8px 0;font-size:14px;font-weight:600;color:#1e40af;">💡 Tips for Success</p>
                                    <ul style="margin:0;padding:0 0 0 18px;font-size:13px;line-height:1.8;color:#1e3a5f;">
                                        <li>Find a quiet environment with a stable internet connection</li>
                                        <li>Read each question carefully before answering</li>
                                        @if($timeLimit)
                                        <li>Keep an eye on the timer — you have {{ $timeLimit }} minutes</li>
                                        @endif
                                        <li>Once submitted, your answers cannot be changed</li>
                                    </ul>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td style="padding:20px 32px 28px 32px;border-top:1px solid #e5e7eb;">
                        <p style="margin:0 0 6px 0;font-size:14px;line-height:1.6;color:#374151;">
                            We look forward to learning more about you through this interview!
                        </p>
                        <p style="margin:0;font-size:14px;line-height:1.6;color:#6b7280;">
                            Best regards,<br>
                            <strong style="color:#111827;">{{ $siteName }}</strong>
                        </p>
                    </td>
                </tr>

            </table>

            {{-- Sub-footer --}}
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;">
                <tr>
                    <td style="padding:16px 32px;text-align:center;">
                        <p style="margin:0;font-size:12px;color:#9ca3af;">
                            This email was sent because you applied for {{ $programTitle }} at {{ $siteName }}.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
