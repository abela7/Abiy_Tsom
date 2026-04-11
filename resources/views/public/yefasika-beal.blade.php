@extends('layouts.public-fasika')

@section('title', $pageTitle)

@section('content')
    @include('public.partials.yefasika-beal-background')

    <main class="relative z-10 flex flex-col gap-6 ybb-page sm:gap-8"
          x-data="fasikaGreetingPage({
              initialUrl: @js($shareUrl),
              initialShareText: @js($shareText),
              storeUrl: @js(route('public.yefasika-beal.store')),
              csrf: @js(csrf_token()),
              initialSenderName: '',
          })">
        {{-- Hero: cross + titles in a focused glass frame --}}
        <div class="mx-auto w-full max-w-md rounded-3xl border border-white/[0.08] bg-black/30 p-5 shadow-[0_20px_50px_-12px_rgba(0,0,0,0.5)] ring-1 ring-white/[0.04] backdrop-blur-md sm:max-w-lg sm:p-6">
            <div class="mx-auto w-full max-w-[16rem] origin-top scale-[0.94] sm:max-w-none sm:scale-100">
                @include('member.partials.fasika-celebration-banner')
            </div>
        </div>

        {{-- Reading card: message only (modern editorial stack) --}}
        <article
            class="relative mx-auto w-full max-w-md overflow-hidden rounded-3xl border border-white/[0.09] bg-gradient-to-br from-white/[0.07] via-zinc-950/30 to-zinc-950/50 px-6 py-9 shadow-[0_32px_64px_-16px_rgba(0,0,0,0.55)] ring-1 ring-inset ring-white/[0.06] backdrop-blur-xl sm:max-w-lg sm:px-9 sm:py-11"
            itemscope
            itemtype="https://schema.org/Article">
            <div class="pointer-events-none absolute -left-24 top-20 h-64 w-64 rounded-full bg-[#e2ca18]/[0.06] blur-3xl"></div>
            <div class="pointer-events-none absolute -right-16 bottom-0 h-48 w-48 rounded-full bg-violet-500/[0.07] blur-3xl"></div>

            <header class="relative text-balance text-center">
                <p id="ybb-greeting"
                   class="mx-auto max-w-[30ch] text-[1.0625rem] font-medium leading-[1.72] tracking-wide text-white/[0.94] sm:text-lg sm:leading-[1.78]">
                    {{ __('app.yefasika_beal_short_greeting_line_one') }}
                </p>
                <p class="mx-auto mt-6 max-w-[26ch] text-xl font-semibold leading-snug tracking-wide text-[#F5E6B3] sm:text-2xl sm:leading-tight">
                    {{ __('app.yefasika_beal_short_greeting_line_two') }}
                </p>
            </header>

            <div class="relative mx-auto my-9 flex items-center gap-3 sm:my-10"
                 aria-hidden="true">
                <span class="h-px flex-1 max-w-[4.5rem] bg-gradient-to-r from-transparent to-white/20"></span>
                <span class="size-1.5 shrink-0 rounded-full bg-[#e2ca18]/70 shadow-[0_0_12px_rgba(226,202,24,0.45)]"></span>
                <span class="h-px flex-1 max-w-[4.5rem] bg-gradient-to-l from-transparent to-white/20"></span>
            </div>

            <div class="relative mx-auto max-w-prose space-y-6 text-center text-[1.0625rem] leading-[1.92] text-zinc-100/90 sm:text-[1.075rem] sm:leading-[2.02]">
                <p class="text-pretty">{{ __('app.yefasika_beal_long_message_paragraph_one') }}</p>
                <p class="text-pretty">{{ __('app.yefasika_beal_long_message_paragraph_two') }}</p>
                <p class="pt-1 text-lg font-semibold leading-snug text-[#e2ca18] sm:text-xl">
                    {{ __('app.yefasika_beal_long_message_closing') }}
                </p>
            </div>

            @if($share)
                <footer class="relative mt-12 border-t border-white/[0.08] pt-10 text-center">
                    <p class="text-2xl font-medium leading-snug tracking-wide text-[#FFF8DC] sm:text-[1.7rem]">
                        {{ __('app.yefasika_beal_from_name', ['name' => $share->sender_name]) }}
                    </p>
                </footer>
            @endif
        </article>

        {{-- Share / personalize: separate surface = clearer modern affordance --}}
        <section class="relative mx-auto w-full max-w-md rounded-2xl border border-[#e2ca18]/[0.22] bg-zinc-950/70 px-4 py-5 shadow-[0_16px_36px_-12px_rgba(0,0,0,0.4)] ring-1 ring-inset ring-white/[0.04] backdrop-blur-xl sm:max-w-lg sm:px-5 sm:py-6"
                 aria-labelledby="ybb-share-heading">
            <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-[#e2ca18]/40 to-transparent"></div>

            <h2 id="ybb-share-heading"
                class="mx-auto max-w-prose text-center text-base font-extrabold leading-relaxed text-[#e2ca18] sm:text-lg">
                {{ __('app.yefasika_beal_generator_title') }}
            </h2>

            <div class="mt-5 space-y-4">
                <div x-show="!composerOpen" x-cloak class="flex justify-center">
                    <button type="button"
                            @click="openComposer()"
                            class="touch-manipulation inline-flex h-12 w-full max-w-xs items-center justify-center gap-2 rounded-xl bg-[#e2ca18] px-5 text-sm font-bold tracking-wide text-zinc-950 shadow-[0_8px_24px_-6px_rgba(226,202,24,0.4)] transition hover:bg-[#edd85c] hover:shadow-[0_12px_28px_-6px_rgba(226,202,24,0.45)] active:scale-[0.98] sm:w-auto sm:min-w-[13rem]">
                        <svg class="h-5 w-5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ __('app.yefasika_beal_add_name_button') }}
                    </button>
                </div>

                <div x-show="composerOpen" x-cloak class="mx-auto max-w-md space-y-4">
                    <div>
                        <label for="fasika-sender-name" class="sr-only">{{ __('app.yefasika_beal_name_label') }}</label>
                        <input id="fasika-sender-name"
                               x-ref="senderNameInput"
                               x-model.trim="senderName"
                               type="text"
                               maxlength="120"
                               autocomplete="name"
                               class="h-12 w-full rounded-xl border border-white/12 bg-zinc-900/80 px-4 text-center text-base text-white shadow-inner outline-none transition placeholder:text-zinc-500 focus:border-[#e2ca18]/50 focus:bg-zinc-900 focus:ring-2 focus:ring-[#e2ca18]/25"
                               placeholder="{{ __('app.yefasika_beal_name_placeholder') }}">
                        <p x-show="errorMessage" x-cloak class="mt-2 text-center text-sm font-medium text-rose-300" x-text="errorMessage"></p>
                    </div>

                    <div class="flex flex-col gap-2.5 sm:flex-row sm:justify-center">
                        <button type="button"
                                @click="createPersonalizedLink()"
                                :disabled="isSubmitting"
                                class="touch-manipulation inline-flex h-12 flex-1 items-center justify-center gap-2 rounded-xl bg-[#e2ca18] px-5 text-sm font-bold text-zinc-950 shadow-[0_8px_22px_-6px_rgba(226,202,24,0.38)] transition hover:bg-[#edd85c] disabled:cursor-not-allowed disabled:opacity-50 active:scale-[0.98] sm:max-w-xs sm:flex-none">
                            <svg class="h-5 w-5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                            </svg>
                            <span x-text="isSubmitting ? @js(__('app.yefasika_beal_generating')) : @js(__('app.yefasika_beal_generate_button'))"></span>
                        </button>

                        <button type="button"
                                @click="closeComposer()"
                                class="touch-manipulation inline-flex h-12 items-center justify-center rounded-xl border border-white/15 bg-transparent px-5 text-sm font-semibold text-zinc-300 transition hover:bg-white/[0.06] active:scale-[0.98]">
                            {{ __('app.cancel') }}
                        </button>
                    </div>
                </div>

                <div x-show="generatedUrl" x-cloak class="space-y-3 rounded-xl border border-white/10 bg-zinc-900/50 p-4 ring-1 ring-inset ring-white/[0.04]">
                    <div class="text-center">
                        <p class="text-sm font-semibold text-[#e2ca18]">{{ __('app.yefasika_beal_ready_title') }}</p>
                        <p class="mt-1.5 text-xs leading-relaxed text-zinc-400">{{ __('app.yefasika_beal_ready_help') }}</p>
                    </div>

                    <div class="rounded-xl border border-white/8 bg-black/40 px-3 py-3 text-center font-mono text-[11px] leading-relaxed break-all text-zinc-300 sm:text-xs"
                         x-text="generatedUrl"></div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:justify-center">
                        <button type="button"
                                @click="shareGenerated()"
                                class="touch-manipulation inline-flex h-12 flex-1 items-center justify-center gap-2 rounded-xl bg-[#e2ca18] px-4 text-sm font-bold text-zinc-950 shadow-md transition hover:bg-[#edd85c] active:scale-[0.98] sm:flex-none">
                            <svg class="h-5 w-5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                            </svg>
                            {{ __('app.yefasika_beal_share_personalized_button') }}
                        </button>

                        <button type="button"
                                @click="copyGenerated()"
                                class="touch-manipulation inline-flex h-12 items-center justify-center gap-2 rounded-xl border border-white/15 px-4 text-sm font-semibold text-zinc-200 transition hover:bg-white/[0.06] active:scale-[0.98]">
                            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                            </svg>
                            {{ __('app.yefasika_beal_copy_personalized_button') }}
                        </button>
                    </div>
                </div>

                <p x-show="copied" x-cloak x-transition class="text-center text-sm font-semibold text-[#e2ca18]">
                    {{ __('app.yefasika_beal_link_copied') }}
                </p>
            </div>
        </section>

        <p class="mx-auto mt-2 max-w-md pb-2 text-center">
            <a href="{{ rtrim((string) config('app.parish_website_url'), '/') }}/"
               class="inline-flex items-center justify-center rounded-full px-4 py-2 text-xs font-medium text-zinc-500 transition hover:bg-white/[0.04] hover:text-zinc-300">
                {{ __('app.yefasika_beal_back_home') }}
            </a>
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
