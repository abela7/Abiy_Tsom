{{--
  Fundraising Popup — professional modal overlay.
  Always light theme. Locks body scroll, blocks all background interaction.
  Step 1: Video + intro → Step 2: Contact form → Step 3: Thank you
--}}
<div x-data="fundraisingPopup()"
     x-init="init()"
     x-effect="document.body.classList.toggle('fund-body-lock', open)"
     x-cloak>

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

            {{-- ═══ STEP 1 — Intro ═══ --}}
            <div x-show="step === 1"
                 class="fund-step fund-card overflow-hidden rounded-2xl w-full flex flex-col"
                 style="background:#ffffff;color:#111827;border:1px solid #e5e7eb;box-shadow:0 25px 50px -12px rgba(0,0,0,.25)">

                <div class="overflow-y-auto flex-1 overscroll-contain min-h-0" @touchmove.stop>
                    <template x-if="campaign.embed_url">
                        <div class="fund-video relative w-full bg-black shrink-0">
                            {{-- Thumbnail facade: clean thumbnail + custom play button, no YouTube UI --}}
                            <template x-if="!videoPlaying">
                                <div class="absolute inset-0 cursor-pointer group" @click="videoPlaying = true">
                                    <img :src="thumbnailUrl"
                                         class="w-full h-full object-cover"
                                         @error="$el.src = 'https://img.youtube.com/vi/' + videoId + '/hqdefault.jpg'">
                                    <div class="absolute inset-0 bg-black/25 group-hover:bg-black/15 transition-colors"></div>
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <div class="w-16 h-16 rounded-full flex items-center justify-center shadow-2xl transition-transform group-hover:scale-110 group-active:scale-95"
                                             style="background:#FF0000">
                                            <svg class="w-7 h-7 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M8 5v14l11-7z"/>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            {{-- Actual iframe: only inserted into DOM when user presses play --}}
                            <template x-if="videoPlaying">
                                <iframe :src="autoplayUrl"
                                        class="absolute inset-0 w-full h-full"
                                        frameborder="0"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                        allowfullscreen></iframe>
                            </template>
                        </div>
                    </template>
                    <div class="px-5 sm:px-6 pt-3 pb-5">
                        <h2 class="text-base sm:text-lg font-extrabold leading-snug mb-2"
                            style="color:#0a6286"
                            x-text="campaign.title"></h2>
                        <p class="text-[13px] sm:text-sm leading-relaxed"
                           style="color:#4b5563"
                           x-text="campaign.description"></p>
                    </div>
                </div>

                <div class="px-5 sm:px-6 pb-4 pt-4 space-y-1.5 shrink-0 fund-safe-bottom"
                     style="border-top:1px solid #e5e7eb">
                    <button @click="step = 2"
                            class="fund-cta-btn w-full py-2.5 sm:py-3 font-bold text-sm rounded-2xl active:scale-[0.97] shadow-lg relative overflow-hidden"
                            style="background:#0a6286;color:#fff">
                        <span class="fund-cta-shimmer absolute inset-0 rounded-2xl pointer-events-none"></span>
                        <span class="relative">{{ __('app.fundraising_popup_interested') }}</span>
                    </button>
                    <button @click="skipCountdown === 0 && notToday()"
                            :disabled="skipCountdown > 0"
                            :class="skipCountdown > 0 ? 'opacity-40 cursor-not-allowed' : 'active:scale-[0.97]'"
                            class="w-full py-2 text-sm font-medium rounded-2xl transition"
                            style="color:#6b7280">
                        <span x-show="skipCountdown > 0"
                              x-text="'{{ __('app.fundraising_popup_not_today') }}' + ' (' + skipCountdown + ')'"></span>
                        <span x-show="skipCountdown === 0">{{ __('app.fundraising_popup_not_today') }}</span>
                    </button>
                </div>
            </div>

            {{-- ═══ STEP 2 — Contact form ═══ --}}
            <div x-show="step === 2"
                 class="fund-step fund-card rounded-2xl w-full flex flex-col overflow-hidden"
                 style="background:#ffffff;color:#111827;border:1px solid #e5e7eb;box-shadow:0 25px 50px -12px rgba(0,0,0,.25)">

                <div class="h-1 w-full shrink-0" style="background:linear-gradient(90deg,#0a6286,#e2ca18)"></div>

                <div class="flex items-center justify-between px-5 sm:px-6 pt-4 pb-2 shrink-0">
                    <button @click="step = 1"
                            class="flex items-center gap-1 text-sm transition active:scale-95 -ml-1"
                            style="color:#6b7280">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        {{ __('app.back') }}
                    </button>
                    <span class="text-xs font-medium px-2.5 py-1 rounded-full"
                          style="background:#f3f4f6;color:#6b7280">1 / 2</span>
                </div>

                <div class="flex-1 overflow-y-auto overscroll-contain px-5 sm:px-6 min-h-0" @touchmove.stop>
                    <div class="flex flex-col justify-center min-h-full py-4">
                        <h2 class="text-lg sm:text-xl font-bold mb-1.5" style="color:#111827">{{ __('app.fundraising_form_title') }}</h2>
                        <p class="text-[13px] sm:text-sm leading-relaxed mb-6 sm:mb-8" style="color:#6b7280">{{ __('app.fundraising_form_desc') }}</p>

                        <div class="space-y-4 sm:space-y-5">
                            <div>
                                <label class="block text-sm font-medium mb-1.5" style="color:#111827">{{ __('app.name') }}</label>
                                <input type="text" x-model="form.name"
                                       class="w-full px-4 py-3 sm:py-3.5 rounded-xl sm:rounded-2xl text-sm focus:outline-none focus:ring-2 transition"
                                       :style="'background:#fff;color:#111827;border:2px solid ' + (errors.name ? '#f87171' : '#94a3b8')"
                                       placeholder="{{ __('app.fundraising_name_placeholder') }}"
                                       @keyup.enter="$refs.phoneInput.focus()">
                                <p x-show="errors.name" x-text="errors.name" class="mt-1 text-xs text-red-500"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1.5" style="color:#111827">{{ __('app.phone') }}</label>
                                <input type="tel" x-model="form.phone" x-ref="phoneInput"
                                       inputmode="tel" autocomplete="tel"
                                       class="w-full px-4 py-3 sm:py-3.5 rounded-xl sm:rounded-2xl text-sm focus:outline-none focus:ring-2 transition"
                                       :style="'background:#fff;color:#111827;border:2px solid ' + (errors.phone ? '#f87171' : '#94a3b8')"
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
                            class="w-full py-3.5 sm:py-4 font-bold text-sm text-white rounded-2xl transition active:scale-[0.97] disabled:opacity-60 disabled:cursor-not-allowed shadow-lg"
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

            {{-- ═══ STEP 3 — Thank you ═══ --}}
            <div x-show="step === 3"
                 class="fund-step fund-card rounded-2xl w-full flex flex-col overflow-hidden"
                 style="background:#ffffff;color:#111827;border:1px solid #e5e7eb;box-shadow:0 25px 50px -12px rgba(0,0,0,.25)">

                <div class="h-1 w-full shrink-0" style="background:linear-gradient(90deg,#0a6286,#e2ca18)"></div>

                <div class="flex-1 overflow-y-auto overscroll-contain px-5 sm:px-6 min-h-0" @touchmove.stop>
                    <div class="flex flex-col items-center justify-center text-center min-h-full py-8 sm:py-10">
                        <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full flex items-center justify-center mx-auto mb-5 sm:mb-6 shadow-lg"
                             style="background:linear-gradient(135deg,#0a6286,#0d7aa3)">
                            <svg class="w-8 h-8 sm:w-10 sm:h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <h2 class="text-lg sm:text-xl font-extrabold mb-2" style="color:#0a6286">
                            {{ __('app.fundraising_thankyou_title') }}
                        </h2>
                        <p class="text-[13px] sm:text-sm leading-relaxed max-w-[280px] sm:max-w-xs mx-auto" style="color:#4b5563">
                            {{ __('app.fundraising_thankyou_desc') }}
                        </p>
                    </div>
                </div>

                <div class="px-5 sm:px-6 pt-2 space-y-2.5 shrink-0 fund-safe-bottom">
                    <a :href="campaign.donate_url" target="_blank" rel="noopener"
                       class="flex items-center justify-center gap-2 w-full py-3.5 sm:py-4 font-bold text-sm text-white rounded-2xl transition active:scale-[0.97] shadow-lg"
                       style="background:#0a6286">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                        {{ __('app.fundraising_view_donate_page') }}
                    </a>
                    <button @click="shareLink()"
                            class="flex items-center justify-center gap-2 w-full py-3 sm:py-3.5 font-semibold text-sm rounded-2xl transition active:scale-[0.97]"
                            style="color:#111827;border:1px solid #e5e7eb">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                        </svg>
                        <span x-text="shareCopied ? '{{ __('app.link_copied') }}' : '{{ __('app.fundraising_share') }}'"></span>
                    </button>
                    <button @click="open = false"
                            class="w-full py-2 text-sm transition"
                            style="color:#6b7280">
                        {{ __('app.close') }}
                    </button>
                </div>
            </div>

        </div>
    </template>

