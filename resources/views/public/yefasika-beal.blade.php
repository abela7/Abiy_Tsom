@extends('layouts.public-fasika')

@section('title', $pageTitle)

@section('content')
    @include('public.partials.yefasika-beal-background')

    <main class="relative z-10 space-y-6 ybb-page"
          x-data="{
              copied: false,
              pageUrl: @js($shareUrl),
              shareText: @js(__('app.yefasika_beal_share_text')),
              async shareCard() {
                  if (navigator.share) {
                      try {
                          await navigator.share({ text: this.shareText + '\n' + this.pageUrl, url: this.pageUrl });
                      } catch (_e) {}
                  } else {
                      await this.copyLink();
                  }
              },
              async copyLink() {
                  try {
                      await navigator.clipboard.writeText(this.pageUrl);
                  } catch (_e) {
                      const ta = document.createElement('textarea');
                      ta.value = this.pageUrl;
                      ta.style.cssText = 'position:fixed;opacity:0';
                      document.body.appendChild(ta);
                      ta.select();
                      document.execCommand('copy');
                      document.body.removeChild(ta);
                  }
                  this.copied = true;
                  setTimeout(() => { this.copied = false; }, 2200);
              }
          }">
        @include('member.partials.fasika-celebration-banner')

        <p class="text-center text-sm leading-relaxed text-white/85 px-1">
            {{ __('app.yefasika_beal_intro') }}
        </p>

        <div class="flex flex-col sm:flex-row gap-2 justify-center items-stretch sm:items-center">
            <button type="button"
                    @click="shareCard()"
                    class="touch-manipulation inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-accent text-on-accent text-sm font-bold shadow-lg hover:bg-accent-hover transition active:scale-[0.98]">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                </svg>
                {{ __('app.yefasika_beal_share_button') }}
            </button>
            <button type="button"
                    @click="copyLink()"
                    class="touch-manipulation inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl border border-accent/40 bg-white/5 text-[#F5D060] text-sm font-semibold hover:bg-white/10 transition active:scale-[0.98]">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                </svg>
                {{ __('app.yefasika_beal_copy_link') }}
            </button>
        </div>

        <p x-show="copied"
           x-cloak
           x-transition
           class="text-center text-sm font-semibold text-[#F5D060]">
            {{ __('app.yefasika_beal_link_copied') }}
        </p>

        <p class="text-center text-xs text-white/50 pt-2">
            <a href="{{ rtrim((string) config('app.parish_website_url'), '/') }}/"
               class="underline hover:text-white/80">{{ __('app.yefasika_beal_back_home') }}</a>
        </p>
    </main>
@endsection
