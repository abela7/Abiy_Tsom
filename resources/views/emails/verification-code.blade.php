<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('app.verification_email_subject', ['code' => $code]) }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f1eb;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 20px;">
        <tr>
            <td align="center">
                <table width="420" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">
                    {{-- Header --}}
                    <tr>
                        <td style="background:#0a6286;padding:28px 32px;text-align:center;">
                            <h1 style="margin:0;color:#ffffff;font-size:20px;font-weight:700;">{{ config('app.name', 'Abiy Tsom') }}</h1>
                        </td>
                    </tr>
                    {{-- Body --}}
                    <tr>
                        <td style="padding:32px;">
                            @if($baptismName)
                            <p style="margin:0 0 16px;color:#333;font-size:15px;">{{ __('app.verification_email_greeting', ['name' => $baptismName]) }}</p>
                            @endif

                            <p style="margin:0 0 24px;color:#333;font-size:15px;">{{ __('app.verification_email_body') }}</p>

                            <div style="background:#f4f1eb;border-radius:8px;padding:20px;text-align:center;margin:0 0 24px;">
                                <span style="font-size:32px;font-weight:700;letter-spacing:8px;color:#0a6286;">{{ $code }}</span>
                            </div>

                            <p style="margin:0;color:#888;font-size:13px;">{{ __('app.verification_email_expires') }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
