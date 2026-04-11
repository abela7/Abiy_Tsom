@extends('layouts.member')

@section('title', __('app.survey_page_title') . ' — ' . __('app.app_name'))

@section('content')

@php
    $saveUrl   = route('survey.save',   ['token' => $feedback->token]);
    $submitUrl = route('survey.submit', ['token' => $feedback->token]);
    $thanksUrl = route('survey.thanks', ['token' => $feedback->token]);

    $currentStep = $feedback->calculateCurrentStep();

    $savedAnswers = [
        'q1_usefulness'            => $feedback->q1_usefulness,
        'q2_improvement_feedback'  => $feedback->q2_improvement_feedback ?? '',
        'q3_continuity_preference' => $feedback->q3_continuity_preference,
        'q4_overall_rating'        => $feedback->q4_overall_rating,
    ];

    $ratingLabels = [
        '',
        __('app.survey_q4_poor'),
        __('app.survey_q4_fair'),
        __('app.survey_q4_good'),
        __('app.survey_q4_very_good'),
        __('app.survey_q4_excellent'),
    ];
@endphp

<script>
function surveyWizard() {
    return {
        // ── State ──────────────────────────────────────────────────────────
        step:         {{ $currentStep }},
        saving:       false,
        submitting:   false,
        error:        null,
        answers:      {!! json_encode($savedAnswers, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
        ratingLabels: {!! json_encode($ratingLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},

        // ── Progress (3 visible steps max per path) ────────────────────────
        get displayStep() {
            if (this.step <= 1) return 1;
            if (this.step === 2 || this.step === 3) return 2;
            return 3;
        },

        get progress() {
            return Math.round((this.displayStep - 1) / 3 * 100);
        },

        // ── Skip-logic router ──────────────────────────────────────────────
        nextStep() {
            if (this.step === 1) {
                const q1 = this.answers.q1_usefulness;
                if (q1 === 'not_very_useful' || q1 === 'not_useful') return 2;
                if (q1 === 'very_useful'     || q1 === 'useful')     return 3;
            }
            return 4;
        },

        // ── Go back to previous step ──────────────────────────────────────
        back() {
            if (this.step === 4) {
                const q1 = this.answers.q1_usefulness;
                this.step = (q1 === 'not_very_useful' || q1 === 'not_useful') ? 2 : 3;
            } else {
                this.step = 1;
            }
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        // ── CSRF helper ───────────────────────────────────────────────────
        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        },

        // ── Save draft (auto-sync on every Next click) ────────────────────
        async saveStep() {
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

        // ── Early exit for 'not_seen' ─────────────────────────────────────
        async earlyExit() {
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
                    body: JSON.stringify({ q1_usefulness: 'not_seen' }),
                });
                const data = await res.json();
                if (data.ok && data.redirect) window.location.href = data.redirect;
            } catch (e) {
                this.error      = 'Connection error. Please try again.';
                this.submitting = false;
            }
        },

        // ── Advance to next step ──────────────────────────────────────────
        async next() {
            if (this.step === 1 && this.answers.q1_usefulness === 'not_seen') {
                await this.earlyExit();
                return;
            }
            await this.saveStep();
            if (!this.error) {
                this.step = this.nextStep();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },

        // ── Final submit ──────────────────────────────────────────────────
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
                } else if (data.errors) {
                    const first = Object.values(data.errors)[0]?.[0];
                    this.error      = first ?? 'Validation failed. Please check your answers.';
                    this.submitting = false;
                } else {
                    this.error      = data.message ?? 'Something went wrong. Please try again.';
                    this.submitting = false;
                }
            } catch (e) {
                this.error      = 'Connection error. Please try again.';
                this.submitting = false;
            }
        },
    };
}
</script>

