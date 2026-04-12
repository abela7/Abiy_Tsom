{{-- Fasika Quiz: teaser card + full-screen modal --}}
<div x-data="fasikaQuiz({
         questionsUrl: @js(route('public.yefasika-beal.quiz.questions')),
         answerUrl:    @js(route('public.yefasika-beal.quiz.answer')),
         completeUrl:  @js(route('public.yefasika-beal.quiz.complete')),
         csrf:         @js(csrf_token()),
         thankYouText: @js(__('app.fasika_quiz_results_thank_you_am', [], 'am')),
     })">

    {{-- ═══════════════════════════════════════════
         TEASER CARD (on page)
    ═══════════════════════════════════════════ --}}
    <section class="relative mx-auto w-full max-w-md rounded-2xl border border-[#e2ca18]/[0.22] bg-gradient-to-br from-amber-950/55 via-[#1a1210]/78 to-[#0a2832]/72 px-5 py-6 shadow-[0_16px_36px_-12px_rgba(30,16,8,0.42)] ring-1 ring-inset ring-[#f5d060]/[0.06] backdrop-blur-[3px] backdrop-saturate-125 sm:max-w-lg sm:px-7 sm:py-7">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-[#e2ca18]/50 to-transparent rounded-t-2xl"></div>

        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-base font-extrabold leading-snug text-[#e2ca18] sm:text-lg">
                    ስለ ፋሲካ በዓል ያለወትን እውቀት ይፈትሹ
                </h2>
                <p class="mt-1.5 text-sm leading-relaxed text-zinc-300/75 sm:text-[0.9375rem]">
                    15 ከቀላል እስከ ከባድ ጥያቄዎች አሉ፣ እርስዎ ስንቱን ይመልሳሉ? ይሞክሩት
                </p>
            </div>
            <div class="shrink-0 rounded-xl bg-[#e2ca18]/10 p-2.5 ring-1 ring-[#e2ca18]/20">
                <svg class="h-6 w-6 text-[#e2ca18]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                          d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                </svg>
            </div>
        </div>

        <button type="button"
                @click="openModal()"
                class="mt-5 touch-manipulation inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-[#e2ca18] text-sm font-bold tracking-wide text-zinc-950 shadow-[0_8px_24px_-6px_rgba(226,202,24,0.38)] transition hover:bg-[#edd85c] active:scale-[0.98]">
            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            ጀምር
        </button>

        <div class="pointer-events-none absolute inset-x-0 bottom-0 h-px bg-gradient-to-r from-transparent via-[#e2ca18]/25 to-transparent rounded-b-2xl"></div>
    </section>

    {{-- ═══════════════════════════════════════════
         FULL-SCREEN MODAL — teleported to <body> so position:fixed is viewport-
         relative (#ybb-main-content uses transform, which traps fixed children).
         z-index above #ri-overlay (9999).
    ═══════════════════════════════════════════ --}}
    <template x-teleport="body">
    <div x-show="modalOpen"
         x-cloak
         class="fixed inset-0 z-[100050]"
         @keydown.escape.window="maybeClose()">

        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-black/82"
             x-show="modalOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="maybeClose()">
        </div>

        {{-- Modal card: full-screen on mobile, centered on desktop --}}
        <div class="pointer-events-none absolute inset-0 flex items-end justify-center sm:items-center sm:p-6">
            <div class="pointer-events-auto flex max-h-[100dvh] w-full flex-col rounded-t-3xl bg-[#0f0a1a] shadow-2xl ring-1 ring-white/10 sm:max-h-[90vh] sm:max-w-lg sm:rounded-3xl"
                 x-show="modalOpen"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
                 @click.stop>

                {{-- Drag handle (mobile) --}}
                <div class="mx-auto mt-2.5 h-1 w-10 rounded-full bg-white/20 sm:hidden shrink-0"></div>

                {{-- ── INTRO ── --}}
                <div x-show="state === 'intro'" class="flex flex-col overflow-y-auto">
                    <div class="flex items-center justify-between px-5 pt-4 pb-3 border-b border-white/[0.07] shrink-0 sm:px-7">
                        <h3 class="text-base font-bold text-white">ፈተናውን ጀምሩ</h3>
                        <button type="button"
                                @click="closeModal()"
                                class="rounded-lg p-1.5 text-zinc-400 transition hover:bg-white/10 hover:text-white"
                                aria-label="{{ __('app.fasika_quiz_modal_close_aria') }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="px-5 py-5 space-y-4 sm:px-7">
                        <p class="text-sm leading-relaxed text-zinc-300/80">15 ጥያቄዎች · 10 ደቂቃ · ከቀላል እስከ ከባድ</p>
                        <div>
                            <label for="fq-name" class="block text-xs font-semibold text-zinc-400 mb-1.5">ስምዎ (አማራጭ)</label>
                            <input id="fq-name" x-model.trim="participantName" type="text" maxlength="120" autocomplete="name"
                                   @keydown.enter="startQuiz()"
                                   class="h-11 w-full rounded-xl border border-white/10 bg-white/[0.06] px-4 text-sm text-white shadow-inner outline-none transition placeholder:text-zinc-500 focus:border-[#e2ca18]/40 focus:ring-2 focus:ring-[#e2ca18]/20"
                                   placeholder="ስምዎን ያስገቡ">
                        </div>
                        <p x-show="errorMessage" x-cloak class="text-xs font-medium text-rose-300 text-center" x-text="errorMessage"></p>
                        <button type="button" @click="startQuiz()" :disabled="isLoading"
                                class="touch-manipulation inline-flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-[#e2ca18] text-sm font-bold text-zinc-950 shadow-[0_8px_24px_-6px_rgba(226,202,24,0.38)] transition hover:bg-[#edd85c] disabled:opacity-60 active:scale-[0.98]">
                            <span x-show="!isLoading">ጀምር</span>
                            <span x-show="isLoading" x-cloak>በመጫን ላይ...</span>
                        </button>
                    </div>
                </div>

                {{-- ── PLAYING ── --}}
                <div x-show="state === 'playing'" x-cloak class="flex flex-col min-h-0 flex-1">

                    {{-- Header (timer + cancel) --}}
                    <div class="flex items-center gap-2 border-b border-white/[0.07] px-5 pb-2.5 pt-3 shrink-0 sm:gap-3 sm:px-7">
                        <div class="flex min-w-0 flex-1 items-center gap-2 sm:gap-3">
                            <div class="flex shrink-0 items-center gap-1.5 min-w-[3.25rem]"
                                 :class="timeLeft <= 60 ? 'text-rose-400' : 'text-[#e2ca18]'">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="font-mono text-sm font-bold tabular-nums" x-text="formattedTime"></span>
                            </div>
                            <div class="h-1.5 min-w-0 flex-1 overflow-hidden rounded-full bg-white/10">
                                <div class="h-full rounded-full bg-[#e2ca18] transition-all duration-500" :style="`width:${progress}%`"></div>
                            </div>
                            <span class="shrink-0 text-right text-xs font-semibold text-zinc-400 min-w-[2.5rem]">
                                <span x-text="currentIndex + 1"></span>/<span x-text="questions.length"></span>
                            </span>
                        </div>
                        <button type="button"
                                @click="cancelQuiz()"
                                class="shrink-0 rounded-lg p-1.5 text-zinc-400 transition hover:bg-white/10 hover:text-white"
                                aria-label="{{ __('app.fasika_quiz_cancel_quiz_aria') }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Question area (scrollable) --}}
                    <div class="overflow-y-auto flex-1 px-5 py-4 sm:px-7 space-y-3.5">
                        {{-- Difficulty --}}
                        <div x-show="currentQuestion">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                  :class="{
                                      'bg-green-500/15 text-green-400 ring-1 ring-green-500/20': currentQuestion?.difficulty === 'easy',
                                      'bg-yellow-500/15 text-yellow-400 ring-1 ring-yellow-500/20': currentQuestion?.difficulty === 'medium',
                                      'bg-red-500/15 text-red-400 ring-1 ring-red-500/20': currentQuestion?.difficulty === 'hard',
                                  }"
                                  x-text="{easy:'ቀላል · 1pt', medium:'መካከለኛ · 2pt', hard:'ከባድ · 3pt'}[currentQuestion?.difficulty] ?? ''">
                            </span>
                        </div>

                        {{-- Question --}}
                        <p class="text-[0.9375rem] font-semibold leading-[1.75] text-white sm:text-base"
                           x-text="currentQuestion?.question"></p>

                        {{-- Options --}}
                        <div class="space-y-2.5 pt-1">
                            <template x-for="opt in ['a','b','c','d']" :key="opt">
                                <button type="button" @click="selectOption(opt)" :disabled="selectedOption !== null"
                                        class="touch-manipulation group relative w-full rounded-xl border px-4 py-3.5 text-left text-sm font-medium leading-snug transition-all duration-200 active:scale-[0.98] disabled:cursor-default"
                                        :class="optionClass(opt)">
                                    <span class="flex items-start gap-3">
                                        <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full border text-[10px] font-bold transition-colors duration-200"
                                              :class="optionBadgeClass(opt)"
                                              x-text="opt.toUpperCase()"></span>
                                        <span x-text="currentQuestion?.['option_' + opt]"></span>
                                    </span>
                                    <span x-show="feedback && opt === feedback.correct_option"
                                          class="absolute right-3 top-1/2 -translate-y-1/2 text-green-400">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </span>
                                </button>
                            </template>
                        </div>

                        {{-- Feedback --}}
                        <div x-show="feedback" x-cloak
                             x-transition:enter="transition ease-out duration-250"
                             x-transition:enter-start="opacity-0 translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="rounded-xl px-4 py-3 text-sm font-semibold text-center"
                             :class="feedback?.is_correct ? 'bg-green-500/15 text-green-300 ring-1 ring-green-500/20' : 'bg-rose-500/15 text-rose-300 ring-1 ring-rose-500/20'">
                            <span x-text="feedback?.is_correct ? 'በትክክል ተመልሷል! ✓' : 'አልተመለሰም! ✗'"></span>
                        </div>
                    </div>

                    {{-- Nav buttons (sticky bottom) --}}
                    <div class="shrink-0 flex gap-3 px-5 py-3.5 border-t border-white/[0.07] bg-[#0f0a1a] sm:px-7">
                        <button type="button" @click="prevQuestion()" :disabled="currentIndex === 0"
                                class="touch-manipulation flex-1 inline-flex h-10 items-center justify-center gap-1.5 rounded-xl border border-white/10 text-sm font-semibold text-zinc-300 transition hover:bg-white/[0.06] active:scale-[0.98] disabled:opacity-30 disabled:cursor-not-allowed">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            ቀዳሚ
                        </button>
                        <template x-if="currentIndex < questions.length - 1">
                            <button type="button" @click="nextQuestion()" :disabled="!hasAnsweredCurrent"
                                    class="touch-manipulation flex-1 inline-flex h-10 items-center justify-center gap-1.5 rounded-xl bg-[#e2ca18] text-sm font-bold text-zinc-950 shadow transition hover:bg-[#edd85c] active:scale-[0.98] disabled:opacity-40 disabled:cursor-not-allowed">
                                ቀጣይ
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </template>
                        <template x-if="currentIndex === questions.length - 1">
                            <button type="button" @click="finishQuiz()" :disabled="!hasAnsweredCurrent"
                                    class="touch-manipulation flex-1 inline-flex h-10 items-center justify-center gap-1.5 rounded-xl bg-[#e2ca18] text-sm font-bold text-zinc-950 shadow transition hover:bg-[#edd85c] active:scale-[0.98] disabled:opacity-40 disabled:cursor-not-allowed">
                                ጨርስ
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- ── RESULTS ── --}}
                <div x-show="state === 'results'" x-cloak class="flex flex-col overflow-y-auto">
                    <div class="flex items-center justify-between px-5 pt-4 pb-3 border-b border-white/[0.07] shrink-0 sm:px-7">
                        <h3 class="text-base font-bold text-white">ውጤትዎ</h3>
                        <button type="button"
                                @click="closeModal()"
                                class="rounded-lg p-1.5 text-zinc-400 transition hover:bg-white/10 hover:text-white"
                                aria-label="{{ __('app.fasika_quiz_modal_close_aria') }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="overflow-y-auto flex-1 px-5 py-6 sm:px-7 space-y-5">
                        <div class="flex flex-col items-center text-center">
                            <div class="relative flex h-28 w-28 items-center justify-center rounded-full border-4 border-[#e2ca18]/40 bg-[#e2ca18]/10 shadow-[0_0_40px_-10px_rgba(226,202,24,0.35)]">
                                <div>
                                    <p class="text-3xl font-black text-[#e2ca18]" x-text="results?.score ?? 0"></p>
                                    <p class="text-xs font-semibold text-zinc-400">/<span x-text="results?.total_possible ?? 30"></span></p>
                                </div>
                            </div>
                            <p class="mt-4 text-lg font-bold text-white" x-text="scoreLabel()"></p>
                            <div class="mt-1.5 flex flex-wrap justify-center gap-3 text-sm text-zinc-400">
                                <span><span class="font-semibold text-white" x-text="results?.correct_count"></span> ትክክለኛ / <span x-text="results?.total_questions"></span></span>
                                <span>·</span>
                                <span><span class="font-semibold text-white" x-text="results?.percentage"></span>%</span>
                            </div>
                        </div>
                        <div class="flex gap-3 pt-1">
                            <button type="button" @click="resetQuiz()"
                                    class="touch-manipulation flex-1 inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-[#e2ca18]/30 text-sm font-semibold text-[#e2ca18] transition hover:bg-[#e2ca18]/10 active:scale-[0.98]">
                                {{ __('app.fasika_quiz_try_again_button') }}
                            </button>
                            <button type="button" @click="closeModal()"
                                    class="touch-manipulation flex-1 inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-white/10 text-sm font-semibold text-zinc-300 transition hover:bg-white/[0.06] active:scale-[0.98]">
                                {{ __('app.fasika_quiz_close_button') }}
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    </template>
</div>

