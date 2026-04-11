{{-- Fasika celebration card (member day + public Yefasika Beal). Styles stay with the card. --}}
<style>
    @keyframes fasika-glow {
        0%,100% { opacity: 0.7; transform: translate(-50%,-50%) scale(1); }
        50%      { opacity: 1;   transform: translate(-50%,-50%) scale(1.1); }
    }
    @keyframes fasika-rays {
        from { transform: rotate(0deg); }
        to   { transform: rotate(360deg); }
    }
    .fasika-shimmer-text {
        background: linear-gradient(90deg, #B8860B 0%, #F5E6A3 30%, #D4A537 50%, #FFF8DC 70%, #B8860B 100%);
        background-size: 200% auto;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        animation: fasika-shimmer 3s linear infinite;
    }
    @keyframes fasika-shimmer {
        to { background-position: 200% center; }
    }
</style>
<div class="relative rounded-3xl overflow-hidden px-5 py-6 text-center space-y-3">
    {{-- Ambient glow behind artwork --}}
    <div style="position:absolute;top:50%;left:50%;width:280px;height:280px;border-radius:50%;
         background:radial-gradient(circle,rgba(245,208,96,0.22) 0%,transparent 70%);
         transform:translate(-50%,-50%);pointer-events:none;
         animation:fasika-glow 3.5s ease-in-out infinite;"></div>

    {{-- Risen artwork: fixed box + min-w-0 so flex does not honor 794px intrinsic width. --}}
    <div class="relative mx-auto flex h-20 w-14 shrink-0 items-center justify-center overflow-visible min-h-0 min-w-0 sm:h-24 sm:w-16">
        <div class="pointer-events-none absolute inset-[-14%] opacity-[0.22]"
             style="animation: fasika-rays 28s linear infinite;">
            @for ($r = 0; $r < 12; $r++)
            <div style="position:absolute;top:50%;left:50%;width:2px;height:48px;
                 background:linear-gradient(to top,#F5D060,transparent);
                 transform-origin:bottom center;
                 transform:translate(-50%,-100%) rotate({{ $r * 30 }}deg);"></div>
            @endfor
        </div>
        <img src="{{ asset('images/Risen.svg') }}"
             alt="{{ __('app.fasika_celebration_risen_image_alt') }}"
             class="relative z-10 flex max-h-full max-w-full min-h-0 min-w-0 items-center justify-center object-contain object-center drop-shadow-[0_0_16px_rgba(245,208,96,0.38)]"
             decoding="async"
             loading="lazy">
    </div>

    <p class="text-xs font-bold uppercase tracking-[0.25em] text-[rgba(245,208,96,0.7)]">
        {{ __('app.fasika_eyebrow') }}
    </p>

    <h2 class="text-3xl font-black leading-tight fasika-shimmer-text">
        {{ __('app.fasika_banner_main') }}
    </h2>

    <div class="space-y-2 text-center">
        @foreach(trans('app.fasika_banner_lines') as $fasikaBannerLine)
            <h3 class="text-base font-semibold text-white/90 leading-snug">{{ $fasikaBannerLine }}</h3>
        @endforeach
    </div>

    @if($fasikaCelebrationShowFooterBadge ?? false)
        <div class="inline-flex items-center justify-center px-4 py-2.5 rounded-full
                    bg-white/10 border border-[rgba(245,208,96,0.22)]">
            <span class="text-sm font-semibold text-[#F5D060]">{{ __('app.fasika_banner_badge') }}</span>
        </div>
    @endif
</div>
