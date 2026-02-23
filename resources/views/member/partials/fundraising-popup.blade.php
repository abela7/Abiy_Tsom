{{--
  Fundraising Popup — shown once per day to members until they express interest.
  Loaded lazily via AbiyTsom.get('/api/member/fundraising/popup').
--}}
<div x-data="fundraisingPopup()"
     x-init="init()"
     x-cloak>

    {{-- Backdrop --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[90]"
         style="display:none;">
    </div>

    {{-- Modal --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-250"
         x-transition:enter-start="opacity-0 translate-y-8 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 scale-95"
         class="fixed inset-x-0 bottom-0 z-[100] mx-auto max-w-lg"
         style="display:none;">

        <div class="bg-card rounded-t-3xl shadow-2xl border border-border overflow-hidden max-h-[90vh] overflow-y-auto">

            {{-- Drag handle --}}
            <div class="flex justify-center pt-3 pb-1">
                <div class="w-10 h-1 bg-muted-text/30 rounded-full"></div>
            </div>

            {{-- Step 1: Campaign intro --}}
            <div x-show="step === 1">

                {{-- YouTube embed --}}
                <template x-if="campaign.embed_url">
                    <div class="relative w-full bg-black" style="padding-top:56.25%">
                        <iframe :src="campaign.embed_url"
                                class="absolute inset-0 w-full h-full"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen
                                loading="lazy">
                        </iframe>
                    </div>
                </template>

                <div class="px-5 pt-4 pb-2">
                    {{-- Gold accent bar --}}
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-1 h-5 rounded-full bg-accent-secondary shrink-0"></div>
                        <p class="text-xs font-semibold text-accent-secondary uppercase tracking-wide">{{ __('app.fundraising_popup_badge') }}</p>
                    </div>

                    <h2 class="text-xl font-bold leading-snug mb-2"
                        style="color: #e2ca18;"
                        x-text="campaign.title"></h2>
                    <p class="text-sm text-secondary leading-relaxed" x-text="campaign.description"></p>
                </div>

                <div class="px-5 pb-5 pt-3 space-y-2.5">
                    <button @click="step = 2"
                            class="w-full py-3 bg-accent-secondary text-white font-semibold text-sm rounded-2xl hover:opacity-90 active:scale-95 transition shadow-md">
                        🙏 {{ __('app.fundraising_popup_interested') }}
                    </button>
                    <button @click="notToday()"
                            class="w-full py-2.5 text-sm text-muted-text font-medium rounded-2xl hover:bg-muted active:scale-95 transition">
                        {{ __('app.fundraising_popup_not_today') }}
                    </button>
                </div>
            </div>

            {{-- Step 2: Contact form --}}
            <div x-show="step === 2" class="px-5 pt-4 pb-6">
                <button @click="step = 1" class="flex items-center gap-1 text-muted-text text-sm mb-4 hover:text-primary transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    {{ __('app.back') }}
                </button>

                <h2 class="text-base font-bold text-primary mb-1">{{ __('app.fundraising_form_title') }}</h2>
                <p class="text-sm text-muted-text mb-4">{{ __('app.fundraising_form_desc') }}</p>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-secondary mb-1">{{ __('app.name') }} *</label>
                        <input type="text" x-model="form.name"
                               class="w-full px-3 py-2.5 rounded-xl border bg-surface text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent"
                               :class="errors.name ? 'border-red-400' : 'border-border'"
                               placeholder="{{ __('app.fundraising_name_placeholder') }}"
                               @keyup.enter="$refs.phoneInput.focus()">
                        <p x-show="errors.name" x-text="errors.name" class="mt-1 text-xs text-red-500"></p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-secondary mb-1">{{ __('app.phone') }} *</label>
                        <input type="tel" x-model="form.phone" x-ref="phoneInput"
                               class="w-full px-3 py-2.5 rounded-xl border bg-surface text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent"
                               :class="errors.phone ? 'border-red-400' : 'border-border'"
                               placeholder="{{ __('app.fundraising_phone_placeholder') }}">
                        <p x-show="errors.phone" x-text="errors.phone" class="mt-1 text-xs text-red-500"></p>
                    </div>
                </div>

                <button @click="submitInterest()"
                        :disabled="submitting"
                        class="mt-5 w-full py-3 bg-accent text-on-accent font-semibold text-sm rounded-2xl hover:opacity-90 active:scale-95 transition disabled:opacity-50 disabled:cursor-not-allowed shadow-md">
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

            {{-- Step 3: Thank you --}}
            <div x-show="step === 3" class="px-5 pt-6 pb-8 text-center">
                <div class="w-16 h-16 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h2 class="text-lg font-bold text-primary mb-2">{{ __('app.fundraising_thankyou_title') }}</h2>
                <p class="text-sm text-secondary leading-relaxed mb-6">{{ __('app.fundraising_thankyou_desc') }}</p>

                <div class="space-y-3">
                    <a :href="campaign.donate_url" target="_blank" rel="noopener"
                       class="flex items-center justify-center gap-2 w-full py-3 bg-accent-secondary text-white font-semibold text-sm rounded-2xl hover:opacity-90 active:scale-95 transition shadow-md">
                        🌐 {{ __('app.fundraising_view_donate_page') }}
                    </a>
                    <button @click="shareLink()"
                            class="flex items-center justify-center gap-2 w-full py-2.5 bg-muted text-primary text-sm font-medium rounded-2xl hover:bg-border active:scale-95 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                        </svg>
                        <span x-text="shareCopied ? '{{ __('app.link_copied') }}' : '{{ __('app.fundraising_share') }}'"></span>
                    </button>
                    <button @click="open = false"
                            class="w-full py-2 text-xs text-muted-text hover:text-primary transition">
                        {{ __('app.close') }}
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
function fundraisingPopup() {
    return {
        open: false,
        step: 1,
        campaign: {},
        form: { name: '', phone: '' },
        errors: {},
        submitting: false,
        shareCopied: false,

        async init() {
            // Slight delay so the page settles before loading the popup
            await new Promise(r => setTimeout(r, 1800));
            try {
                const data = await AbiyTsom.get('/api/member/fundraising/popup');
                if (data.show) {
                    this.campaign = data;
                    this.open = true;
                }
            } catch (e) {
                // Silently fail — popup is non-critical
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

        validate() {
            this.errors = {};
            if (!this.form.name.trim()) {
                this.errors.name = '{{ __('app.fundraising_name_required') }}';
            }
            if (!this.form.phone.trim()) {
                this.errors.phone = '{{ __('app.fundraising_phone_required') }}';
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
