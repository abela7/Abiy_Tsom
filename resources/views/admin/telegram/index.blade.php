@extends('layouts.admin')
@section('title', __('app.telegram_settings'))

@section('content')
@php
    $telegramLoginUrl = session('telegram_access_url');
    $telegramLoginExpires = (int) (session('telegram_access_expires') ?? 0);
    $telegramMiniLoginUrl = session('telegram_mini_access_url');
    $publicBotLink = $publicBotName ? ('https://t.me/' . $publicBotName) : '';
    $botStatusLine = $publicBotName ? __('app.telegram_bot_ready') : __('app.telegram_bot_username_missing');
    $telegramBuilderConfig = (array) ($builderConfig ?? []);
    $telegramBuilderUi = (array) data_get($telegramBuilderConfig, 'ui', []);
    $telegramBuilderCommands = (array) data_get($telegramBuilderConfig, 'commands', []);
    $telegramBuilderMemberButtons = (array) data_get($telegramBuilderConfig, 'member_buttons', []);
    $telegramBuilderAdminButtons = (array) data_get($telegramBuilderConfig, 'admin_buttons', []);
@endphp

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
            botUsername: @js($botUsername),
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
                    <label for="bot_username" class="block text-sm font-medium text-secondary mb-1.5">
                        {{ __('app.telegram_bot_username') }}
                    </label>
                    <input type="text"
                           id="bot_username"
                           name="bot_username"
                           x-model="botUsername"
                           value="{{ old('bot_username', $botUsername) }}"
                           placeholder="abiytsombot"
                           maxlength="255"
                           class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none font-mono text-sm">
                    <p class="text-xs text-muted-text mt-1.5">{{ __('app.telegram_bot_username_help') }}</p>
                </div>

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
    <form method="POST"
          action="{{ route('admin.telegram.builder.update') }}"
          class="lg:col-span-2 space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
            <h2 class="text-base font-semibold text-primary mb-4">{{ __('app.telegram_builder_ui') }}</h2>
            <div class="space-y-4">
                <div>
                    <label for="ui_menu_button_label" class="block text-sm font-medium text-secondary mb-1.5">
                        {{ __('app.telegram_builder_menu_button') }}
                    </label>
                    <input type="text"
                           id="ui_menu_button_label"
                           name="ui[menu_button_label]"
                           value="{{ old('ui.menu_button_label', (string) data_get($telegramBuilderUi, 'menu_button_label', 'Open Abiy Tsom')) }}"
                           maxlength="80"
                           class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none">
                </div>
                <div>
                    <label for="ui_welcome_message" class="block text-sm font-medium text-secondary mb-1.5">
                        {{ __('app.telegram_builder_welcome') }}
                    </label>
                    <textarea id="ui_welcome_message"
                              name="ui[welcome_message]"
                              rows="2"
                              maxlength="300"
                              class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none">{{ old('ui.welcome_message', (string) data_get($telegramBuilderUi, 'welcome_message', 'Welcome to Abiy Tsom.')) }}</textarea>
                </div>
                <div>
                    <label for="ui_help_message" class="block text-sm font-medium text-secondary mb-1.5">
                        {{ __('app.telegram_builder_help') }}
                    </label>
                    <textarea id="ui_help_message"
                              name="ui[help_message]"
                              rows="2"
                              maxlength="220"
                              class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none">{{ old('ui.help_message', (string) data_get($telegramBuilderUi, 'help_message', 'Use the buttons below. If your account is linked, the app opens in one tap.')) }}</textarea>
                </div>
                <div>
                    <label for="ui_not_linked_message" class="block text-sm font-medium text-secondary mb-1.5">
                        {{ __('app.telegram_builder_not_linked') }}
                    </label>
                    <textarea id="ui_not_linked_message"
                              name="ui[not_linked_message]"
                              rows="2"
                              maxlength="220"
                              class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none">{{ old('ui.not_linked_message', (string) data_get($telegramBuilderUi, 'not_linked_message', 'Your account is not linked yet. Open the app and continue securely.')) }}</textarea>
                </div>
            </div>
        </div>

        <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
            <h2 class="text-base font-semibold text-primary mb-4">{{ __('app.telegram_builder_commands') }}</h2>
            <div class="space-y-4">
                @php
                    $commandInputs = [
                        'menu' => ['label' => __('app.telegram_builder_command_menu'), 'description' => __('app.telegram_builder_command_menu_desc')],
                        'home' => ['label' => __('app.telegram_builder_command_home'), 'description' => __('app.telegram_builder_command_home_desc')],
                        'day' => ['label' => __('app.telegram_builder_command_today'), 'description' => __('app.telegram_builder_command_today_desc')],
                        'admin' => ['label' => __('app.telegram_builder_command_admin'), 'description' => __('app.telegram_builder_command_admin_desc')],
                        'help' => ['label' => __('app.telegram_builder_command_help'), 'description' => __('app.telegram_builder_command_help_desc')],
                    ];
                @endphp
                @foreach($commandInputs as $commandKey => $labels)
                    <div class="rounded-lg border border-border p-3 space-y-2">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox"
                                   name="commands[{{ $commandKey }}][enabled]"
                                   value="1"
                                   @checked((bool) old("commands.$commandKey.enabled", (bool) data_get($telegramBuilderCommands, "{$commandKey}.enabled", true))) />
                            <span class="font-medium text-secondary">{{ $labels['label'] }}</span>
                        </label>
                        <input type="text"
                               name="commands[{{ $commandKey }}][label]"
                               value="{{ old("commands.$commandKey.label", (string) data_get($telegramBuilderCommands, "{$commandKey}.label", $labels['label'])) }}"
                               maxlength="40"
                               class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary text-sm focus:ring-2 focus:ring-accent outline-none">
                        <input type="text"
                               name="commands[{{ $commandKey }}][description]"
                               value="{{ old("commands.$commandKey.description", (string) data_get($telegramBuilderCommands, "{$commandKey}.description", $labels['description'])) }}"
                               maxlength="120"
                               class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary text-sm focus:ring-2 focus:ring-accent outline-none">
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
            <h2 class="text-base font-semibold text-primary mb-4">{{ __('app.telegram_builder_member_keyboard') }}</h2>
            <div class="grid sm:grid-cols-2 gap-3">
                @php
                    $memberButtons = [
                        'home' => __('app.telegram_builder_member_home'),
                        'today' => __('app.telegram_builder_member_today'),
                        'me' => __('app.telegram_builder_member_me'),
                        'help' => __('app.telegram_builder_member_help'),
                    ];
                @endphp
                @foreach($memberButtons as $key => $label)
                    <label class="rounded-lg border border-border p-3 flex flex-col gap-2">
                        <span class="text-sm font-medium text-secondary">{{ $label }}</span>
                        <input type="text"
                               name="member_buttons[{{ $key }}][label]"
                               value="{{ old("member_buttons.$key.label", (string) data_get($telegramBuilderMemberButtons, "{$key}.label", $label)) }}"
                               maxlength="40"
                               class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary text-sm focus:ring-2 focus:ring-accent outline-none">
                        <label class="text-xs text-muted-text inline-flex items-center gap-2">
                            <input type="checkbox"
                                   name="member_buttons[{{ $key }}][enabled]"
                                   value="1"
                                   @checked((bool) old("member_buttons.$key.enabled", (bool) data_get($telegramBuilderMemberButtons, "{$key}.enabled", true))) />
                            {{ __('app.telegram_builder_enabled') }}
                        </label>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
            <h2 class="text-base font-semibold text-primary mb-4">{{ __('app.telegram_builder_admin_keyboard') }}</h2>
            <div class="grid sm:grid-cols-2 gap-3">
                @php
                    $adminButtons = [
                        'admin' => __('app.telegram_builder_admin_panel'),
                        'help' => __('app.telegram_builder_member_help'),
                    ];
                @endphp
                @foreach($adminButtons as $key => $label)
                    <label class="rounded-lg border border-border p-3 flex flex-col gap-2">
                        <span class="text-sm font-medium text-secondary">{{ $label }}</span>
                        <input type="text"
                               name="admin_buttons[{{ $key }}][label]"
                               value="{{ old("admin_buttons.$key.label", (string) data_get($telegramBuilderAdminButtons, "{$key}.label", $label)) }}"
                               maxlength="40"
                               class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary text-sm focus:ring-2 focus:ring-accent outline-none">
                        <label class="text-xs text-muted-text inline-flex items-center gap-2">
                            <input type="checkbox"
                                   name="admin_buttons[{{ $key }}][enabled]"
                                   value="1"
                                   @checked((bool) old("admin_buttons.$key.enabled", (bool) data_get($telegramBuilderAdminButtons, "{$key}.enabled", true))) />
                            {{ __('app.telegram_builder_enabled') }}
                        </label>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit"
                    class="px-6 py-2.5 bg-accent text-on-accent rounded-lg font-medium hover:bg-accent-hover transition">
                {{ __('app.save') }}
            </button>
        </div>
    </form>

    <div class="bg-card rounded-xl p-6 shadow-sm border border-border lg:col-span-1 space-y-4">
        <h3 class="text-sm font-semibold text-primary mb-1">{{ __('app.telegram_bot_connection') }}</h3>
        <p class="text-xs text-secondary">{{ $botStatusLine }}</p>

        @if ($publicBotName)
            <div class="rounded-xl border border-border bg-surface text-xs space-y-2 p-3">
                <p class="text-secondary">{{ __('app.telegram_bot_deep_link') }}</p>
                <input id="telegramBotLink"
                       value="{{ $publicBotLink }}"
                       readonly
                       class="w-full text-xs bg-card border border-border rounded-lg px-2.5 py-2 text-secondary">
                <a href="{{ $publicBotLink }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="inline-block px-3 py-2 bg-accent text-on-accent rounded-lg text-xs hover:bg-accent-hover">
                    {{ __('app.telegram_open_bot') }}
                </a>
            </div>

            <div class="rounded-xl border border-border bg-surface text-xs space-y-2 p-3">
                <p class="text-secondary">Telegram deep-link entry points</p>
                <a href="{{ $telegramMenuStartLink }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="block text-accent hover:text-accent-hover underline">
                    Menu entry point
                </a>
                <a href="{{ $telegramHomeStartLink }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="block text-accent hover:text-accent-hover underline">
                    Member home entry point
                </a>
                <a href="{{ $telegramTodayStartLink }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="block text-accent hover:text-accent-hover underline">
                    Today entry point
                </a>
                <a href="{{ $telegramAdminStartLink }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="block text-accent hover:text-accent-hover underline">
                    Admin entry point
                </a>
                @if ($telegramMiniMemberStartApp)
                    <a href="{{ $telegramMiniMemberStartApp }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="block text-accent hover:text-accent-hover underline">
                        Member mini app (startapp)
                    </a>
                @endif
                @if ($telegramMiniAdminStartApp)
                    <a href="{{ $telegramMiniAdminStartApp }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="block text-accent hover:text-accent-hover underline">
                        Admin mini app (startapp)
                    </a>
                @endif
                @if ($telegramMiniWebAppUrl)
                    <a href="{{ $telegramMiniWebAppUrl }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="block text-accent hover:text-accent-hover underline">
                        Mini app direct
                    </a>
                @endif
            </div>
        @endif

        <div>
            <p class="text-xs text-secondary">{{ __('app.telegram_menu_note') }}</p>
            <p class="text-xs text-muted-text mt-1">Webhook URL:</p>
            <p class="text-xs font-mono break-all text-secondary">{{ $webhookUrl }}</p>
            <form method="POST"
                  action="{{ route('admin.telegram.sync-menu') }}"
                  class="mt-3">
                @csrf
                <button type="submit"
                        class="w-full px-3 py-2 bg-accent text-on-accent rounded-lg text-sm font-medium hover:bg-accent-hover transition">
                    {{ __('app.telegram_sync_menu') }}
                </button>
            </form>
        </div>

        @if ($telegramLoginUrl)
            <div class="rounded-xl border border-green-600/40 bg-green-950/20 text-green-100 p-4">
                <h3 class="text-sm font-semibold text-green-200 mb-3">
                    One-time admin Telegram login link (expires in {{ $telegramLoginExpires }} minutes)
                </h3>
                <div class="space-y-2">
                    <input id="telegramLoginUrl"
                           value="{{ $telegramLoginUrl }}"
                           readonly
                           class="w-full text-xs bg-surface border border-border rounded-lg px-2.5 py-2 text-secondary">
                    <button type="button"
                            class="px-3 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 transition"
                            onclick="navigator.clipboard.writeText(document.getElementById('telegramLoginUrl').value).then(() => alert('Copied'));"
                    >
                        Copy link
                    </button>
                </div>
            </div>
        @endif

        <form method="POST"
              action="{{ route('admin.telegram.login-link') }}"
              class="pt-2 border-t border-border space-y-3">
            @csrf
            <h3 class="text-sm font-semibold text-primary">Generate secure Telegram admin login</h3>
            <div>
                <label for="telegram_expires_in" class="block text-xs font-medium text-secondary mb-1">
                    Expires in minutes (1-120)
                </label>
                <input id="telegram_expires_in"
                       type="number"
                       name="expires_in"
                    min="1"
                    max="120"
                    value="15"
                    class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none">
            </div>
            <button type="submit" class="px-4 py-2 bg-accent text-on-accent rounded-lg text-sm font-medium hover:bg-accent-hover transition">
                Create one-time login link
            </button>
            <button type="submit"
                    name="telegram_mode"
                    value="mini"
                    class="px-4 py-2 bg-secondary text-primary rounded-lg text-sm font-medium border border-border hover:bg-muted transition">
                Create one-tap Telegram mini-app link
            </button>
            <p class="text-[11px] text-muted-text">Keep this link private. It can be used once.</p>
        </form>
        @if ($telegramMiniLoginUrl)
            <div class="rounded-xl border border-emerald-600/40 bg-emerald-950/20 text-emerald-100 p-4 mt-3">
                <h3 class="text-sm font-semibold text-emerald-200 mb-3">
                    One-tap admin Telegram mini-app link (expires in {{ $telegramLoginExpires }} minutes)
                </h3>
                <div class="space-y-2">
                    <input id="telegramMiniLoginUrl"
                           value="{{ $telegramMiniLoginUrl }}"
                           readonly
                           class="w-full text-xs bg-surface border border-border rounded-lg px-2.5 py-2 text-secondary">
                    <button type="button"
                            class="px-3 py-2 bg-emerald-700 text-white rounded-lg text-sm hover:bg-emerald-600 transition"
                            onclick="navigator.clipboard.writeText(document.getElementById('telegramMiniLoginUrl').value).then(() => alert('Copied'));"
                    >
                        Copy mini-app link
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
