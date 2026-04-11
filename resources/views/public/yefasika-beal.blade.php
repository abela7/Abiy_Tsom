@extends('layouts.public-fasika')

@section('title', $pageTitle)

@section('content')
    @include('public.partials.yefasika-beal-background')

    <main class="relative z-10 space-y-5 ybb-page"
          x-data="fasikaGreetingPage({
              initialUrl: @js($shareUrl),
              initialShareText: @js($shareText),
              storeUrl: @js(route('public.yefasika-beal.store')),
              csrf: @js(csrf_token()),
              initialSenderName: '',
          })">
        @include('member.partials.fasika-celebration-banner')

        <section class="rounded-3xl border border-[rgba(245,208,96,0.22)] bg-[rgba(20,10,40,0.55)] px-5 py-6 text-center shadow-[0_25px_60px_rgba(0,0,0,0.28)]">
            <p class="text-base font-semibold leading-relaxed text-white/95">
                {{ __('app.yefasika_beal_short_greeting_line_one') }}
            </p>
            <p class="mt-3 text-lg font-bold text-[#F5D060]">
                {{ __('app.yefasika_beal_short_greeting_line_two') }}
            </p>
            @if($share)
                <p class="mt-4 inline-flex items-center justify-center rounded-full border border-[rgba(245,208,96,0.22)] bg-white/5 px-4 py-2 text-sm font-semibold text-white/85">
                    {{ __('app.yefasika_beal_from_name', ['name' => $share->sender_name]) }}
                </p>
            @endif
        </section>

        <section class="rounded-3xl border border-[rgba(245,208,96,0.22)] bg-[rgba(20,10,40,0.6)] px-5 py-6 shadow-[0_25px_60px_rgba(0,0,0,0.28)]">
            <div class="space-y-4 text-[15px] leading-8 text-white/90">
                <p>{{ __('app.yefasika_beal_long_message_paragraph_one') }}</p>
                <p>{{ __('app.yefasika_beal_long_message_paragraph_two') }}</p>
                <p class="font-bold text-[#F5D060]">{{ __('app.yefasika_beal_long_message_closing') }}</p>
                @if($share)
                    <p class="pt-2 text-base font-semibold text-white">{{ __('app.yefasika_beal_from_name', ['name' => $share->sender_name]) }}</p>
                @endif
            </div>
        </section>

        <section class="rounded-3xl border border-[rgba(245,208,96,0.22)] bg-[rgba(20,10,40,0.58)] px-5 py-6 shadow-[0_25px_60px_rgba(0,0,0,0.28)]">
            <div class="space-y-4">
                <div class="text-center">
                    <h2 class="text-lg font-black text-[#F5D060]">{{ __('app.yefasika_beal_generator_title') }}</h2>
                    <p class="mt-2 text-sm leading-6 text-white/75">{{ __('app.yefasika_beal_generator_help') }}</p>
                </div>

                <div x-show="!composerOpen" x-cloak class="flex justify-center">
                    <button type="button"
                            @click="openComposer()"
                            class="touch-manipulation inline-flex items-center justify-center gap-2 rounded-2xl bg-accent px-5 py-3 text-sm font-bold text-on-accent shadow-lg transition hover:bg-accent-hover active:scale-[0.98]">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ __('app.yefasika_beal_add_name_button') }}
                    </button>
                </div>

                <div x-show="composerOpen" x-cloak class="space-y-4">
                    <div>
                        <label for="fasika-sender-name" class="mb-2 block text-sm font-semibold text-white/85">
                            {{ __('app.yefasika_beal_name_label') }}
                        </label>
                        <input id="fasika-sender-name"
                               x-ref="senderNameInput"
                               x-model.trim="senderName"
                               type="text"
                               maxlength="120"
                               class="w-full rounded-2xl border border-[rgba(245,208,96,0.22)] bg-white/10 px-4 py-3 text-sm text-white outline-none transition placeholder:text-white/40 focus:border-[#F5D060] focus:ring-2 focus:ring-[#F5D060]/20"
                               placeholder="{{ __('app.yefasika_beal_name_placeholder') }}">
                        <p x-show="errorMessage" x-cloak class="mt-2 text-sm font-medium text-rose-300" x-text="errorMessage"></p>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <button type="button"
                                @click="createPersonalizedLink()"
                                :disabled="isSubmitting"
                                class="touch-manipulation inline-flex flex-1 items-center justify-center gap-2 rounded-2xl bg-accent px-5 py-3 text-sm font-bold text-on-accent shadow-lg transition hover:bg-accent-hover disabled:cursor-not-allowed disabled:opacity-60 active:scale-[0.98]">
                            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A2 2 0 0122 9.514v4.972a2 2 0 01-2.447 1.79L15 14m-6 4h6a2 2 0 002-2V8a2 2 0 00-2-2H9a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            <span x-text="isSubmitting ? @js(__('app.yefasika_beal_generating')) : @js(__('app.yefasika_beal_generate_button'))"></span>
                        </button>

                        <button type="button"
                                @click="closeComposer()"
                                class="touch-manipulation inline-flex items-center justify-center rounded-2xl border border-[rgba(245,208,96,0.22)] bg-white/5 px-5 py-3 text-sm font-semibold text-[#F5D060] transition hover:bg-white/10 active:scale-[0.98]">
                            {{ __('app.cancel') }}
                        </button>
                    </div>
                </div>

                <div x-show="generatedUrl" x-cloak class="space-y-4 rounded-2xl border border-[rgba(245,208,96,0.22)] bg-white/5 p-4">
                    <div>
                        <p class="text-sm font-semibold text-[#F5D060]">{{ __('app.yefasika_beal_ready_title') }}</p>
                        <p class="mt-1 text-sm leading-6 text-white/75">{{ __('app.yefasika_beal_ready_help') }}</p>
                    </div>

                    <div class="rounded-2xl border border-[rgba(245,208,96,0.16)] bg-black/15 px-4 py-3 text-sm break-all text-white/90" x-text="generatedUrl"></div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <button type="button"
                                @click="shareGenerated()"
                                class="touch-manipulation inline-flex flex-1 items-center justify-center gap-2 rounded-2xl bg-accent px-5 py-3 text-sm font-bold text-on-accent shadow-lg transition hover:bg-accent-hover active:scale-[0.98]">
                            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                            </svg>
                            {{ __('app.yefasika_beal_share_personalized_button') }}
                        </button>

                        <button type="button"
                                @click="copyGenerated()"
                                class="touch-manipulation inline-flex items-center justify-center gap-2 rounded-2xl border border-[rgba(245,208,96,0.22)] bg-white/5 px-5 py-3 text-sm font-semibold text-[#F5D060] transition hover:bg-white/10 active:scale-[0.98]">
                            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                            </svg>
                            {{ __('app.yefasika_beal_copy_personalized_button') }}
                        </button>
                    </div>
                </div>

                <p x-show="copied" x-cloak x-transition class="text-center text-sm font-semibold text-[#F5D060]">
                    {{ __('app.yefasika_beal_link_copied') }}
                </p>
            </div>
        </section>

        <p class="text-center text-xs text-white/50 pt-2">
            <a href="{{ rtrim((string) config('app.parish_website_url'), '/') }}/"
               class="underline hover:text-white/80">{{ __('app.yefasika_beal_back_home') }}</a>
        </p>
    </main>
