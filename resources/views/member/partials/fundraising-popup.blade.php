{{--
  Fundraising Popup — professional modal overlay.
  Locks body scroll, blocks all background interaction.
  Step 1: Video + intro → Step 2: Contact form → Step 3: Thank you
--}}
<div x-data="fundraisingPopup()"
     x-init="init()"
     x-effect="document.body.classList.toggle('fund-body-lock', open)"
     x-cloak>

    {{-- ── Full-screen overlay: backdrop + card in one container ── --}}
    <template x-teleport="body">
        <div x-show="open"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fund-overlay"
             style="display:none;"
             @touchmove.self.prevent>

            {{-- ══════════════════════════════════════════════
                 STEP 1 — Intro
            ══════════════════════════════════════════════ --}}
            <div x-show="step === 1"
                 x-transition:enter="transition ease-out duration-300 delay-75"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="fund-card bg-card shadow-2xl border border-border overflow-hidden
                        rounded-2xl w-full sm:max-w-lg flex flex-col">

                {{-- Scrollable body --}}
                <div class="overflow-y-auto flex-1 overscroll-contain min-h-0"
                     @touchmove.stop>

                    {{-- YouTube embed --}}
                    <template x-if="campaign.embed_url">
                        <div class="fund-video relative w-full bg-black shrink-0">
                            <iframe :src="campaign.embed_url"
                                    class="absolute inset-0 w-full h-full"
                                    frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen
                                    loading="lazy">
                            </iframe>
                        </div>
                    </template>

                    {{-- Text --}}
                    <div class="px-5 sm:px-6 pt-3 pb-5">
                        <h2 class="text-base sm:text-lg font-extrabold leading-snug mb-2 text-[#e2ca18]"
                            x-text="campaign.title"></h2>
                        <p class="text-[13px] sm:text-sm text-secondary leading-relaxed"
                           x-text="campaign.description"></p>
                    </div>
                </div>

                {{-- Buttons --}}
                <div class="px-5 sm:px-6 pb-4 pt-4 space-y-1.5 shrink-0 border-t border-border/50 fund-safe-bottom">
                    <button @click="step = 2"
                            class="fund-cta-btn w-full py-2.5 sm:py-3 font-bold text-sm rounded-2xl active:scale-[0.97] shadow-lg relative overflow-hidden"
                            style="background:#0a6286;color:#fff">
                        <span class="fund-cta-shimmer absolute inset-0 rounded-2xl pointer-events-none"></span>
                        <span class="relative">{{ __('app.fundraising_popup_interested') }}</span>
                    </button>
                    <button @click="notToday()"
                            class="w-full py-2 text-sm text-muted-text font-medium rounded-2xl hover:bg-muted active:scale-[0.97] transition">
                        {{ __('app.fundraising_popup_not_today') }}
                    </button>
                </div>
            </div>

            {{-- ══════════════════════════════════════════════
                 STEP 2 — Contact form
            ══════════════════════════════════════════════ --}}
            <div x-show="step === 2"
                 x-transition:enter="transition ease-out duration-250"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="fund-card bg-card rounded-2xl shadow-2xl border border-border
                        w-full sm:max-w-lg flex flex-col overflow-hidden">

                <div class="h-1 w-full shrink-0" style="background:linear-gradient(90deg,#0a6286,#e2ca18)"></div>

                <div class="flex items-center justify-between px-5 sm:px-6 pt-4 pb-2 shrink-0">
                    <button @click="step = 1"
                            class="flex items-center gap-1 text-sm text-muted-text hover:text-primary transition active:scale-95 -ml-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        {{ __('app.back') }}
                    </button>
                    <span class="text-xs text-muted-text font-medium px-2.5 py-1 rounded-full bg-muted">1 / 2</span>
                </div>

                <div class="flex-1 overflow-y-auto overscroll-contain px-5 sm:px-6 min-h-0"
                     @touchmove.stop>
                    <div class="flex flex-col justify-center min-h-full py-4">
                        <h2 class="text-lg sm:text-xl font-bold text-primary mb-1.5">{{ __('app.fundraising_form_title') }}</h2>
                        <p class="text-[13px] sm:text-sm text-muted-text leading-relaxed mb-6 sm:mb-8">{{ __('app.fundraising_form_desc') }}</p>

                        <div class="space-y-4 sm:space-y-5">
                            <div>
                                <label class="block text-sm font-medium text-primary mb-1.5">{{ __('app.name') }}</label>
                                <input type="text" x-model="form.name"
                                       class="w-full px-4 py-3 sm:py-3.5 rounded-xl sm:rounded-2xl border bg-surface text-primary text-sm
                                              focus:outline-none focus:ring-2 transition placeholder-muted-text"
                                       :class="errors.name
                                           ? 'border-red-400 focus:ring-red-300/40'
                                           : 'border-border focus:ring-[#0a6286]/40 focus:border-[#0a6286]'"
                                       placeholder="{{ __('app.fundraising_name_placeholder') }}"
                                       @keyup.enter="$refs.phoneInput.focus()">
                                <p x-show="errors.name" x-text="errors.name" class="mt-1 text-xs text-red-500"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-primary mb-1.5">{{ __('app.phone') }}</label>
                                <input type="tel" x-model="form.phone" x-ref="phoneInput"
                                       inputmode="tel" autocomplete="tel"
                                       class="w-full px-4 py-3 sm:py-3.5 rounded-xl sm:rounded-2xl border bg-surface text-primary text-sm
                                              focus:outline-none focus:ring-2 transition placeholder-muted-text"
                                       :class="errors.phone
                                           ? 'border-red-400 focus:ring-red-300/40'
                                           : 'border-border focus:ring-[#0a6286]/40 focus:border-[#0a6286]'"
                                       placeholder="{{ __('app.fundraising_phone_placeholder') }}"
                                       @keyup.enter="submitInterest()">
                                <p x-show="errors.phone" x-text="errors.phone" class="mt-1 text-xs text-red-500"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-5 sm:px-6 pt-3 shrink-0 fund-safe-bottom">
                    <button @click="submitInterest()"
                            :disabled="submitting"
                            class="w-full py-3.5 sm:py-4 font-bold text-sm text-white rounded-2xl transition active:scale-[0.97]
                                   disabled:opacity-60 disabled:cursor-not-allowed shadow-lg"
                            style="background:#0a6286">
                        <span x-show="!submitting">{{ __('app.fundraising_submit') }}</span>
                        <span x-show="submitting" class="flex items-center justify-center gap-2">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            {{ __('app.saving') }}...
                        </span>
                    </button>
                </div>
            </div>

            {{-- ══════════════════════════════════════════════
                 STEP 3 — Thank you
            ══════════════════════════════════════════════ --}}
            <div x-show="step === 3"
                 x-transition:enter="transition ease-out duration-250"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="fund-card bg-card rounded-2xl shadow-2xl border border-border
                        w-full sm:max-w-lg flex flex-col overflow-hidden">

                <div class="h-1 w-full shrink-0" style="background:linear-gradient(90deg,#0a6286,#e2ca18)"></div>

                <div class="flex-1 overflow-y-auto overscroll-contain px-5 sm:px-6 min-h-0"
                     @touchmove.stop>
                    <div class="flex flex-col items-center justify-center text-center min-h-full py-8 sm:py-10">
                        <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full flex items-center justify-center mx-auto mb-5 sm:mb-6 shadow-lg"
                             style="background:linear-gradient(135deg,#0a6286,#0d7aa3)">
                            <svg class="w-8 h-8 sm:w-10 sm:h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <h2 class="text-lg sm:text-xl font-extrabold mb-2 text-[#0a6286] dark:text-[#e2ca18]">
                            {{ __('app.fundraising_thankyou_title') }}
                        </h2>
                        <p class="text-[13px] sm:text-sm text-secondary leading-relaxed max-w-[280px] sm:max-w-xs mx-auto">
                            {{ __('app.fundraising_thankyou_desc') }}
                        </p>
                    </div>
                </div>

                <div class="px-5 sm:px-6 pt-2 space-y-2.5 shrink-0 fund-safe-bottom">
                    <a :href="campaign.donate_url" target="_blank" rel="noopener"
                       class="flex items-center justify-center gap-2 w-full py-3.5 sm:py-4 font-bold text-sm text-white
                              rounded-2xl transition active:scale-[0.97] shadow-lg"
                       style="background:#0a6286">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                        {{ __('app.fundraising_view_donate_page') }}
                    </a>
                    <button @click="shareLink()"
                            class="flex items-center justify-center gap-2 w-full py-3 sm:py-3.5 font-semibold text-sm text-primary
                                   rounded-2xl border border-border hover:bg-muted active:scale-[0.97] transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                        </svg>
                        <span x-text="shareCopied ? '{{ __('app.link_copied') }}' : '{{ __('app.fundraising_share') }}'"></span>
                    </button>
                    <button @click="open = false"
                            class="w-full py-2 text-sm text-muted-text hover:text-primary transition">
                        {{ __('app.close') }}
                    </button>
                </div>
            </div>

        </div>
    </template>

</div>

@push('scripts')
<style>
/* ── Body scroll lock when modal is open ── */
body.fund-body-lock {
    overflow: hidden !important;
    position: fixed !important;
    inset: 0 !important;
    width: 100% !important;
}

/* ── Full-screen overlay — above everything ── */
.fund-overlay {
    position: fixed;
    inset: 0;
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    background: rgba(0, 0, 0, 0.80);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    overscroll-behavior: contain;
    touch-action: none;
}

/* ── Card sizing ── */
.fund-card {
    max-height: calc(100vh - 2rem);
    max-height: calc(100dvh - 2rem);
}
@media (min-width: 640px) {
    .fund-card {
        max-height: 88vh;
        max-height: 88dvh;
    }
}

/* ── Video: 16:9 ── */
.fund-video {
    aspect-ratio: 16 / 9;
}

/* ── Safe area bottom padding ── */
.fund-safe-bottom {
    padding-bottom: max(1rem, env(safe-area-inset-bottom));
}

/* ── CTA pulse ── */
@keyframes fund-pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(10,98,134,0.55), 0 4px 14px rgba(10,98,134,0.30); }
    50%       { box-shadow: 0 0 0 10px rgba(10,98,134,0), 0 4px 14px rgba(10,98,134,0.10); }
}
.fund-cta-btn {
    animation: fund-pulse 2.2s ease-in-out infinite;
    transition: transform 0.1s, box-shadow 0.1s;
}
.fund-cta-btn:active { animation: none; }

