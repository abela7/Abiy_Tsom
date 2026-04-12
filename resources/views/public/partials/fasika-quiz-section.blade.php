{{-- Fasika Quiz Section: interactive multiple-choice quiz --}}
<section class="relative mx-auto w-full max-w-md rounded-2xl border border-[#e2ca18]/[0.22] bg-gradient-to-br from-amber-950/55 via-[#1a1210]/78 to-[#0a2832]/72 shadow-[0_16px_36px_-12px_rgba(30,16,8,0.42)] ring-1 ring-inset ring-[#f5d060]/[0.06] backdrop-blur-[3px] backdrop-saturate-125 overflow-hidden sm:max-w-lg"
         aria-labelledby="fq-heading"
         x-data="fasikaQuiz({
             questionsUrl: @js(route('public.yefasika-beal.quiz.questions')),
             answerUrl:    @js(route('public.yefasika-beal.quiz.answer')),
             completeUrl:  @js(route('public.yefasika-beal.quiz.complete')),
             csrf:         @js(csrf_token()),
         })">

    {{-- Top gold line --}}
    <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-[#e2ca18]/50 to-transparent"></div>

    {{-- ══════════════════════════════════════════
         STATE: intro
    ══════════════════════════════════════════ --}}
    <div x-show="state === 'intro'" x-cloak class="px-5 py-6 sm:px-7 sm:py-7">
        <h2 id="fq-heading" class="text-base font-extrabold leading-snug text-[#e2ca18] sm:text-lg">
            ስለ ፋሲካ በዓል ያለወትን እውቀት ይፈትሹ
        </h2>
        <p class="mt-2 text-sm leading-relaxed text-zinc-300/80 sm:text-[0.9375rem]">
            15 ከቀላል እስከ ከባድ ጥያቄዎች አሉ፣ እርስዎ ስንቱን ይመልሳሉ? ይሞክሩት
        </p>

        <div class="mt-5">
            <label for="fq-name" class="sr-only">ስምዎ</label>
            <input id="fq-name"
                   x-model.trim="participantName"
                   type="text"
                   maxlength="120"
                   autocomplete="name"
                   class="h-11 w-full rounded-xl border border-white/10 bg-zinc-900/70 px-4 text-center text-sm text-white shadow-inner outline-none transition placeholder:text-zinc-500 focus:border-[#e2ca18]/40 focus:ring-2 focus:ring-[#e2ca18]/20"
                   placeholder="ስምዎን ያስገቡ (አማራጭ)">
        </div>

        <button type="button"
                @click="startQuiz()"
                :disabled="isLoading"
                class="mt-4 touch-manipulation inline-flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-[#e2ca18] text-sm font-bold tracking-wide text-zinc-950 shadow-[0_8px_24px_-6px_rgba(226,202,24,0.4)] transition hover:bg-[#edd85c] disabled:cursor-not-allowed disabled:opacity-60 active:scale-[0.98]">
            <span x-show="!isLoading">ጀምር</span>
            <span x-show="isLoading" x-cloak>በመጫን ላይ...</span>
        </button>

        <p x-show="errorMessage" x-cloak class="mt-2 text-center text-xs font-medium text-rose-300" x-text="errorMessage"></p>
    </div>

    {{-- ══════════════════════════════════════════
         STATE: playing
    ══════════════════════════════════════════ --}}
    <div x-show="state === 'playing'" x-cloak class="flex flex-col">

        {{-- Header bar: timer + progress --}}
        <div class="flex items-center justify-between gap-4 border-b border-white/[0.07] px-5 py-3 sm:px-7">
            {{-- Timer --}}
            <div class="flex items-center gap-1.5"
                 :class="timeLeft <= 60 ? 'text-rose-400' : 'text-[#e2ca18]'">
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="font-mono text-sm font-bold tabular-nums" x-text="formattedTime"></span>
            </div>

            {{-- Question counter --}}
            <span class="text-xs font-semibold text-zinc-400">
                <span x-text="currentIndex + 1"></span>/<span x-text="questions.length"></span>
            </span>

            {{-- Progress bar --}}
            <div class="flex-1 h-1.5 rounded-full bg-white/10 overflow-hidden max-w-[120px]">
                <div class="h-full rounded-full bg-[#e2ca18] transition-all duration-500"
                     :style="`width:${progress}%`"></div>
            </div>
        </div>

        {{-- Difficulty badge --}}
        <div class="px-5 pt-4 sm:px-7" x-show="currentQuestion">
            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                  :class="{
                      'bg-green-500/15 text-green-400 ring-1 ring-green-500/20': currentQuestion?.difficulty === 'easy',
                      'bg-yellow-500/15 text-yellow-400 ring-1 ring-yellow-500/20': currentQuestion?.difficulty === 'medium',
                      'bg-red-500/15 text-red-400 ring-1 ring-red-500/20': currentQuestion?.difficulty === 'hard',
                  }"
                  x-text="{easy:'ቀላል · 1pt', medium:'መካከለኛ · 2pt', hard:'ከባድ · 3pt'}[currentQuestion?.difficulty] ?? ''">
            </span>
        </div>

        {{-- Question text --}}
        <div class="px-5 pt-3 pb-4 sm:px-7" x-show="currentQuestion">
            <p class="text-[0.9375rem] font-semibold leading-[1.7] text-white sm:text-base"
               x-text="currentQuestion?.question"></p>
        </div>

        {{-- Options --}}
        <div class="space-y-2.5 px-5 pb-5 sm:px-7" x-show="currentQuestion">
            <template x-for="opt in ['a','b','c','d']" :key="opt">
                <button type="button"
                        @click="selectOption(opt)"
                        :disabled="selectedOption !== null"
                        class="touch-manipulation group relative w-full rounded-xl border px-4 py-3.5 text-left text-sm font-medium leading-snug transition-all duration-200 active:scale-[0.98] disabled:cursor-default"
                        :class="optionClass(opt)">
                    <span class="flex items-start gap-3">
                        <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full border text-[10px] font-bold transition-colors duration-200"
                              :class="optionBadgeClass(opt)"
                              x-text="opt.toUpperCase()"></span>
                        <span x-text="currentQuestion?.['option_' + opt]"></span>
                    </span>

                    {{-- Correct checkmark --}}
                    <span x-show="feedback && opt === feedback.correct_option"
                          class="absolute right-3 top-1/2 -translate-y-1/2 text-green-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                    </span>
                </button>
            </template>
        </div>

        {{-- Feedback banner --}}
        <div x-show="feedback" x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="mx-5 mb-5 rounded-xl px-4 py-3 text-sm font-semibold text-center sm:mx-7"
             :class="feedback?.is_correct ? 'bg-green-500/15 text-green-300 ring-1 ring-green-500/20' : 'bg-rose-500/15 text-rose-300 ring-1 ring-rose-500/20'">
            <span x-text="feedback?.is_correct ? 'በትክክል ተመልሷል! ✓' : 'አልተመለሰም! ✗'"></span>
        </div>
    </div>

    {{-- ══════════════════════════════════════════
         STATE: results
    ══════════════════════════════════════════ --}}
    <div x-show="state === 'results'" x-cloak class="px-5 py-6 sm:px-7 sm:py-8">

        {{-- Score circle --}}
        <div class="flex flex-col items-center text-center">
            <div class="relative flex h-28 w-28 items-center justify-center rounded-full border-4 border-[#e2ca18]/40 bg-[#e2ca18]/10 shadow-[0_0_40px_-10px_rgba(226,202,24,0.35)]">
                <div>
                    <p class="text-3xl font-black text-[#e2ca18]" x-text="results?.score ?? 0"></p>
                    <p class="text-xs font-semibold text-zinc-400">/<span x-text="results?.total_possible ?? 30"></span></p>
                </div>
            </div>

            <p class="mt-4 text-lg font-bold text-white" x-text="scoreLabel()"></p>

            <div class="mt-2 flex flex-wrap justify-center gap-3 text-sm text-zinc-400">
                <span><span class="font-semibold text-white" x-text="results?.correct_count"></span> ትክክለኛ / <span x-text="results?.total_questions"></span> ጥያቄ</span>
                <span>·</span>
                <span><span class="font-semibold text-white" x-text="results?.percentage"></span>%</span>
            </div>
        </div>

        {{-- Leaderboard --}}
        <div class="mt-6" x-show="results?.leaderboard?.length">
            <h3 class="mb-3 text-sm font-extrabold text-[#e2ca18]">ምርጥ 10 ተወዳዳሪዎች</h3>
            <div class="space-y-1.5">
                <template x-for="(entry, i) in results?.leaderboard ?? []" :key="i">
                    <div class="flex items-center justify-between rounded-lg px-3 py-2 text-sm"
                         :class="entry.participant_name === participantName && participantName ? 'bg-[#e2ca18]/10 ring-1 ring-[#e2ca18]/30' : 'bg-white/[0.03]'">
                        <span class="flex items-center gap-2">
                            <span class="w-5 text-center font-bold"
                                  :class="i === 0 ? 'text-yellow-400' : i === 1 ? 'text-zinc-300' : i === 2 ? 'text-amber-600' : 'text-zinc-500'"
                                  x-text="i + 1"></span>
                            <span class="font-medium text-white" x-text="entry.participant_name"></span>
                        </span>
                        <span class="font-bold text-[#e2ca18]" x-text="entry.score + '/' + entry.total_possible"></span>
                    </div>
                </template>
            </div>
        </div>

        {{-- Play again --}}
        <button type="button"
                @click="resetQuiz()"
                class="mt-6 touch-manipulation inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl border border-[#e2ca18]/30 bg-transparent text-sm font-semibold text-[#e2ca18] transition hover:bg-[#e2ca18]/10 active:scale-[0.98]">
            ዳግም ይሞክሩ
        </button>
    </div>

    {{-- Bottom gold line --}}
    <div class="pointer-events-none absolute inset-x-0 bottom-0 h-px bg-gradient-to-r from-transparent via-[#e2ca18]/25 to-transparent"></div>
