{{-- Fasika: shell copied from Good Friday (day.blade.php); enhance visuals later --}}
<style>
    html.dark body { background: transparent !important; }
    html.dark { background: #030303 !important; }
    .fasika-page {
        --color-card: rgba(10, 10, 18, 0.52);
        --color-muted: rgba(15, 15, 25, 0.45);
        --color-border: rgba(255, 255, 255, 0.09);
    }
    .fasika-page > * {
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
    }
    /* ── Timeline node dots → blood red ── */
    .fasika-page [data-timeline-node] {
        background-color: rgb(140,0,0) !important;
        box-shadow: 0 0 10px rgba(200,0,0,0.45), 0 1px 4px rgba(0,0,0,0.7) !important;
    }
    .fasika-page [data-timeline-node] [class~="animate-ping"] {
        background-color: rgba(220,30,30,0.55) !important;
    }
    /* Timeline connector line → dark maroon */
    .fasika-page [data-timeline-line] {
        background: rgba(100,0,0,0.35) !important;
    }

    /* FAQ modal & other fixed overlays — fully solid so text is readable */
    .fasika-page [class~="z-50"],
    .fasika-page [class~="z-50"] * {
        --color-card: rgb(13, 13, 20);
        --color-muted: rgb(20, 20, 30);
        --color-border: rgba(255,255,255,0.13);
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
        background: linear-gradient(90deg,#B8860B 0%,#F5E6A3 30%,#D4A537 50%,#FFF8DC 70%,#B8860B 100%);
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
<div style="position:fixed;inset:0;z-index:0;
     background-image:url('https://abiytsom.abuneteklehaymanot.org/images/Jesus-.jpg');
     background-size:cover;background-position:center top;background-repeat:no-repeat;">
    <div style="position:absolute;inset:0;background:rgba(0,0,0,0.42);"></div>
</div>
<div id="fasika-blood-wrap" style="position:fixed;inset:0;z-index:3;pointer-events:none;overflow:hidden;transform:translateZ(0);"></div>
<style>
/* Y-axis fall — ease-in simulates gravity acceleration */
@-webkit-keyframes gf-fall{
  0%  {-webkit-transform:translate3d(0,-90px,0);opacity:0}
  5%  {opacity:1}
  82% {opacity:.92}
  96% {-webkit-transform:translate3d(0,calc(var(--gf-end) - 30px),0);opacity:.22}
  100%{-webkit-transform:translate3d(0,var(--gf-end),0);opacity:0}
}
@keyframes gf-fall{
  0%  {transform:translate3d(0,-90px,0);opacity:0}
  5%  {opacity:1}
  82% {opacity:.92}
  96% {transform:translate3d(0,calc(var(--gf-end) - 30px),0);opacity:.22}
  100%{transform:translate3d(0,var(--gf-end),0);opacity:0}
}
/* Subtle X-axis drift — amplitude set per drop via --gf-sx */
@-webkit-keyframes gf-sway{
  0%  {-webkit-transform:translateX(0)}
  30% {-webkit-transform:translateX(var(--gf-sx,0px))}
  65% {-webkit-transform:translateX(calc(var(--gf-sx,0px) * -.55))}
  100%{-webkit-transform:translateX(0)}
}
@keyframes gf-sway{
  0%  {transform:translateX(0)}
  30% {transform:translateX(var(--gf-sx,0px))}
  65% {transform:translateX(calc(var(--gf-sx,0px) * -.55))}
  100%{transform:translateX(0)}
}
/* Splash burst at impact */
@-webkit-keyframes gf-splash{
  0%,88%{opacity:0;-webkit-transform:translateX(-50%) scale(.4)}
  94%   {opacity:1;-webkit-transform:translateX(-50%) scale(1)}
  100%  {opacity:0;-webkit-transform:translateX(-50%) scale(3.5)}
}
@keyframes gf-splash{
  0%,88%{opacity:0;transform:translateX(-50%) scale(.4)}
  94%   {opacity:1;transform:translateX(-50%) scale(1)}
  100%  {opacity:0;transform:translateX(-50%) scale(3.5)}
}
</style>
<script>
    window.addEventListener('alpine:initialized', function () {
        window.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme: 'dark' } }));
    }, { once: true });

    /* ── Physics blood-drop that slides from first to last timeline node ── */
    (function gfTimelineDrop(){
        var NS = 'http://www.w3.org/2000/svg';

        function build(){
            var nodes = document.querySelectorAll('.fasika-page [data-timeline-node]');
            if (nodes.length < 2) return;

            var first = nodes[0];
            var last  = nodes[nodes.length - 1];
            var container = first.closest('.relative');
            if (!container) return;

            var cr  = container.getBoundingClientRect();
            var fr  = first.getBoundingClientRect();
            var lr  = last.getBoundingClientRect();
            var cx  = fr.left + fr.width  / 2 - cr.left;   /* rail centre X */
            var sy  = fr.top  + fr.height / 2 - cr.top;    /* start Y (first dot centre) */
            var ey  = lr.top  + lr.height / 2 - cr.top;    /* end Y   (last  dot centre) */
            var H   = ey - sy;
            if (H < 20) return;

            /* Ensure container is positioned */
            if (getComputedStyle(container).position === 'static')
                container.style.position = 'relative';

            /* ── Trail smear: full height, scaled from 0 at top ── */
            /* Gradient: barely visible at top (old/dry blood), builds bold toward drop tip */
            var trail = document.createElement('div');
            trail.style.cssText = 'position:absolute;pointer-events:none;z-index:4;'
                + 'left:'+(cx-1)+'px;top:'+sy+'px;'
                + 'width:2px;height:'+H+'px;'
                + 'background:linear-gradient(180deg,'
                +   'rgba(100,0,0,0.05) 0%,'
                +   'rgba(125,0,0,0.22) 40%,'
                +   'rgba(160,0,0,0.78) 80%,'
                +   'rgba(145,0,0,0.55) 100%);'
                + 'transform:scaleY(0);transform-origin:top center;'
                + 'border-radius:1px;'
                + 'will-change:transform;';
            container.appendChild(trail);

            /* ── Timeline drop: simple oval with soft inner glow — distinct from main drops ── */
            var DW = 8, DH = 13;
            var gId = 'gfTg'+(Math.random()*1e9|0);
            var svg = document.createElementNS(NS,'svg');
            svg.setAttribute('width', DW);
            svg.setAttribute('height', DH);
            svg.setAttribute('viewBox','-4 -9 8 13');
            svg.style.cssText = 'display:block;overflow:visible;';

            var defs = document.createElementNS(NS,'defs');
            /* Simple 3-stop radial — warm centre, deep dark edge, no focal offset */
            var rg = document.createElementNS(NS,'radialGradient');
            rg.setAttribute('id', gId);
            rg.setAttribute('cx','42%'); rg.setAttribute('cy','35%'); rg.setAttribute('r','60%');
            [['0','rgba(230,60,60,1)'],['0.55','rgba(130,0,0,1)'],['1','rgba(30,0,0,1)']
            ].forEach(function(s){
                var st=document.createElementNS(NS,'stop');
                st.setAttribute('offset',s[0]);
                st.setAttribute('stop-color',s[1]);
                rg.appendChild(st);
            });
            defs.appendChild(rg);
            svg.appendChild(defs);

            /* Rounded teardrop body — slightly fatter/softer than the main drops */
            var body = document.createElementNS(NS,'path');
            body.setAttribute('d','M0,-8 C-1.8,-5.5 -4,-2 -4,1 C-4,3.5 -2.2,4.5 0,4.5 C2.2,4.5 4,3.5 4,1 C4,-2 1.8,-5.5 0,-8 Z');
            body.setAttribute('fill','url(#'+gId+')');
            svg.appendChild(body);

            /* Single soft inner highlight — keeps it readable but simple */
            var hl = document.createElementNS(NS,'ellipse');
            hl.setAttribute('cx','-1'); hl.setAttribute('cy','-4');
            hl.setAttribute('rx','1.2'); hl.setAttribute('ry','2');
            hl.setAttribute('fill','rgba(255,200,200,0.28)');
            hl.setAttribute('transform','rotate(-18,-1,-4)');
            svg.appendChild(hl);

            var drop = document.createElement('div');
            drop.style.cssText = 'position:absolute;pointer-events:none;z-index:5;'
                + 'left:'+(cx - DW/2)+'px;top:'+(sy - DH*0.7)+'px;'
                + 'width:'+DW+'px;height:'+DH+'px;'
                + 'will-change:transform,opacity;'
                + '-webkit-backface-visibility:hidden;backface-visibility:hidden;';
            drop.appendChild(svg);
            container.appendChild(drop);

            /* ── Physics easing: surface-tension hold → breakaway → slide → absorb ── */
            function ease(t){
                if (t < 0.10) return Math.pow(t/0.10, 3) * 0.015;          /* hold */
                if (t < 0.22) { var a=(t-0.10)/0.12; return 0.015+a*a*0.185; } /* break */
                if (t < 0.88) return 0.20 + (t-0.22)/0.66 * 0.77;          /* slide */
                var b=(t-0.88)/0.12; return 0.97 + b*b*0.03;                /* absorb */
            }

            var FALL_MS  = 7500;   /* time to slide full rail */
            var PAUSE_MS = 2800;   /* rest + fade at bottom, then reset */
            var CYCLE    = FALL_MS + PAUSE_MS;
            var t0 = null;

            function frame(ts){
                if(!t0) t0=ts;
                var e = (ts-t0) % CYCLE;

                /* ── Live recalc: keeps drop correct when cards expand ── */
                var cr2 = container.getBoundingClientRect();
                var fr2 = first.getBoundingClientRect();
                var lr2 = last.getBoundingClientRect();
                var sy2 = fr2.top + fr2.height/2 - cr2.top;
                var H2  = (lr2.top + lr2.height/2) - (fr2.top + fr2.height/2);
                if (H2 > 20) {
                    trail.style.top    = sy2 + 'px';
                    trail.style.height = H2  + 'px';
                    drop.style.top     = (sy2 - DH * 0.7) + 'px';
                }
                var liveH = H2 > 20 ? H2 : H;

                if (e < FALL_MS) {
                    var t = e / FALL_MS;
                    var p = ease(t);
                    var y = p * liveH;

                    /* Squash-stretch: elongate as it speeds up */
                    var speed = t > 0.22 ? Math.min(1.35, 1 + (t-0.22)*0.55) : 1;
                    drop.style.transform  = 'translateY('+y.toFixed(1)+'px) scaleY('+speed.toFixed(3)+') scaleX('+(1/speed).toFixed(3)+')';
                    drop.style.opacity    = '1';
                    trail.style.transform = 'scaleY('+Math.max(0,p).toFixed(4)+')';
                    trail.style.opacity   = '1';

                } else {
                    var pt = (e - FALL_MS) / PAUSE_MS;

                    if (pt < 0.35) {
                        /* Drop "absorbed" — squash flat */
                        var sq = 1 - pt/0.35 * 0.85;
                        drop.style.transform = 'translateY('+liveH.toFixed(1)+'px) scaleY('+Math.max(0.15,sq).toFixed(3)+') scaleX('+(1+(1-sq)*0.5).toFixed(3)+')';
                        drop.style.opacity   = '1';
                    } else {
                        /* Fade out both */
                        var fo = Math.max(0, 1 - (pt-0.35)/0.3);
                        drop.style.opacity  = fo.toFixed(3);
                        trail.style.opacity = fo.toFixed(3);
                    }

                    if (pt > 0.72) {
                        /* Reset invisibly */
                        drop.style.transform  = 'translateY(0) scaleY(1) scaleX(1)';
                        trail.style.transform = 'scaleY(0)';
                    }
                }
                requestAnimationFrame(frame);
            }
            requestAnimationFrame(frame);
        }

        /* Run after Alpine + DOM have settled */
        window.addEventListener('alpine:initialized', function(){
            setTimeout(build, 900);
        });
        setTimeout(build, 1300); /* fallback for non-Alpine contexts */
    })();

    (function(){
        var wrap = document.getElementById('fasika-blood-wrap');
        if (!wrap) return;
        var NS = 'http://www.w3.org/2000/svg';
        var active = 0, MAX = 3;
        var fallEnd = Math.max(window.innerHeight + 100, 1100);

        function uid(){ return 'gf'+Math.random().toString(36).slice(2); }

        /* Build one SVG stop */
        function stop(rg, offset, color){
            var s = document.createElementNS(NS,'stop');
            s.setAttribute('offset', offset);
            s.setAttribute('stop-color', color);
            rg.appendChild(s);
        }

        /* Build one SVG ellipse */
        function ellipse(g, cx,cy,rx,ry,fill,rot){
            var e = document.createElementNS(NS,'ellipse');
            e.setAttribute('cx',cx); e.setAttribute('cy',cy);
            e.setAttribute('rx',rx); e.setAttribute('ry',ry);
            e.setAttribute('fill',fill);
            if(rot) e.setAttribute('transform','rotate('+rot+','+cx+','+cy+')');
            g.appendChild(e);
        }

        function spawn(){
            if (active >= MAX) return;
            active++;

            var gradId = uid();
            var clipId = uid();
            /* Small drop: 5–10 px rendered radius */
            var size   = 5 + Math.random() * 5;
            var dur    = (1.15 + Math.random() * 0.9).toFixed(2);
            var x      = 18 + Math.random() * (window.innerWidth - 36);
            var swayAmt= ((2.5 + Math.random() * 3) * (Math.random() > .5 ? 1 : -1)).toFixed(1);
            var swayDur= (0.65 + Math.random() * 0.55).toFixed(2);
            var trailW = Math.max(1.2, size * 0.22);

            /* ── Outer wrapper: Y fall only (ease-in = gravity) ── */
            var el = document.createElement('div');
            el.style.cssText = 'position:absolute;top:0;left:'+x+'px;'
                + '--gf-end:'+fallEnd+'px;'
                + 'will-change:transform,opacity;'
                + '-webkit-backface-visibility:hidden;backface-visibility:hidden;'
                + '-webkit-animation:gf-fall '+dur+'s cubic-bezier(0.42,0,1,1) forwards;'
                + 'animation:gf-fall '+dur+'s cubic-bezier(0.42,0,1,1) forwards;';

            /* ── Inner div: X sway (independent period) ── */
            var inner = document.createElement('div');
            inner.style.cssText = '--gf-sx:'+swayAmt+'px;'
                + 'will-change:transform;'
                + '-webkit-animation:gf-sway '+swayDur+'s ease-in-out infinite;'
                + 'animation:gf-sway '+swayDur+'s ease-in-out infinite;';

            /* ── Trail ── */
            var trail = document.createElement('div');
            trail.style.cssText = 'position:absolute;bottom:'+(size*1.55)+'px;left:50%;'
                + '-webkit-transform:translateX(-50%);transform:translateX(-50%);'
                + 'width:'+trailW+'px;height:48px;'
                + 'background:linear-gradient(180deg,'
                +   'rgba(110,0,0,0) 0%,'
                +   'rgba(145,0,0,.14) 48%,'
                +   'rgba(172,0,0,.42) 100%);'
                + 'border-radius:1px 1px 0 0;';
            inner.appendChild(trail);

            /* ── SVG teardrop ── */
            var svgW = size * 2;
            var svgH = size * 3.0;
            var svg = document.createElementNS(NS,'svg');
            svg.setAttribute('width',  svgW);
            svg.setAttribute('height', svgH);
            /* Path spans -20..20 wide, -38..35 tall → viewBox center at (0,-1.5) */
            svg.setAttribute('viewBox','-22 -40 44 78');
            svg.style.cssText = 'display:block;overflow:visible;';

            var defs = document.createElementNS(NS,'defs');

            /* Radial gradient with focal point (fx,fy) for realistic off-center highlight */
            var rg = document.createElementNS(NS,'radialGradient');
            rg.setAttribute('id',   gradId);
            rg.setAttribute('cx',   '40%');  rg.setAttribute('cy',   '30%');
            rg.setAttribute('fx',   '28%');  rg.setAttribute('fy',   '18%');
            rg.setAttribute('r',    '68%');
            stop(rg, 0,    'rgb(255,62,62)');   /* bright highlight */
            stop(rg, 0.14, 'rgb(218,16,16)');   /* vivid red */
            stop(rg, 0.38, 'rgb(158,4,4)');     /* mid red */
            stop(rg, 0.68, 'rgb(72,0,0)');      /* deep shadow */
            stop(rg, 1,    'rgb(12,0,0)');      /* near-black rim */
            defs.appendChild(rg);

            /* Clip path so highlights never bleed outside the drop */
            var cp   = document.createElementNS(NS,'clipPath');
            cp.setAttribute('id', clipId);
            var cpPath = document.createElementNS(NS,'path');
            cpPath.setAttribute('d',
                'M0,-38 C-7,-30 -20,-13 -20,5 C-20,25 -10,35 0,35'
               +' C10,35 20,25 20,5 C20,-13 7,-30 0,-38 Z');
            cp.appendChild(cpPath);
            defs.appendChild(cp);

            svg.appendChild(defs);

            /* Main body */
            var body = document.createElementNS(NS,'path');
            body.setAttribute('d',
                'M0,-38 C-7,-30 -20,-13 -20,5 C-20,25 -10,35 0,35'
               +' C10,35 20,25 20,5 C20,-13 7,-30 0,-38 Z');
            body.setAttribute('fill','url(#'+gradId+')');
            svg.appendChild(body);

            /* Highlights — all clipped inside drop shape */
            var hGroup = document.createElementNS(NS,'g');
            hGroup.setAttribute('clip-path','url(#'+clipId+')');

            /* 1. Diffuse glow — large soft upper-left area */
            ellipse(hGroup, -7,-14, 10,15, 'rgba(255,155,155,0.14)', -22);
            /* 2. Primary specular — medium bright */
            ellipse(hGroup, -5,-21,  5, 8, 'rgba(255,225,225,0.30)', -16);
            /* 3. Sharp specular — small intense spot */
            ellipse(hGroup, -3,-29,  2, 3.5,'rgba(255,250,250,0.52)', -10);
            /* 4. Bottom glint — faint inner reflection at base */
            ellipse(hGroup,  2, 27,  4, 2.5,'rgba(255,90,90,0.09)',     0);

            svg.appendChild(hGroup);
            inner.appendChild(svg);
            el.appendChild(inner);

            /* ── Splash at impact point ── */
            var splash = document.createElement('div');
            splash.style.cssText = 'position:absolute;bottom:8vh;left:'+x+'px;'
                + 'width:4px;height:4px;opacity:0;'
                + 'will-change:opacity,transform;'
                + '-webkit-backface-visibility:hidden;backface-visibility:hidden;'
                + '-webkit-animation:gf-splash '+dur+'s cubic-bezier(0.42,0,1,1) forwards;'
                + 'animation:gf-splash '+dur+'s cubic-bezier(0.42,0,1,1) forwards;';
            /* 3 particles: center blob + 2 satellites */
            [
                'position:absolute;width:7px;height:7px;border-radius:50%;background:rgba(155,0,0,.60);top:-3px;left:-3px;',
                'position:absolute;width:3px;height:3px;border-radius:50%;background:rgba(130,0,0,.52);top:-13px;left:-9px;',
                'position:absolute;width:2px;height:2px;border-radius:50%;background:rgba(125,0,0,.46);top:-8px;left:10px;',
            ].forEach(function(css){
                var p = document.createElement('div');
                p.style.cssText = css;
                splash.appendChild(p);
            });
            wrap.appendChild(splash);
            wrap.appendChild(el);

            function done(){
                try{wrap.removeChild(el);}catch(e){}
                try{wrap.removeChild(splash);}catch(e){}
                active--;
            }
            el.addEventListener('animationend',       done);
            el.addEventListener('webkitAnimationEnd', done);
        }

        setTimeout(spawn, 400);
        (function tick(){ spawn(); setTimeout(tick, 2200 + Math.random()*1400); })();
    })();
</script>

