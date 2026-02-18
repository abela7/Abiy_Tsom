@extends('layouts.admin')
@section('title', __('app.whatsapp_settings'))

@section('content')
@include('admin.whatsapp._nav')

<div class="mb-6">
    <h1 class="text-2xl font-bold text-primary">{{ __('app.whatsapp_settings') }}</h1>
    <p class="text-sm text-muted-text mt-1">{{ __('app.whatsapp_settings_help') }}</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <form method="POST"
          action="{{ route('admin.whatsapp.update') }}"
          class="lg:col-span-2 space-y-6"
          x-data="{
            instanceId: @js($instanceId),
            token: @js($token),
            testPhone: '',
            testing: false,
            testResult: null,
            testMessage: '',
            async testConnection() {
                if (!this.instanceId || !this.token || !this.testPhone) {
                    this.testResult = 'error';
                    this.testMessage = '{{ __('app.whatsapp_test_fill_all') }}';
                    return;
                }
                this.testing = true;
                this.testResult = null;
                this.testMessage = '';
                try {
                    const response = await fetch('{{ route('admin.whatsapp.test') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            instance_id: this.instanceId,
                            token: this.token,
                            test_phone: this.testPhone
                        })
                    });
                    
                    // Check if response is JSON
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        const htmlText = await response.text();
                        console.error('Received HTML instead of JSON:', htmlText.substring(0, 500));
                        this.testResult = 'error';
                        this.testMessage = 'Server error: Expected JSON but got HTML. Check console for details.';
                        return;
                    }
                    
                    const data = await response.json();
                    if (response.ok && data.success) {
                        this.testResult = 'success';
                        this.testMessage = data.message || '{{ __('app.whatsapp_test_success') }}';
                    } else {
                        this.testResult = 'error';
                        this.testMessage = data.message || '{{ __('app.whatsapp_test_failed') }}';
                    }
                } catch (error) {
                    console.error('Connection error:', error);
                    this.testResult = 'error';
                    this.testMessage = 'Error: ' + (error.message || 'Connection failed. Check console for details.');
                } finally {
                    this.testing = false;
                }
            }
          }">
        @csrf
        @method('PUT')

        <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
            <h2 class="text-base font-semibold text-primary mb-4">{{ __('app.ultramsg_credentials') }}</h2>
            
            <div class="space-y-4">
                <div>
                    <label for="instance_id" class="block text-sm font-medium text-secondary mb-1.5">
                        {{ __('app.ultramsg_instance_id') }}
                        <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           id="instance_id"
                           name="instance_id"
                           x-model="instanceId"
                           value="{{ old('instance_id', $instanceId) }}"
                           placeholder="instance1234"
                           maxlength="100"
                           class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none">
                    <p class="text-xs text-muted-text mt-1.5">{{ __('app.ultramsg_instance_id_help') }}</p>
                </div>

                <div>
                    <label for="token" class="block text-sm font-medium text-secondary mb-1.5">
                        {{ __('app.ultramsg_token') }}
                        <span class="text-red-500">*</span>
                    </label>
                    <input type="password"
                           id="token"
                           name="token"
                           x-model="token"
                           value="{{ old('token', $token) }}"
                           placeholder="your_api_token_here"
                           maxlength="255"
                           class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none font-mono text-sm">
                    <p class="text-xs text-muted-text mt-1.5">{{ __('app.ultramsg_token_help') }}</p>
                </div>

                <div>
                    <label for="base_url" class="block text-sm font-medium text-secondary mb-1.5">
                        {{ __('app.ultramsg_base_url') }}
                    </label>
                    <input type="url"
                           id="base_url"
                           name="base_url"
                           value="{{ old('base_url', $baseUrl) }}"
                           placeholder="https://api.ultramsg.com"
                           maxlength="255"
                           class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none">
                    <p class="text-xs text-muted-text mt-1.5">{{ __('app.ultramsg_base_url_help') }}</p>
                </div>
            </div>
        </div>

        <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
            <h2 class="text-base font-semibold text-primary mb-4">{{ __('app.whatsapp_test_connection') }}</h2>
            
            <div class="space-y-4">
                <div>
                    <label for="test_phone" class="block text-sm font-medium text-secondary mb-1.5">
                        {{ __('app.whatsapp_test_phone') }}
                    </label>
                    <input type="tel"
                           id="test_phone"
                           x-model="testPhone"
                           placeholder="+447700900123"
                           maxlength="20"
                           class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none">
                    <p class="text-xs text-muted-text mt-1.5">{{ __('app.whatsapp_test_phone_help') }}</p>
                </div>

                <button type="button"
                        @click="testConnection"
                        :disabled="testing"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                    <svg x-show="testing" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="testing ? '{{ __('app.testing') }}...' : '{{ __('app.test_connection') }}'"></span>
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

    {{-- Webhook Secret --}}
    <div class="bg-card rounded-xl p-6 shadow-sm border border-border lg:col-span-2">
        <h2 class="text-base font-semibold text-primary mb-2">{{ __('app.whatsapp_webhook_secret_title') }}</h2>
        <p class="text-xs text-muted-text mb-4">{{ __('app.whatsapp_webhook_secret_help') }}</p>

        <form method="POST" action="{{ route('admin.whatsapp.update-webhook-secret') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="webhook_secret" class="block text-sm font-medium text-secondary mb-1.5">
                    {{ __('app.whatsapp_webhook_secret_label') }}
                </label>
                <div class="flex gap-2">
                    <input type="text"
                           id="webhook_secret"
                           name="webhook_secret"
                           value="{{ old('webhook_secret', $webhookSecret) }}"
                           placeholder="e.g. my-strong-secret-key-123"
                           maxlength="255"
                           class="flex-1 px-3 py-2 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none font-mono text-sm">
                    <button type="button"
                            onclick="document.getElementById('webhook_secret').value = crypto.randomUUID().replaceAll('-','')"
                            class="px-3 py-2 bg-surface text-secondary rounded-lg text-xs font-medium hover:bg-muted transition whitespace-nowrap">
                        {{ __('app.generate') }}
                    </button>
                </div>
                <p class="text-xs text-muted-text mt-1.5">{{ __('app.whatsapp_webhook_secret_instructions') }}</p>
            </div>

            <button type="submit"
                    class="px-4 py-2 bg-accent text-on-accent rounded-lg font-medium hover:bg-accent-hover transition text-sm">
                {{ __('app.save') }}
            </button>

            @if(session('webhook_secret_success'))
            <div class="p-3 rounded-lg text-sm bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300 border border-green-200 dark:border-green-800">
                {{ session('webhook_secret_success') }}
            </div>
            @endif
        </form>
    </div>

    {{-- Once Per Day Toggle --}}
    <div class="bg-card rounded-xl p-6 shadow-sm border border-border lg:col-span-2">
        <h2 class="text-base font-semibold text-primary mb-2">{{ __('app.whatsapp_once_only_label') }}</h2>
        <p class="text-xs text-muted-text mb-4">{{ __('app.whatsapp_once_only_help') }}</p>

        <form method="POST" action="{{ route('admin.whatsapp.update-reminder-once-only') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <label class="flex items-start gap-2.5 cursor-pointer">
                <input type="hidden" name="reminder_once_only" value="0">
                <input type="checkbox"
                       name="reminder_once_only"
                       value="1"
                       {{ $reminderOnceOnly ? 'checked' : '' }}
                       class="mt-0.5 w-4 h-4 text-accent bg-card border-border rounded focus:ring-2 focus:ring-accent">
                <span class="text-sm text-secondary">{{ __('app.whatsapp_once_only_label') }}</span>
            </label>

            <button type="submit"
                    class="px-4 py-2 bg-accent text-on-accent rounded-lg font-medium hover:bg-accent-hover transition text-sm">
                {{ __('app.save') }}
            </button>

            @if(session('reminder_once_only_success'))
            <div class="p-3 rounded-lg text-sm bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300 border border-green-200 dark:border-green-800">
                {{ session('reminder_once_only_success') }}
            </div>
            @endif
        </form>
    </div>

    {{-- Webhook Settings Form --}}
    @if($instanceId && $token)
    <div class="lg:col-span-2"
         x-data="{
            webhookUrl: @js($currentSettings['webhook_url'] ?? ''),
            messageReceived: @js(($currentSettings['webhook_message_received'] ?? 'off') === 'on'),
            messageCreate: @js(($currentSettings['webhook_message_create'] ?? 'off') === 'on'),
            messageAck: @js(($currentSettings['webhook_message_ack'] ?? 'off') === 'on'),
            downloadMedia: @js(($currentSettings['webhook_message_download_media'] ?? 'off') === 'on'),
            sendDelay: @js($currentSettings['sendDelay'] ?? 1),
            sendDelayMax: @js($currentSettings['sendDelayMax'] ?? 15),
            updating: false,
            updateResult: null,
            updateMessage: '',
            async updateWebhook() {
                if (!this.webhookUrl) {
                    this.updateResult = 'error';
                    this.updateMessage = 'Please enter a webhook URL.';
                    return;
                }
                this.updating = true;
                this.updateResult = null;
                this.updateMessage = '';
                try {
                    const response = await fetch('{{ route('admin.whatsapp.webhook') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            webhook_url: this.webhookUrl,
                            webhook_message_received: this.messageReceived,
                            webhook_message_create: this.messageCreate,
                            webhook_message_ack: this.messageAck,
                            webhook_message_download_media: this.downloadMedia,
                            sendDelay: parseInt(this.sendDelay),
                            sendDelayMax: parseInt(this.sendDelayMax)
                        })
                    });
                    
                    // Check if response is JSON
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        const htmlText = await response.text();
                        console.error('Received HTML instead of JSON:', htmlText.substring(0, 500));
                        this.updateResult = 'error';
                        this.updateMessage = 'Server error: Expected JSON but got HTML. Check console for details.';
                        return;
                    }
                    
                    const data = await response.json();
                    if (response.ok && data.success) {
                        this.updateResult = 'success';
                        this.updateMessage = data.message || '{{ __('app.whatsapp_webhook_update_success') }}';
                    } else {
                        this.updateResult = 'error';
                        this.updateMessage = data.message || '{{ __('app.whatsapp_webhook_update_failed') }}';
                    }
                } catch (error) {
                    console.error('Webhook update error:', error);
                    this.updateResult = 'error';
                    this.updateMessage = 'Error: ' + (error.message || 'Connection failed. Check console for details.');
                } finally {
                    this.updating = false;
                }
            }
         }">
        <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h2 class="text-base font-semibold text-primary">{{ __('app.whatsapp_webhook_settings') }}</h2>
                    <p class="text-xs text-muted-text mt-1">{{ __('app.whatsapp_webhook_help') }}</p>
                </div>
                @if($currentSettings)
                <span class="text-xs text-green-600 dark:text-green-400 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    {{ __('app.current_webhook_settings') }}
                </span>
                @endif
            </div>

            <div class="space-y-4">
                <div>
                    <label for="webhook_url" class="block text-sm font-medium text-secondary mb-1.5">
                        {{ __('app.whatsapp_webhook_url') }}
                        <span class="text-red-500">*</span>
                    </label>
                    <input type="url"
                           id="webhook_url"
                           x-model="webhookUrl"
                           placeholder="{{ url('/webhooks/ultramsg') }}"
                           maxlength="500"
                           class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none text-sm">
                    <p class="text-xs text-muted-text mt-1.5">{{ __('app.whatsapp_webhook_url_help') }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="checkbox"
                               x-model="messageReceived"
                               class="mt-0.5 w-4 h-4 text-accent bg-card border-border rounded focus:ring-2 focus:ring-accent">
                        <span class="text-sm text-secondary">{{ __('app.whatsapp_webhook_message_received') }}</span>
                    </label>

                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="checkbox"
                               x-model="messageCreate"
                               class="mt-0.5 w-4 h-4 text-accent bg-card border-border rounded focus:ring-2 focus:ring-accent">
                        <span class="text-sm text-secondary">{{ __('app.whatsapp_webhook_message_create') }}</span>
                    </label>

                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="checkbox"
                               x-model="messageAck"
                               class="mt-0.5 w-4 h-4 text-accent bg-card border-border rounded focus:ring-2 focus:ring-accent">
                        <span class="text-sm text-secondary">{{ __('app.whatsapp_webhook_message_ack') }}</span>
                    </label>

                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="checkbox"
                               x-model="downloadMedia"
                               class="mt-0.5 w-4 h-4 text-accent bg-card border-border rounded focus:ring-2 focus:ring-accent">
                        <span class="text-sm text-secondary">{{ __('app.whatsapp_webhook_download_media') }}</span>
                    </label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="sendDelay" class="block text-sm font-medium text-secondary mb-1.5">
                            {{ __('app.whatsapp_send_delay') }}
                        </label>
                        <input type="number"
                               id="sendDelay"
                               x-model="sendDelay"
                               min="1"
                               max="60"
                               class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none">
                        <p class="text-xs text-muted-text mt-1.5">{{ __('app.whatsapp_send_delay_help') }}</p>
                    </div>

                    <div>
                        <label for="sendDelayMax" class="block text-sm font-medium text-secondary mb-1.5">
                            {{ __('app.whatsapp_send_delay_max') }}
                        </label>
                        <input type="number"
                               id="sendDelayMax"
                               x-model="sendDelayMax"
                               min="1"
                               max="120"
                               class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none">
                        <p class="text-xs text-muted-text mt-1.5">{{ __('app.whatsapp_send_delay_max_help') }}</p>
                    </div>
                </div>

                <button type="button"
                        @click="updateWebhook"
                        :disabled="updating"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                    <svg x-show="updating" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="updating ? '{{ __('app.updating_webhook') }}...' : '{{ __('app.update_webhook_settings') }}'"></span>
                </button>

                <div x-show="updateResult"
                     x-transition
                     class="p-3 rounded-lg text-sm"
                     :class="{
                        'bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300 border border-green-200 dark:border-green-800': updateResult === 'success',
                        'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 border border-red-200 dark:border-red-800': updateResult === 'error'
                     }">
                    <p x-text="updateMessage"></p>
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="lg:col-span-2">
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-300 rounded-xl p-6">
            <p class="text-sm">{{ __('app.webhook_not_loaded') }}</p>
        </div>
    </div>
    @endif

    <div class="lg:col-span-1">
        <div class="bg-card rounded-xl p-6 shadow-sm border border-border space-y-6 sticky top-20">
            <div>
                <h3 class="text-sm font-semibold text-primary mb-2">{{ __('app.whatsapp_how_to_get_credentials') }}</h3>
                <ol class="text-xs text-secondary space-y-2 list-decimal list-inside">
                    <li>{{ __('app.whatsapp_step_1') }}</li>
                    <li>{{ __('app.whatsapp_step_2') }}</li>
                    <li>{{ __('app.whatsapp_step_3') }}</li>
                    <li>{{ __('app.whatsapp_step_4') }}</li>
                </ol>
            </div>

            <div>
                <h3 class="text-sm font-semibold text-primary mb-2">{{ __('app.whatsapp_pricing_note') }}</h3>
                <p class="text-xs text-secondary">{{ __('app.whatsapp_pricing_details') }}</p>
            </div>

            <div>
                <h3 class="text-sm font-semibold text-primary mb-2">{{ __('app.whatsapp_reuse_instance') }}</h3>
                <p class="text-xs text-secondary">{{ __('app.whatsapp_reuse_instance_help') }}</p>
            </div>

            <div class="pt-4 border-t border-border">
                <a href="https://ultramsg.com" 
                   target="_blank" 
                   rel="noopener noreferrer"
                   class="text-xs text-accent hover:text-accent-hover flex items-center gap-1">
                    {{ __('app.visit_ultramsg') }}
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                    </svg>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