@push('scripts')
<script>
function fasikaQuiz(config) {
    return {
        modalOpen: false, state: 'intro', participantName: '', isLoading: false, errorMessage: '',
        token: '', questions: [], currentIndex: 0, selectedOption: null, feedback: null,
        answers: [], answeredMap: {}, score: 0, timeLeft: 600, timerInterval: null, results: null,

        get currentQuestion() { return this.questions[this.currentIndex] ?? null; },
        get hasAnsweredCurrent() { return this.currentQuestion && !!this.answeredMap[this.currentQuestion.id]; },
        get progress() { return this.questions.length ? Math.round((this.currentIndex / this.questions.length) * 100) : 0; },
        get formattedTime() { const m = Math.floor(this.timeLeft / 60), s = this.timeLeft % 60; return m + ':' + String(s).padStart(2, '0'); },

        openModal()  { this.modalOpen = true;  document.body.style.overflow = 'hidden'; },
        closeModal() {
            if (this.timerInterval) { clearInterval(this.timerInterval); this.timerInterval = null; }
            if (this.state === 'playing' || this.state === 'results') { this.resetQuiz(); }
            this.isLoading = false;
            this.modalOpen = false;
            document.body.style.overflow = '';
        },
        cancelQuiz() { this.closeModal(); },
        maybeClose() { this.closeModal(); },

        optionClass(opt) {
            if (!this.feedback) {
                if (this.selectedOption === opt) return 'border-[#e2ca18]/50 bg-[#e2ca18]/10 text-white';
                return 'border-white/10 bg-white/[0.04] text-zinc-200 hover:border-white/20 hover:bg-white/[0.07]';
            }
            if (opt === this.feedback.correct_option) return 'border-green-500/50 bg-green-500/10 text-green-200';
            if (opt === this.selectedOption && !this.feedback.is_correct) return 'border-rose-500/50 bg-rose-500/10 text-rose-200';
            return 'border-white/[0.06] bg-white/[0.02] text-zinc-500';
        },
        optionBadgeClass(opt) {
            if (!this.feedback) {
                if (this.selectedOption === opt) return 'border-[#e2ca18] bg-[#e2ca18]/20 text-[#e2ca18]';
                return 'border-white/20 text-zinc-400';
            }
            if (opt === this.feedback.correct_option) return 'border-green-500 bg-green-500/20 text-green-400';
            if (opt === this.selectedOption && !this.feedback.is_correct) return 'border-rose-500 bg-rose-500/20 text-rose-400';
            return 'border-white/10 text-zinc-600';
        },
        scoreLabel() { return config.thankYouText || ''; },

        async startQuiz() {
            this.isLoading = true; this.errorMessage = '';
            try {
                const res = await fetch(config.questionsUrl, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (!res.ok || !data.questions?.length) { this.errorMessage = 'ጥያቄዎቹን ማምጣት አልተቻለም። ደግሞ ይሞክሩ።'; return; }
                this.token = data.token; this.questions = data.questions; this.state = 'playing'; this.startTimer();
            } catch { this.errorMessage = 'ግንኙነት ስህተት። እባክዎ ደግሞ ይሞክሩ።'; }
            finally { this.isLoading = false; }
        },
        startTimer() {
            this.timerInterval = setInterval(() => {
                if (this.timeLeft <= 0) { clearInterval(this.timerInterval); this.finishQuiz(); return; }
                this.timeLeft--;
            }, 1000);
        },

        async selectOption(opt) {
            if (this.selectedOption !== null) return;
            this.selectedOption = opt;
            const qId = this.currentQuestion.id;
            try {
                const res = await fetch(config.answerUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': config.csrf },
                    body: JSON.stringify({ token: this.token, question_id: qId, selected_option: opt }),
                });
                const data = await res.json();
                this.feedback = data;
                if (data.is_correct) this.score += data.points_earned;
                this.answeredMap[qId] = { selectedOption: opt, feedback: data };
                this.answers.push({ question_id: qId, selected_option: opt, correct_option: data.correct_option, is_correct: data.is_correct, points_earned: data.points_earned });
            } catch {
                const fb = { is_correct: false, correct_option: opt, points_earned: 0 };
                this.feedback = fb; this.answeredMap[qId] = { selectedOption: opt, feedback: fb };
                this.answers.push({ question_id: qId, selected_option: opt, correct_option: opt, is_correct: false, points_earned: 0 });
            }
        },
        nextQuestion() { if (this.currentIndex >= this.questions.length - 1) return; this.currentIndex++; this.restoreAnswerState(); },
        prevQuestion() { if (this.currentIndex <= 0) return; this.currentIndex--; this.restoreAnswerState(); },
        restoreAnswerState() {
            const saved = this.currentQuestion ? this.answeredMap[this.currentQuestion.id] : null;
            if (saved) { this.selectedOption = saved.selectedOption; this.feedback = saved.feedback; }
            else { this.selectedOption = null; this.feedback = null; }
        },

        async finishQuiz() {
            if (this.timerInterval) { clearInterval(this.timerInterval); this.timerInterval = null; }
            try {
                const res = await fetch(config.completeUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': config.csrf },
                    body: JSON.stringify({ token: this.token, name: this.participantName || null, answers: this.answers, time_taken_seconds: 600 - this.timeLeft }),
                });
                this.results = await res.json();
            } catch {
                this.results = { score: this.score, total_possible: 30, percentage: Math.round((this.score / 30) * 100), correct_count: this.answers.filter(a => a.is_correct).length, total_questions: this.answers.length };
            }
            this.state = 'results';
        },
        resetQuiz() {
            if (this.timerInterval) { clearInterval(this.timerInterval); this.timerInterval = null; }
            this.state = 'intro'; this.token = ''; this.questions = []; this.currentIndex = 0;
            this.selectedOption = null; this.feedback = null; this.answers = []; this.answeredMap = {};
            this.score = 0; this.timeLeft = 600; this.results = null; this.errorMessage = '';
        },
    };
}
</script>
@endpush
