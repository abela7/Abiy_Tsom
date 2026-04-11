@extends('layouts.member')

@section('title', __('app.fasika_page_title') . ' — ' . __('app.app_name'))

@section('content')
<div x-data="fasikaPage()" x-init="start()"
     class="fasika-page relative min-h-screen overflow-hidden">

    {{-- ═══════════════════════════════════════════════════════════════════
         BACKGROUND — dark-to-gold gradient + radial light burst
         ═══════════════════════════════════════════════════════════════════ --}}
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute inset-0 bg-gradient-to-b from-[#1a0e2e] via-[#2d1854] to-[#0f0a1a]"></div>
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_50%_30%,rgba(212,165,87,0.25)_0%,transparent_65%)]"></div>
        <div class="absolute top-[15%] left-1/2 -translate-x-1/2 w-[600px] h-[600px] rounded-full
                    bg-[radial-gradient(circle,rgba(245,208,96,0.18)_0%,rgba(212,165,87,0.08)_40%,transparent_70%)]
                    animate-[glow-pulse_4s_ease-in-out_infinite]"></div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════
         FLOATING GOLDEN PARTICLES (Canvas)
         ═══════════════════════════════════════════════════════════════════ --}}
    <canvas id="fasika-particles" class="absolute inset-0 pointer-events-none z-[1]"></canvas>

    {{-- ═══════════════════════════════════════════════════════════════════
         MAIN CONTENT
         ═══════════════════════════════════════════════════════════════════ --}}
    <div class="relative z-10 max-w-lg mx-auto px-5 py-10 text-center space-y-8">

        {{-- Animated Ethiopian Cross --}}
        <div class="relative mx-auto w-32 h-32 mt-4">
            {{-- Outer glow ring --}}
            <div class="absolute inset-[-20px] rounded-full animate-[cross-glow_3s_ease-in-out_infinite]
                        bg-[radial-gradient(circle,rgba(245,208,96,0.3)_0%,transparent_70%)]"></div>
            {{-- Light rays --}}
            <div class="absolute inset-[-40px] animate-[rays-spin_30s_linear_infinite] opacity-40">
                @for ($r = 0; $r < 12; $r++)
                    <div class="absolute top-1/2 left-1/2 w-[2px] h-[90px] origin-bottom -translate-x-1/2 -translate-y-full
                                bg-gradient-to-t from-[#F5D060] to-transparent"
                         style="transform: translate(-50%, -100%) rotate({{ $r * 30 }}deg)"></div>
                @endfor
            </div>
            {{-- The Cross SVG --}}
            <svg class="relative w-full h-full drop-shadow-[0_0_30px_rgba(245,208,96,0.6)]"
                 viewBox="0 0 100 100" fill="none">
                {{-- Ethiopian Meskel cross shape --}}
                <defs>
                    <linearGradient id="cross-gold" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#F5E6A3"/>
                        <stop offset="50%" stop-color="#D4A537"/>
                        <stop offset="100%" stop-color="#B8860B"/>
                    </linearGradient>
                </defs>
                {{-- Base --}}
                <rect x="42" y="5" width="16" height="90" rx="3" fill="url(#cross-gold)"/>
                <rect x="15" y="28" width="70" height="16" rx="3" fill="url(#cross-gold)"/>
                {{-- Decorative ends (Ethiopian style) --}}
                <rect x="38" y="2" width="24" height="8" rx="4" fill="url(#cross-gold)"/>
                <rect x="38" y="90" width="24" height="8" rx="4" fill="url(#cross-gold)"/>
                <rect x="10" y="24" width="8" height="24" rx="4" fill="url(#cross-gold)"/>
                <rect x="82" y="24" width="8" height="24" rx="4" fill="url(#cross-gold)"/>
                {{-- Center diamond --}}
                <rect x="44" y="30" width="12" height="12" rx="2" transform="rotate(45 50 36)" fill="#FFF8DC" opacity="0.8"/>
            </svg>
        </div>

        {{-- Eyebrow --}}
        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-[#F5D060]/80 animate-[fade-up_1s_ease-out_0.3s_both]">
            {{ __('app.fasika_eyebrow') }}
        </p>

        {{-- Hero — "Christ is Risen!" --}}
        <div class="space-y-3 animate-[fade-up_1s_ease-out_0.5s_both]">
            <h1 class="text-[32px] sm:text-[38px] font-black leading-tight fasika-gold-text">
                ክርስቶስ ተንሥአ ከሙታን!
            </h1>
            <p class="text-[22px] sm:text-[26px] font-bold text-white/90 tracking-wide">
                Christ is Risen!
            </p>
        </div>

        {{-- Response --}}
        <div class="animate-[fade-up_1s_ease-out_0.8s_both]">
            <div class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full
                        bg-white/10 backdrop-blur-sm border border-[#F5D060]/20">
                <span class="text-[15px] font-semibold text-[#F5D060]">{{ __('app.fasika_response_am') }}</span>
                <span class="text-white/40">•</span>
                <span class="text-[15px] font-semibold text-white/80">{{ __('app.fasika_response_en') }}</span>
            </div>
        </div>

        {{-- Greeting & Message --}}
        <div class="animate-[fade-up_1s_ease-out_1s_both] space-y-4 pt-2">
            @if ($member?->baptism_name)
                <p class="text-[17px] text-[#F5D060] font-semibold">
                    {{ __('app.fasika_greeting', ['name' => $member->baptism_name]) }}
                </p>
            @endif

            <p class="text-[15px] text-white/75 leading-relaxed max-w-sm mx-auto">
                {{ __('app.fasika_message') }}
            </p>
        </div>

        {{-- Journey summary card --}}
        @if ($member && $daysCompleted > 0)
        <div class="animate-[fade-up_1s_ease-out_1.2s_both]">
            <div class="rounded-2xl bg-white/8 backdrop-blur-sm border border-[#F5D060]/15 px-6 py-5 space-y-4">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#F5D060]/60">
                    {{ __('app.fasika_journey_label') }}
                </p>

                {{-- Progress ring --}}
                <div class="relative mx-auto w-28 h-28">
                    <svg class="w-full h-full -rotate-90" viewBox="0 0 120 120">
                        <circle cx="60" cy="60" r="52" stroke="rgba(245,208,96,0.1)" stroke-width="8" fill="none"/>
                        <circle cx="60" cy="60" r="52" stroke="url(#ring-gold)" stroke-width="8" fill="none"
                                stroke-linecap="round"
                                :stroke-dasharray="`${ringProgress} ${ringTotal}`"/>
                        <defs>
                            <linearGradient id="ring-gold" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0%" stop-color="#F5D060"/>
                                <stop offset="100%" stop-color="#D4A537"/>
                            </linearGradient>
                        </defs>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-2xl font-black text-[#F5D060]" x-text="animatedDays">0</span>
                        <span class="text-[10px] text-white/50 uppercase tracking-wider">{{ __('app.fasika_days_unit') }}</span>
                    </div>
                </div>

                <p class="text-sm text-white/60">
                    {{ __('app.fasika_journey_complete', ['total' => $totalDays]) }}
                </p>
            </div>
        </div>
        @endif

        {{-- Easter scripture --}}
        <div class="animate-[fade-up_1s_ease-out_1.4s_both]">
            <div class="rounded-2xl bg-white/5 border border-white/10 px-6 py-5">
                <p class="text-[13px] text-white/60 leading-relaxed italic">
                    "{{ __('app.fasika_scripture_text') }}"
                </p>
                <p class="text-xs text-[#F5D060]/60 font-semibold mt-3">
                    {{ __('app.fasika_scripture_ref') }}
                </p>
            </div>
        </div>

        {{-- Action buttons --}}
        <div class="animate-[fade-up_1s_ease-out_1.6s_both] space-y-3 pt-2">
            @if ($surveyToken)
                <a href="{{ route('survey.show', ['token' => $surveyToken]) }}"
                   class="block w-full py-3.5 rounded-xl bg-gradient-to-r from-[#D4A537] to-[#F5D060]
                          text-[#1a0e2e] font-bold text-[15px] transition active:scale-[0.98]
                          shadow-[0_4px_20px_rgba(245,208,96,0.3)] hover:shadow-[0_6px_30px_rgba(245,208,96,0.4)]">
                    {{ __('app.fasika_survey_cta') }}
                </a>
            @endif

            <button type="button" @click="share()"
                    class="block w-full py-3 rounded-xl border border-[#F5D060]/25 text-[#F5D060] font-semibold text-[14px]
                           hover:bg-[#F5D060]/10 transition active:scale-[0.98]">
                {{ __('app.fasika_share_btn') }}
            </button>
        </div>

        {{-- Church name --}}
        <div class="animate-[fade-up_1s_ease-out_1.8s_both] pt-4 pb-6">
            <p class="text-xs text-white/30 font-medium">
                {{ __('app.fasika_church_name') }}
            </p>
            <p class="text-[10px] text-white/20 mt-1">
                {{ __('app.fasika_year_label', ['year' => $year]) }}
            </p>
        </div>
    </div>
</div>

<style>
    .fasika-page {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }

    .fasika-gold-text {
        background: linear-gradient(180deg, #FFF8DC 0%, #F5D060 40%, #D4A537 80%, #B8860B 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        filter: drop-shadow(0 2px 8px rgba(212, 165, 55, 0.4));
    }

    @keyframes glow-pulse {
        0%, 100% { opacity: 0.8; transform: translate(-50%, 0) scale(1); }
        50%      { opacity: 1;   transform: translate(-50%, 0) scale(1.08); }
    }

    @keyframes cross-glow {
        0%, 100% { opacity: 0.6; transform: scale(1); }
        50%      { opacity: 1;   transform: scale(1.12); }
    }

    @keyframes rays-spin {
        from { transform: rotate(0deg); }
        to   { transform: rotate(360deg); }
    }

    @keyframes fade-up {
        from { opacity: 0; transform: translateY(20px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* Hide the normal page background */
    .fasika-page { margin: -1rem; padding-top: 0; }
    @media (min-width: 640px) { .fasika-page { margin: -1.5rem; } }
</style>

<script>
function fasikaPage() {
    return {
        animatedDays: 0,
        targetDays: {{ $daysCompleted }},
        ringProgress: 0,
        ringTotal: {{ round(2 * 3.14159 * 52) }},

        start() {
            // Force dark theme for the celebration
            window.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme: 'dark' } }));

            // Animate the day counter
            this.$nextTick(() => {
                this.initParticles();
                this.animateCounter();
            });
        },

        animateCounter() {
            if (this.targetDays <= 0) return;
            const duration = 2000;
            const start = performance.now();
            const circumference = {{ round(2 * 3.14159 * 52) }};
            const targetArc = (this.targetDays / {{ $totalDays }}) * circumference;

            const animate = (now) => {
                const elapsed = now - start;
                const progress = Math.min(elapsed / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 3); // ease-out cubic

                this.animatedDays = Math.round(eased * this.targetDays);
                this.ringProgress = eased * targetArc;

                if (progress < 1) requestAnimationFrame(animate);
            };
            requestAnimationFrame(animate);
        },

        initParticles() {
            const canvas = document.getElementById('fasika-particles');
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            let w = canvas.width  = canvas.offsetWidth;
            let h = canvas.height = canvas.offsetHeight;

            const particles = [];
            const count = Math.min(60, Math.floor(w * h / 8000));

            for (let i = 0; i < count; i++) {
                particles.push({
                    x: Math.random() * w,
                    y: Math.random() * h,
                    r: Math.random() * 2.5 + 0.5,
                    speed: Math.random() * 0.4 + 0.15,
                    opacity: Math.random() * 0.5 + 0.2,
                    drift: (Math.random() - 0.5) * 0.3,
                    phase: Math.random() * Math.PI * 2,
                });
            }

            const draw = () => {
                ctx.clearRect(0, 0, w, h);
                for (const p of particles) {
                    p.y -= p.speed;
                    p.x += Math.sin(p.phase) * p.drift;
                    p.phase += 0.01;
                    p.opacity += (Math.random() - 0.5) * 0.02;
                    p.opacity = Math.max(0.1, Math.min(0.7, p.opacity));

                    if (p.y < -10) { p.y = h + 10; p.x = Math.random() * w; }

                    ctx.beginPath();
                    ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                    ctx.fillStyle = `rgba(245, 208, 96, ${p.opacity})`;
                    ctx.fill();
                }
                requestAnimationFrame(draw);
            };
            draw();

            window.addEventListener('resize', () => {
                w = canvas.width  = canvas.offsetWidth;
                h = canvas.height = canvas.offsetHeight;
            });
        },

        async share() {
            const text = '{{ __("app.fasika_share_text") }}';
            const url  = window.location.href;

            if (navigator.share) {
                try {
                    await navigator.share({ title: '{{ __("app.fasika_share_title") }}', text, url });
                } catch (e) { /* user cancelled */ }
            } else {
                // Fallback: copy to clipboard
                try {
                    await navigator.clipboard.writeText(text + '\n' + url);
                    alert('{{ __("app.fasika_copied") }}');
                } catch (e) {}
            }
        },
    };
}
</script>
@endsection
