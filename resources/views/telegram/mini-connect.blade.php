@extends('layouts.member-guest')

@section('title', 'Opening Abiy Tsom...')

@section('content')
    <div class="flex flex-col items-center justify-center min-h-[60vh] text-center gap-4">
        <div class="w-10 h-10 border-4 border-accent border-t-transparent rounded-full animate-spin"></div>
        <p id="status-message" class="text-sm text-muted-text">{{ __('app.loading') }}</p>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const submitUrl   = '{{ route('telegram.mini.connect.submit') }}';
            const homeUrl     = '{{ route('home') }}';
            const accessUrl   = '{{ $telegramAccessUrl }}';
            const prefilledCode = @json($prefilledCode);
            const purposeHint   = @json($purposeHint);
            const token = document.querySelector('meta[name="csrf-token"]')?.content || '';

            const telegram = window.Telegram?.WebApp;
            if (telegram) {
                telegram.expand();
                telegram.ready?.();
            }

            const redirect = (url) => { window.location.href = url; };

            const buildAccessUrl = (code) => {
                const url = new URL(accessUrl, window.location.origin);
                url.searchParams.set('code', code);
                if (purposeHint) {
                    url.searchParams.set('purpose', purposeHint);
                }
                return url.toString();
            };

            const doConnect = async () => {
                // Not inside Telegram
                if (!telegram?.initData) {
                    if (prefilledCode) {
                        // A code was passed in the URL — try consuming it directly
                        redirect(buildAccessUrl(prefilledCode));
                    } else {
                        // No context — send to normal login / onboarding
                        redirect(homeUrl);
                    }
                    return;
                }

                const startParam = telegram.initDataUnsafe?.start_param || '';

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
                            init_data:  telegram.initData,
                            start_param: startParam,
                            code: prefilledCode || '',
                        }),
                    });

                    const payload = await response.json().catch(() => null);

                    if (payload?.status === 'linked' && payload.access_url) {
                        // Already linked — open the app
                        redirect(payload.access_url);
                        return;
                    }

                    // Not linked or any error — send to login / onboarding
                    redirect(homeUrl);
                } catch (e) {
                    redirect(homeUrl);
                }
            };

            doConnect();
        })();
    </script>
@endpush
