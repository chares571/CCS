<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS Password Reset Code</title>
</head>
<body style="margin:0; padding:0; background:#f2f7ff; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; line-height: 1.5; color:#0f172a;">
    @php
        $logoSrc = null;
        $logoRelativePath = 'images/branding/CCS_logo.png';
        $logoPath = public_path($logoRelativePath);

        if (isset($message) && file_exists($logoPath) && method_exists($message, 'embed')) {
            try {
                $logoSrc = $message->embed($logoPath);
            } catch (\Throwable $e) {
                $logoSrc = null;
            }
        }

        if (!$logoSrc && file_exists($logoPath)) {
            $logoSrc = asset($logoRelativePath);
        }
    @endphp
    <span style="display:none !important; visibility:hidden; opacity:0; color:transparent; height:0; width:0; overflow:hidden;">
        {{ $accountLabel ? ($accountLabel.' - ') : '' }}Your Cabugbugan Community School password reset code: {{ $code }} (expires in {{ $expiresInMinutes }} minutes).
    </span>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f2f7ff; padding: 28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="max-width:600px; width:100%;">
                    <tr>
                        <td style="padding: 0 6px 12px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background: linear-gradient(180deg,#2d7ec2,#0f4c81); border-radius: 18px; overflow:hidden;">
                                <tr>
                                    <td style="padding: 18px 18px 14px; text-align:center;">
                                        @if($logoSrc)
                                            <img src="{{ $logoSrc }}" alt="Cabugbugan Community School logo" width="54" height="54" style="display:inline-block; border-radius: 50%; background:#ffffff; padding:8px;">
                                        @else
                                            <div style="display:inline-block; width:54px; height:54px; border-radius:50%; background:#ffffff; padding:8px;"></div>
                                        @endif
                                        <div style="height: 10px;"></div>
                                        <div style="font-size: 18px; font-weight: 800; color:#ffffff; letter-spacing: .2px;">
                                            Cabugbugan Community School
                                        </div>
                                        <div style="font-size: 13px; color: rgba(255,255,255,.85);">
                                            Information and Online Enrollment System
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 0 6px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#ffffff; border-radius: 18px; box-shadow: 0 14px 34px rgba(15,76,129,.18);">
                                <tr>
                                    <td style="padding: 22px 22px 10px;">
                                        <div style="font-size: 12px; font-weight: 800; color:#0f4c81; text-transform: uppercase; letter-spacing: .14em;">
                                            Account Recovery
                                        </div>
                                        @if(!empty($accountLabel))
                                            <div style="font-size: 13px; color:#334155; margin-top: 8px;">
                                                {{ $accountLabel }}
                                            </div>
                                        @endif
                                        <div style="font-size: 22px; font-weight: 900; margin-top: 6px; color:#0b1324;">
                                            Password reset verification code
                                        </div>
                                        <div style="font-size: 14px; color:#334155; margin-top: 10px;">
                                            Enter this 6-digit code on the verification page to continue. It expires in <strong>{{ $expiresInMinutes }} minutes</strong>.
                                        </div>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding: 12px 22px 14px;">
                                        <div style="background:#f3f7ff; border: 1px solid #d9e6f5; border-radius: 16px; padding: 18px; text-align:center;">
                                            <div style="font-size: 12px; color:#0f4c81; font-weight: 800; letter-spacing: .14em; text-transform: uppercase;">
                                                Your Code
                                            </div>
                                            <div style="margin-top: 10px; font-size: 32px; font-weight: 900; letter-spacing: .34em; color:#0b1324;">
                                                {{ $code }}
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding: 0 22px 22px;">
                                        <div style="font-size: 13px; color:#475569;">
                                            If you didn’t request a password reset, you can safely ignore this email. For your security, never share this code with anyone.
                                        </div>
                                        <div style="margin-top: 14px; font-size: 12px; color:#64748b;">
                                            Need help? Contact your school's ICT coordinator.
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 14px 10px 0; text-align:center; font-size: 12px; color:#64748b;">
                            &copy; {{ now()->year }} Cabugbugan Community School • Tagudin District, Ilocos Sur
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
