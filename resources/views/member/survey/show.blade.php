<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="theme-sepia">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Post-Fasika Feedback — Abiy Tsom</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .star-btn { transition: transform 0.12s ease; }
        .star-btn:active { transform: scale(0.88); }
    </style>
</head>
<body class="bg-background min-h-screen flex flex-col">

@php
    $saveUrl   = route('survey.save',   ['token' => $feedback->token]);
    $submitUrl = route('survey.submit', ['token' => $feedback->token]);

    $savedAnswers = [
        'q1' => $feedback->q1_overall_rating,
        'q2' => $feedback->q2_most_used_feature,
        'q3' => $feedback->q3_himamat_rating,
        'q4' => $feedback->q4_whatsapp_reminder_useful,
        'q5' => $feedback->q5_suggestion ?? '',
        'q6' => $feedback->q6_opt_in_future_fasts,
    ];

    $features = [
        'daily_content' => ['en' => 'Daily Readings', 'icon' => '📖'],
        'himamat'       => ['en' => 'Himamat (Prayer Hours)', 'icon' => '🙏'],
        'reminders'     => ['en' => 'WhatsApp Reminders', 'icon' => '📲'],
        'events'        => ['en' => 'Events & Announcements', 'icon' => '📢'],
        'all_equal'     => ['en' => 'All equally', 'icon' => '⭐'],
    ];
@endphp

<div
    x-data="{
        step: {{ $feedback->calculateCurrentStep() }},
        totalSteps: 5,
        saving: false,
        submitting: false,
        error: null,
        answers: @json($savedAnswers),

        get progress() { return Math.round((this.step - 1) / this.totalSteps * 100); },

        csrfToken() {
            return document.querySelector('meta[name=csrf-token]')?.content ?? '';
        },

        async saveStep() {
            if (this.saving) return;
            this.saving = true;
            this.error  = null;
            try {
                const res = await fetch('{{ $saveUrl }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type':  'application/json',
                        'Accept':        'application/json',
                        'X-CSRF-TOKEN':  this.csrfToken(),
                    },
                    body: JSON.stringify(this.answers),
                });
                const data = await res.json();
                if (!data.ok && data.reason === 'already_submitted') {
                    window.location.href = '{{ route('survey.thanks', ['token' => $feedback->token]) }}';
                }
            } catch (e) {
                this.error = 'Connection error. Please try again.';
            } finally {
                this.saving = false;
            }
        },

        async next() {
            await this.saveStep();
            if (!this.error) this.step++;
        },

        async submitSurvey() {
            if (this.submitting) return;
            this.submitting = true;
            this.error      = null;
            try {
                const res = await fetch('{{ $submitUrl }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type':  'application/json',
                        'Accept':        'application/json',
                        'X-CSRF-TOKEN':  this.csrfToken(),
                    },
                    body: JSON.stringify(this.answers),
                });
                const data = await res.json();
                if (data.ok && data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    this.error = 'Something went wrong. Please try again.';
                }
            } catch (e) {
                this.error = 'Connection error. Please try again.';
            } finally {
                this.submitting = false;
            }
        },
    }"
    class="w-full max-w-lg mx-auto px-4 py-8 flex flex-col gap-6"
