<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0a6286">
    <meta name="robots" content="noindex">
    <title>{{ __('app.nav_home') }} - {{ __('app.app_name') }}</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        html,body{height:100%;background:#0f172a;font-family:system-ui,sans-serif;color:#f8fafc}
        .c{display:flex;flex-direction:column;min-height:100%;padding:12px;gap:12px}
        .btn-today{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 20px;background:linear-gradient(135deg,#c9a227,#e2ca18);border-radius:16px;border:0;width:100%;text-decoration:none;color:#0f172a;font-size:18px;font-weight:600;cursor:pointer;touch-action:manipulation}
        .btn-today:active{opacity:.95}
        .btn-today svg{width:24px;height:24px;flex-shrink:0}
        .countdown{background:linear-gradient(135deg,#0a6286,#134e5e);border-radius:16px;padding:16px;position:relative;overflow:hidden}
        .countdown:before{content:'';position:absolute;top:-48px;right:-48px;width:128px;height:128px;border-radius:50%;background:rgba(226,202,24,.2);filter:blur(40px)}
        .countdown h2{font-size:18px;font-weight:800;margin-bottom:12px;padding-right:0}
        .countdown .grid{display:grid;grid-template-columns:repeat(4,1fr);gap:8px}
        .countdown .cell{display:flex;flex-direction:column;align-items:center;padding:12px 8px;background:rgba(255,255,255,.08);border-radius:12px;border:1px solid rgba(255,255,255,.1)}
        .countdown .cell span{font-size:24px;font-weight:800;tabular-nums:1;line-height:1}
        .countdown .cell small{font-size:9px;text-transform:uppercase;letter-spacing:.1em;margin-top:4px;opacity:.7}
        .countdown .bar{margin-top:12px;height:6px;background:rgba(255,255,255,.1);border-radius:999px;overflow:hidden}
        .countdown .bar-fill{height:100%;background:#e2ca18;border-radius:999px;transition:width 1s ease-out}
        .countdown.done{text-align:center;padding:32px 16px}
        .countdown.done h2{font-size:24px;margin-bottom:8px}
        .countdown.done p{opacity:.8;font-size:14px}
        .btn-close{margin-top:auto;padding:12px 20px;background:#0a6286;color:#fff;border:0;border-radius:12px;font-size:16px;font-weight:600;cursor:pointer;touch-action:manipulation;width:100%}
        .btn-close:active{opacity:.9}
    </style>
</head>
<body>
    <div class="c">
        @if($todayUrl)
        <a href="{{ $todayUrl }}" class="btn-today">
            <span>{{ $viewTodayLabel }}</span>
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
        </a>
        @endif

        <div class="countdown" id="countdown">
            <h2>{{ __('app.easter_countdown') }}</h2>
            <div id="countdown-active">
                <div class="grid">
                    <div class="cell"><span id="d">0</span><small>{{ __('app.days') }}</small></div>
                    <div class="cell"><span id="h">0</span><small>{{ __('app.hours') }}</small></div>
                    <div class="cell"><span id="m">0</span><small>{{ __('app.minutes') }}</small></div>
                    <div class="cell"><span id="s">0</span><small>{{ __('app.seconds') }}</small></div>
                </div>
                <div class="bar"><div class="bar-fill" id="bar-fill" style="width:100%"></div></div>
            </div>
            <div id="countdown-done" style="display:none">
                <h2>{{ __('app.christ_is_risen') }}</h2>
                <p>{{ __('app.easter_countdown_subtitle') }}</p>
            </div>
        </div>

        <button type="button" class="btn-close" onclick="closePage()">{{ __('app.close') }}</button>
    </div>
    <script>
        (function(){
            var easterIso = '{{ $easterAt->format('c') }}';
            var lentIso = '{{ $lentStartAt->format('c') }}';
            var target = new Date(easterIso);
            var lentStart = new Date(lentIso);
            var totalWindow = Math.max(1, (target - lentStart) / 1000);

            function pad(n){ return String(Math.max(0, n)).padStart(2, '0'); }

            function tick(){
                var now = new Date();
                var diff = Math.max(0, Math.floor((target - now) / 1000));
                var d = Math.floor(diff / 86400);
                var h = Math.floor((diff % 86400) / 3600);
                var m = Math.floor((diff % 3600) / 60);
                var s = diff % 60;
                var pct = Math.min(100, Math.max(0, (diff / totalWindow) * 100));

                document.getElementById('d').textContent = pad(d);
                document.getElementById('h').textContent = pad(h);
                document.getElementById('m').textContent = pad(m);
                document.getElementById('s').textContent = pad(s);
                document.getElementById('bar-fill').style.width = Math.max(0, 100 - pct) + '%';

                if (diff <= 0) {
                    document.getElementById('countdown-active').style.display = 'none';
                    document.getElementById('countdown-done').style.display = 'block';
                    document.getElementById('countdown').classList.add('done');
                    clearInterval(iv);
                }
            }

            tick();
            var iv = setInterval(tick, 1000);
        })();
        function closePage(){
            var t = window.Telegram && window.Telegram.WebApp;
            if (t && t.close) t.close(); else window.history.back();
        }
    </script>
    <script src="https://telegram.org/js/telegram-web-app.js" async onload="(function(){var t=window.Telegram&&window.Telegram.WebApp;if(t){t.expand();t.ready&&t.ready()}})()"></script>
</body>
</html>
