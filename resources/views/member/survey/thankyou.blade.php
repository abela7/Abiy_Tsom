@extends('layouts.member')

@section('title', __('app.survey_thanks_title') . ' — ' . __('app.app_name'))

@section('content')
<div class="max-w-sm mx-auto text-center py-16 px-4">
    <div class="text-6xl mb-6">🙏</div>

    <h1 class="text-[24px] font-bold text-primary mb-3">{{ __('app.survey_thanks_heading') }}</h1>

    <p class="text-[15px] text-secondary leading-relaxed mb-6">
        @if ($member?->baptism_name)
            {{ __('app.fasika_greeting', ['name' => $member->baptism_name]) }}<br>
        @endif
        {{ __('app.survey_thanks_message') }}
    </p>

    @if ($feedback->q3_continuity_preference === 'all_seasons')
        <div class="bg-card rounded-2xl border border-border px-5 py-4 text-left mb-6">
            <p class="text-[13px] font-semibold text-accent mb-1">{{ __('app.survey_thanks_allseasons_title') }}</p>
            <p class="text-[13px] text-secondary">{{ __('app.survey_thanks_allseasons_body') }}</p>
        </div>
    @endif

    <a href="{{ route('fasika') }}"
       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-accent text-white text-sm font-semibold hover:bg-accent/90 transition mb-4">
        {{ __('app.survey_thanks_fasika_link') }}
    </a>

    <p class="text-xs text-muted-text mt-4">{{ __('app.survey_thanks_bless') }}</p>
</div>
@endsection
