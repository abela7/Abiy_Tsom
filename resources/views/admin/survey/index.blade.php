@extends('layouts.admin')

@section('title', 'Post-Fasika Survey Results')

@section('content')

@php
    $usefulnessLabels = [
        'very_useful'     => 'Very useful',
        'useful'          => 'Useful',
        'not_very_useful' => 'Not very useful',
        'not_useful'      => 'Not useful at all',
        'not_seen'        => "Didn't use it",
    ];
    $continuityLabels = [
        'all_seasons'    => 'All seasons (Filseta etc.)',
        'abiy_tsom_only' => 'Abiy Tsom only',
    ];
@endphp

<div class="max-w-5xl">

    {{-- Page header --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-7">
        <div>
            <h1 class="text-2xl font-bold text-primary">Post-Fasika Survey Results</h1>
            <p class="text-sm text-muted-text mt-0.5">Abiy Tsom {{ now()->year }}</p>
        </div>
        @if($submitted > 0)
            <a href="{{ route('admin.survey.export') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-accent text-white text-sm font-semibold hover:bg-accent/90 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Export CSV
            </a>
        @endif
    </div>

    {{-- Summary cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-8">
        <div class="bg-card rounded-2xl border border-border shadow-sm p-4 text-center">
            <p class="text-2xl font-bold text-primary">{{ $submitted }}</p>
            <p class="text-xs text-muted-text mt-1">Submitted</p>
        </div>
        <div class="bg-card rounded-2xl border border-border shadow-sm p-4 text-center">
            <p class="text-2xl font-bold text-primary">{{ $rate }}%</p>
            <p class="text-xs text-muted-text mt-1">Response Rate</p>
        </div>
        <div class="bg-card rounded-2xl border border-border shadow-sm p-4 text-center">
            <p class="text-2xl font-bold text-primary">{{ $avgRating ? number_format((float)$avgRating, 1) : '—' }}</p>
            <p class="text-xs text-muted-text mt-1">Avg Rating</p>
        </div>
        <div class="bg-card rounded-2xl border border-border shadow-sm p-4 text-center">
            <p class="text-2xl font-bold text-accent">{{ $wantAllSeasons }}</p>
            <p class="text-xs text-muted-text mt-1">Want All Seasons</p>
        </div>
    </div>

    @if($submitted > 0)
    <div class="grid sm:grid-cols-2 gap-5 mb-8">

        {{-- Q1 Usefulness breakdown --}}
        <div class="bg-card rounded-2xl border border-border shadow-sm p-5">
            <h3 class="text-sm font-bold text-primary mb-4">Q1 — App Usefulness</h3>
            <div class="space-y-2.5">
                @foreach ($usefulnessLabels as $key => $label)
                    @php $count = $usefulnessBreakdown[$key] ?? 0; $pct = $submitted > 0 ? round($count / $submitted * 100) : 0; @endphp
                    <div class="flex items-center gap-3 text-sm">
                        <span class="w-36 text-muted-text shrink-0 truncate text-xs">{{ $label }}</span>
                        <div class="flex-1 h-2 bg-muted rounded-full overflow-hidden">
                            <div class="h-full bg-accent rounded-full" style="width: {{ $pct }}%"></div>
                        </div>
                        <span class="w-12 text-right text-xs text-muted-text shrink-0">{{ $count }} ({{ $pct }}%)</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Q3 Continuity preference --}}
        <div class="bg-card rounded-2xl border border-border shadow-sm p-5">
            <h3 class="text-sm font-bold text-primary mb-1">Q3 — Future Season Preference</h3>
            <p class="text-xs text-muted-text mb-4">Only shown to members who found it useful</p>
            @if($continuityBreakdown->isEmpty())
                <p class="text-sm text-muted-text">No data yet.</p>
            @else
                @php $continuityTotal = $continuityBreakdown->sum(); @endphp
                <div class="space-y-2.5">
                    @foreach ($continuityLabels as $key => $label)
                        @php $count = $continuityBreakdown[$key] ?? 0; $pct = $continuityTotal > 0 ? round($count / $continuityTotal * 100) : 0; @endphp
                        <div class="flex items-center gap-3 text-sm">
                            <span class="w-36 text-muted-text shrink-0 truncate text-xs">{{ $label }}</span>
                            <div class="flex-1 h-2 bg-muted rounded-full overflow-hidden">
                                <div class="h-full bg-accent/70 rounded-full" style="width: {{ $pct }}%"></div>
                            </div>
                            <span class="w-12 text-right text-xs text-muted-text shrink-0">{{ $count }} ({{ $pct }}%)</span>
                        </div>
                    @endforeach
                </div>

                {{-- Retention highlight --}}
                @php $allSeasonsCount = $continuityBreakdown['all_seasons'] ?? 0; @endphp
                @if($allSeasonsCount > 0)
                    <div class="mt-4 px-3 py-2.5 rounded-xl bg-accent/8 border border-accent/20">
                        <p class="text-xs font-semibold text-accent">
                            {{ $allSeasonsCount }} member{{ $allSeasonsCount > 1 ? 's' : '' }}
                            ({{ $continuityTotal > 0 ? round($allSeasonsCount / $continuityTotal * 100) : 0 }}%)
                            want reminders for ALL future fasting seasons — strong Filseta signal 📅
                        </p>
                    </div>
                @endif
            @endif
        </div>

        {{-- Q4 Rating distribution --}}
        <div class="bg-card rounded-2xl border border-border shadow-sm p-5">
            <h3 class="text-sm font-bold text-primary mb-4">Q4 — Overall Rating</h3>
            @if($ratingDistribution->isEmpty())
                <p class="text-sm text-muted-text">No ratings yet.</p>
            @else
                <div class="space-y-2.5">
                    @foreach ([5,4,3,2,1] as $star)
                        @php $count = $ratingDistribution[$star] ?? 0; $ratingTotal = $ratingDistribution->sum(); $pct = $ratingTotal > 0 ? round($count / $ratingTotal * 100) : 0; @endphp
                        <div class="flex items-center gap-3 text-sm">
                            <span class="w-16 text-muted-text shrink-0 text-xs">{{ str_repeat('⭐', $star) }}</span>
                            <div class="flex-1 h-2 bg-muted rounded-full overflow-hidden">
                                <div class="h-full bg-accent rounded-full" style="width: {{ $pct }}%"></div>
                            </div>
                            <span class="w-8 text-right text-xs text-muted-text shrink-0">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Q2 Improvement snippets --}}
        <div class="bg-card rounded-2xl border border-border shadow-sm p-5">
            <h3 class="text-sm font-bold text-primary mb-4">Q2 — Improvement Feedback</h3>
            @php $feedbackTexts = $responses->filter(fn($fb) => filled($fb->q2_improvement_feedback)); @endphp
            @if($feedbackTexts->isEmpty())
                <p class="text-sm text-muted-text">No written feedback yet.</p>
            @else
                <div class="space-y-3 max-h-52 overflow-y-auto pr-1">
                    @foreach($feedbackTexts as $fb)
                        <div class="text-[13px] text-secondary leading-relaxed border-l-2 border-accent/30 pl-3 whitespace-pre-line">{{ $fb->q2_improvement_feedback }}</div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Status row --}}
    <div class="flex gap-3 text-sm text-muted-text mb-6">
        <span>{{ $total }} invited</span>
        <span>·</span>
        <span class="text-green-600 dark:text-green-400 font-medium">{{ $submitted }} submitted</span>
        <span>·</span>
        <span>{{ $draft }} in progress</span>
        <span>·</span>
        <span>{{ $pending }} not started</span>
    </div>

    {{-- Individual responses --}}
    @if($responses->isEmpty())
        <div class="bg-card rounded-2xl border border-border shadow-sm p-10 text-center">
            <p class="text-muted-text text-sm">No submissions yet.</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($responses as $fb)
                <div x-data="{ open: false }"
                     class="bg-card rounded-2xl border border-border shadow-sm overflow-hidden">

                    <button type="button" @click="open = !open"
                            class="w-full flex items-center gap-4 px-5 py-4 text-left hover:bg-muted/30 transition touch-manipulation">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-primary truncate">
                                {{ $fb->member?->baptism_name ?? 'Member #'.$fb->member_id }}
                            </p>
                            <p class="text-xs text-muted-text mt-0.5">{{ $fb->submitted_at?->diffForHumans() }}</p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0 text-xs">
                            @if($fb->q4_overall_rating)
                                <span>{{ str_repeat('⭐', $fb->q4_overall_rating) }}</span>
                            @endif
                            @if($fb->q1_usefulness)
                                <span class="px-2 py-0.5 rounded-full bg-muted text-muted-text">
                                    {{ $usefulnessLabels[$fb->q1_usefulness] ?? $fb->q1_usefulness }}
                                </span>
                            @endif
                        </div>
                        <svg class="w-4 h-4 text-muted-text transition-transform duration-200 shrink-0"
                             :class="open && 'rotate-180'"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div x-show="open" x-cloak>
                        <div class="border-t border-border/50 px-5 py-4 space-y-3 text-sm">
                            <div class="grid sm:grid-cols-2 gap-3">
                                <div>
                                    <p class="text-xs text-muted-text mb-0.5">Q1 — Usefulness</p>
                                    <p class="text-primary font-medium">{{ $usefulnessLabels[$fb->q1_usefulness] ?? '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-muted-text mb-0.5">Q3 — Future seasons</p>
                                    <p class="text-primary font-medium">{{ $continuityLabels[$fb->q3_continuity_preference] ?? '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-muted-text mb-0.5">Q4 — Overall rating</p>
                                    <p class="text-primary font-medium">{{ $fb->q4_overall_rating ? str_repeat('⭐', $fb->q4_overall_rating) : '—' }}</p>
                                </div>
                            </div>
                            @if($fb->q2_improvement_feedback)
                                <div>
                                    <p class="text-xs text-muted-text mb-1">Q2 — Improvement feedback</p>
                                    <p class="text-secondary leading-relaxed whitespace-pre-line bg-muted/40 rounded-xl px-4 py-3">{{ $fb->q2_improvement_feedback }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-5">{{ $responses->links() }}</div>
    @endif

</div>
@endsection
