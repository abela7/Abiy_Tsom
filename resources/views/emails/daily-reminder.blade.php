@php
    $locale = $member->locale ?? $member->whatsapp_language ?? 'am';
    $isAm = $locale === 'am';
    $dayTitle = $isAm ? ($dailyContent->day_title_am ?: $dailyContent->day_title_en) : ($dailyContent->day_title_en ?: $dailyContent->day_title_am);
    $bibleRef = $isAm ? ($dailyContent->bible_reference_am ?: $dailyContent->bible_reference_en) : ($dailyContent->bible_reference_en ?: $dailyContent->bible_reference_am);
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('app.email_reminder_subject', ['day' => $dailyContent->day_number], $locale) }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f1eb;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 20px;">
        <tr>
            <td align="center">
                <table width="480" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">
                    {{-- Header --}}
                    <tr>
                        <td style="background:#0a6286;padding:28px 32px;text-align:center;">
                            <h1 style="margin:0;color:#ffffff;font-size:20px;font-weight:700;">{{ config('app.name', 'Abiy Tsom') }}</h1>
                            <p style="margin:8px 0 0;color:rgba(255,255,255,0.8);font-size:13px;">
                                {{ __('app.email_reminder_day_label', ['day' => $dailyContent->day_number], $locale) }}
                            </p>
                        </td>
                    </tr>
                    {{-- Body --}}
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px;color:#333;font-size:15px;">
                                {{ __('app.email_reminder_greeting', ['name' => $member->baptism_name], $locale) }}
                            </p>

                            <p style="margin:0 0 24px;color:#555;font-size:15px;">
                                {{ __('app.email_reminder_body', [], $locale) }}
                            </p>

                            @if($dayTitle)
                            <div style="background:#f4f1eb;border-radius:8px;padding:16px 20px;margin:0 0 16px;">
                                <p style="margin:0;color:#0a6286;font-size:16px;font-weight:700;">{{ $dayTitle }}</p>
                                @if($bibleRef)
                                <p style="margin:6px 0 0;color:#888;font-size:13px;">{{ $bibleRef }}</p>
                                @endif
                            </div>
                            @endif

                            {{-- CTA Button --}}
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $dayUrl }}" target="_blank"
                                           style="display:inline-block;padding:14px 40px;background:#0a6286;color:#ffffff;border-radius:8px;font-weight:700;font-size:15px;text-decoration:none;">
                                            {{ __('app.email_reminder_cta', [], $locale) }}
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0;color:#aaa;font-size:12px;text-align:center;">
                                {{ __('app.email_reminder_footer', [], $locale) }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