</section>

@push('scripts')
<script>
function fasikaQuiz(config) {
    return {
        state: 'intro',
        participantName: '',
        isLoading: false,
        errorMessage: '',

        token: '',
        questions: [],
        currentIndex: 0,
        selectedOption: null,
        feedback: null,
        answers: [],
        score: 0,

        timeLeft: 600,
        timerInterval: null,

        results: null,

        get currentQuestion() {
            return this.questions[this.currentIndex] ?? null;
        },
        get progress() {
            if (!this.questions.length) return 0;
            return Math.round((this.currentIndex / this.questions.length) * 100);
        },
        get formattedTime() {
            const m = Math.floor(this.timeLeft / 60);
            const s = this.timeLeft % 60;
            return m + ':' + String(s).padStart(2, '0');
        },

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
                return 'border-white/20 text-zinc-400 group-hover:border-white/40';
            }
            if (opt === this.feedback.correct_option) return 'border-green-500 bg-green-500/20 text-green-400';
            if (opt === this.selectedOption && !this.feedback.is_correct) return 'border-rose-500 bg-rose-500/20 text-rose-400';
            return 'border-white/10 text-zinc-600';
        },

        scoreLabel() {
            const pct = this.results?.percentage ?? 0;
            if (pct === 100) return 'አስደናቂ! ሙሉ ነጥብ! 🏆';
            if (pct >= 80)  return 'እጅግ ጥሩ! 🌟';
            if (pct >= 60)  return 'ጥሩ ሙከራ! 👍';
            if (pct >= 40)  return 'ቀጥሉ! ይሞክሩ! 💪';
            return 'ዳግም ይሞክሩ! 📖';
        },

        async startQuiz() {
            this.isLoading = true;
            this.errorMessage = '';

            try {
                const res = await fetch(config.questionsUrl, {
                    headers: { 'Accept': 'application/json' },
                });
                const data = await res.json();

                if (!res.ok || !data.questions?.length) {
                    this.errorMessage = 'ጥያቄዎቹን ማምጣት አልተቻለም። ደግሞ ይሞክሩ።';
                    return;
                }

                this.token     = data.token;
                this.questions = data.questions;
                this.state     = 'playing';
                this.startTimer();
            } catch {
                this.errorMessage = 'ግንኙነት ስህተት። እባክዎ ደግሞ ይሞክሩ።';
            } finally {
                this.isLoading = false;
            }
        },

        startTimer() {
            this.timerInterval = setInterval(() => {
                if (this.timeLeft <= 0) {
                    clearInterval(this.timerInterval);
                    this.finishQuiz();
                    return;
                }
                this.timeLeft--;
            }, 1000);
        },

        async selectOption(opt) {
            if (this.selectedOption !== null) return;
            this.selectedOption = opt;

            try {
                const res = await fetch(config.answerUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': config.csrf,
                    },
                    body: JSON.stringify({
                        token: this.token,
                        question_id: this.currentQuestion.id,
                        selected_option: opt,
                    }),
                });

                const data = await res.json();
                this.feedback = data;

                if (data.is_correct) this.score += data.points_earned;

                this.answers.push({
                    question_id:     this.currentQuestion.id,
                    selected_option: opt,
                    correct_option:  data.correct_option,
                    is_correct:      data.is_correct,
                    points_earned:   data.points_earned,
                });
            } catch {
                // Still advance even on network error
                this.answers.push({
                    question_id:     this.currentQuestion.id,
                    selected_option: opt,
                    correct_option:  opt,
                    is_correct:      false,
                    points_earned:   0,
                });
            }

            setTimeout(() => this.nextQuestion(), 1800);
        },

        nextQuestion() {
            if (this.currentIndex >= this.questions.length - 1) {
                this.finishQuiz();
            } else {
                this.currentIndex++;
                this.selectedOption = null;
                this.feedback = null;
            }
        },

        async finishQuiz() {
            clearInterval(this.timerInterval);
            const timeTaken = 600 - this.timeLeft;

            try {
                const res = await fetch(config.completeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': config.csrf,
                    },
                    body: JSON.stringify({
                        token:               this.token,
                        name:                this.participantName || null,
                        answers:             this.answers,
                        time_taken_seconds:  timeTaken,
                    }),
                });

                this.results = await res.json();
            } catch {
                this.results = {
                    score:          this.score,
                    total_possible: 30,
                    percentage:     Math.round((this.score / 30) * 100),
                    correct_count:  this.answers.filter(a => a.is_correct).length,
                    total_questions: this.answers.length,
                    leaderboard:    [],
                };
            }

            this.state = 'results';
        },

        resetQuiz() {
            clearInterval(this.timerInterval);
            this.state           = 'intro';
            this.token           = '';
            this.questions       = [];
            this.currentIndex    = 0;
            this.selectedOption  = null;
            this.feedback        = null;
            this.answers         = [];
            this.score           = 0;
            this.timeLeft        = 600;
            this.results         = null;
            this.errorMessage    = '';
        },
    };
}
</script>
@endpush
