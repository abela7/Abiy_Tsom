{{-- Public Yefasika Beal: full-bleed photo + scrims + floating gold particles (public only). --}}
<style>
    html.ybb-fullbleed,
    html.ybb-fullbleed body {
        margin: 0;
        padding: 0;
        width: 100%;
        max-width: 100%;
        min-height: 100vh;
        min-height: 100svh;
        min-height: 100dvh;
        min-height: -webkit-fill-available;
    }

    html.ybb-fullbleed body {
        overflow-x: hidden;
        display: flex;
        flex-direction: column;
        align-items: stretch;
    }

    html.ybb-fullbleed.dark body { background: transparent !important; }
    html.dark { background: #0f0a1a !important; }

    .ybb-page {
        --color-card: rgba(45, 24, 84, 0.52);
        --color-muted: rgba(26, 14, 46, 0.45);
        --color-border: rgba(245, 208, 96, 0.18);
        --color-primary: #F5D060;
        --color-secondary: rgba(255, 255, 255, 0.75);
        --color-muted-text: rgba(245, 208, 96, 0.55);
    }

    /* Top-to-bottom reading flow (letter), not vertically centered. */
    html.ybb-fullbleed body > main.ybb-page {
        flex: 1 0 auto;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        width: 100%;
        max-width: 36rem;
        margin-left: auto;
        margin-right: auto;
        box-sizing: border-box;
        padding-left: max(1rem, env(safe-area-inset-left, 0px));
        padding-right: max(1rem, env(safe-area-inset-right, 0px));
        padding-top: max(1.25rem, env(safe-area-inset-top, 0px));
        padding-bottom: max(2.5rem, env(safe-area-inset-bottom, 0px));
    }

    /* Full-viewport photo (Tailwind preflight-safe). */
    .ybb-bg-img-fixed {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 0;
        display: block;
        width: 100%;
        height: 100vh;
        height: 100svh;
        height: 100dvh;
        min-height: -webkit-fill-available;
        max-width: none;
        max-height: none;
        margin: 0;
        object-fit: cover;
        object-position: center center;
        transform: scale(1.08);
        transform-origin: center center;
        pointer-events: none;
        user-select: none;
    }

    @supports (height: 100lvh) {
        .ybb-bg-img-fixed {
            height: 100lvh;
        }
    }

    .ybb-bg-scrim {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 1;
        pointer-events: none;
    }

    #ybb-particles.ybb-particles-full {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 2;
        pointer-events: none;
        width: 100%;
        height: 100vh;
        height: 100svh;
        height: 100dvh;
        min-height: -webkit-fill-available;
        overflow: hidden;
    }

    @supports (height: 100lvh) {
        #ybb-particles.ybb-particles-full {
            height: 100lvh;
        }
    }
</style>

<script>document.documentElement.classList.add('ybb-fullbleed');</script>

<img src="{{ asset('images/Jesus_In_Eastern.avif') }}"
     alt=""
     width="1920"
     height="1080"
     decoding="async"
     fetchpriority="high"
     sizes="100vw"
     class="ybb-bg-img-fixed"
     aria-hidden="true">

<div class="ybb-bg-scrim"
     style="background:linear-gradient(to bottom, rgba(22, 18, 38, 0.68), rgba(40, 32, 62, 0.42), rgba(14, 12, 28, 0.76));"
     aria-hidden="true"></div>
<div class="ybb-bg-scrim"
     style="background:radial-gradient(ellipse 120% 70% at 50% 12%, rgba(230, 200, 130, 0.2) 0%, transparent 55%);"
     aria-hidden="true"></div>

<canvas id="ybb-particles"
        class="ybb-particles-full"
        aria-hidden="true"></canvas>

<script>
    (function initYbbParticles() {
        function run() {
            var canvas = document.getElementById('ybb-particles');
            if (!canvas) return;
            var ctx = canvas.getContext('2d');
            var W, H, particles = [];

            function resize() {
                var vv = window.visualViewport;
                var w = vv ? vv.width : window.innerWidth;
                var h = Math.max(
                    window.innerHeight || 0,
                    document.documentElement.clientHeight || 0,
                    vv ? vv.height : 0
                );
                W = canvas.width = Math.max(1, Math.round(w));
                H = canvas.height = Math.max(1, Math.round(h));
            }
            resize();
            window.addEventListener('resize', resize);
            if (window.visualViewport) {
                window.visualViewport.addEventListener('resize', resize);
                window.visualViewport.addEventListener('scroll', resize);
            }

            /* Denser field: area-based count, capped for performance on huge screens. */
            var count = Math.min(260, Math.max(70, Math.floor((W * H) / 3200)));
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
