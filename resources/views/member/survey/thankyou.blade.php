@extends('layouts.member')

@section('title', 'Thank You — ' . __('app.app_name'))

@section('content')
<div class="max-w-sm mx-auto text-center py-16 px-4">
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
@endsection