<div x-data="surveyWizard()" class="max-w-lg mx-auto px-4 py-6 space-y-5">

    {{-- Header --}}
    <div class="text-center pb-1">
        <p class="text-xs font-semibold text-accent uppercase tracking-widest mb-1">Abiy Tsom {{ now()->year }}</p>
        <h1 class="text-[22px] font-bold text-primary leading-snug">{{ __('app.survey_page_title') }}</h1>
        <p class="text-sm text-muted-text mt-1">{{ __('app.survey_subtitle') }}</p>
    </div>

    {{-- Progress bar --}}
    <div class="space-y-1.5">
        <div class="w-full h-1.5 bg-muted rounded-full overflow-hidden">
            <div class="h-full bg-accent rounded-full transition-all duration-500"
                 :style="`width: ${progress}%`"></div>
        </div>
        @php [$stepBefore, $stepAfter] = explode(':step', __('app.survey_step_of')) + ['', '']; @endphp
        <p class="text-xs text-muted-text text-right">
            {{ $stepBefore }}<span x-text="displayStep"></span>{{ $stepAfter }}
        </p>
    </div>

    {{-- Error banner --}}
    <template x-if="error">
        <div class="rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-300"
             x-text="error">
        </div>
    </template>

    {{-- ── STEP 1 — Q1: Usefulness ── --}}
    <template x-if="step === 1">
        <div>
            <div class="bg-card rounded-2xl border border-border shadow-sm p-5 space-y-4">
                <div class="flex items-start gap-3">
                    <span class="w-7 h-7 rounded-full bg-accent/10 text-accent flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">1</span>
                    <h2 class="text-[15px] font-bold text-primary leading-snug">
                        {{ __('app.survey_q1_question') }}
                    </h2>
                </div>

                <div class="space-y-2">
                    @foreach ([
                        'very_useful'     => ['label' => __('app.survey_q1_very_useful'),    'icon' => '🌟'],
                        'useful'          => ['label' => __('app.survey_q1_useful'),         'icon' => '👍'],
                        'not_very_useful' => ['label' => __('app.survey_q1_not_very_useful'),'icon' => '😐'],
                        'not_useful'      => ['label' => __('app.survey_q1_not_useful'),     'icon' => '👎'],
                        'not_seen'        => ['label' => __('app.survey_q1_not_seen'),       'icon' => '👀'],
                    ] as $value => $opt)
                        <button type="button"
                                @click="answers.q1_usefulness = '{{ $value }}'"
                                class="w-full flex items-center gap-3 px-4 py-3.5 rounded-xl border text-left transition touch-manipulation"
                                :class="answers.q1_usefulness === '{{ $value }}'
                                    ? 'border-accent bg-accent/10 text-accent font-semibold'
                                    : 'border-border bg-muted/20 text-primary hover:border-accent/40 hover:bg-muted/40'">
                            <span class="text-xl shrink-0">{{ $opt['icon'] }}</span>
                            <span class="flex-1 text-[14px]">{{ $opt['label'] }}</span>
                            <span x-show="answers.q1_usefulness === '{{ $value }}'"
                                  class="w-5 h-5 rounded-full bg-accent flex items-center justify-center shrink-0">
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                </svg>
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>

            <button type="button" @click="next()" :disabled="!answers.q1_usefulness || saving || submitting"
                    class="mt-4 w-full py-3.5 rounded-xl bg-accent text-white font-bold text-[15px] transition active:scale-[0.98] disabled:opacity-40 disabled:cursor-not-allowed touch-manipulation">
                <span x-show="!saving && !submitting">{{ __('app.survey_continue') }}</span>
                <span x-show="saving || submitting" class="flex items-center justify-center gap-2">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                    </svg>
                    <span x-text="answers.q1_usefulness === 'not_seen' ? '{{ __('app.survey_submitting') }}' : '{{ __('app.survey_saving') }}'"></span>
                </span>
            </button>
        </div>
    </template>

    {{-- ── STEP 2 — Q2: Improvement feedback (negative branch) ── --}}
    <template x-if="step === 2">
        <div>
            <div class="bg-card rounded-2xl border border-border shadow-sm p-5 space-y-4">
                <div class="flex items-start gap-3">
                    <span class="w-7 h-7 rounded-full bg-accent/10 text-accent flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">2</span>
                    <h2 class="text-[15px] font-bold text-primary leading-snug">
                        {{ __('app.survey_q2_question') }} <span class="text-muted-text font-normal text-[13px]">— {{ __('app.survey_q2_optional') }}</span>
                    </h2>
                </div>
                <p class="text-[13px] text-muted-text -mt-1 pl-10">{{ __('app.survey_q2_subtitle') }}</p>

                <textarea x-model="answers.q2_improvement_feedback"
                          placeholder="{{ __('app.survey_q2_placeholder') }}"
                          rows="5"
                          maxlength="2000"
                          class="w-full rounded-xl border border-border bg-muted/20 px-4 py-3 text-[14px] text-primary placeholder-muted-text resize-none focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent/60 transition">
                </textarea>
                <p class="text-xs text-muted-text text-right -mt-2">
                    <span x-text="(answers.q2_improvement_feedback ?? '').length"></span> / 2000
                </p>
            </div>

            <div class="mt-4 flex gap-3">
                <button type="button" @click="back()"
                        class="px-5 py-3.5 rounded-xl border border-border text-primary text-[15px] font-semibold hover:bg-muted/40 transition touch-manipulation">
                    {{ __('app.survey_back') }}
                </button>
                <button type="button" @click="next()" :disabled="saving"
                        class="flex-1 py-3.5 rounded-xl bg-accent text-white font-bold text-[15px] transition active:scale-[0.98] disabled:opacity-40 touch-manipulation">
                    <span x-show="!saving">{{ __('app.survey_continue') }}</span>
                    <span x-show="saving">{{ __('app.survey_saving') }}</span>
                </button>
            </div>
        </div>
    </template>

    {{-- ── STEP 3 — Q3: Continuity preference (positive branch) ── --}}
    <template x-if="step === 3">
        <div>
            <div class="bg-card rounded-2xl border border-border shadow-sm p-5 space-y-4">
                <div class="flex items-start gap-3">
                    <span class="w-7 h-7 rounded-full bg-accent/10 text-accent flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">2</span>
                    <h2 class="text-[15px] font-bold text-primary leading-snug">
                        {{ __('app.survey_q3_question') }}
                    </h2>
                </div>

                <div class="space-y-2">
                    @foreach ([
                        'all_seasons'    => ['label' => __('app.survey_q3_all_seasons'),    'icon' => '✅'],
                        'abiy_tsom_only' => ['label' => __('app.survey_q3_abiy_tsom_only'), 'icon' => '📅'],
                    ] as $value => $opt)
                        <button type="button"
                                @click="answers.q3_continuity_preference = '{{ $value }}'"
                                class="w-full flex items-start gap-3 px-4 py-3.5 rounded-xl border text-left transition touch-manipulation"
                                :class="answers.q3_continuity_preference === '{{ $value }}'
                                    ? 'border-accent bg-accent/10 text-accent font-semibold'
                                    : 'border-border bg-muted/20 text-primary hover:border-accent/40 hover:bg-muted/40'">
                            <span class="text-xl shrink-0 mt-0.5">{{ $opt['icon'] }}</span>
                            <span class="flex-1 text-[14px] leading-snug">{{ $opt['label'] }}</span>
                            <span x-show="answers.q3_continuity_preference === '{{ $value }}'"
                                  class="w-5 h-5 rounded-full bg-accent flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                </svg>
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="mt-4 flex gap-3">
                <button type="button" @click="back()"
                        class="px-5 py-3.5 rounded-xl border border-border text-primary text-[15px] font-semibold hover:bg-muted/40 transition touch-manipulation">
                    {{ __('app.survey_back') }}
                </button>
                <button type="button" @click="next()" :disabled="!answers.q3_continuity_preference || saving"
                        class="flex-1 py-3.5 rounded-xl bg-accent text-white font-bold text-[15px] transition active:scale-[0.98] disabled:opacity-40 disabled:cursor-not-allowed touch-manipulation">
                    <span x-show="!saving">{{ __('app.survey_continue') }}</span>
                    <span x-show="saving">{{ __('app.survey_saving') }}</span>
                </button>
            </div>
        </div>
    </template>

    {{-- ── STEP 4 — Q4: Overall rating (both branches merge) ── --}}
    <template x-if="step === 4">
        <div>
            <div class="bg-card rounded-2xl border border-border shadow-sm p-5 space-y-5">
                <div class="flex items-start gap-3">
                    <span class="w-7 h-7 rounded-full bg-accent/10 text-accent flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">3</span>
                    <h2 class="text-[15px] font-bold text-primary leading-snug">
                        {{ __('app.survey_q4_question') }}
                    </h2>
                </div>

                <div class="flex justify-center gap-3 py-2">
                    @for ($i = 1; $i <= 5; $i++)
                        <button type="button"
                                @click="answers.q4_overall_rating = {{ $i }}"
                                class="text-4xl transition-all duration-150 active:scale-90 touch-manipulation focus:outline-none"
                                :class="answers.q4_overall_rating >= {{ $i }} ? 'opacity-100 scale-110' : 'opacity-25 hover:opacity-50'">
                            ⭐
                        </button>
                    @endfor
                </div>

                <p class="text-center text-sm font-medium h-5 transition-all"
                   :class="answers.q4_overall_rating ? 'text-accent' : 'text-muted-text'"
                   x-text="ratingLabels[answers.q4_overall_rating ?? 0]">
                </p>
            </div>

            <button type="button" @click="back()"
                    class="mt-4 w-full py-2.5 rounded-xl border border-border text-muted-text text-[14px] font-medium hover:bg-muted/40 transition touch-manipulation">
                {{ __('app.survey_back') }}
            </button>

            <button type="button" @click="submitSurvey()" :disabled="!answers.q4_overall_rating || submitting"
                    class="mt-3 w-full py-3.5 rounded-xl bg-accent text-white font-bold text-[15px] transition active:scale-[0.98] disabled:opacity-40 disabled:cursor-not-allowed touch-manipulation">
                <span x-show="!submitting">{{ __('app.survey_submit_btn') }}</span>
                <span x-show="submitting" class="flex items-center justify-center gap-2">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                    </svg>
                    {{ __('app.survey_submitting') }}
                </span>
            </button>
            <p class="text-xs text-center text-muted-text mt-3">{{ __('app.survey_privacy') }}</p>
        </div>
    </template>

</div>
@endsection
