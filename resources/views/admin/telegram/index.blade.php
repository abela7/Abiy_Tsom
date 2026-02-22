@extends('layouts.admin')
@section('title', __('app.telegram_settings'))

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-primary">{{ __('app.telegram_settings') }}</h1>
    <p class="text-sm text-muted-text mt-1">{{ __('app.telegram_settings_help') }}</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <form method="POST"
          action="{{ route('admin.telegram.update') }}"
          class="lg:col-span-2 space-y-6"
          x-data="{
            botToken: @js($botToken),
            defaultChatId: @js($defaultChatId),
            testChatId: '',
            testing: false,
            testResult: null,
            testMessage: '',
            async testConnection() {
                const chatId = (this.testChatId || this.defaultChatId || '').trim();

                if (!this.botToken || !chatId) {
                    this.testResult = 'error';
                    this.testMessage = '{{ __('app.telegram_test_fill_all') }}';
                    return;
                }

                this.testing = true;
                this.testResult = null;
                this.testMessage = '';

                try {
                    const response = await fetch('{{ route('admin.telegram.test') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            bot_token: this.botToken,
                            chat_id: chatId
                        })
                    });

                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        const htmlText = await response.text();
                        console.error('Received HTML instead of JSON:', htmlText.substring(0, 500));
                        this.testResult = 'error';
                        this.testMessage = '{{ __('app.telegram_test_error') }}';
                        return;
                    }

                    const data = await response.json();
                    if (response.ok && data.success) {
                        this.testResult = 'success';
                        this.testMessage = data.message || '{{ __('app.telegram_test_success') }}';
                    } else {
                        this.testResult = 'error';
                        this.testMessage = data.message || '{{ __('app.telegram_test_failed') }}';
                    }
                } catch (error) {
                    console.error('Connection error:', error);
                    this.testResult = 'error';
                    this.testMessage = '{{ __('app.telegram_test_error') }}';
                } finally {
                    this.testing = false;
                }
            }
          }">
        @csrf
        @method('PUT')

        <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
            <h2 class="text-base font-semibold text-primary mb-4">{{ __('app.telegram_credentials') }}</h2>

            <div class="space-y-4">
                <div>
                    <label for="bot_token" class="block text-sm font-medium text-secondary mb-1.5">
                        {{ __('app.telegram_bot_token') }}
                        <span class="text-red-500">*</span>
                    </label>
                    <input type="password"
                           id="bot_token"
                           name="bot_token"
                           x-model="botToken"
                           value="{{ old('bot_token', $botToken) }}"
                           placeholder="123456789:AAHh..."
                           maxlength="255"
                           class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none font-mono text-sm">
                    <p class="text-xs text-muted-text mt-1.5">{{ __('app.telegram_bot_token_help') }}</p>
                </div>

                <div>
                    <label for="default_chat_id" class="block text-sm font-medium text-secondary mb-1.5">
                        {{ __('app.telegram_default_chat_id') }}
                    </label>
                    <input type="text"
                           id="default_chat_id"
                           name="default_chat_id"
                           x-model="defaultChatId"
                           value="{{ old('default_chat_id', $defaultChatId) }}"
                           placeholder="1234567890 or -1001234567890"
                           maxlength="255"
                           class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none">
                    <p class="text-xs text-muted-text mt-1.5">{{ __('app.telegram_default_chat_id_help') }}</p>
                </div>
            </div>
        </div>

        <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
            <h2 class="text-base font-semibold text-primary mb-4">{{ __('app.telegram_test_connection') }}</h2>
            <div class="space-y-4">
                <div>
                    <label for="test_chat_id" class="block text-sm font-medium text-secondary mb-1.5">
                        {{ __('app.telegram_test_chat_id') }}
                    </label>
                    <input type="text"
                           id="test_chat_id"
                           x-model="testChatId"
                           placeholder="{{ __('app.telegram_test_chat_id_placeholder') }}"
                           maxlength="255"
                           class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none">
                    <p class="text-xs text-muted-text mt-1.5">{{ __('app.telegram_test_chat_id_help') }}</p>
                </div>

                <button type="button"
                        @click="testConnection"
                        :disabled="testing"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                    <svg x-show="testing" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="testing ? '{{ __('app.testing') }}...' : '{{ __('app.telegram_test_connection') }}'"></span>
                </button>

                <div x-show="testResult"
                     x-transition
                     class="p-3 rounded-lg text-sm"
                     :class="{
                        'bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300 border border-green-200 dark:border-green-800': testResult === 'success',
                        'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 border border-red-200 dark:border-red-800': testResult === 'error'
                     }">
                    <p x-text="testMessage"></p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit"
                    class="px-6 py-2.5 bg-accent text-on-accent rounded-lg font-medium hover:bg-accent-hover transition">
                {{ __('app.save') }}
            </button>
            <a href="{{ route('admin.dashboard') }}"
               class="px-6 py-2.5 bg-surface text-secondary rounded-lg font-medium hover:bg-muted transition">
                {{ __('app.cancel') }}
            </a>
        </div>
    </form>

    <div class="bg-card rounded-xl p-6 shadow-sm border border-border lg:col-span-1">
        <h3 class="text-sm font-semibold text-primary mb-2">{{ __('app.telegram_how_to_get_credentials') }}</h3>
        <ol class="text-xs text-secondary space-y-2 list-decimal list-inside">
            <li>{{ __('app.telegram_step_1') }}</li>
            <li>{{ __('app.telegram_step_2') }}</li>
            <li>{{ __('app.telegram_step_3') }}</li>
            <li>{{ __('app.telegram_step_4') }}</li>
        </ol>

        <div class="pt-4 border-t border-border mt-4">
            <a href="https://t.me/BotFather" target="_blank" rel="noopener noreferrer" class="text-xs text-accent hover:text-accent-hover">
                {{ __('app.open_botfather') }} &rarr;
            </a>
        </div>
    </div>
</div>
@endsection