@keyframes fund-shimmer {
    0%   { transform: translateX(-120%) skewX(-20deg); opacity: 0; }
    15%  { opacity: 0.30; }
    85%  { opacity: 0.30; }
    100% { transform: translateX(220%) skewX(-20deg); opacity: 0; }
}
.fund-cta-shimmer {
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.45), transparent);
    animation: fund-shimmer 2.8s ease-in-out infinite;
}
</style>
<script>
function fundraisingPopup() {
    const scrollY = { value: 0 };
    return {
        open: false,
        step: 1,
        campaign: {},
        form: { name: '', phone: '' },
        errors: {},
        submitting: false,
        shareCopied: false,

        async init() {
            this.$watch('open', (val) => {
                if (val) {
                    scrollY.value = window.scrollY;
                    document.body.style.top = `-${scrollY.value}px`;
                } else {
                    document.body.style.top = '';
                    window.scrollTo(0, scrollY.value);
                }
            });
            await new Promise(r => setTimeout(r, 1800));
            try {
                const data = await AbiyTsom.get('/api/member/fundraising/popup');
                if (data.show) {
                    this.campaign = data;
                    this.open = true;
                }
            } catch (e) { /* non-critical */ }
        },

        async notToday() {
            this.open = false;
            try {
                await AbiyTsom.api('/api/member/fundraising/snooze', {
                    campaign_id: this.campaign.campaign_id,
                });
            } catch (e) { /* best-effort */ }
        },

        isValidUkPhone(phone) {
            const digits = phone.replace(/\D/g, '');
            let national = digits;
            if (digits.length === 12 && digits.startsWith('44')) {
                national = digits.slice(2);
            } else if (digits.length === 11 && digits.startsWith('0')) {
                national = digits.slice(1);
            }
            return national.length === 10 && /^[1237]/.test(national);
        },

        validate() {
            this.errors = {};
            if (!this.form.name.trim()) {
                this.errors.name = '{{ __('app.fundraising_name_required') }}';
            }
            if (!this.form.phone.trim()) {
                this.errors.phone = '{{ __('app.fundraising_phone_required') }}';
            } else if (!this.isValidUkPhone(this.form.phone)) {
                this.errors.phone = '{{ __('app.fundraising_phone_invalid_uk') }}';
            }
            return Object.keys(this.errors).length === 0;
        },

        async submitInterest() {
            if (!this.validate()) return;
            this.submitting = true;
            try {
                const res = await AbiyTsom.api('/api/member/fundraising/interested', {
                    campaign_id:   this.campaign.campaign_id,
                    contact_name:  this.form.name.trim(),
                    contact_phone: this.form.phone.trim(),
                });
                if (res.errors) {
                    if (res.errors.contact_name) this.errors.name = res.errors.contact_name[0];
                    if (res.errors.contact_phone) this.errors.phone = res.errors.contact_phone[0];
                    return;
                }
                if (res.success) {
                    if (res.donate_url) this.campaign.donate_url = res.donate_url;
                    this.step = 3;
                }
            } catch (e) {
                this.errors.phone = '{{ __('app.error_try_again') }}';
            } finally {
                this.submitting = false;
            }
        },

        async shareLink() {
            const url = this.campaign.donate_url || 'https://donate.abuneteklehaymanot.org/';
            if (navigator.share) {
                try {
                    await navigator.share({
                        title: '{{ __('app.fundraising_share_title') }}',
                        text:  '{{ __('app.fundraising_share_text') }}',
                        url,
                    });
                } catch (e) { /* user cancelled */ }
            } else {
                try {
                    await navigator.clipboard.writeText(url);
                    this.shareCopied = true;
                    setTimeout(() => { this.shareCopied = false; }, 2500);
                } catch (e) { /* fallback */ }
            }
        },
    };
}
</script>
@endpush
