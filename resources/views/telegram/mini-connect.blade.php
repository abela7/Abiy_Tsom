@extends('layouts.member-guest')

@section('title', 'Telegram Mini App Login')

@section('content')
    <div class="bg-card border border-border rounded-2xl p-6 shadow-sm max-w-2xl mx-auto">
        <h1 class="text-2xl font-bold text-primary mb-2">Telegram mini app login</h1>
        <p id="status-message" class="text-sm text-muted-text mb-4">
            Open this page from Telegram and we will continue automatically.
        </p>

        <div id="manual-block" class="hidden">
            <label for="access-code" class="block text-sm font-medium text-secondary mb-1">
                One-time Telegram link code (used only if you opened this page outside Telegram)
            </label>
            <input id="access-code"
                   type="text"
                   class="w-full px-3 py-2 border border-border rounded-lg bg-surface text-primary focus:ring-2 focus:ring-accent outline-none font-mono text-sm"
                   value="{{ $prefilledCode }}"
                   autocomplete="one-time-code"
                   inputmode="text"
                   placeholder="Example: A3b...">
        </div>

        <div class="mt-4 space-y-2">
            <button id="open-btn"
                    type="button"
                    class="w-full bg-accent text-on-accent rounded-lg px-4 py-2.5 font-medium hover:bg-accent-hover transition">
                Continue
            </button>

            <a href="{{ route('home') }}"
               class="block text-center px-4 py-2.5 rounded-lg border border-border text-sm text-secondary hover:bg-muted transition">
                Continue in normal browser
            </a>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const submitUrl = '{{ route('telegram.mini.connect.submit') }}';
            const launchUrl = new URL('{{ $telegramAccessUrl }}', window.location.origin);
            const prefilledCode = @json($prefilledCode);
            const purposeHint = @json($purposeHint);
            const manualBlock = document.getElementById('manual-block');
            const accessInput = document.getElementById('access-code');
            const status = document.getElementById('status-message');
            const continueBtn = document.getElementById('open-btn');
            const token = document.querySelector('meta[name="csrf-token"]')?.content || '';

            const telegram = window.Telegram?.WebApp;
            if (telegram) {
                telegram.expand();
                telegram.ready?.();
            }

            const showMessage = (message, type = 'info') => {
                if (!status) {
                    return;
                }

                status.textContent = message;
                status.classList.remove('text-muted-text', 'text-amber-400', 'text-red-400');

                if (type === 'error') {
                    status.classList.add('text-red-400');
                } else if (type === 'warn') {
                    status.classList.add('text-amber-400');
                } else {
                    status.classList.add('text-muted-text');
                }
            };

            const forceManualMode = (message, type = 'warn') => {
                manualBlock?.classList.remove('hidden');
                if (message) {
                    showMessage(message, type);
                }
            };

            const normalizeCode = (value) => (value || '').toString().trim();

            const redirectFromAccessUrl = (code) => {
                const url = new URL(launchUrl.toString(), window.location.origin);
                url.searchParams.set('code', code);

                if (purposeHint) {
                    url.searchParams.set('purpose', purposeHint);
                }

                window.location.href = url.toString();
            };

            const openWithCode = (code) => {
                const normalized = normalizeCode(code);
                if (!normalized) {
                    showMessage('Type a valid one-time code to continue.', 'warn');
                    forceManualMode('Type a valid one-time code to continue.', 'warn');
                    return;
                }

                redirectFromAccessUrl(normalized);
            };

            const completeAutoOpen = async () => {
                if (!telegram || !telegram.initData) {
                    if (prefilledCode) {
                        forceManualMode('Open this page in Telegram with your one-tap member/admin link, or continue with your code.');
                    } else {
                        forceManualMode('Open this page in Telegram to continue automatically.', 'warn');
                    }
                    return;
                }

                showMessage('Opening your secure link...');
                const startParam = telegram.initDataUnsafe?.start_param ? telegram.initDataUnsafe.start_param : '';

                try {
                    const response = await fetch(submitUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            init_data: telegram.initData,
                            start_param: startParam,
                            code: prefilledCode || '',
                        }),
                    });

                    const payload = await response.json().catch(() => null);
                    if (!response.ok || !payload) {
                        showMessage('Could not connect to Telegram right now. Use the code below if you have it.', 'warn');
                        forceManualMode('Could not connect to Telegram right now. Use the code below if you have it.', 'warn');
                        return;
                    }

                    if (payload.status === 'linked' && payload.access_url) {
                        window.location.href = payload.access_url;
                        return;
                    }

                    showMessage(payload.message || 'Please continue using the available one-tap link.', 'warn');
                    forceManualMode(payload.message || 'Please continue using the available one-tap link.', 'warn');
                } catch (error) {
                    console.error('Mini app connect error:', error);
                    showMessage('Could not connect to Telegram right now.', 'error');
                    forceManualMode('Could not connect to Telegram right now.', 'error');
                }
            };

            continueBtn?.addEventListener('click', () => openWithCode(accessInput?.value || ''));

            if (prefilledCode) {
                if (!telegram) {
                    manualBlock?.classList.remove('hidden');
                    showMessage('Use the code below to continue.');
                } else {
                    completeAutoOpen();
                }
            } else {
                completeAutoOpen();
            }
        })();
    </script>
@endpush
