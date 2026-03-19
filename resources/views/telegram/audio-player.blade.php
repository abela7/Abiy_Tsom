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
        html,body{height:100%;background:#0f172a;font-family:system-ui,-apple-system,sans-serif;color:#f8fafc}
        .c{display:flex;flex-direction:column;min-height:100%;padding:16px;gap:16px}
        .icon{width:120px;height:120px;margin:24px auto;background:linear-gradient(135deg,#0a6286,#0d9488);border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 8px 32px rgba(10,98,134,.4)}
        .icon svg{width:56px;height:56px;fill:#fff}
        .t{text-align:center;font-size:20px;font-weight:700;line-height:1.3;padding:0 8px}
        .player{background:#1e293b;border-radius:16px;padding:20px;margin-top:auto}
        .player audio{width:100%;height:48px;border-radius:8px;outline:none}
        .btn{padding:14px 24px;background:#0a6286;color:#fff;border:0;border-radius:12px;font-size:16px;font-weight:600;cursor:pointer;touch-action:manipulation;width:100%;text-align:center}
        .btn:active{opacity:.85}
    </style>
</head>
<body>
    <div class="c">
        <div class="icon">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55C7.79 13 6 14.79 6 17s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/></svg>
        </div>

        @if($title)
        <div class="t">{{ $title }}</div>
        @endif

        <div class="player">
            <audio controls autoplay controlsList="nodownload" preload="auto">
                <source src="{{ $audioUrl }}">
            </audio>
        </div>

        <button type="button" class="btn" onclick="closePage()">{{ __('app.close') }}</button>
    </div>
    <script>
        function closePage() {
            var twa = window.Telegram && window.Telegram.WebApp;
            if (twa && twa.close) { twa.close(); } else { window.history.back(); }
        }
    </script>
    <script src="https://telegram.org/js/telegram-web-app.js" async onload="(function(){var t=window.Telegram&&window.Telegram.WebApp;if(t){t.expand();t.ready&&t.ready()}})()"></script>
</body>
</html>
