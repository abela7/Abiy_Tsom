{{-- Fasika: dark theme + photo + gold gradient + floating particles (no blood effects). --}}
<style>
    html.dark body { background: transparent !important; }
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

    /* FAQ modal & other fixed overlays — fully solid so text is readable */
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
</style>

{{-- Photo + liturgical purple gradient + gold wash (same stack as pre–Good-Friday-copy Easter) --}}
<div style="position:fixed;inset:0;z-index:0;">
    <div style="position:absolute;inset:0;
         background-image:url('{{ asset('images/Jesus_In_Eastern.avif') }}');
         background-size:cover;background-position:center top;background-repeat:no-repeat;"></div>
    <div style="position:absolute;inset:0;
         background:linear-gradient(to bottom, rgba(26, 14, 46, 0.62), rgba(45, 24, 84, 0.55), rgba(15, 10, 26, 0.72));"></div>
    <div style="position:absolute;inset:0;
         background:radial-gradient(ellipse at 50% 18%, rgba(212, 165, 87, 0.22) 0%, transparent 58%);"></div>
</div>

<canvas id="fasika-particles"
        style="position:fixed;inset:0;z-index:1;pointer-events:none;width:100%;height:100%;"></canvas>

<script>
    window.addEventListener('alpine:initialized', function () {
        window.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme: 'dark' } }));
    }, { once: true });

    (function initFasikaParticles() {
        function run() {
            var canvas = document.getElementById('fasika-particles');
            if (!canvas) return;
            var ctx = canvas.getContext('2d');
            var W, H, particles = [];

            function resize() {
                W = canvas.width = canvas.offsetWidth;
                H = canvas.height = canvas.offsetHeight;
            }
            resize();
            window.addEventListener('resize', resize);

            var count = Math.min({{ \App\Services\AbiyTsomStructure::TOTAL_DAYS }}, Math.floor((W * H) / 9000));
            for (var i = 0; i < count; i++) {
                particles.push({
                    x: Math.random() * W,
                    y: Math.random() * H,
                    r: Math.random() * 2 + 0.4,
                    speed: Math.random() * 0.35 + 0.12,
                    opacity: Math.random() * 0.45 + 0.15,
                    drift: (Math.random() - 0.5) * 0.28,
                    phase: Math.random() * Math.PI * 2,
                });
            }

            (function draw() {
                ctx.clearRect(0, 0, W, H);
                for (var j = 0; j < particles.length; j++) {
                    var p = particles[j];
                    p.y -= p.speed;
                    p.x += Math.sin(p.phase) * p.drift;
                    p.phase += 0.01;
                    p.opacity += (Math.random() - 0.5) * 0.018;
                    p.opacity = Math.max(0.08, Math.min(0.65, p.opacity));
                    if (p.y < -10) {
                        p.y = H + 10;
                        p.x = Math.random() * W;
                    }
                    ctx.beginPath();
                    ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                    ctx.fillStyle = 'rgba(245,208,96,' + p.opacity + ')';
                    ctx.fill();
                }
                requestAnimationFrame(draw);
            })();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', run);
        } else {
            run();
        }
    })();
</script>