@endsection

@push('scripts')
<script>
function fasikaGreetingPage(config) {
    return {
        composerOpen: false,
        isSubmitting: false,
        copied: false,
        errorMessage: '',
        senderName: config.initialSenderName || '',
        generatedUrl: '',
        generatedShareText: '',
        async copyText(value) {
            try {
                await navigator.clipboard.writeText(value);
            } catch (_error) {
                const ta = document.createElement('textarea');
                ta.value = value;
                ta.style.cssText = 'position:fixed;opacity:0';
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
            }
            this.copied = true;
            setTimeout(() => { this.copied = false; }, 2200);
        },
        openComposer() {
            this.composerOpen = true;
            this.errorMessage = '';
            this.$nextTick(() => this.$refs.senderNameInput?.focus());
        },
        closeComposer() {
            this.composerOpen = false;
            this.errorMessage = '';
        },
        async createPersonalizedLink() {
            if (!this.senderName) {
                this.errorMessage = @js(__('app.yefasika_beal_name_required'));
                return;
            }

            this.isSubmitting = true;
            this.errorMessage = '';

            try {
                const response = await fetch(config.storeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': config.csrf,
                    },
                    body: JSON.stringify({ sender_name: this.senderName }),
                });

                const payload = await response.json();

                if (!response.ok) {
                    this.errorMessage = payload?.message || @js(__('app.yefasika_beal_create_failed'));
                    return;
                }

                this.generatedUrl = payload.share_url || '';
                this.generatedShareText = payload.share_text || '';
            } catch (_error) {
                this.errorMessage = @js(__('app.yefasika_beal_create_failed'));
            } finally {
                this.isSubmitting = false;
            }
        },
        async shareGenerated() {
            if (!this.generatedUrl) {
                return;
            }

            if (navigator.share) {
                try {
                    await navigator.share({
                        text: this.generatedShareText + '\n' + this.generatedUrl,
                        url: this.generatedUrl,
                    });
                    return;
                } catch (_error) {}
            }

            await this.copyGenerated();
        },
        async copyGenerated() {
            if (!this.generatedUrl) {
                return;
            }

            await this.copyText(this.generatedUrl);
        },
    };
}
</script>
@endpush
