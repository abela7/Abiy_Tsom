<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="theme-sepia">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You — Abiy Tsom</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-background min-h-screen flex items-center justify-center px-4">

<div class="w-full max-w-sm mx-auto text-center py-16">
    <div class="text-6xl mb-6">🙏</div>

    <h1 class="text-[24px] font-bold text-primary mb-3">Thank you!</h1>

    <p class="text-[15px] text-secondary leading-relaxed mb-6">
        @if ($member?->baptism_name)
            Dear {{ $member->baptism_name }},<br>
        @endif
        Your feedback has been received. It means a lot to us and will help shape a better experience for future seasons.
    </p>

    @if ($feedback->q6_opt_in_future_fasts)
        <div class="bg-card rounded-2xl border border-border px-5 py-4 text-left mb-6">
            <p class="text-[13px] font-semibold text-accent mb-1">✅ You're on the list!</p>
            <p class="text-[13px] text-secondary">We'll notify you when Filseta and other fasting seasons begin.</p>
        </div>
    @endif

    <p class="text-xs text-muted-text">አቢይ ጾምን ሞልቶ ጸልዮ ፈጽሞ ለተሳተፉ ሁሉ እናመሰግናለን።</p>
</div>

</body>
</html>
