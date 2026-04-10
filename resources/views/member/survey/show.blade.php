@extends('layouts.member')

@section('title', 'Post-Fasika Feedback — ' . __('app.app_name'))

@section('content')

@php
    $saveUrl   = route('survey.save',   ['token' => $feedback->token]);
    $submitUrl = route('survey.submit', ['token' => $feedback->token]);
    $thanksUrl = route('survey.thanks', ['token' => $feedback->token]);

    $savedAnswers = [
        'q1' => $feedback->q1_overall_rating,
        'q2' => $feedback->q2_most_used_feature,
        'q3' => $feedback->q3_himamat_rating,
        'q4' => $feedback->q4_whatsapp_reminder_useful,
        'q5' => $feedback->q5_suggestion ?? '',
        'q6' => $feedback->q6_opt_in_future_fasts,
    ];

    $currentStep = $feedback->calculateCurrentStep();

    $features = [
        'daily_content' => ['label' => 'Daily Readings',          'icon' => '📖'],
        'himamat'       => ['label' => 'Himamat (Prayer Hours)',   'icon' => '🙏'],
        'reminders'     => ['label' => 'WhatsApp Reminders',       'icon' => '📲'],
        'events'        => ['label' => 'Events & Announcements',   'icon' => '📢'],
        'all_equal'     => ['label' => 'All equally',              'icon' => '⭐'],
    ];
@endphp

<div
    x-data="{
        step: {{ $currentStep }},
        totalSteps: 5,
        saving: false,
        submitting: false,
        error: null,
        answers: @json($savedAnswers),

        get progress() {
            return Math.round((this.step - 1) / this.totalSteps * 100);
        },

        csrfToken() {
            return document.querySelector('meta[name=csrf-token]')?.content ?? '';
        },

        async saveStep() {
            if (this.saving) return;
            this.saving = true;
            this.error  = null;
            try {
                const res  = await fetch('{{ $saveUrl }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept':       'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                    body: JSON.stringify(this.answers),
                });
                const data = await res.json();
                if (!data.ok && data.reason === 'already_submitted') {
                    window.location.href = '{{ $thanksUrl }}';
                }
            } catch (e) {
                this.error = 'Connection error. Please try again.';
            } finally {
                this.saving = false;
            }
        },

        async next() {
            await this.saveStep();
            if (!this.error) {
                this.step++;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },

        async submitSurvey() {
            if (this.submitting) return;
            this.submitting = true;
            this.error      = null;
            try {
                const res  = await fetch('{{ $submitUrl }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept':       'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                    body: JSON.stringify(this.answers),
                });
                const data = await res.json();
                if (data.ok && data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    this.error      = 'Something went wrong. Please try again.';
                    this.submitting = false;
                }
            } catch (e) {
                this.error      = 'Connection error. Please try again.';
                this.submitting = false;
            }
        },
    }"
    class="max-w-lg mx-auto px-4 py-6 space-y-5"
