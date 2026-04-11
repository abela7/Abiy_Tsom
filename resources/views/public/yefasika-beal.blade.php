@extends('layouts.public-fasika')

@section('title', $pageTitle)

@section('content')
    @include('public.partials.yefasika-beal-background')

    <main class="relative z-10 ybb-page"
          x-data="fasikaGreetingPage({
              initialUrl: @js($shareUrl),
              initialShareText: @js($shareText),
              storeUrl: @js(route('public.yefasika-beal.store')),
              csrf: @js(csrf_token()),
              initialSenderName: '',
          })">
        {{-- Liturgical cross / title: slightly scaled so content reads first --}}
        <div class="mx-auto w-full max-w-[17.5rem] origin-top scale-[0.92] pb-2 sm:max-w-none sm:scale-100 sm:pb-4">
            @include('member.partials.fasika-celebration-banner')
        </div>

        <article
            class="relative overflow-hidden rounded-[1.75rem] border border-white/[0.12] bg-gradient-to-b from-white/[0.09] via-white/[0.04] to-white/[0.02] px-5 py-8 shadow-[0_24px_80px_rgba(0,0,0,0.35)] backdrop-blur-md sm:px-8 sm:py-10"
            itemscope
            itemtype="https://schema.org/Article">
            <div class="pointer-events-none absolute inset-x-0 top-0 h-32 bg-gradient-to-b from-[rgba(245,208,96,0.08)] to-transparent"></div>

            {{-- Short greeting: typographic hierarchy --}}
            <header class="relative text-center">
                <p id="ybb-greeting"
                   class="mx-auto max-w-[28ch] text-[1.05rem] font-medium leading-[1.75] tracking-wide text-white/[0.93] sm:text-[1.125rem] sm:leading-[1.8]">
                    {{ __('app.yefasika_beal_short_greeting_line_one') }}
                </p>
                <p class="mx-auto mt-5 max-w-[24ch] text-xl font-semibold leading-snug tracking-wide text-[#F5E6B3] sm:text-2xl">
                    {{ __('app.yefasika_beal_short_greeting_line_two') }}
                </p>
            </header>

            <div class="relative mx-auto my-8 h-px max-w-[12rem] bg-gradient-to-r from-transparent via-[#F5D060]/45 to-transparent"
                 aria-hidden="true"></div>

            {{-- Long blessing: reading rhythm --}}
            <div class="relative mx-auto max-w-prose space-y-5 text-center text-[1.0625rem] leading-[1.95] text-white/[0.88] sm:text-[1.075rem] sm:leading-[2]">
                <p>{{ __('app.yefasika_beal_long_message_paragraph_one') }}</p>
                <p>{{ __('app.yefasika_beal_long_message_paragraph_two') }}</p>
                <p class="pt-1 text-lg font-semibold leading-snug text-[#F5D060] sm:text-xl">
                    {{ __('app.yefasika_beal_long_message_closing') }}
                </p>
            </div>

            @if($share)
                <footer class="relative mt-10 border-t border-white/[0.1] pt-8 text-center">
                    <p class="text-2xl font-medium leading-snug tracking-wide text-[#FFF8DC] sm:text-[1.65rem]">
                        {{ __('app.yefasika_beal_from_name', ['name' => $share->sender_name]) }}
                    </p>
                </footer>
            @endif

            {{-- Invitation + name flow: one calm band, not nested cards --}}
            <section class="relative mt-10 rounded-2xl border border-[rgba(245,208,96,0.15)] bg-black/20 px-4 py-7 sm:px-6"
                     aria-labelledby="ybb-share-heading">
                <h2 id="ybb-share-heading"
                    class="text-center text-[0.68rem] font-bold uppercase tracking-[0.22em] text-[#F5D060]/75">
                    {{ __('app.yefasika_beal_generator_title') }}
                </h2>
                <p class="mx-auto mt-4 max-w-md text-center text-[0.98rem] leading-relaxed text-white/80">
                    {{ __('app.yefasika_beal_cta_lead') }}
                </p>
                <p class="mx-auto mt-2 max-w-sm text-center text-sm leading-relaxed text-white/55">
                    {{ __('app.yefasika_beal_generator_help') }}
                </p>

                <div class="mt-7 space-y-5">
                    <div x-show="!composerOpen" x-cloak class="flex justify-center">
                        <button type="button"
                                @click="openComposer()"
                                class="touch-manipulation inline-flex min-h-[3rem] w-full max-w-xs items-center justify-center gap-2 rounded-full bg-gradient-to-b from-[#e2ca18] to-[#b8940f] px-6 text-sm font-bold text-[#1a1208] shadow-[0_10px_30px_rgba(226,202,24,0.25)] ring-1 ring-white/20 transition hover:brightness-105 active:scale-[0.98] sm:w-auto sm:min-w-[14rem]">
                            <svg class="h-5 w-5 shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
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
                                   class="w-full rounded-2xl border border-white/15 bg-white/[0.07] px-4 py-3.5 text-center text-base text-white outline-none ring-0 transition placeholder:text-white/35 focus:border-[#F5D060]/50 focus:bg-white/[0.1] focus:shadow-[0_0_0_3px_rgba(245,208,96,0.12)]"
                                   placeholder="{{ __('app.yefasika_beal_name_placeholder') }}">
                            <p x-show="errorMessage" x-cloak class="mt-2 text-center text-sm font-medium text-rose-300" x-text="errorMessage"></p>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row sm:justify-center">
                            <button type="button"
                                    @click="createPersonalizedLink()"
                                    :disabled="isSubmitting"
                                    class="touch-manipulation inline-flex min-h-[3rem] flex-1 items-center justify-center gap-2 rounded-full bg-gradient-to-b from-[#e2ca18] to-[#b8940f] px-5 text-sm font-bold text-[#1a1208] shadow-[0_10px_28px_rgba(226,202,24,0.22)] transition hover:brightness-105 disabled:cursor-not-allowed disabled:opacity-55 active:scale-[0.98] sm:max-w-xs sm:flex-none">
                                <svg class="h-5 w-5 shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A2 2 0 0122 9.514v4.972a2 2 0 01-2.447 1.79L15 14m-6 4h6a2 2 0 002-2V8a2 2 0 00-2-2H9a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                <span x-text="isSubmitting ? @js(__('app.yefasika_beal_generating')) : @js(__('app.yefasika_beal_generate_button'))"></span>
                            </button>

                            <button type="button"
                                    @click="closeComposer()"
                                    class="touch-manipulation inline-flex min-h-[3rem] items-center justify-center rounded-full border border-white/15 bg-transparent px-5 text-sm font-semibold text-white/75 transition hover:bg-white/[0.06] active:scale-[0.98]">
                                {{ __('app.cancel') }}
                            </button>
                        </div>
                    </div>

                    <div x-show="generatedUrl" x-cloak class="space-y-4 rounded-2xl border border-[rgba(245,208,96,0.14)] bg-white/[0.04] px-4 py-5">
                        <div class="text-center">
                            <p class="text-sm font-semibold text-[#F5D060]">{{ __('app.yefasika_beal_ready_title') }}</p>
                            <p class="mt-1.5 text-xs leading-relaxed text-white/60">{{ __('app.yefasika_beal_ready_help') }}</p>
                        </div>

                        <div class="rounded-xl border border-white/10 bg-black/25 px-3 py-2.5 text-center text-xs leading-relaxed break-all text-white/80"
                             x-text="generatedUrl"></div>

                        <div class="flex flex-col gap-2.5 sm:flex-row sm:justify-center">
                            <button type="button"
                                    @click="shareGenerated()"
                                    class="touch-manipulation inline-flex min-h-[2.85rem] flex-1 items-center justify-center gap-2 rounded-full bg-gradient-to-b from-[#e2ca18] to-[#b8940f] px-4 text-sm font-bold text-[#1a1208] shadow-[0_8px_24px_rgba(226,202,24,0.2)] transition hover:brightness-105 active:scale-[0.98] sm:flex-none">
                                <svg class="h-5 w-5 shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                                </svg>
                                {{ __('app.yefasika_beal_share_personalized_button') }}
                            </button>

                            <button type="button"
                                    @click="copyGenerated()"
                                    class="touch-manipulation inline-flex min-h-[2.85rem] items-center justify-center gap-2 rounded-full border border-white/15 px-4 text-sm font-semibold text-[#F5E6B3] transition hover:bg-white/[0.06] active:scale-[0.98]">
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
        </article>

        <p class="mt-8 text-center text-xs text-white/45">
            <a href="{{ rtrim((string) config('app.parish_website_url'), '/') }}/"
               class="underline decoration-white/20 underline-offset-4 transition hover:text-white/75 hover:decoration-white/40">{{ __('app.yefasika_beal_back_home') }}</a>
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