>

    {{-- Header --}}
    <div class="text-center">
        <p class="text-xs font-semibold text-accent uppercase tracking-widest mb-1">Abiy Tsom {{ now()->year }}</p>
        <h1 class="text-[22px] font-bold text-primary leading-snug">Post-Fasika Feedback</h1>
        <p class="text-sm text-muted-text mt-1">Help us serve you better next season</p>
    </div>

    {{-- Progress bar --}}
    <div class="w-full h-1.5 bg-muted rounded-full overflow-hidden">
        <div class="h-full bg-accent rounded-full transition-all duration-500"
             :style="'width: ' + progress + '%'">
        </div>
    </div>
    <p class="text-xs text-muted-text text-center -mt-4">
        Step <span x-text="step"></span> of <span x-text="totalSteps"></span>
    </p>

    {{-- Error banner --}}
    <div x-show="error" x-cloak x-transition
         class="rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-300">
        <span x-text="error"></span>
    </div>

    {{-- ─────────────── STEP 1 — Overall Rating ─────────────── --}}
    <div x-show="step === 1" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-x-4"
         x-transition:enter-end="opacity-100 translate-x-0">
        <div class="bg-card rounded-2xl border border-border shadow-sm p-6">
            <div class="flex items-center gap-3 mb-5">
                <span class="w-8 h-8 rounded-full bg-accent/10 text-accent flex items-center justify-center text-sm font-bold shrink-0">1</span>
                <h2 class="text-[16px] font-bold text-primary leading-snug">How would you rate your overall Abiy Tsom experience this year?</h2>
            </div>

            {{-- Star rating --}}
            <div class="flex justify-center gap-3 py-3">
                @for ($i = 1; $i <= 5; $i++)
                    <button type="button"
                            @click="answers.q1 = {{ $i }}"
                            class="star-btn text-4xl focus:outline-none"
                            :class="answers.q1 >= {{ $i }} ? 'opacity-100' : 'opacity-25 hover:opacity-60'"
                            aria-label="Rate {{ $i }} star">
                        ⭐
                    </button>
                @endfor
            </div>
            <p class="text-center text-sm text-muted-text mt-2 h-5"
               x-text="['', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'][answers.q1 ?? 0]">
            </p>
        </div>

        <button type="button"
                @click="next()"
                :disabled="!answers.q1 || saving"
                class="mt-4 w-full py-3.5 rounded-xl bg-accent text-white font-bold text-[15px] transition disabled:opacity-40 disabled:cursor-not-allowed active:scale-[0.98] touch-manipulation">
            <span x-show="!saving">Continue →</span>
            <span x-show="saving" x-cloak>Saving…</span>
        </button>
    </div>

    {{-- ─────────────── STEP 2 — Most Used Feature ─────────────── --}}
    <div x-show="step === 2" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-x-4"
         x-transition:enter-end="opacity-100 translate-x-0">
        <div class="bg-card rounded-2xl border border-border shadow-sm p-6">
            <div class="flex items-center gap-3 mb-5">
                <span class="w-8 h-8 rounded-full bg-accent/10 text-accent flex items-center justify-center text-sm font-bold shrink-0">2</span>
                <h2 class="text-[16px] font-bold text-primary leading-snug">Which feature did you use the most?</h2>
            </div>

            <div class="space-y-2.5">
                @foreach ($features as $key => $feature)
                    <button type="button"
                            @click="answers.q2 = '{{ $key }}'"
                            class="w-full flex items-center gap-3 px-4 py-3.5 rounded-xl border text-left transition touch-manipulation"
                            :class="answers.q2 === '{{ $key }}'
                                ? 'border-accent bg-accent/8 text-accent font-semibold'
                                : 'border-border bg-muted/30 text-primary hover:border-accent/40'">
                        <span class="text-xl shrink-0">{{ $feature['icon'] }}</span>
                        <span class="text-[14px]">{{ $feature['en'] }}</span>
                        <span x-show="answers.q2 === '{{ $key }}'"
                              class="ml-auto w-5 h-5 rounded-full bg-accent flex items-center justify-center shrink-0">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                        </span>
                    </button>
                @endforeach
            </div>
        </div>

        <button type="button"
                @click="next()"
                :disabled="!answers.q2 || saving"
                class="mt-4 w-full py-3.5 rounded-xl bg-accent text-white font-bold text-[15px] transition disabled:opacity-40 disabled:cursor-not-allowed active:scale-[0.98] touch-manipulation">
            <span x-show="!saving">Continue →</span>
            <span x-show="saving" x-cloak>Saving…</span>
        </button>
    </div>

    {{-- ─────────────── STEP 3 — Himamat + WhatsApp ─────────────── --}}
    <div x-show="step === 3" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-x-4"
         x-transition:enter-end="opacity-100 translate-x-0">
        <div class="bg-card rounded-2xl border border-border shadow-sm p-6 space-y-7">

            {{-- Q3 Himamat --}}
            <div>
                <div class="flex items-center gap-3 mb-4">
                    <span class="w-8 h-8 rounded-full bg-accent/10 text-accent flex items-center justify-center text-sm font-bold shrink-0">3a</span>
                    <h2 class="text-[15px] font-bold text-primary leading-snug">How was the Himamat prayer experience?</h2>
                </div>
                <div class="flex justify-center gap-3">
                    @for ($i = 1; $i <= 5; $i++)
                        <button type="button"
                                @click="answers.q3 = {{ $i }}"
                                class="star-btn text-3xl focus:outline-none"
                                :class="answers.q3 >= {{ $i }} ? 'opacity-100' : 'opacity-25 hover:opacity-60'"
                                aria-label="Rate {{ $i }}">
                            ⭐
                        </button>
                    @endfor
                </div>
            </div>

            <div class="h-px bg-border/50"></div>

            {{-- Q4 WhatsApp --}}
            <div>
                <div class="flex items-center gap-3 mb-4">
                    <span class="w-8 h-8 rounded-full bg-accent/10 text-accent flex items-center justify-center text-sm font-bold shrink-0">3b</span>
                    <h2 class="text-[15px] font-bold text-primary leading-snug">Were the WhatsApp reminders helpful?</h2>
                </div>
                <div class="flex gap-3">
                    <button type="button"
                            @click="answers.q4 = true"
                            class="flex-1 py-3 rounded-xl border text-[14px] font-semibold transition touch-manipulation"
                            :class="answers.q4 === true
                                ? 'border-accent bg-accent/8 text-accent'
                                : 'border-border bg-muted/30 text-primary hover:border-accent/40'">
                        👍  Yes
                    </button>
                    <button type="button"
                            @click="answers.q4 = false"
                            class="flex-1 py-3 rounded-xl border text-[14px] font-semibold transition touch-manipulation"
                            :class="answers.q4 === false
                                ? 'border-accent bg-accent/8 text-accent'
                                : 'border-border bg-muted/30 text-primary hover:border-accent/40'">
                        👎  No
                    </button>
                </div>
            </div>
        </div>

        <button type="button"
                @click="next()"
                :disabled="(!answers.q3 || answers.q4 === null) || saving"
                class="mt-4 w-full py-3.5 rounded-xl bg-accent text-white font-bold text-[15px] transition disabled:opacity-40 disabled:cursor-not-allowed active:scale-[0.98] touch-manipulation">
            <span x-show="!saving">Continue →</span>
            <span x-show="saving" x-cloak>Saving…</span>
        </button>
    </div>

    {{-- ─────────────── STEP 4 — Suggestion ─────────────── --}}
    <div x-show="step === 4" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-x-4"
         x-transition:enter-end="opacity-100 translate-x-0">
        <div class="bg-card rounded-2xl border border-border shadow-sm p-6">
            <div class="flex items-center gap-3 mb-5">
                <span class="w-8 h-8 rounded-full bg-accent/10 text-accent flex items-center justify-center text-sm font-bold shrink-0">4</span>
                <h2 class="text-[16px] font-bold text-primary leading-snug">Any suggestions for improvement? <span class="text-muted-text font-normal">(optional)</span></h2>
            </div>

            <textarea
                x-model="answers.q5"
                placeholder="Share your thoughts — we read every response…"
                rows="5"
                maxlength="2000"
                class="w-full rounded-xl border border-border bg-muted/30 px-4 py-3 text-[14px] text-primary placeholder-muted-text resize-none focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent/60 transition"
            ></textarea>
            <p class="text-xs text-muted-text text-right mt-1">
                <span x-text="(answers.q5 ?? '').length"></span> / 2000
            </p>
        </div>

        <button type="button"
                @click="next()"
                :disabled="saving"
                class="mt-4 w-full py-3.5 rounded-xl bg-accent text-white font-bold text-[15px] transition disabled:opacity-40 active:scale-[0.98] touch-manipulation">
            <span x-show="!saving">Continue →</span>
            <span x-show="saving" x-cloak>Saving…</span>
        </button>
    </div>

    {{-- ─────────────── STEP 5 — Opt-in + Submit ─────────────── --}}
    <div x-show="step === 5" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-x-4"
         x-transition:enter-end="opacity-100 translate-x-0">
        <div class="bg-card rounded-2xl border border-border shadow-sm p-6">
            <div class="flex items-center gap-3 mb-4">
                <span class="w-8 h-8 rounded-full bg-accent/10 text-accent flex items-center justify-center text-sm font-bold shrink-0">5</span>
                <h2 class="text-[15px] font-bold text-primary leading-snug">Would you like to stay connected and receive reminders for future fasting seasons (e.g., Filseta)?</h2>
            </div>
            <div class="flex gap-3">
                <button type="button"
                        @click="answers.q6 = true"
                        class="flex-1 py-3 rounded-xl border text-[14px] font-semibold transition touch-manipulation"
                        :class="answers.q6 === true
                            ? 'border-accent bg-accent/8 text-accent'
                            : 'border-border bg-muted/30 text-primary hover:border-accent/40'">
                    ✅  Yes, keep me connected
                </button>
                <button type="button"
                        @click="answers.q6 = false"
                        class="flex-1 py-3 rounded-xl border text-[14px] font-semibold transition touch-manipulation"
                        :class="answers.q6 === false
                            ? 'border-accent bg-accent/8 text-accent'
                            : 'border-border bg-muted/30 text-primary hover:border-accent/40'">
                    No thanks
                </button>
            </div>
        </div>

        <button type="button"
                @click="submitSurvey()"
                :disabled="answers.q6 === null || submitting"
                class="mt-4 w-full py-3.5 rounded-xl bg-accent text-white font-bold text-[15px] transition disabled:opacity-40 disabled:cursor-not-allowed active:scale-[0.98] touch-manipulation">
            <span x-show="!submitting">Submit Feedback 🙏</span>
            <span x-show="submitting" x-cloak>Submitting…</span>
        </button>

        <p class="text-xs text-center text-muted-text mt-3">Your response is private and only seen by our team.</p>
    </div>

</div>
</body>
</html>
