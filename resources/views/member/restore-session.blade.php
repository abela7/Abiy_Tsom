<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('app.member_restore_title') }}</title>
    <style>
        body{margin:0;font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:#f6f1e7;color:#0f172a;display:flex;min-height:100vh;align-items:center;justify-content:center;padding:24px}
        .card{width:min(100%,420px);background:#fff;border:1px solid rgba(15,23,42,.08);border-radius:24px;box-shadow:0 24px 60px rgba(15,23,42,.12);padding:28px}
        .spinner{width:36px;height:36px;border:3px solid rgba(10,98,134,.15);border-top-color:#0a6286;border-radius:999px;animation:spin .9s linear infinite;margin:0 auto 16px}
        .title{font-size:1.25rem;font-weight:800;margin:0 0 8px;text-align:center}
        .body{font-size:.95rem;line-height:1.5;color:#475569;text-align:center;margin:0}
        .actions{display:none;margin-top:20px}
        .button{display:inline-flex;align-items:center;justify-content:center;width:100%;min-height:44px;border-radius:14px;background:#0a6286;color:#fff;text-decoration:none;font-weight:700}
        @keyframes spin{to{transform:rotate(360deg)}}
    </style>
</head>
<body>
    <div class="card">
        <div class="spinner" id="restore-spinner"></div>
        <h1 class="title">{{ __('app.member_restore_title') }}</h1>
        <p class="body" id="restore-message">{{ __('app.member_restore_body') }}</p>
        <div class="actions" id="restore-actions">
            <a class="button" href="{{ $homeUrl }}">{{ __('app.member_restore_continue') }}</a>
        </div>
    </div>

    <script>
        (async function () {
            const storageKey = @json($storageKey);
            const restoreUrl = @json($restoreUrl);
            const redirectUrl = @json($redirectUrl);
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            const message = document.getElementById('restore-message');
            const actions = document.getElementById('restore-actions');
            const spinner = document.getElementById('restore-spinner');

            let rememberToken = null;
            try {
                rememberToken = window.localStorage.getItem(storageKey);
            } catch (error) {
                rememberToken = null;
            }

            if (!rememberToken) {
                spinner.style.display = 'none';
                actions.style.display = 'block';
                message.textContent = @json(__('app.member_restore_failed'));
                return;
            }

            try {
                const response = await fetch(restoreUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ remember_token: rememberToken }),
                });

                const data = await response.json().catch(() => ({}));
                if (response.ok && data && data.success) {
                    window.location.replace(redirectUrl);
                    return;
                }

                try { window.localStorage.removeItem(storageKey); } catch (error) {}
            } catch (error) {}

            spinner.style.display = 'none';
            actions.style.display = 'block';
            message.textContent = @json(__('app.member_restore_failed'));
        })();
    </script>
</body>
</html>
