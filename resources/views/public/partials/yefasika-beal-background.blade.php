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

    #ybb-particles.ybb-particles-full,
    #ybb-particles-front.ybb-particles-foreground {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        pointer-events: none;
        width: 100%;
        height: 100vh;
        height: 100svh;
        height: 100dvh;
        min-height: -webkit-fill-available;
        overflow: hidden;
    }

    #ybb-particles.ybb-particles-full {
        z-index: 2;
    }

    /* Soft dust in front of cards (still non-interactive). */
    #ybb-particles-front.ybb-particles-foreground {
        z-index: 11;
    }

    @supports (height: 100lvh) {
        #ybb-particles.ybb-particles-full,
        #ybb-particles-front.ybb-particles-foreground {
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
<canvas id="ybb-particles-front"
        class="ybb-particles-foreground"
        aria-hidden="true"></canvas>

<script>
    (function initYbbParticles() {
        function makeParticles(n, W, H, opts) {
            opts = opts || {};
            var rMul = opts.rMul == null ? 1 : opts.rMul;
            var speedMul = opts.speedMul == null ? 1 : opts.speedMul;
            var opMax = opts.opMax == null ? 0.65 : opts.opMax;
            var list = [];
            for (var i = 0; i < n; i++) {
                list.push({
                    x: Math.random() * W,
                    y: Math.random() * H,
                    r: (Math.random() * 2 + 0.4) * rMul,
                    speed: (Math.random() * 0.35 + 0.12) * speedMul,
                    dir: Math.random() < 0.5 ? -1 : 1,
                    opacity: Math.random() * Math.min(0.45, opMax - 0.1) + 0.12,
                    opMax: opMax,
                    drift: (Math.random() - 0.5) * 0.28,
                    phase: Math.random() * Math.PI * 2,
                });
            }
            return list;
        }

        function wrapParticle(p, W, H) {
            if (p.y < -12) {
                p.y = H + 12 + Math.random() * 120;
                p.x = Math.random() * W;
                if (Math.random() < 0.35) {
                    p.dir = Math.random() < 0.5 ? -1 : 1;
                }
            } else if (p.y > H + 12) {
                p.y = -12 - Math.random() * 120;
                p.x = Math.random() * W;
                if (Math.random() < 0.35) {
                    p.dir = Math.random() < 0.5 ? -1 : 1;
                }
            }
            if (p.x < -12) {
                p.x = W + 12;
            } else if (p.x > W + 12) {
                p.x = -12;
            }
        }

        function run() {
            var canvasBack = document.getElementById('ybb-particles');
            var canvasFront = document.getElementById('ybb-particles-front');
            if (!canvasBack || !canvasFront) {
                return;
            }
            var ctxBack = canvasBack.getContext('2d');
            var ctxFront = canvasFront.getContext('2d');
            var W = 1;
            var H = 1;
            var particlesBack = [];
            var particlesFront = [];

            function resize() {
                var vv = window.visualViewport;
                var w = vv ? vv.width : window.innerWidth;
                var h = Math.max(
                    window.innerHeight || 0,
                    document.documentElement.clientHeight || 0,
                    vv ? vv.height : 0
                );
                W = Math.max(1, Math.round(w));
                H = Math.max(1, Math.round(h));
                canvasBack.width = W;
                canvasBack.height = H;
                canvasFront.width = W;
                canvasFront.height = H;
                var base = Math.min(260, Math.max(70, Math.floor((W * H) / 3200)));
                particlesBack = makeParticles(base, W, H, { opMax: 0.62 });
                var frontCount = Math.min(110, Math.max(28, Math.floor(base * 0.38)));
                particlesFront = makeParticles(frontCount, W, H, {
                    rMul: 0.75,
                    speedMul: 0.55,
                    opMax: 0.38,
                });
            }
            resize();
            window.addEventListener('resize', resize);
            if (window.visualViewport) {
                window.visualViewport.addEventListener('resize', resize);
                window.visualViewport.addEventListener('scroll', resize);
            }

            function stepAndDraw(ctx, particles) {
                ctx.clearRect(0, 0, W, H);
                for (var j = 0; j < particles.length; j++) {
                    var p = particles[j];
                    p.y += p.speed * p.dir;
                    p.x += Math.sin(p.phase) * p.drift;
                    p.phase += 0.011;
                    p.opacity += (Math.random() - 0.5) * 0.016;
                    p.opacity = Math.max(0.06, Math.min(p.opMax, p.opacity));
                    wrapParticle(p, W, H);
                    ctx.beginPath();
                    ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                    ctx.fillStyle = 'rgba(245,208,96,' + p.opacity + ')';
                    ctx.fill();
                }
            }

            (function draw() {
                stepAndDraw(ctxBack, particlesBack);
                stepAndDraw(ctxFront, particlesFront);
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
