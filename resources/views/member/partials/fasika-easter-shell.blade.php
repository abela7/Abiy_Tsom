{{-- Fasika: one fixed full-screen background (cover) + tint. No particles. --}}
<style>
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

    /* One layer: photo + gradients, always cover the viewport (mobile-friendly). */
    .fasika-bg-cover {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 0;
        background-color: #0f0a1a;
        background-image:
            linear-gradient(to bottom, rgba(26, 14, 46, 0.6), rgba(45, 24, 84, 0.5), rgba(15, 10, 26, 0.72)),
            radial-gradient(ellipse 130% 70% at 50% 18%, rgba(212, 165, 87, 0.2) 0%, transparent 52%),
            url("{{ asset('images/Jesus_In_Eastern.avif') }}");
        background-size: cover, cover, cover;
        background-position: center, center, center;
        background-repeat: no-repeat, no-repeat, no-repeat;
    }
</style>

<div class="fasika-bg-cover" aria-hidden="true"></div>

<script>
    window.addEventListener('alpine:initialized', function () {
        window.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme: 'dark' } }));
    }, { once: true });
</script>
