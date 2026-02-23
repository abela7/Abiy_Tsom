<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0a6286">
    <meta name="robots" content="noindex">
    <title>{{ $title ?: __('app.app_name') }}</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        html,body{height:100%;background:#0f172a;font-family:system-ui,sans-serif}
        .c{display:flex;flex-direction:column;min-height:100%;padding:12px}
        .p{flex:1;min-height:0;border-radius:12px;overflow:hidden;background:#1e293b;position:relative;aspect-ratio:16/9;cursor:pointer}
        .p img{width:100%;height:100%;object-fit:cover;display:block}
        .p iframe{width:100%;height:100%;border:0;display:block}
        .play{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.3);border:0;cursor:pointer}
        .play svg{width:72px;height:72px;filter:drop-shadow(0 2px 8px rgba(0,0,0,.6))}
        .t{margin-top:12px;padding:12px 16px;border-radius:12px;background:#1e293b;font-size:16px;font-weight:600;color:#f8fafc;line-height:1.4}
        .b{margin-top:12px;padding:12px 20px;background:#0a6286;color:#fff;border:0;border-radius:12px;font-size:16px;font-weight:600;cursor:pointer;touch-action:manipulation;width:100%}
        .b:active{opacity:.9}
    </style>
</head>
<body>
    <div class="c">
        <div class="p" id="player" onclick="loadPlayer()">
            <img src="https://i.ytimg.com/vi/{{ $videoId }}/hqdefault.jpg"
                 alt="{{ $title ?: __('app.app_name') }}"
                 loading="eager">
            <button type="button" class="play" aria-label="{{ __('app.listen') }}">
                <svg viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="40" cy="40" r="40" fill="rgba(255,255,255,0.95)"/>
                    <polygon points="32,22 62,40 32,58" fill="#0a6286"/>
                </svg>
            </button>
        </div>
        @if($title)
        <div class="t">{{ $title }}</div>
        @endif
        <button type="button" class="b" onclick="closePage()">{{ __('app.close') }}</button>
    </div>
    <script>
        function loadPlayer() {
            var p = document.getElementById('player');
            p.style.cursor = 'default';
            p.innerHTML = '<iframe src="https://www.youtube.com/embed/{{ $videoId }}?autoplay=1&rel=0&playsinline=1" title="{{ e($title ?: __('app.app_name')) }}" allow="accelerometer;autoplay;clipboard-write;encrypted-media;gyroscope;picture-in-picture" allowfullscreen style="width:100%;height:100%;border:0;display:block"></iframe>';
        }
        function closePage() {
            var twa = window.Telegram && window.Telegram.WebApp;
            if (twa && twa.close) { twa.close(); } else { window.history.back(); }
        }
    </script>
    <script src="https://telegram.org/js/telegram-web-app.js" async onload="(function(){var t=window.Telegram&&window.Telegram.WebApp;if(t){t.expand();t.ready&&t.ready()}})()"></script>
</body>
</html>
