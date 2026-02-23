<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0a6286">
    <meta name="robots" content="noindex">
    <title>{{ __('app.video_player') }} - {{ __('app.app_name') }}</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; background: #0f172a; }
        .container { display: flex; flex-direction: column; min-height: 100%; padding: 12px; }
        .player-wrap { flex: 1; min-height: 0; border-radius: 12px; overflow: hidden; background: #1e293b; }
        .player-wrap iframe { width: 100%; height: 100%; min-height: 200px; border: none; display: block; }
        .content-area { margin-top: 12px; padding: 12px 16px; border-radius: 12px; background: #1e293b; min-height: 60px; }
        .content-area .title { font-size: 16px; font-weight: 600; color: #f8fafc; line-height: 1.4; }
        .content-area .thumb { width: 100%; max-height: 140px; object-fit: cover; border-radius: 8px; margin-bottom: 10px; }
        .close-btn { margin-top: 12px; padding: 12px 20px; background: #0a6286; color: white; border: none; border-radius: 12px; font-size: 16px; font-weight: 600; cursor: pointer; }
        .close-btn:active { opacity: 0.9; }
    </style>
</head>
<body>
    <div class="container">
        <div class="player-wrap">
            <iframe
                src="https://www.youtube.com/embed/{{ $videoId }}?autoplay=1"
                title="{{ __('app.video_player') }}"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                allowfullscreen
            ></iframe>
        </div>
        <div class="content-area">
            <img src="{{ $mainImageUrl }}" alt="{{ __('app.app_name') }}" class="thumb">
            @if(!empty($title))
                <p class="title">{{ $title }}</p>
            @else
                <p class="title">{{ __('app.app_name') }}</p>
            @endif
        </div>
        <button type="button" class="close-btn" onclick="closeWebApp()">
            {{ __('app.close') }}
        </button>
    </div>
    <script>
        function closeWebApp() {
            if (window.Telegram?.WebApp?.close) {
                window.Telegram.WebApp.close();
            } else {
                window.history.back();
            }
        }
        if (window.Telegram?.WebApp) {
            window.Telegram.WebApp.expand();
            window.Telegram.WebApp.ready?.();
        }
    </script>
</body>
</html>
