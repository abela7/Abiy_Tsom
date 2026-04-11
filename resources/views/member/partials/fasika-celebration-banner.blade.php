{{-- Fasika celebration card (shared by member day and public Yefasika Beal page). --}}
<div class="relative rounded-3xl overflow-hidden px-5 py-6 text-center space-y-3">
    {{-- Ambient glow behind cross --}}
    <div style="position:absolute;top:50%;left:50%;width:280px;height:280px;border-radius:50%;
         background:radial-gradient(circle,rgba(245,208,96,0.22) 0%,transparent 70%);
         transform:translate(-50%,-50%);pointer-events:none;
         animation:fasika-glow 3.5s ease-in-out infinite;"></div>

    {{-- Ethiopian cross with spinning rays --}}
    <div class="relative mx-auto w-20 h-20">
        <div style="position:absolute;inset:-28px;animation:fasika-rays 28s linear infinite;opacity:0.35;">
            @for ($r = 0; $r < 12; $r++)
            <div style="position:absolute;top:50%;left:50%;width:2px;height:72px;
                 background:linear-gradient(to top,#F5D060,transparent);
                 transform-origin:bottom center;
                 transform:translate(-50%,-100%) rotate({{ $r * 30 }}deg);"></div>
            @endfor
        </div>
        <svg class="relative w-full h-full drop-shadow-[0_0_24px_rgba(245,208,96,0.5)]"
             viewBox="0 0 100 100" fill="none" aria-hidden="true">
            <defs>
                <linearGradient id="fg-cross" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%"   stop-color="#F5E6A3"/>
                    <stop offset="50%"  stop-color="#D4A537"/>
                    <stop offset="100%" stop-color="#B8860B"/>
                </linearGradient>
            </defs>
            <rect x="42" y="5"  width="16" height="90" rx="3" fill="url(#fg-cross)"/>
            <rect x="15" y="28" width="70" height="16" rx="3" fill="url(#fg-cross)"/>
            <rect x="38" y="2"  width="24" height="8"  rx="4" fill="url(#fg-cross)"/>
            <rect x="38" y="90" width="24" height="8"  rx="4" fill="url(#fg-cross)"/>
            <rect x="10" y="24" width="8"  height="24" rx="4" fill="url(#fg-cross)"/>
            <rect x="82" y="24" width="8"  height="24" rx="4" fill="url(#fg-cross)"/>
            <rect x="44" y="30" width="12" height="12" rx="2" transform="rotate(45 50 36)" fill="#FFF8DC" opacity="0.8"/>
        </svg>
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

    <div class="inline-flex items-center justify-center px-4 py-2.5 rounded-full
                bg-white/10 border border-[rgba(245,208,96,0.22)]">
        <span class="text-sm font-semibold text-[#F5D060]">{{ __('app.fasika_banner_badge') }}</span>
    </div>
</div>
