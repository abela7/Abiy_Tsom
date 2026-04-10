@extends('layouts.admin')

@section('title', 'Post-Fasika Survey Results')

@section('content')
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
            <p class="text-2xl font-bold text-primary">{{ $avgRating ? number_format((float) $avgRating, 1) : '—' }}</p>
            <p class="text-xs text-muted-text mt-1">Avg Rating</p>
        </div>
        <div class="bg-card rounded-2xl border border-border shadow-sm p-4 text-center">
            <p class="text-2xl font-bold text-accent">{{ $optInCount }}</p>
            <p class="text-xs text-muted-text mt-1">Opt-in Filseta</p>
        </div>
    </div>

    {{-- Charts row --}}
    @if($submitted > 0)
    <div class="grid sm:grid-cols-2 gap-5 mb-8">

        {{-- Rating distribution --}}
        <div class="bg-card rounded-2xl border border-border shadow-sm p-5">
            <h3 class="text-sm font-bold text-primary mb-4">Overall Rating Distribution</h3>
            <div class="space-y-2.5">
                @foreach ([5,4,3,2,1] as $star)
                    @php $count = $ratingDistribution[$star] ?? 0; $pct = $submitted > 0 ? round($count / $submitted * 100) : 0; @endphp
                    <div class="flex items-center gap-3 text-sm">
                        <span class="w-16 text-muted-text shrink-0">{{ str_repeat('⭐', $star) }}</span>
                        <div class="flex-1 h-2.5 bg-muted rounded-full overflow-hidden">
                            <div class="h-full bg-accent rounded-full" style="width: {{ $pct }}%"></div>
                        </div>
                        <span class="w-8 text-right text-xs text-muted-text shrink-0">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Feature breakdown --}}
        <div class="bg-card rounded-2xl border border-border shadow-sm p-5">
            <h3 class="text-sm font-bold text-primary mb-4">Most Used Feature</h3>
            @php
                $featureLabels = [
                    'daily_content' => 'Daily Readings',
                    'himamat'       => 'Himamat',
                    'reminders'     => 'WhatsApp Reminders',
                    'events'        => 'Events',
                    'all_equal'     => 'All equally',
                ];
            @endphp
            @if($featureBreakdown->isEmpty())
                <p class="text-sm text-muted-text">No data yet.</p>
            @else
                <div class="space-y-2.5">
                    @foreach($featureBreakdown as $key => $count)
                        @php $pct = $submitted > 0 ? round($count / $submitted * 100) : 0; @endphp
                        <div class="flex items-center gap-3 text-sm">
                            <span class="w-36 text-muted-text shrink-0 truncate">{{ $featureLabels[$key] ?? $key }}</span>
                            <div class="flex-1 h-2.5 bg-muted rounded-full overflow-hidden">
                                <div class="h-full bg-accent/70 rounded-full" style="width: {{ $pct }}%"></div>
                            </div>
                            <span class="w-8 text-right text-xs text-muted-text shrink-0">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Status breakdown --}}
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

                    {{-- Row header --}}
                    <button type="button" @click="open = !open"
                            class="w-full flex items-center gap-4 px-5 py-4 text-left hover:bg-muted/30 transition touch-manipulation">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-primary truncate">
                                {{ $fb->member?->baptism_name ?? 'Member #'.$fb->member_id }}
                            </p>
                            <p class="text-xs text-muted-text mt-0.5">
                                {{ $fb->submitted_at?->diffForHumans() }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <span class="text-sm">{{ str_repeat('⭐', (int) $fb->q1_overall_rating) }}</span>
                            @if($fb->q6_opt_in_future_fasts)
                                <span class="px-2 py-0.5 rounded-full bg-accent/10 text-accent text-xs font-medium">Opt-in ✓</span>
                            @endif
                        </div>
                        <svg class="w-4 h-4 text-muted-text transition-transform duration-200 shrink-0"
                             :class="open && 'rotate-180'"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    {{-- Expanded detail --}}
                    <div x-show="open" x-cloak x-collapse>
                        <div class="border-t border-border/50 px-5 py-4 space-y-3 text-sm">
                            <div class="grid sm:grid-cols-2 gap-3">
                                <div>
                                    <p class="text-xs text-muted-text mb-0.5">Most used feature</p>
                                    <p class="text-primary font-medium">{{ $featureLabels[$fb->q2_most_used_feature] ?? $fb->q2_most_used_feature ?? '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-muted-text mb-0.5">Himamat rating</p>
                                    <p class="text-primary font-medium">{{ str_repeat('⭐', (int) $fb->q3_himamat_rating) ?: '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-muted-text mb-0.5">WhatsApp reminders useful</p>
                                    <p class="text-primary font-medium">
                                        @if($fb->q4_whatsapp_reminder_useful === null) —
                                        @elseif($fb->q4_whatsapp_reminder_useful) 👍 Yes
                                        @else 👎 No
                                        @endif
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-muted-text mb-0.5">Opt-in future seasons</p>
                                    <p class="text-primary font-medium">
                                        @if($fb->q6_opt_in_future_fasts === null) —
                                        @elseif($fb->q6_opt_in_future_fasts) ✅ Yes
                                        @else No
                                        @endif
                                    </p>
                                </div>
                            </div>
                            @if($fb->q5_suggestion)
                                <div>
                                    <p class="text-xs text-muted-text mb-1">Suggestion</p>
                                    <p class="text-secondary leading-relaxed whitespace-pre-line bg-muted/40 rounded-xl px-4 py-3">{{ $fb->q5_suggestion }}</p>
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
