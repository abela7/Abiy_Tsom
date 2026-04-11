{{-- Public Yefasika Beal only — not shared with member Fasika day. --}}
<style>
    html:has(.ybb-bg),
    html:has(.ybb-bg) body {
        margin: 0;
        padding: 0;
        min-height: 100vh;
        min-height: 100dvh;
        min-height: -webkit-fill-available;
    }

    html:has(.ybb-bg) body {
        overflow-x: hidden;
        display: flex;
        flex-direction: column;
        align-items: stretch;
    }

    html.dark body { background: #0f0a1a !important; }
    html.dark { background: #0f0a1a !important; }

    .ybb-page {
        --color-card: rgba(45, 24, 84, 0.52);
        --color-muted: rgba(26, 14, 46, 0.45);
        --color-border: rgba(245, 208, 96, 0.18);
        --color-primary: #F5D060;
        --color-secondary: rgba(255, 255, 255, 0.75);
        --color-muted-text: rgba(245, 208, 96, 0.55);
    }
    .ybb-page > * {
        backdrop-filter: blur(3px);
        -webkit-backdrop-filter: blur(3px);
    }

    /* Fill the viewport and center the card (avoids top gap + bottom-heavy look). */
    html:has(.ybb-bg) body > main.ybb-page {
        flex: 1 0 auto;
        display: flex;
        flex-direction: column;
        justify-content: center;
        width: 100%;
        max-width: 32rem;
        margin-left: auto;
        margin-right: auto;
        box-sizing: border-box;
        padding-left: max(1rem, env(safe-area-inset-left, 0px));
        padding-right: max(1rem, env(safe-area-inset-right, 0px));
        padding-top: max(1.25rem, env(safe-area-inset-top, 0px));
        padding-bottom: max(1.25rem, env(safe-area-inset-bottom, 0px));
    }

    .ybb-bg {
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

    /* Beat Tailwind preflight img { max-width:100%; height:auto } so cover works. */
    .ybb-bg__photo {
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

    .ybb-bg__scrim {
        position: absolute;
        inset: 0;
        pointer-events: none;
        background:
            linear-gradient(to bottom, rgba(26, 14, 46, 0.6), rgba(45, 24, 84, 0.5), rgba(15, 10, 26, 0.72)),
            radial-gradient(ellipse 130% 70% at 50% 18%, rgba(212, 165, 87, 0.2) 0%, transparent 52%);
    }
</style>

<div class="ybb-bg" aria-hidden="true">
    <img class="ybb-bg__photo"
         src="{{ asset('images/Jesus_In_Eastern.avif') }}"
         alt=""
         width="1600"
         height="1067"
         sizes="100vw"
         decoding="async"
         fetchpriority="high">
    <div class="ybb-bg__scrim" aria-hidden="true"></div>
</div>
