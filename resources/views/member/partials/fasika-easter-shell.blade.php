{{-- Member Fasika day only — not used on the public Yefasika Beal page. --}}
<style>
    html:has(.fasika-member-bg),
    html:has(.fasika-member-bg) body {
        min-height: 100vh;
        min-height: 100dvh;
        min-height: -webkit-fill-available;
    }

    html:has(.fasika-member-bg) body {
        overflow-x: hidden;
    }

    html.dark body { background: #0f0a1a !important; }
    html.dark { background: #0f0a1a !important; }

    .fasika-page {
        --color-card: rgba(45, 24, 84, 0.52);
        --color-muted: rgba(26, 14, 46, 0.45);
        --color-border: rgba(245, 208, 96, 0.18);
        --color-primary: #F5D060;
        --color-secondary: rgba(255, 255, 255, 0.75);
        --color-muted-text: rgba(245, 208, 96, 0.55);
    }
    .fasika-page > * {
        backdrop-filter: blur(3px);
        -webkit-backdrop-filter: blur(3px);
    }

    .fasika-page [class~="z-50"],
    .fasika-page [class~="z-50"] * {
        --color-card: rgb(20, 10, 40);
        --color-muted: rgb(30, 15, 55);
        --color-border: rgba(245, 208, 96, 0.22);
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
    }

    .fasika-member-bg {
        position: fixed;
        z-index: 0;
        inset: 0;
        width: 100%;
        min-width: 100%;
        height: 100vh;
        height: 100svh;
        height: 100dvh;
        height: -webkit-fill-available;
        min-height: 100vh;
        min-height: 100svh;
        min-height: 100dvh;
        min-height: -webkit-fill-available;
        overflow: hidden;
        background-color: #0f0a1a;
        -webkit-transform: translateZ(0);
        transform: translateZ(0);
    }

    .fasika-member-bg__photo {
        display: block;
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        min-width: 100%;
        min-height: 100%;
        max-width: none;
        max-height: none;
        margin: 0;
        object-fit: cover;
        object-position: center center;
        pointer-events: none;
    }

    .fasika-member-bg__scrim {
        position: absolute;
        inset: 0;
        pointer-events: none;
        background:
            linear-gradient(to bottom, rgba(26, 14, 46, 0.6), rgba(45, 24, 84, 0.5), rgba(15, 10, 26, 0.72)),
            radial-gradient(ellipse 130% 70% at 50% 18%, rgba(212, 165, 87, 0.2) 0%, transparent 52%);
    }
</style>

<div class="fasika-member-bg" aria-hidden="true">
    <img class="fasika-member-bg__photo"
         src="{{ asset('images/Jesus_In_Eastern.avif') }}"
         alt=""
         width="1600"
         height="1067"
         sizes="100vw"
         decoding="async"
         fetchpriority="high">
    <div class="fasika-member-bg__scrim" aria-hidden="true"></div>
</div>

<script>
    window.addEventListener('alpine:initialized', function () {
        window.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme: 'dark' } }));
    }, { once: true });
</script>
