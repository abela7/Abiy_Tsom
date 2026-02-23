@extends('layouts.member-guest')

@section('title', 'Telegram Link')

@section('content')
    <div class="bg-card border border-border rounded-2xl p-6 shadow-sm">
        <h1 class="text-2xl font-bold text-primary mb-2">Telegram Mini App</h1>
        <p class="text-sm text-secondary mb-4">
            Paste your one-time Telegram link code and open your dashboard from the secure launcher.
        </p>

        <label for="access-code" class="block text-sm font-medium text-secondary mb-1">
            One-time access code
        </label>
        <input id="access-code"
               type="text"
               class="w-full px-3 py-2 border border-border rounded-lg bg-surface text-primary focus:ring-2 focus:ring-accent outline-none font-mono text-sm"
               value="{{ $prefilledCode }}"
               autocomplete="one-time-code"
               inputmode="text"
               placeholder="Example: A3b...">

        <p id="code-message" class="mt-3 text-sm text-muted-text">
            This code is used once and works for your current login only.
        </p>

        <div class="mt-4 space-y-2">
            <button id="open-btn"
                    type="button"
                    class="w-full bg-accent text-on-accent rounded-lg px-4 py-2.5 font-medium hover:bg-accent-hover transition">
                Open Securely
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
            const accessUrl = new URL('{{ $telegramAccessUrl }}', window.location.origin);
            const prefilledCode = @json($prefilledCode);
            const purposeHint = @json($purposeHint);
            const input = document.getElementById('access-code');
            const message = document.getElementById('code-message');
            const openBtn = document.getElementById('open-btn');

            const telegram = window.Telegram?.WebApp;
            if (telegram) {
                telegram.expand();
                telegram.ready?.();
                telegram.enableClosingConfirmation(false);
            }

            if (prefilledCode) {
                input.value = prefilledCode;
            }

            const normalizeCode = (value) => (value || '').toString().trim();

            openBtn.addEventListener('click', () => {
                const code = normalizeCode(input.value);
                if (!code) {
                    message.textContent = 'Paste a valid code to continue.';
                    message.classList.remove('text-muted-text');
                    message.classList.add('text-red-400');
                    return;
                }

                const url = new URL(accessUrl.toString(), window.location.origin);
                url.searchParams.set('code', code);
                if (purposeHint) {
                    url.searchParams.set('purpose', purposeHint);
                }

                window.location.href = url.toString();
            });
        })();
    </script>
@endpush
