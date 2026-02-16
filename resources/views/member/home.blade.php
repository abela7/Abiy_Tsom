@extends('layouts.member')

@section('title', __('app.nav_home') . ' - ' . __('app.app_name'))

@section('content')
<div class="px-4 pt-4 space-y-4">

    {{-- Today bar — date, day number, View Today button --}}
    <div class="flex flex-wrap items-center justify-between gap-3 py-3 px-4 rounded-2xl bg-card border border-border/60 shadow-sm">
        <div class="flex flex-wrap items-center gap-3 min-w-0">
            <span class="text-sm font-bold text-primary">{{ now()->locale('en')->translatedFormat('l, j F Y') }}</span>
            @if($season && $today)
                <span class="text-xs font-semibold text-muted-text">
                    · {{ __('app.day_of', ['day' => $today->day_number, 'total' => $season->total_days]) }}
                </span>
            @endif
        </div>
        @php $dayToken = isset($member) && $member?->token ? '?token=' . e($member->token) : ''; @endphp
        @if(isset($viewTodayTarget) && $viewTodayTarget)
            <a href="{{ route('member.day', $viewTodayTarget) }}{{ $dayToken }}"
               class="shrink-0 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-accent text-on-accent text-sm font-bold hover:bg-accent-hover transition shadow-sm">
                {{ $today ? __('app.view_today') : __('app.view_recommended_day') }}
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </a>
        @else
            <a href="{{ route('member.calendar') }}{{ $dayToken }}"
               class="shrink-0 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-muted text-muted-text text-sm font-semibold hover:bg-border transition">
                {{ __('app.view_today') }}
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </a>
        @endif
    </div>

    {{-- Easter countdown — visible to all members, mobile-first --}}
    <div class="relative overflow-hidden rounded-3xl shadow-2xl border border-white/10 bg-gradient-to-br from-[#0a6286] via-[#134e5e] to-[#0a6286]"
         x-data="easterCountdown('{{ $easterAt->format('c') }}', '{{ $lentStartAt->format('c') }}')">

        {{-- Decorative ambient glows (fixed gold — same in light/dark) --}}
        <div class="absolute -top-24 -right-24 w-64 h-64 rounded-full bg-easter-gold/20 blur-[80px] pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-24 w-64 h-64 rounded-full bg-white/5 blur-[80px] pointer-events-none"></div>

        <div class="relative p-4 sm:p-8 text-white" x-show="totalSeconds > 0">

            {{-- Top row: title + circular % ring --}}
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-base sm:text-2xl font-black tracking-tight leading-tight">{{ __('app.easter_countdown') }}</h2>

                {{-- Circular progress ring --}}
                <div class="relative w-14 h-14 sm:w-20 sm:h-20 shrink-0 group">
                    <svg class="w-full h-full -rotate-90" viewBox="0 0 64 64">
                        <circle cx="32" cy="32" r="28" fill="none"
                                stroke="currentColor" stroke-width="5" class="text-white/10"/>
                        <circle cx="32" cy="32" r="28" fill="none"
                                stroke="currentColor" stroke-width="5"
                                stroke-linecap="round"
                                class="text-easter-gold transition-all duration-1000 ease-out"
                                :stroke-dasharray="2 * Math.PI * 28"
                                :stroke-dashoffset="2 * Math.PI * 28 * (1 - (100 - progressPct) / 100)"/>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-xs sm:text-base font-black text-easter-gold" x-text="animatedPct + '%'"></span>
                    </div>
                </div>
            </div>

            {{-- Countdown digits --}}
            <div class="grid grid-cols-4 gap-2 sm:gap-4">
                {{-- Days --}}
                <div class="relative flex flex-col items-center py-3 sm:py-6 rounded-2xl bg-white/5 backdrop-blur-md border border-white/10 shadow-inner group transition-all duration-300 hover:bg-white/10">
                    <span class="text-xl sm:text-5xl font-black tabular-nums leading-none tracking-tighter text-white drop-shadow-sm" x-text="pad(days)">—</span>
                    <span class="text-[8px] sm:text-[11px] text-white/60 mt-1.5 font-bold uppercase tracking-widest">{{ __('app.days') }}</span>
                </div>
                {{-- Hours --}}
                <div class="relative flex flex-col items-center py-3 sm:py-6 rounded-2xl bg-white/5 backdrop-blur-md border border-white/10 shadow-inner group transition-all duration-300 hover:bg-white/10">
                    <span class="text-xl sm:text-5xl font-black tabular-nums leading-none tracking-tighter text-white drop-shadow-sm" x-text="pad(hours)">—</span>
                    <span class="text-[8px] sm:text-[11px] text-white/60 mt-1.5 font-bold uppercase tracking-widest">{{ __('app.hours') }}</span>
                </div>
                {{-- Minutes --}}
                <div class="relative flex flex-col items-center py-3 sm:py-6 rounded-2xl bg-white/5 backdrop-blur-md border border-white/10 shadow-inner group transition-all duration-300 hover:bg-white/10">
                    <span class="text-xl sm:text-5xl font-black tabular-nums leading-none tracking-tighter text-white drop-shadow-sm" x-text="pad(minutes)">—</span>
                    <span class="text-[8px] sm:text-[11px] text-white/60 mt-1.5 font-bold uppercase tracking-widest">{{ __('app.minutes') }}</span>
                </div>
                {{-- Seconds --}}
                <div class="relative flex flex-col items-center py-3 sm:py-6 rounded-2xl bg-white/5 backdrop-blur-md border border-white/10 shadow-inner group transition-all duration-300 hover:bg-white/10">
                    <span class="text-xl sm:text-5xl font-black tabular-nums leading-none tracking-tighter text-white drop-shadow-sm easter-pulse" x-text="pad(seconds)">—</span>
                    <span class="text-[8px] sm:text-[11px] text-white/60 mt-1.5 font-bold uppercase tracking-widest">{{ __('app.seconds') }}</span>
                </div>
            </div>

            {{-- Bottom progress bar (100 → 0) --}}
            <div class="mt-6">
                <div class="h-2 sm:h-3 w-full bg-white/10 rounded-full overflow-hidden p-0.5 border border-white/5">
                    <div class="h-full rounded-full bg-easter-gold transition-all duration-1000 ease-out shadow-[0_0_10px_rgba(212,175,55,0.4)]"
                         :style="'width: ' + progressPct + '%'"></div>
                </div>
            </div>
        </div>

        {{-- Easter reached --}}
        <div x-show="totalSeconds <= 0" class="relative p-8 sm:p-12 text-center text-white space-y-4">
            <div class="mx-auto w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-easter-gold/20 flex items-center justify-center border border-easter-gold/30 animate-bounce">
                <svg class="w-8 h-8 sm:w-10 sm:h-10 text-easter-gold" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2L14.09 8.26L21 9.27L16 13.14L17.18 20.02L12 16.77L6.82 20.02L8 13.14L3 9.27L9.91 8.26L12 2Z"/>
                </svg>
            </div>
            <h2 class="text-2xl sm:text-5xl font-black tracking-tighter leading-none">{{ __('app.christ_is_risen') }}</h2>
            <p class="text-white/70 text-base sm:text-lg font-medium">{{ __('app.easter_countdown_subtitle') }}</p>
        </div>
    </div>

    {{-- Announcements — blog-style previews, Read more opens full post --}}
    @if($announcements->isNotEmpty())
    @php $navToken = isset($currentMember) ? '?token=' . e($currentMember->token) : ''; @endphp
    <section class="space-y-5">
        <h2 class="text-xs font-bold text-muted-text uppercase tracking-wider">{{ __('app.announcements_section') }}</h2>
        @foreach($announcements as $announcement)
        <article class="bg-card rounded-2xl shadow-sm border border-border overflow-hidden">
            <a href="{{ route('member.announcement.show', $announcement) }}{{ $navToken }}" class="block group">
                @if($announcement->photo)
                    <div class="relative w-full aspect-[16/9] sm:aspect-[21/9] overflow-hidden bg-muted">
                        <img src="{{ $announcement->photo_url }}" alt=""
                             class="absolute inset-0 w-full h-full object-cover object-center group-hover:scale-[1.02] transition-transform duration-300">
                        @if($announcement->hasYoutubeVideo())
                            <div class="absolute bottom-2 right-2 flex items-center gap-1 px-2 py-1 rounded-lg bg-black/60 text-white text-xs font-medium">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                {{ __('app.watch') }}
                            </div>
                        @endif
                    </div>
                @elseif($announcement->hasYoutubeVideo())
                    <div class="relative w-full aspect-video overflow-hidden bg-muted rounded-t-2xl">
                        <img src="https://img.youtube.com/vi/{{ $announcement->youtubeVideoId() }}/mqdefault.jpg" alt=""
                             class="absolute inset-0 w-full h-full object-cover object-center group-hover:scale-[1.02] transition-transform duration-300">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="w-14 h-14 rounded-full bg-black/60 flex items-center justify-center text-white">
                                <svg class="w-7 h-7 ml-1" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                            </div>
                        </div>
                    </div>
                @endif
                <div class="p-4 sm:p-5">
                    <h3 class="text-lg sm:text-xl font-bold text-primary group-hover:text-accent transition">
                        {{ $announcement->title }}
                    </h3>
                    @if($announcement->description)
                        <p class="mt-2 text-sm text-secondary leading-relaxed line-clamp-2 sm:line-clamp-3">
                            {{ $announcement->description }}
                        </p>
                        <span class="mt-3 text-accent font-semibold text-sm inline-flex items-center gap-1 group-hover:gap-2 transition-all">
                            {{ __('app.read_more') }}
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </span>
                    @endif
                </div>
            </a>
        </article>
        @endforeach
    </section>
    @endif

    @if($today)
        {{-- Weekly theme banner --}}
        @if($weekTheme)
        <div class="bg-accent rounded-2xl p-4 text-on-accent shadow-lg">
            <div class="flex items-center gap-2 mb-1">
                <span class="text-accent-secondary font-semibold text-sm">{{ __('app.week', ['number' => $weekTheme->week_number]) }}</span>
                <span class="text-on-accent/60">|</span>
                <span class="text-sm text-on-accent/80">{{ localized($weekTheme, 'name') ?? $weekTheme->name_en ?? $weekTheme->name_geez ?? '-' }}</span>
            </div>
            <h3 class="font-bold text-lg">{{ $weekTheme->meaning }}</h3>
            @if($weekTheme->gospel_reference || $weekTheme->epistles_reference || $weekTheme->liturgy)
                <div class="text-sm text-on-accent/70 mt-1 space-y-0.5">
                    @if($weekTheme->gospel_reference)
                        <p>{{ __('app.gospel_reference') }}: {{ $weekTheme->gospel_reference }}</p>
                    @endif
                    @if($weekTheme->epistles_reference)
                        <p>{{ __('app.epistles_reference') }}: {{ $weekTheme->epistles_reference }}</p>
                    @endif
                    @if($weekTheme->liturgy)
                        <p class="italic">{{ $weekTheme->liturgy }}</p>
                    @endif
                </div>
            @endif
        </div>
        @endif

        {{-- Day content (Bible, Mezmur, checklist, etc.) is shown on member.day view only --}}

    @else
        {{-- No daily content — announcements (if any) are shown at top --}}
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', function() {
    Alpine.data('easterCountdown', function(easterIso, lentStartIso) {
        var target = new Date(easterIso);
        var lentStart = new Date(lentStartIso);
        var totalWindowSeconds = Math.max(1, (target - lentStart) / 1000);
        return {
            days: 0,
            hours: 0,
            minutes: 0,
            seconds: 0,
            totalSeconds: 1,
            progressPct: 100,
            animatedPct: 0,
            pad: function(n) {
                return String(n).padStart(2, '0');
            },
            animationDone: false,
            animatePercentage: function() {
                var self = this;
                var completedPct = 100 - this.progressPct;
                var duration = 1500;
                var startTime = null;

                function step(timestamp) {
                    if (!startTime) startTime = timestamp;
                    var progress = Math.min((timestamp - startTime) / duration, 1);
                    self.animatedPct = Math.floor(progress * completedPct);
                    if (progress < 1) {
                        window.requestAnimationFrame(step);
                    } else {
                        self.animationDone = true;
                    }
                }
                window.requestAnimationFrame(step);
            },
            tick: function() {
                var now = new Date();
                var diff = Math.max(0, Math.floor((target - now) / 1000));
                this.totalSeconds = diff;
                this.days = Math.floor(diff / 86400);
                this.hours = Math.floor((diff % 86400) / 3600);
                this.minutes = Math.floor((diff % 3600) / 60);
                this.seconds = diff % 60;
                var secondsRemaining = diff;
                this.progressPct = Math.min(100, Math.max(0, (secondsRemaining / totalWindowSeconds) * 100));

                if (this.animationDone) {
                    this.animatedPct = Math.round(100 - this.progressPct);
                }
            },
            init: function() {
                var self = this;
                self.tick();
                self.animatePercentage();
                setInterval(function() { self.tick(); }, 1000);
            }
        };
    });
});

</script>
@endpush