>

    {{-- Header --}}
    <div class="text-center pb-1">
        <p class="text-xs font-semibold text-accent uppercase tracking-widest mb-1">Abiy Tsom {{ now()->year }}</p>
        <h1 class="text-[22px] font-bold text-primary leading-snug">Post-Fasika Feedback</h1>
        <p class="text-sm text-muted-text mt-1">Help us serve you better next season</p>
    </div>

    {{-- Progress bar --}}
    <div class="space-y-1.5">
        <div class="w-full h-2 bg-muted rounded-full overflow-hidden">
            <div class="h-full bg-accent rounded-full transition-all duration-500"
                 :style="`width: ${progress}%`"></div>
        </div>
        <p class="text-xs text-muted-text text-right">
            Step <span x-text="step"></span> of <span x-text="totalSteps"></span>
        </p>
    </div>

    {{-- Error banner --}}
    <template x-if="error">
        <div class="rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-300"
             x-text="error">
        </div>
    </template>

    {{-- ── STEP 1 — Overall Rating ── --}}
    <template x-if="step === 1">
        <div>
            <div class="bg-card rounded-2xl border border-border shadow-sm p-5 space-y-5">
                <div class="flex items-start gap-3">
                    <span class="w-7 h-7 rounded-full bg-accent/10 text-accent flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">1</span>
                    <h2 class="text-[15px] font-bold text-primary leading-snug">How would you rate your overall Abiy Tsom experience this year?</h2>
                </div>

                <div class="flex justify-center gap-3 py-2">
                    @for ($i = 1; $i <= 5; $i++)
                        <button type="button"
                                @click="answers.q1 = {{ $i }}"
                                class="text-4xl transition-all duration-150 active:scale-90 touch-manipulation focus:outline-none"
                                :class="answers.q1 >= {{ $i }} ? 'opacity-100 scale-110' : 'opacity-25 hover:opacity-50'">
                            ⭐
                        </button>
                    @endfor
                </div>

                <p class="text-center text-sm font-medium h-5 transition-all"
                   :class="answers.q1 ? 'text-accent' : 'text-muted-text'"
                   x-text="['', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'][answers.q1 ?? 0]">
                </p>
            </div>

            <button type="button" @click="next()" :disabled="!answers.q1 || saving"
                    class="mt-4 w-full py-3.5 rounded-xl bg-accent text-white font-bold text-[15px] transition active:scale-[0.98] disabled:opacity-40 disabled:cursor-not-allowed touch-manipulation">
                <span x-show="!saving">Continue →</span>
                <span x-show="saving" class="flex items-center justify-center gap-2">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                    </svg>
                    Saving…
                </span>
            </button>
        </div>
    </template>

    {{-- ── STEP 2 — Most Used Feature ── --}}
    <template x-if="step === 2">
        <div>
            <div class="bg-card rounded-2xl border border-border shadow-sm p-5 space-y-4">
                <div class="flex items-start gap-3">
                    <span class="w-7 h-7 rounded-full bg-accent/10 text-accent flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">2</span>
                    <h2 class="text-[15px] font-bold text-primary leading-snug">Which feature did you use the most?</h2>
                </div>

                <div class="space-y-2">
                    @foreach ($features as $key => $feature)
                        <button type="button"
                                @click="answers.q2 = '{{ $key }}'"
                                class="w-full flex items-center gap-3 px-4 py-3.5 rounded-xl border text-left transition touch-manipulation"
                                :class="answers.q2 === '{{ $key }}'
                                    ? 'border-accent bg-accent/10 text-accent font-semibold'
                                    : 'border-border bg-muted/20 text-primary hover:border-accent/40 hover:bg-muted/40'">
                            <span class="text-xl shrink-0">{{ $feature['icon'] }}</span>
                            <span class="flex-1 text-[14px]">{{ $feature['label'] }}</span>
                            <span x-show="answers.q2 === '{{ $key }}'"
                                  class="w-5 h-5 rounded-full bg-accent flex items-center justify-center shrink-0">
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                </svg>
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>

            <button type="button" @click="next()" :disabled="!answers.q2 || saving"
                    class="mt-4 w-full py-3.5 rounded-xl bg-accent text-white font-bold text-[15px] transition active:scale-[0.98] disabled:opacity-40 disabled:cursor-not-allowed touch-manipulation">
                <span x-show="!saving">Continue →</span>
                <span x-show="saving">Saving…</span>
            </button>
        </div>
    </template>

    {{-- ── STEP 3 — Himamat + WhatsApp ── --}}
    <template x-if="step === 3">
        <div>
            <div class="bg-card rounded-2xl border border-border shadow-sm p-5 space-y-6">

                <div class="space-y-3">
                    <div class="flex items-start gap-3">
                        <span class="w-7 h-7 rounded-full bg-accent/10 text-accent flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">3a</span>
                        <h2 class="text-[15px] font-bold text-primary leading-snug">How was the Himamat prayer experience?</h2>
                    </div>
                    <div class="flex justify-center gap-3">
                        @for ($i = 1; $i <= 5; $i++)
                            <button type="button" @click="answers.q3 = {{ $i }}"
                                    class="text-3xl transition-all active:scale-90 touch-manipulation focus:outline-none"
                                    :class="answers.q3 >= {{ $i }} ? 'opacity-100' : 'opacity-25 hover:opacity-50'">
                                ⭐
                            </button>
                        @endfor
                    </div>
                </div>

                <div class="h-px bg-border/50"></div>

                <div class="space-y-3">
                    <div class="flex items-start gap-3">
                        <span class="w-7 h-7 rounded-full bg-accent/10 text-accent flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">3b</span>
                        <h2 class="text-[15px] font-bold text-primary leading-snug">Were the WhatsApp reminders helpful?</h2>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" @click="answers.q4 = true"
                                class="flex-1 py-3 rounded-xl border text-[14px] font-semibold transition touch-manipulation"
                                :class="answers.q4 === true ? 'border-accent bg-accent/10 text-accent' : 'border-border bg-muted/20 text-primary hover:border-accent/40'">
                            👍 &nbsp;Yes
                        </button>
                        <button type="button" @click="answers.q4 = false"
                                class="flex-1 py-3 rounded-xl border text-[14px] font-semibold transition touch-manipulation"
                                :class="answers.q4 === false ? 'border-accent bg-accent/10 text-accent' : 'border-border bg-muted/20 text-primary hover:border-accent/40'">
                            👎 &nbsp;No
                        </button>
                    </div>
                </div>
            </div>

            <button type="button" @click="next()" :disabled="(!answers.q3 || answers.q4 === null) || saving"
                    class="mt-4 w-full py-3.5 rounded-xl bg-accent text-white font-bold text-[15px] transition active:scale-[0.98] disabled:opacity-40 disabled:cursor-not-allowed touch-manipulation">
                <span x-show="!saving">Continue →</span>
                <span x-show="saving">Saving…</span>
            </button>
        </div>
    </template>

    {{-- ── STEP 4 — Suggestion ── --}}
    <template x-if="step === 4">
        <div>
            <div class="bg-card rounded-2xl border border-border shadow-sm p-5 space-y-4">
                <div class="flex items-start gap-3">
                    <span class="w-7 h-7 rounded-full bg-accent/10 text-accent flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">4</span>
                    <h2 class="text-[15px] font-bold text-primary leading-snug">
                        Any suggestions for improvement?
                        <span class="text-muted-text font-normal text-[13px]"> — optional</span>
                    </h2>
                </div>

                <textarea x-model="answers.q5"
                          placeholder="Share your thoughts — we read every response…"
                          rows="5"
                          maxlength="2000"
                          class="w-full rounded-xl border border-border bg-muted/20 px-4 py-3 text-[14px] text-primary placeholder-muted-text resize-none focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent/60 transition">
                </textarea>
                <p class="text-xs text-muted-text text-right -mt-2">
                    <span x-text="(answers.q5 ?? '').length"></span> / 2000
                </p>
            </div>

            <button type="button" @click="next()" :disabled="saving"
                    class="mt-4 w-full py-3.5 rounded-xl bg-accent text-white font-bold text-[15px] transition active:scale-[0.98] disabled:opacity-40 touch-manipulation">
                <span x-show="!saving">Continue →</span>
                <span x-show="saving">Saving…</span>
            </button>
        </div>
    </template>

    {{-- ── STEP 5 — Opt-in + Submit ── --}}
    <template x-if="step === 5">
        <div>
            <div class="bg-card rounded-2xl border border-border shadow-sm p-5 space-y-4">
                <div class="flex items-start gap-3">
                    <span class="w-7 h-7 rounded-full bg-accent/10 text-accent flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">5</span>
                    <h2 class="text-[15px] font-bold text-primary leading-snug">
                        Would you like to stay connected and receive reminders for future fasting seasons (e.g., Filseta)?
                    </h2>
                </div>
                <div class="flex gap-3">
                    <button type="button" @click="answers.q6 = true"
                            class="flex-1 py-3 rounded-xl border text-[13px] font-semibold transition touch-manipulation"
                            :class="answers.q6 === true ? 'border-accent bg-accent/10 text-accent' : 'border-border bg-muted/20 text-primary hover:border-accent/40'">
                        ✅ &nbsp;Yes, keep me connected
                    </button>
                    <button type="button" @click="answers.q6 = false"
                            class="flex-1 py-3 rounded-xl border text-[13px] font-semibold transition touch-manipulation"
                            :class="answers.q6 === false ? 'border-accent bg-accent/10 text-accent' : 'border-border bg-muted/20 text-primary hover:border-accent/40'">
                        No thanks
                    </button>
                </div>
            </div>

            <button type="button" @click="submitSurvey()" :disabled="answers.q6 === null || submitting"
                    class="mt-4 w-full py-3.5 rounded-xl bg-accent text-white font-bold text-[15px] transition active:scale-[0.98] disabled:opacity-40 disabled:cursor-not-allowed touch-manipulation">
                <span x-show="!submitting">Submit Feedback 🙏</span>
                <span x-show="submitting" class="flex items-center justify-center gap-2">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                    </svg>
                    Submitting…
                </span>
            </button>
            <p class="text-xs text-center text-muted-text mt-3">Your response is private and only seen by our team.</p>
        </div>
    </template>

</div>
@endsection