</div>

@push('scripts')
<style>
body.fund-body-lock {
    overflow: hidden !important;
    position: fixed !important;
    inset: 0 !important;
    width: 100% !important;
}
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
.fund-card {
    max-width: 420px;
    max-height: calc(100vh - 2rem);
    max-height: calc(100dvh - 2rem);
}
.fund-step {
    animation: fund-step-in 0.25s ease-out both;
}
@keyframes fund-step-in {
    from { opacity: 0; transform: scale(0.97); }
    to   { opacity: 1; transform: scale(1); }
}
@media (min-width: 640px) {
    .fund-card { max-height: 88vh; max-height: 88dvh; }
}
.fund-video { aspect-ratio: 16 / 9; position: relative; }
.fund-safe-bottom { padding-bottom: max(1rem, env(safe-area-inset-bottom)); }

@keyframes fund-pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(10,98,134,0.55), 0 4px 14px rgba(10,98,134,0.30); }
    50%       { box-shadow: 0 0 0 10px rgba(10,98,134,0), 0 4px 14px rgba(10,98,134,0.10); }
}
.fund-cta-btn { animation: fund-pulse 2.2s ease-in-out infinite; transition: transform 0.1s, box-shadow 0.1s; }
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
        skipCountdown: 0,
        _skipTimer: null,
        videoPlaying: false,

        get videoId() {
            if (!this.campaign.embed_url) return '';
            try {
                const parts = new URL(this.campaign.embed_url).pathname.split('/');
                const idx = parts.indexOf('embed');
                return idx >= 0 ? (parts[idx + 1] ?? '').split('?')[0] : '';
            } catch { return ''; }
        },

        get thumbnailUrl() {
            return this.videoId
                ? `https://img.youtube.com/vi/${this.videoId}/maxresdefault.jpg`
                : '';
        },

        get autoplayUrl() {
            if (!this.campaign.embed_url) return '';
            try {
                const url = new URL(this.campaign.embed_url);
                url.searchParams.set('autoplay', '1');
                url.searchParams.set('rel', '0');
                return url.toString();
            } catch { return this.campaign.embed_url; }
        },

        async init() {
            this.$watch('open', (val, oldVal) => {
                if (val) {
                    scrollY.value = window.scrollY;
                    document.body.style.top = `-${scrollY.value}px`;
                    // Start skip countdown when video popup opens
                    if (this.campaign.embed_url && this.step === 1) {
                        this.skipCountdown = 20;
                        clearInterval(this._skipTimer);
                        this._skipTimer = setInterval(() => {
                            if (this.skipCountdown > 0) this.skipCountdown--;
                            else clearInterval(this._skipTimer);
                        }, 1000);
                    }
                } else {
                    document.body.style.top = '';
                    window.scrollTo(0, scrollY.value);
                    clearInterval(this._skipTimer);
                    this.skipCountdown = 0;
                    this.videoPlaying = false;
                    if (oldVal === true) {
                        window.dispatchEvent(new CustomEvent('fundraising-ready'));
                    }
                }
            });
            await new Promise(r => setTimeout(r, 5000));
            try {
                const data = await AbiyTsom.get('/api/member/fundraising/popup');
                if (data.show) {
                    this.campaign = data;
                    this.open = true;
                    // Start countdown after campaign is loaded (watcher fires before embed_url is set)
                    if (data.embed_url) {
                        this.skipCountdown = 20;
                        clearInterval(this._skipTimer);
                        this._skipTimer = setInterval(() => {
                            if (this.skipCountdown > 0) this.skipCountdown--;
                            else clearInterval(this._skipTimer);
                        }, 1000);
                    }
                } else {
                    window.dispatchEvent(new CustomEvent('fundraising-ready'));
                }
            } catch (e) {
                window.dispatchEvent(new CustomEvent('fundraising-ready'));
            }
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
