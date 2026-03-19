<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0a6286">
    <meta name="robots" content="noindex">
    <title>{{ $title ?: __('app.app_name') }}</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        html,body{height:100%;background:#0f172a;font-family:system-ui,-apple-system,sans-serif;color:#f8fafc}
        .c{display:flex;flex-direction:column;min-height:100%;padding:0}

        /* Player section */
        .player-section{background:linear-gradient(180deg,#0a6286 0%,#0f172a 100%);padding:16px 16px 20px}
        .video-wrap{border-radius:12px;overflow:hidden;background:#1e293b;aspect-ratio:16/9;position:relative}
        .video-wrap iframe,.video-wrap img{width:100%;height:100%;border:0;display:block;object-fit:cover}
        .play-btn{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.3);border:0;cursor:pointer}
        .play-btn svg{width:64px;height:64px;filter:drop-shadow(0 2px 8px rgba(0,0,0,.5))}
        .audio-wrap{background:#1e293b;border-radius:12px;padding:16px}
        .audio-wrap audio{width:100%;height:44px;border-radius:8px}

        /* Title */
        .title-section{padding:16px 16px 0}
        .title{font-size:20px;font-weight:700;line-height:1.3}
        .desc{font-size:14px;color:#94a3b8;margin-top:6px;line-height:1.5}

        /* Lyrics */
        .lyrics-section{flex:1;padding:16px;overflow-y:auto}
        .lyrics-header{font-size:13px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:1px;margin-bottom:12px;display:flex;align-items:center;gap:8px}
        .lyrics-header::after{content:'';flex:1;height:1px;background:#334155}
        .lyrics-text{font-size:16px;line-height:2;color:#e2e8f0;white-space:pre-wrap;word-wrap:break-word}

        /* Close */
        .close-section{padding:12px 16px 16px;background:#0f172a}
        .btn{padding:14px 24px;background:#1e293b;color:#94a3b8;border:1px solid #334155;border-radius:12px;font-size:15px;font-weight:600;cursor:pointer;touch-action:manipulation;width:100%;text-align:center}
        .btn:active{opacity:.85}
    </style>
</head>
<body>
    <div class="c">
        {{-- Player --}}
        <div class="player-section">
            @if($videoId)
                <div class="video-wrap" id="player" onclick="loadYT()">
                    <img src="https://i.ytimg.com/vi/{{ $videoId }}/hqdefault.jpg" alt="{{ e($title) }}" loading="eager">
                    <button type="button" class="play-btn" aria-label="{{ __('app.listen') }}">
                        <svg viewBox="0 0 80 80"><circle cx="40" cy="40" r="40" fill="rgba(255,255,255,0.95)"/><polygon points="32,22 62,40 32,58" fill="#0a6286"/></svg>
                    </button>
                </div>
            @elseif($mediaUrl)
                <div class="audio-wrap">
                    <audio controls autoplay controlsList="nodownload" preload="auto">
                        <source src="{{ $mediaUrl }}">
                    </audio>
                </div>
            @endif
        </div>

        {{-- Title --}}
        <div class="title-section">
            <div class="title">{{ $title }}</div>
            @if($description)
                <div class="desc">{{ $description }}</div>
            @endif
        </div>

        {{-- Lyrics --}}
        @if($lyrics && trim($lyrics) !== '')
        <div class="lyrics-section">
            <div class="lyrics-header">{{ $locale === 'am' ? 'ግጥም' : 'Lyrics' }}</div>
            <div class="lyrics-text">{{ $lyrics }}</div>
        </div>
        @endif

        {{-- Close --}}
        <div class="close-section">
            <button type="button" class="btn" onclick="closePage()">{{ __('app.close') }}</button>
        </div>
    </div>

    <script>
        function loadYT() {
            var p = document.getElementById('player');
            if (!p) return;
            p.style.cursor = 'default';
            p.onclick = null;
            p.innerHTML = '<iframe src="https://www.youtube.com/embed/{{ $videoId }}?autoplay=1&rel=0&playsinline=1" allow="accelerometer;autoplay;clipboard-write;encrypted-media;gyroscope;picture-in-picture" allowfullscreen></iframe>';
        }
        function closePage() {
            var twa = window.Telegram && window.Telegram.WebApp;
            if (twa && twa.close) { twa.close(); } else { window.history.back(); }
        }
    </script>
    <script src="https://telegram.org/js/telegram-web-app.js" async onload="(function(){var t=window.Telegram&&window.Telegram.WebApp;if(t){t.expand();t.ready&&t.ready()}})()"></script>
</body>
</html>
