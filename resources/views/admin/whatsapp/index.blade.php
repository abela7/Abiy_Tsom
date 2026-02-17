@extends('layouts.admin')
@section('title', __('app.whatsapp_settings'))

@section('content')
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
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            instance_id: this.instanceId,
                            token: this.token,
                            test_phone: this.testPhone
                        })
                    });
                    const data = await response.json();
                    if (response.ok && data.success) {
                        this.testResult = 'success';
                        this.testMessage = data.message || '{{ __('app.whatsapp_test_success') }}';
                    } else {
                        this.testResult = 'error';
                        this.testMessage = data.message || '{{ __('app.whatsapp_test_failed') }}';
                    }
                } catch (error) {
                    this.testResult = 'error';
                    this.testMessage = '{{ __('app.whatsapp_test_error') }}';
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
