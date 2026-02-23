@extends('layouts.admin')
@section('title', __('app.telegram_my_link_title'))

@section('content')
<div class="max-w-lg mx-auto">
    <h1 class="text-2xl font-bold text-primary mb-6">{{ __('app.telegram_my_link_title') }}</h1>

    <div class="bg-card rounded-xl shadow-sm border border-border p-6 space-y-6">

        {{-- Code display --}}
        <div class="text-center">
            <p class="text-sm text-muted-text mb-3">{{ __('app.telegram_my_link_your_code') }}</p>
            <div class="inline-flex items-center gap-3 bg-muted rounded-xl px-6 py-4">
                <span id="tg-code" class="text-4xl font-mono font-bold tracking-widest text-primary select-all">{{ $code }}</span>
                <button type="button"
                        onclick="copyCode()"
                        class="text-accent hover:text-accent-hover transition"
                        title="{{ __('app.copy') }}">
                    <svg id="copy-icon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    <svg id="check-icon" class="w-6 h-6 hidden text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </button>
            </div>
            <p class="text-xs text-muted-text mt-2">{{ __('app.telegram_my_link_expires') }}</p>
        </div>

        {{-- Steps --}}
        <ol class="space-y-3 text-sm text-secondary">
            <li class="flex gap-3">
                <span class="shrink-0 w-6 h-6 rounded-full bg-accent text-on-accent text-xs font-bold flex items-center justify-center">1</span>
                <span>{{ __('app.telegram_my_link_step1') }}</span>
            </li>
            <li class="flex gap-3">
                <span class="shrink-0 w-6 h-6 rounded-full bg-accent text-on-accent text-xs font-bold flex items-center justify-center">2</span>
                <span>{{ __('app.telegram_my_link_step2') }}</span>
            </li>
            <li class="flex gap-3">
                <span class="shrink-0 w-6 h-6 rounded-full bg-accent text-on-accent text-xs font-bold flex items-center justify-center">3</span>
                <span>{{ __('app.telegram_my_link_step3') }}</span>
            </li>
            <li class="flex gap-3">
                <span class="shrink-0 w-6 h-6 rounded-full bg-accent text-on-accent text-xs font-bold flex items-center justify-center">4</span>
                <span>{{ __('app.telegram_my_link_step4') }}</span>
            </li>
        </ol>

        {{-- Open bot button --}}
        @if($botUsername)
        <a href="https://t.me/{{ $botUsername }}"
           target="_blank"
           rel="noopener noreferrer"
           class="flex items-center justify-center gap-2 w-full py-3 rounded-xl bg-accent text-on-accent font-semibold hover:bg-accent-hover transition">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 8.248-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12l-6.871 4.326-2.962-.924c-.643-.204-.657-.643.136-.953l11.57-4.461c.537-.194 1.006.131.833.941z"/>
            </svg>
            {{ __('app.telegram_my_link_open_bot') }}
        </a>
        @endif

        {{-- Refresh link --}}
        <div class="text-center">
            <a href="{{ route('admin.telegram.my-link') }}"
               class="text-sm text-accent hover:underline">
                {{ __('app.telegram_my_link_generate_new') }}
            </a>
        </div>
    </div>
</div>

<script>
function copyCode() {
    const code = document.getElementById('tg-code').textContent.trim();
    navigator.clipboard.writeText(code).then(() => {
        document.getElementById('copy-icon').classList.add('hidden');
        document.getElementById('check-icon').classList.remove('hidden');
        setTimeout(() => {
            document.getElementById('copy-icon').classList.remove('hidden');
            document.getElementById('check-icon').classList.add('hidden');
        }, 2000);
    });
}
</script>
@endsection
