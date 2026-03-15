@extends('layouts.admin')
@section('title', __('app.whatsapp_template_title'))

@section('content')
@include('admin.whatsapp._nav')

@php
    $firstTemplateKey = $templates[0]['key'] ?? '';
    $bulkTemplateKeys = [
        'whatsapp_bulk_message_header',
        'whatsapp_bulk_message_content',
        'whatsapp_bulk_message_final',
    ];
    $initialWorkspace = old('recipient_mode') || old('bulk_message_en') || old('bulk_message_am')
        ? 'bulk'
        : 'main';
    $templateMeta = [
        'whatsapp_daily_reminder_header' => [
            'group' => 'daily',
            'description' => __('app.whatsapp_template_desc_daily_header'),
        ],
        'whatsapp_daily_reminder_content' => [
            'group' => 'daily',
            'description' => __('app.whatsapp_template_desc_daily_content'),
        ],
        'whatsapp_daily_reminder_footer' => [
            'group' => 'daily',
            'description' => __('app.whatsapp_template_desc_daily_footer'),
        ],
        'whatsapp_daily_reminder_yearly_block' => [
            'group' => 'daily',
            'description' => __('app.whatsapp_template_desc_daily_yearly_block'),
        ],
        'whatsapp_daily_reminder_monthly_block' => [
            'group' => 'daily',
            'description' => __('app.whatsapp_template_desc_daily_monthly_block'),
        ],
        'whatsapp_bulk_message_header' => [
            'group' => 'bulk',
            'description' => __('app.whatsapp_template_desc_bulk_header'),
        ],
        'whatsapp_bulk_message_content' => [
            'group' => 'bulk',
            'description' => __('app.whatsapp_template_desc_bulk_content'),
        ],
        'whatsapp_bulk_message_final' => [
            'group' => 'bulk',
            'description' => __('app.whatsapp_template_desc_bulk_final'),
        ],
        'whatsapp_confirmation_prompt_message' => [
            'group' => 'confirmation',
            'description' => __('app.whatsapp_template_desc_confirm_prompt'),
        ],
        'whatsapp_invalid_reply_message' => [
            'group' => 'confirmation',
            'description' => __('app.whatsapp_template_desc_invalid_reply'),
        ],
        'whatsapp_confirmation_activated_message' => [
            'group' => 'confirmation',
            'description' => __('app.whatsapp_template_desc_confirmed_notice'),
        ],
        'whatsapp_confirmation_go_back_message' => [
            'group' => 'confirmation',
            'description' => __('app.whatsapp_template_desc_go_back'),
        ],
        'whatsapp_confirmation_rejected_message' => [
            'group' => 'confirmation',
            'description' => __('app.whatsapp_template_desc_rejected_notice'),
        ],
    ];

    $templateAliasMap = [
        'whatsapp_daily_reminder_header' => [':header_en', ':header_am'],
        'whatsapp_daily_reminder_yearly_block' => [':commemorations_block_en', ':commemorations_block_am'],
        'whatsapp_daily_reminder_monthly_block' => [':commemorations_block_en', ':commemorations_block_am'],
        'whatsapp_daily_reminder_footer' => [':footer_en', ':footer_am'],
        'whatsapp_daily_reminder_content' => [':header_en', ':commemorations_block_en', ':footer_en', ':header_am', ':commemorations_block_am', ':footer_am'],
        'whatsapp_bulk_message_final' => [':name', ':header_en', ':content_en', ':header_am', ':content_am', ':header', ':content', ':url', ':url_1', ':url_2', ':url_3'],
    ];

    $templateGroups = [
        'daily' => array_values(array_filter($templates, static fn (array $template): bool => ($templateMeta[$template['key']]['group'] ?? 'confirmation') === 'daily')),
        'bulk' => array_values(array_filter($templates, static fn (array $template): bool => ($templateMeta[$template['key']]['group'] ?? 'confirmation') === 'bulk')),
        'confirmation' => array_values(array_filter($templates, static fn (array $template): bool => ($templateMeta[$template['key']]['group'] ?? 'confirmation') === 'confirmation')),
    ];
@endphp

<style>
    [x-cloak] { display: none !important; }
    .wa-bubble { position: relative; }
    .wa-bubble::before {
        content: '';
        position: absolute;
        top: 0;
        left: -6px;
        width: 12px;
        height: 12px;
        background: inherit;
        clip-path: polygon(100% 0, 0 0, 100% 100%);
    }
    .wa-bubble-en::before { background: #dcf8c6; }
    .wa-bubble-am::before { background: #fff; }
    @media (prefers-color-scheme: dark) {
        .wa-bubble-en::before { background: #025144; }
        .wa-bubble-am::before { background: #1f2c34; }
    }
</style>

<div x-data="whatsappTemplateEditor(@js($firstTemplateKey), @js($activeMembers->count()), @js(old('recipient_mode', 'all_active')), @js(collect(old('selected_member_ids', []))->map(fn ($id) => (string) $id)->values()->all()), @js($initialWorkspace), @js($bulkTemplateKeys))" class="space-y-6">

    {{-- Page header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-primary">{{ __('app.whatsapp_template_title') }}</h1>
            <p class="mt-1 max-w-2xl text-sm text-muted-text">{{ __('app.whatsapp_template_help') }}</p>
        </div>
        <button x-show="activeWorkspace === 'main'" x-cloak type="submit" form="whatsapp-template-form"
            class="inline-flex items-center gap-2 rounded-xl bg-accent px-5 py-2.5 text-sm font-semibold text-on-accent shadow-sm transition hover:bg-accent-hover active:scale-[0.97] shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ __('app.whatsapp_template_save') }}
        </button>
    </div>

    <div class="inline-grid grid-cols-1 gap-2 rounded-2xl border border-border bg-card p-2 shadow-sm sm:grid-cols-2">
        <button
            type="button"
            @click="switchWorkspace('main')"
            :class="activeWorkspace === 'main' ? 'bg-accent text-on-accent shadow-sm' : 'text-secondary hover:bg-muted/60'"
            class="rounded-xl px-4 py-3 text-left transition"
        >
            <span class="block text-sm font-semibold">{{ __('app.whatsapp_template_tab_templates') }}</span>
            <span class="mt-1 block text-xs" :class="activeWorkspace === 'main' ? 'text-on-accent/80' : 'text-muted-text'">{{ __('app.whatsapp_template_tab_templates_help') }}</span>
        </button>
        <button
            type="button"
            @click="switchWorkspace('bulk')"
            :class="activeWorkspace === 'bulk' ? 'bg-accent text-on-accent shadow-sm' : 'text-secondary hover:bg-muted/60'"
            class="rounded-xl px-4 py-3 text-left transition"
        >
            <span class="block text-sm font-semibold">{{ __('app.whatsapp_template_tab_bulk') }}</span>
            <span class="mt-1 block text-xs" :class="activeWorkspace === 'bulk' ? 'text-on-accent/80' : 'text-muted-text'">{{ __('app.whatsapp_template_tab_bulk_help') }}</span>
        </button>
    </div>

    {{-- Top cards: Workflow + Test --}}
    <div x-show="activeWorkspace === 'main'" x-cloak class="grid gap-4 lg:grid-cols-[1fr_380px]">

        {{-- Workflow steps --}}
        <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
            <div class="flex items-center gap-3 mb-4">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-accent/10 text-accent">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <h2 class="text-sm font-semibold text-primary">{{ __('app.whatsapp_template_workflow_title') }}</h2>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1 flex items-start gap-3 rounded-xl border border-border bg-surface px-3.5 py-3">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-accent text-[11px] font-bold text-on-accent">1</span>
                    <p class="text-sm text-secondary leading-snug">{{ __('app.whatsapp_template_workflow_step_1') }}</p>
                </div>
                <div class="hidden sm:flex items-center text-muted-text">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </div>
                <div class="flex-1 flex items-start gap-3 rounded-xl border border-border bg-surface px-3.5 py-3">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-accent text-[11px] font-bold text-on-accent">2</span>
                    <p class="text-sm text-secondary leading-snug">{{ __('app.whatsapp_template_workflow_step_2') }}</p>
                </div>
                <div class="hidden sm:flex items-center text-muted-text">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </div>
                <div class="flex-1 flex items-start gap-3 rounded-xl border border-border bg-surface px-3.5 py-3">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-accent text-[11px] font-bold text-on-accent">3</span>
                    <p class="text-sm text-secondary leading-snug">{{ __('app.whatsapp_template_workflow_step_3') }}</p>
                </div>
            </div>
        </div>

        {{-- Test send --}}
        <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
            <div class="flex items-center gap-3 mb-4">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-primary">{{ __('app.whatsapp_template_test_title') }}</h2>
                    <p class="text-xs text-muted-text">{{ __('app.whatsapp_template_test_help') }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('admin.whatsapp.template.test') }}" class="space-y-3">
                @csrf
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                    <div>
                        <label for="template-test-member" class="mb-1 block text-xs font-medium text-secondary">{{ __('app.whatsapp_template_test_member_label') }}</label>
                        <select id="template-test-member" name="member_id" required
                            class="w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm text-primary outline-none transition focus:ring-2 focus:ring-accent">
                            <option value="">{{ __('app.whatsapp_template_test_member_placeholder') }}</option>
                            @foreach($testMembers as $member)
                                @php
                                    $memberLabel = trim((string) ($member->baptism_name ?: ''));
                                    if ($memberLabel === '') {
                                        $memberLabel = __('app.whatsapp_template_test_member_fallback');
                                    }
                                    $memberLabel .= ' - '.$member->whatsapp_phone;
                                    if ($member->whatsapp_confirmation_status) {
                                        $memberLabel .= ' - '.$member->whatsapp_confirmation_status;
                                    }
                                    if ($member->whatsapp_language) {
                                        $memberLabel .= ' - '.strtoupper((string) $member->whatsapp_language);
                                    }
                                @endphp
                                <option value="{{ $member->id }}" @selected((string) old('template_test_member_id') === (string) $member->id)>
                                    {{ $memberLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="template-test-language" class="mb-1 block text-xs font-medium text-secondary">{{ __('app.whatsapp_template_test_language_label') }}</label>
                        <select id="template-test-language" name="test_locale"
                            class="w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm text-primary outline-none transition focus:ring-2 focus:ring-accent">
                            <option value="member" @selected((string) old('template_test_locale', 'member') === 'member')>{{ __('app.whatsapp_template_test_language_member') }}</option>
                            <option value="en" @selected((string) old('template_test_locale') === 'en')>{{ __('app.whatsapp_template_test_language_en') }}</option>
                            <option value="am" @selected((string) old('template_test_locale') === 'am')>{{ __('app.whatsapp_template_test_language_am') }}</option>
                        </select>
                    </div>
                </div>
                <button type="submit" @disabled($testMembers->isEmpty())
                    class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 active:scale-[0.97] disabled:cursor-not-allowed disabled:opacity-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    {{ __('app.whatsapp_template_send_test') }}
                </button>
            </form>
        </div>
    </div>

    {{-- Bulk send --}}
    <div x-show="activeWorkspace === 'bulk'" x-cloak class="rounded-2xl border border-border bg-card p-5 shadow-sm space-y-5">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h2 class="text-base font-semibold text-primary">{{ __('app.whatsapp_bulk_send_title') }}</h2>
                <p class="mt-1 max-w-3xl text-sm text-muted-text">{{ __('app.whatsapp_bulk_send_help') }}</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-accent/10 px-3 py-1 text-xs font-semibold text-accent">
                <span x-text="'{{ __('app.whatsapp_bulk_send_count', ['count' => '__COUNT__']) }}'.replace('__COUNT__', bulkRecipientCount())"></span>
            </span>
        </div>

        <div class="flex items-start gap-3 rounded-xl border border-amber-300/50 bg-amber-50/60 px-4 py-3 text-sm text-amber-800 dark:bg-amber-900/15 dark:text-amber-200 dark:border-amber-500/20">
            <svg class="w-5 h-5 shrink-0 mt-0.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
            <span>{{ __('app.whatsapp_bulk_send_warning') }}</span>
        </div>

        <div class="rounded-2xl border border-border bg-surface/30 p-4">
            <div class="flex flex-wrap items-center gap-3">
                <h3 class="text-sm font-semibold text-primary">{{ __('app.whatsapp_bulk_message_placeholder_title') }}</h3>
                <code class="rounded-lg border border-border bg-card px-3 py-1.5 text-xs font-mono text-primary">:name</code>
            </div>
            <p class="mt-2 text-sm text-muted-text">{{ __('app.whatsapp_bulk_message_placeholder_help') }}</p>
        </div>

        <form method="POST" action="{{ route('admin.whatsapp.template.bulk-send') }}" class="space-y-4">
            @csrf

            <div class="space-y-4">
                <div class="grid gap-4 md:grid-cols-1">
                    <div>
                        <label for="bulk-recipient-mode" class="mb-1 block text-xs font-medium text-secondary">{{ __('app.whatsapp_bulk_send_recipient_mode_label') }}</label>
                        <select
                            id="bulk-recipient-mode"
                            name="recipient_mode"
                            x-model="bulkRecipientMode"
                            class="w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm text-primary outline-none transition focus:ring-2 focus:ring-accent"
                        >
                            <option value="all_active">{{ __('app.whatsapp_bulk_send_mode_all') }}</option>
                            <option value="selected_active">{{ __('app.whatsapp_bulk_send_mode_selected') }}</option>
                        </select>
                    </div>
                </div>

                <div x-show="isBulkSelectedMode()" x-cloak>
                    <label for="bulk-members" class="mb-1 block text-xs font-medium text-secondary">{{ __('app.whatsapp_bulk_send_members_label') }}</label>
                    <select
                        id="bulk-members"
                        name="selected_member_ids[]"
                        multiple
                        size="8"
                        x-model="bulkSelectedMembers"
                        class="w-full rounded-xl border border-border bg-surface px-3 py-2 text-sm text-primary outline-none transition focus:ring-2 focus:ring-accent"
                    >
                        @foreach($activeMembers as $member)
                            <option value="{{ $member->id }}">
                                {{ trim((string) ($member->baptism_name ?: __('app.whatsapp_template_test_member_fallback'))) }} - {{ $member->whatsapp_phone }} - {{ strtoupper((string) ($member->whatsapp_language ?: 'en')) }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-muted-text">{{ __('app.whatsapp_bulk_send_members_placeholder') }}</p>
                </div>

                <div>
                    <label for="bulk-sample-member" class="mb-1 block text-xs font-medium text-secondary">{{ __('app.whatsapp_bulk_sample_member_label') }}</label>
                    <select id="bulk-sample-member" name="bulk_sample_member_id"
                        class="w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm text-primary outline-none transition focus:ring-2 focus:ring-accent">
                        <option value="">{{ __('app.whatsapp_bulk_sample_member_placeholder') }}</option>
                        @foreach($activeMembers as $member)
                            <option value="{{ $member->id }}" @selected((string) old('bulk_sample_member_id') === (string) $member->id)>
                                {{ trim((string) ($member->baptism_name ?: __('app.whatsapp_template_test_member_fallback'))) }} - {{ $member->whatsapp_phone }} - {{ strtoupper((string) ($member->whatsapp_language ?: 'am')) }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-muted-text">{{ __('app.whatsapp_bulk_sample_member_help') }}</p>
                </div>

                <div class="grid gap-4 xl:grid-cols-2">
                    <div class="rounded-2xl border border-border bg-card p-4 space-y-4">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-sm font-semibold text-primary">{{ __('app.whatsapp_bulk_send_message_en_label') }}</h3>
                            <code class="rounded-lg border border-border bg-surface px-3 py-1.5 text-xs font-mono text-primary">:name</code>
                        </div>
                        <textarea
                            id="bulk-message-en"
                            name="bulk_message_en"
                            rows="10"
                            class="w-full rounded-xl border border-border bg-surface px-4 py-3 text-sm leading-6 text-primary outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20 resize-y"
                        >{{ old('bulk_message_en', $bulkMessages['en'] ?? '') }}</textarea>
                        <div>
                            <div class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-muted-text">{{ __('app.whatsapp_bulk_preview_label') }}</div>
                            <div class="rounded-xl border border-border bg-surface/50 p-4">
                                <p id="bulk-preview-en" class="whitespace-pre-wrap break-words text-sm leading-6 text-primary"></p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-border bg-card p-4 space-y-4">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-sm font-semibold text-primary">{{ __('app.whatsapp_bulk_send_message_am_label') }}</h3>
                            <code class="rounded-lg border border-border bg-surface px-3 py-1.5 text-xs font-mono text-primary">:name</code>
                        </div>
                        <textarea
                            id="bulk-message-am"
                            name="bulk_message_am"
                            rows="10"
                            class="w-full rounded-xl border border-border bg-surface px-4 py-3 text-sm leading-6 text-primary outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20 resize-y"
                        >{{ old('bulk_message_am', $bulkMessages['am'] ?? '') }}</textarea>
                        <div>
                            <div class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-muted-text">{{ __('app.whatsapp_bulk_preview_label') }}</div>
                            <div class="rounded-xl border border-border bg-surface/50 p-4">
                                <p id="bulk-preview-am" class="whitespace-pre-wrap break-words text-sm leading-6 text-primary"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <button type="submit" formaction="{{ route('admin.whatsapp.template.bulk-save') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-border bg-card px-5 py-2.5 text-sm font-semibold text-primary shadow-sm transition hover:bg-muted/50 active:scale-[0.97]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ __('app.whatsapp_bulk_save_button') }}
                    </button>
                    <button type="submit" formaction="{{ route('admin.whatsapp.template.bulk-test') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-2.5 text-sm font-semibold text-emerald-700 shadow-sm transition hover:bg-emerald-100 active:scale-[0.97] dark:border-emerald-500/20 dark:bg-emerald-900/15 dark:text-emerald-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        {{ __('app.whatsapp_bulk_send_sample_button') }}
                    </button>
                    <button type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 active:scale-[0.97]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        {{ __('app.whatsapp_bulk_send_button') }}
                    </button>
                </div>
            </div>

        </form>
    </div>

    {{-- Main editor area --}}
    <div x-show="activeWorkspace === 'main'" x-cloak class="grid gap-5 xl:grid-cols-[260px_minmax(0,1fr)]">

        {{-- Sidebar: template list --}}
        <aside class="space-y-3 xl:sticky xl:top-24 xl:self-start">
            @foreach(['daily' => __('app.whatsapp_template_group_daily'), 'bulk' => __('app.whatsapp_template_group_bulk'), 'confirmation' => __('app.whatsapp_template_group_confirmation')] as $groupKey => $groupLabel)
            <details x-show="shouldShowGroup('{{ $groupKey }}')" x-cloak class="rounded-2xl border border-border bg-card shadow-sm overflow-hidden" @if($groupKey === 'daily' || $groupKey === 'bulk') open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 bg-surface/60 marker:hidden">
                    <h2 class="text-[11px] font-bold uppercase tracking-widest text-muted-text">{{ $groupLabel }}</h2>
                    <span class="text-xs font-bold text-muted-text">+</span>
                </summary>
                <div class="p-2 space-y-1 border-t border-border">
                    @foreach($templateGroups[$groupKey] as $template)
                        <button type="button"
                            @click="selectTemplate('{{ $template['key'] }}')"
                            :class="activeTemplate === '{{ $template['key'] }}'
                                ? 'bg-accent/10 text-accent border-accent/20 shadow-sm'
                                : 'bg-transparent text-secondary border-transparent hover:bg-muted/50'"
                            class="w-full rounded-xl border px-3 py-2.5 text-left transition-all duration-150">
                            <div class="text-[13px] font-semibold leading-snug">{{ $template['title'] }}</div>
                            <div class="mt-0.5 text-[11px] leading-relaxed text-muted-text line-clamp-2">{{ $templateMeta[$template['key']]['description'] ?? '' }}</div>
                        </button>
                    @endforeach
                </div>
            </details>
            @endforeach
        </aside>

        {{-- Editor panels --}}
        <div class="space-y-4">
            {{-- Warning --}}
            <div class="flex items-start gap-3 rounded-xl border border-amber-300/50 bg-amber-50/60 px-4 py-3 text-sm text-amber-800 dark:bg-amber-900/15 dark:text-amber-200 dark:border-amber-500/20">
                <svg class="w-5 h-5 shrink-0 mt-0.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                <span>{{ __('app.whatsapp_template_warning') }}</span>
            </div>

            <section
                x-cloak
                x-show="activeWorkspace === 'main' && ['whatsapp_daily_reminder_header','whatsapp_daily_reminder_yearly_block','whatsapp_daily_reminder_monthly_block','whatsapp_daily_reminder_footer','whatsapp_daily_reminder_content'].includes(activeTemplate)"
                class="rounded-2xl border border-border bg-card shadow-sm overflow-hidden"
            >
                <div class="border-b border-border bg-surface/60 px-5 py-4">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 class="text-base font-semibold text-primary">{{ __('app.whatsapp_template_final_preview_title') }}</h2>
                            <p class="mt-1 text-sm text-muted-text">{{ __('app.whatsapp_template_final_preview_help') }}</p>
                        </div>
                        <button
                            type="button"
                            @click="selectTemplate('whatsapp_daily_reminder_content')"
                            class="inline-flex items-center justify-center rounded-lg border border-border bg-card px-4 py-2 text-sm font-semibold text-primary transition hover:bg-muted/50"
                        >
                            {{ __('app.whatsapp_template_final_preview_open') }}
                        </button>
                    </div>
                </div>
                <div class="grid gap-4 p-5 md:grid-cols-2">
                    <div class="rounded-2xl overflow-hidden border border-border shadow-sm">
                        <div class="bg-[#075e54] dark:bg-[#1f2c34] px-3 py-2 flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-full bg-white/20 flex items-center justify-center">
                                <svg class="w-3.5 h-3.5 text-white/80" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-white text-xs font-medium">{{ __('app.abiy_tsom_bot') }}</p>
                            </div>
                            <span class="flex h-5 items-center rounded bg-white/15 px-1.5 text-[10px] font-bold text-white/80">EN</span>
                        </div>
                        <div class="bg-[#ece5dd] dark:bg-[#0b141a] px-3 py-3 min-h-[180px]">
                            <div class="ml-1.5">
                                <div class="wa-bubble wa-bubble-en inline-block max-w-[95%] rounded-lg rounded-tl-none bg-[#dcf8c6] dark:bg-[#025144] px-3 py-2 shadow-sm">
                                    <p id="final-preview-en" class="whitespace-pre-wrap break-words text-[13px] leading-[1.45] text-[#111b21] dark:text-[#e9edef]"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl overflow-hidden border border-border shadow-sm">
                        <div class="bg-[#075e54] dark:bg-[#1f2c34] px-3 py-2 flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-full bg-white/20 flex items-center justify-center">
                                <svg class="w-3.5 h-3.5 text-white/80" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-white text-xs font-medium">{{ __('app.abiy_tsom_bot') }}</p>
                            </div>
                            <span class="flex h-5 items-center rounded bg-white/15 px-1.5 text-[10px] font-bold text-white/80">AM</span>
                        </div>
                        <div class="bg-[#ece5dd] dark:bg-[#0b141a] px-3 py-3 min-h-[180px]">
                            <div class="ml-1.5">
                                <div class="wa-bubble wa-bubble-am inline-block max-w-[95%] rounded-lg rounded-tl-none bg-white dark:bg-[#1f2c34] px-3 py-2 shadow-sm">
                                    <p id="final-preview-am" class="whitespace-pre-wrap break-words text-[13px] leading-[1.45] text-[#111b21] dark:text-[#e9edef]"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <form id="whatsapp-template-form" method="POST" action="{{ route('admin.whatsapp.template.update') }}" class="space-y-4">
                @csrf
                @method('PUT')

                @foreach($templates as $template)
                <section x-cloak x-show="activeTemplate === '{{ $template['key'] }}' && workspaceAllowsTemplate('{{ $template['key'] }}')"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    class="rounded-2xl border border-border bg-card shadow-sm overflow-hidden">

                    {{-- Template header --}}
                    <div class="border-b border-border bg-surface/50 px-5 py-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2 mb-1.5">
                                    <span class="inline-flex items-center rounded-md bg-accent/10 px-2 py-0.5 text-[10px] font-bold uppercase tracking-widest text-accent">
                                        {{
                                            ($templateMeta[$template['key']]['group'] ?? 'confirmation') === 'daily'
                                                ? __('app.whatsapp_template_group_daily')
                                                : ((($templateMeta[$template['key']]['group'] ?? 'confirmation') === 'bulk')
                                                    ? __('app.whatsapp_template_group_bulk')
                                                    : __('app.whatsapp_template_group_confirmation'))
                                        }}
                                    </span>
                                    <code class="text-[11px] text-muted-text bg-muted/50 px-2 py-0.5 rounded-md">{{ $template['key'] }}</code>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-lg font-bold text-primary">{{ $template['title'] }}</h2>
                                    @foreach($templateAliasMap[$template['key']] ?? [] as $alias)
                                        <code class="rounded-md border border-border bg-card px-2 py-0.5 text-[11px] font-semibold text-muted-text">{{ $alias }}</code>
                                    @endforeach
                                </div>
                                <p class="mt-1 text-sm text-muted-text">{{ $templateMeta[$template['key']]['description'] ?? '' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-5 space-y-4">
                        @if($template['key'] === 'whatsapp_daily_reminder_content')
                            <div class="rounded-xl border border-primary/15 bg-primary/5 px-4 py-4">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                    <div>
                                        <h3 class="text-sm font-semibold text-primary">{{ __('app.whatsapp_template_final_components_title') }}</h3>
                                        <p class="mt-1 text-sm text-muted-text">{{ __('app.whatsapp_template_final_preview_help') }}</p>
                                    </div>
                                    <div class="flex flex-col gap-3 lg:items-end">
                                        <div class="flex flex-wrap gap-2 lg:justify-end">
                                            <span class="text-[11px] font-semibold uppercase tracking-wide text-muted-text">{{ __('app.whatsapp_template_apply_recommended') }}</span>
                                            <button
                                                type="button"
                                                @click="applyRecommendedFinalTemplate('en')"
                                                class="inline-flex items-center justify-center rounded-lg border border-border bg-card px-3 py-1.5 text-xs font-semibold text-primary transition hover:bg-muted/50"
                                            >
                                                {{ __('app.whatsapp_template_apply_recommended_en') }}
                                            </button>
                                            <button
                                                type="button"
                                                @click="applyRecommendedFinalTemplate('am')"
                                                class="inline-flex items-center justify-center rounded-lg border border-border bg-card px-3 py-1.5 text-xs font-semibold text-primary transition hover:bg-muted/50"
                                            >
                                                {{ __('app.whatsapp_template_apply_recommended_am') }}
                                            </button>
                                        </div>
                                        <div class="text-[11px] font-semibold uppercase tracking-wide text-muted-text">{{ __('app.whatsapp_template_final_components_en') }}</div>
                                        <div class="flex flex-wrap gap-2 lg:justify-end">
                                            <span class="rounded-full border border-border bg-card px-3 py-1 text-xs font-mono text-primary">:header_en</span>
                                            <span class="rounded-full border border-border bg-card px-3 py-1 text-xs font-mono text-primary">:commemorations_block_en</span>
                                            <span class="rounded-full border border-border bg-card px-3 py-1 text-xs font-mono text-primary">:footer_en</span>
                                        </div>
                                        <div class="text-[11px] font-semibold uppercase tracking-wide text-muted-text">{{ __('app.whatsapp_template_final_components_am') }}</div>
                                        <div class="flex flex-wrap gap-2 lg:justify-end">
                                            <span class="rounded-full border border-border bg-card px-3 py-1 text-xs font-mono text-primary">:header_am</span>
                                            <span class="rounded-full border border-border bg-card px-3 py-1 text-xs font-mono text-primary">:commemorations_block_am</span>
                                            <span class="rounded-full border border-border bg-card px-3 py-1 text-xs font-mono text-primary">:footer_am</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @elseif(in_array($template['key'], $bulkTemplateKeys, true))
                            <div class="rounded-xl border border-primary/15 bg-primary/5 px-4 py-4">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                    <div>
                                        <h3 class="text-sm font-semibold text-primary">{{ __('app.whatsapp_bulk_builder_title') }}</h3>
                                        <p class="mt-1 text-sm text-muted-text">
                                            @if($template['key'] === 'whatsapp_bulk_message_header')
                                                {{ __('app.whatsapp_bulk_template_header_help') }}
                                            @elseif($template['key'] === 'whatsapp_bulk_message_content')
                                                {{ __('app.whatsapp_bulk_template_content_help') }}
                                            @else
                                                {{ __('app.whatsapp_bulk_template_final_help') }}
                                            @endif
                                        </p>
                                    </div>
                                    @if($template['key'] === 'whatsapp_bulk_message_final')
                                        <div class="flex flex-col gap-3 lg:items-end">
                                            <div class="flex flex-wrap gap-2 lg:justify-end">
                                                <span class="text-[11px] font-semibold uppercase tracking-wide text-muted-text">{{ __('app.whatsapp_template_apply_recommended') }}</span>
                                                <button
                                                    type="button"
                                                    @click="applyRecommendedBulkFinalTemplate('current')"
                                                    class="inline-flex items-center justify-center rounded-lg border border-border bg-card px-3 py-1.5 text-xs font-semibold text-primary transition hover:bg-muted/50"
                                                >
                                                    {{ __('app.whatsapp_bulk_apply_recommended_current') }}
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="applyRecommendedBulkFinalTemplate('explicit')"
                                                    class="inline-flex items-center justify-center rounded-lg border border-border bg-card px-3 py-1.5 text-xs font-semibold text-primary transition hover:bg-muted/50"
                                                >
                                                    {{ __('app.whatsapp_bulk_apply_recommended_explicit') }}
                                                </button>
                                            </div>
                                            <p class="text-xs text-muted-text lg:max-w-xs lg:text-right">{{ __('app.whatsapp_bulk_default_layout_help') }}</p>
                                            <div class="text-[11px] font-semibold uppercase tracking-wide text-muted-text">{{ __('app.whatsapp_template_final_components_en') }}</div>
                                            <div class="flex flex-wrap gap-2 lg:justify-end">
                                                @foreach([':header_en', ':content_en'] as $placeholder)
                                                    <span class="rounded-full border border-border bg-card px-3 py-1 text-xs font-mono text-primary">{{ $placeholder }}</span>
                                                @endforeach
                                            </div>
                                            <div class="text-[11px] font-semibold uppercase tracking-wide text-muted-text">{{ __('app.whatsapp_template_final_components_am') }}</div>
                                            <div class="flex flex-wrap gap-2 lg:justify-end">
                                                @foreach([':header_am', ':content_am'] as $placeholder)
                                                    <span class="rounded-full border border-border bg-card px-3 py-1 text-xs font-mono text-primary">{{ $placeholder }}</span>
                                                @endforeach
                                            </div>
                                            <div class="text-[11px] font-semibold uppercase tracking-wide text-muted-text">{{ __('app.whatsapp_bulk_placeholders_title') }}</div>
                                            <div class="flex flex-wrap gap-2 lg:justify-end">
                                                @foreach([':name', ':header', ':content', ':url_1', ':url_2', ':url_3'] as $placeholder)
                                                    <span class="rounded-full border border-border bg-card px-3 py-1 text-xs font-mono text-primary">{{ $placeholder }}</span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            @if($template['key'] === 'whatsapp_bulk_message_final')
                                <div class="rounded-xl border border-primary/15 bg-primary/5 px-4 py-4">
                                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                        <div>
                                            <h3 class="text-sm font-semibold text-primary">{{ __('app.whatsapp_bulk_message_parts_title') }}</h3>
                                            <p class="mt-1 text-sm text-muted-text">{{ __('app.whatsapp_bulk_message_parts_help') }}</p>
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            <button
                                                type="button"
                                                @click="selectTemplate('whatsapp_bulk_message_header')"
                                                class="inline-flex items-center justify-center rounded-lg border border-border bg-card px-3 py-1.5 text-xs font-semibold text-primary transition hover:bg-muted/50"
                                            >
                                                {{ __('app.whatsapp_bulk_open_header_template') }}
                                            </button>
                                            <button
                                                type="button"
                                                @click="selectTemplate('whatsapp_bulk_message_content')"
                                                class="inline-flex items-center justify-center rounded-lg border border-border bg-card px-3 py-1.5 text-xs font-semibold text-primary transition hover:bg-muted/50"
                                            >
                                                {{ __('app.whatsapp_bulk_open_content_template') }}
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mt-4 grid gap-3 md:grid-cols-3">
                                        <div class="rounded-xl border border-border bg-card p-4">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="text-xs font-semibold text-primary">{{ __('app.whatsapp_bulk_message_part_header_title') }}</span>
                                                <code class="rounded border border-border bg-surface px-2 py-1 text-[11px] font-mono text-primary">:header</code>
                                            </div>
                                            <p class="mt-2 text-sm text-muted-text">{{ __('app.whatsapp_bulk_message_part_header_help') }}</p>
                                        </div>
                                        <div class="rounded-xl border border-border bg-card p-4">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="text-xs font-semibold text-primary">{{ __('app.whatsapp_bulk_message_part_content_title') }}</span>
                                                <code class="rounded border border-border bg-surface px-2 py-1 text-[11px] font-mono text-primary">:content</code>
                                            </div>
                                            <p class="mt-2 text-sm text-muted-text">{{ __('app.whatsapp_bulk_message_part_content_help') }}</p>
                                        </div>
                                        <div class="rounded-xl border border-border bg-card p-4">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="text-xs font-semibold text-primary">{{ __('app.whatsapp_bulk_message_part_url_title') }}</span>
                                                <code class="rounded border border-border bg-surface px-2 py-1 text-[11px] font-mono text-primary">:url_1 / :url_2 / :url_3</code>
                                            </div>
                                            <p class="mt-2 text-sm text-muted-text">{{ __('app.whatsapp_bulk_message_part_url_help') }}</p>
                                        </div>
                                    </div>
                                    <div class="mt-4 grid gap-3 lg:grid-cols-3">
                                        <div class="rounded-xl border border-border bg-card p-4">
                                            <div class="text-xs font-semibold text-primary">{{ __('app.whatsapp_bulk_message_part_header_title') }} {{ __('app.whatsapp_bulk_preview_label') }}</div>
                                            <div class="mt-3 space-y-2">
                                                <div>
                                                    <div class="text-[11px] font-semibold uppercase tracking-wide text-muted-text">EN</div>
                                                    <p id="bulk-section-preview-header-en" class="mt-1 whitespace-pre-wrap break-words text-sm text-secondary"></p>
                                                </div>
                                                <div>
                                                    <div class="text-[11px] font-semibold uppercase tracking-wide text-muted-text">AM</div>
                                                    <p id="bulk-section-preview-header-am" class="mt-1 whitespace-pre-wrap break-words text-sm text-secondary"></p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="rounded-xl border border-border bg-card p-4">
                                            <div class="text-xs font-semibold text-primary">{{ __('app.whatsapp_bulk_message_part_content_title') }} {{ __('app.whatsapp_bulk_preview_label') }}</div>
                                            <div class="mt-3 space-y-2">
                                                <div>
                                                    <div class="text-[11px] font-semibold uppercase tracking-wide text-muted-text">EN</div>
                                                    <p id="bulk-section-preview-content-en" class="mt-1 whitespace-pre-wrap break-words text-sm text-secondary"></p>
                                                </div>
                                                <div>
                                                    <div class="text-[11px] font-semibold uppercase tracking-wide text-muted-text">AM</div>
                                                    <p id="bulk-section-preview-content-am" class="mt-1 whitespace-pre-wrap break-words text-sm text-secondary"></p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="rounded-xl border border-border bg-card p-4">
                                            <div class="text-xs font-semibold text-primary">{{ __('app.whatsapp_bulk_message_part_url_title') }} {{ __('app.whatsapp_bulk_preview_label') }}</div>
                                            <div class="mt-3 space-y-2 text-sm text-secondary">
                                                <p><span class="font-mono text-primary">:url_1</span> <span id="bulk-section-preview-url-1"></span></p>
                                                <p><span class="font-mono text-primary">:url_2</span> <span id="bulk-section-preview-url-2"></span></p>
                                                <p><span class="font-mono text-primary">:url_3</span> <span id="bulk-section-preview-url-3"></span></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-4 rounded-xl border border-border bg-card p-4">
                                        <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                                            <div>
                                                <div class="text-xs font-semibold text-primary">{{ __('app.whatsapp_bulk_default_layout_title') }}</div>
                                                <p class="mt-1 text-sm text-muted-text">{{ __('app.whatsapp_bulk_default_layout_help') }}</p>
                                            </div>
                                            <code class="rounded-lg border border-border bg-surface px-3 py-2 text-xs font-mono text-primary">:header\n\n:content\n\n:url_1</code>
                                        </div>
                                    </div>
                                </div>
                            @elseif($template['key'] === 'whatsapp_bulk_message_header')
                                <div class="rounded-xl border border-border bg-surface/60 px-4 py-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <h3 class="text-sm font-semibold text-primary">{{ __('app.whatsapp_bulk_template_input_title') }}</h3>
                                            <p class="mt-1 text-sm text-muted-text">{{ __('app.whatsapp_bulk_template_header_input_help') }}</p>
                                        </div>
                                        <code class="rounded-lg border border-border bg-card px-3 py-2 text-xs font-mono text-primary">:header</code>
                                    </div>
                                </div>
                            @elseif($template['key'] === 'whatsapp_bulk_message_content')
                                <div class="rounded-xl border border-border bg-surface/60 px-4 py-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <h3 class="text-sm font-semibold text-primary">{{ __('app.whatsapp_bulk_template_input_title') }}</h3>
                                            <p class="mt-1 text-sm text-muted-text">{{ __('app.whatsapp_bulk_template_content_input_help') }}</p>
                                        </div>
                                        <code class="rounded-lg border border-border bg-card px-3 py-2 text-xs font-mono text-primary">:content</code>
                                    </div>
                                </div>
                            @endif
                        @endif

                        {{-- Placeholder toolbar --}}
                        <div class="rounded-xl bg-surface/60 border border-border px-4 py-3">
                            <div class="flex items-center justify-between gap-3 mb-2">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                    <h3 class="text-xs font-semibold text-primary uppercase tracking-wide">{{ __('app.whatsapp_template_placeholders') }}</h3>
                                </div>
                                <span class="text-[10px] text-muted-text hidden sm:inline">{{ __('app.whatsapp_template_insert_help') }}</span>
                            </div>
                            @if($template['key'] === 'whatsapp_daily_reminder_content')
                                <div class="space-y-3">
                                    <div>
                                        <div class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-muted-text">{{ __('app.whatsapp_template_final_components_en') }}</div>
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach([':header_en', ':commemorations_block_en', ':footer_en'] as $placeholder)
                                                <button type="button"
                                                    @click.prevent="insertPlaceholder('{{ $placeholder }}')"
                                                    class="group inline-flex items-center gap-1.5 rounded-lg border border-border bg-card pl-2 pr-2.5 py-1.5 text-xs font-mono font-medium text-secondary transition-all hover:border-accent hover:bg-accent/10 hover:text-accent hover:shadow-sm active:scale-95">
                                                    <svg class="w-3 h-3 text-muted-text group-hover:text-accent transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                    {{ $placeholder }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div>
                                        <div class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-muted-text">{{ __('app.whatsapp_template_final_components_am') }}</div>
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach([':header_am', ':commemorations_block_am', ':footer_am'] as $placeholder)
                                                <button type="button"
                                                    @click.prevent="insertPlaceholder('{{ $placeholder }}')"
                                                    class="group inline-flex items-center gap-1.5 rounded-lg border border-border bg-card pl-2 pr-2.5 py-1.5 text-xs font-mono font-medium text-secondary transition-all hover:border-accent hover:bg-accent/10 hover:text-accent hover:shadow-sm active:scale-95">
                                                    <svg class="w-3 h-3 text-muted-text group-hover:text-accent transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                    {{ $placeholder }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @elseif($template['key'] === 'whatsapp_bulk_message_final')
                                <div class="space-y-3">
                                    <div>
                                        <div class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-muted-text">{{ __('app.whatsapp_bulk_default_layout_title') }}</div>
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach([':header', ':content', ':url_1'] as $placeholder)
                                                <button type="button"
                                                    @click.prevent="insertPlaceholder('{{ $placeholder }}')"
                                                    class="group inline-flex items-center gap-1.5 rounded-lg border border-border bg-card pl-2 pr-2.5 py-1.5 text-xs font-mono font-medium text-secondary transition-all hover:border-accent hover:bg-accent/10 hover:text-accent hover:shadow-sm active:scale-95">
                                                    <svg class="w-3 h-3 text-muted-text group-hover:text-accent transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                    {{ $placeholder }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div>
                                        <div class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-muted-text">{{ __('app.whatsapp_template_final_components_en') }}</div>
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach([':header_en', ':content_en'] as $placeholder)
                                                <button type="button"
                                                    @click.prevent="insertPlaceholder('{{ $placeholder }}')"
                                                    class="group inline-flex items-center gap-1.5 rounded-lg border border-border bg-card pl-2 pr-2.5 py-1.5 text-xs font-mono font-medium text-secondary transition-all hover:border-accent hover:bg-accent/10 hover:text-accent hover:shadow-sm active:scale-95">
                                                    <svg class="w-3 h-3 text-muted-text group-hover:text-accent transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                    {{ $placeholder }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div>
                                        <div class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-muted-text">{{ __('app.whatsapp_template_final_components_am') }}</div>
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach([':header_am', ':content_am'] as $placeholder)
                                                <button type="button"
                                                    @click.prevent="insertPlaceholder('{{ $placeholder }}')"
                                                    class="group inline-flex items-center gap-1.5 rounded-lg border border-border bg-card pl-2 pr-2.5 py-1.5 text-xs font-mono font-medium text-secondary transition-all hover:border-accent hover:bg-accent/10 hover:text-accent hover:shadow-sm active:scale-95">
                                                    <svg class="w-3 h-3 text-muted-text group-hover:text-accent transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                    {{ $placeholder }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div>
                                        <div class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-muted-text">{{ __('app.whatsapp_bulk_placeholders_title') }}</div>
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach([':name', ':url_2', ':url_3'] as $placeholder)
                                                <button type="button"
                                                    @click.prevent="insertPlaceholder('{{ $placeholder }}')"
                                                    class="group inline-flex items-center gap-1.5 rounded-lg border border-border bg-card pl-2 pr-2.5 py-1.5 text-xs font-mono font-medium text-secondary transition-all hover:border-accent hover:bg-accent/10 hover:text-accent hover:shadow-sm active:scale-95">
                                                    <svg class="w-3 h-3 text-muted-text group-hover:text-accent transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                    {{ $placeholder }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="flex flex-wrap gap-1.5">
                                    @forelse(array_map(static fn (string $key): string => ':'.$key, $template['placeholder_keys']) as $placeholder)
                                        <button type="button"
                                            @click.prevent="insertPlaceholder('{{ $placeholder }}')"
                                            class="group inline-flex items-center gap-1.5 rounded-lg border border-border bg-card pl-2 pr-2.5 py-1.5 text-xs font-mono font-medium text-secondary transition-all hover:border-accent hover:bg-accent/10 hover:text-accent hover:shadow-sm active:scale-95">
                                            <svg class="w-3 h-3 text-muted-text group-hover:text-accent transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                            {{ $placeholder }}
                                        </button>
                                    @empty
                                        <span class="text-xs text-muted-text">{{ __('app.whatsapp_template_none') }}</span>
                                    @endforelse
                                </div>
                            @endif
                        </div>

                        {{-- EN + AM editors side by side --}}
                        <div class="grid gap-4 md:grid-cols-2">
                            {{-- English editor --}}
                            <div>
                                <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                                    <label for="tpl-en-{{ $template['key'] }}" class="flex items-center gap-2 text-sm font-semibold text-primary">
                                        <span class="flex h-5 w-5 items-center justify-center rounded bg-blue-500/10 text-[10px] font-bold text-blue-600 dark:text-blue-400">EN</span>
                                        {{ __('app.whatsapp_template_en_label') }}
                                    </label>
                                    @if($template['key'] === 'whatsapp_daily_reminder_content')
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach([':header_en', ':commemorations_block_en', ':footer_en'] as $placeholder)
                                                <button
                                                    type="button"
                                                    @click.prevent="insertPlaceholder('{{ $placeholder }}')"
                                                    class="inline-flex items-center rounded-full border border-border bg-card px-2.5 py-1 text-[11px] font-mono font-medium text-primary transition hover:border-accent hover:bg-accent/10 hover:text-accent"
                                                >
                                                    {{ $placeholder }}
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <textarea
                                    id="tpl-en-{{ $template['key'] }}"
                                    name="templates[{{ $template['key'] }}][en]"
                                    rows="14"
                                    data-preview-target="preview-en-{{ $template['key'] }}"
                                    data-locale="en"
                                    data-allowed-placeholders='@json($template['placeholder_keys'])'
                                    @focus="rememberField('tpl-en-{{ $template['key'] }}')"
                                    class="w-full rounded-xl border border-border bg-surface px-4 py-3 text-sm leading-6 text-primary font-mono outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20 resize-y"
                                >{{ old("templates.{$template['key']}.en", $template['en']) }}</textarea>
                            </div>

                            {{-- Amharic editor --}}
                            <div>
                                <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                                    <label for="tpl-am-{{ $template['key'] }}" class="flex items-center gap-2 text-sm font-semibold text-primary">
                                        <span class="flex h-5 w-5 items-center justify-center rounded bg-emerald-500/10 text-[10px] font-bold text-emerald-600 dark:text-emerald-400">AM</span>
                                        {{ __('app.whatsapp_template_am_label') }}
                                    </label>
                                    @if($template['key'] === 'whatsapp_daily_reminder_content')
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach([':header_am', ':commemorations_block_am', ':footer_am'] as $placeholder)
                                                <button
                                                    type="button"
                                                    @click.prevent="insertPlaceholder('{{ $placeholder }}')"
                                                    class="inline-flex items-center rounded-full border border-border bg-card px-2.5 py-1 text-[11px] font-mono font-medium text-primary transition hover:border-accent hover:bg-accent/10 hover:text-accent"
                                                >
                                                    {{ $placeholder }}
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <textarea
                                    id="tpl-am-{{ $template['key'] }}"
                                    name="templates[{{ $template['key'] }}][am]"
                                    rows="14"
                                    data-preview-target="preview-am-{{ $template['key'] }}"
                                    data-locale="am"
                                    data-allowed-placeholders='@json($template['placeholder_keys'])'
                                    @focus="rememberField('tpl-am-{{ $template['key'] }}')"
                                    class="w-full rounded-xl border border-border bg-surface px-4 py-3 text-sm leading-6 text-primary font-mono outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20 resize-y"
                                >{{ old("templates.{$template['key']}.am", $template['am']) }}</textarea>
                            </div>
                        </div>

                        {{-- WhatsApp preview below editors --}}
                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <h3 class="text-sm font-semibold text-primary">{{ __('app.whatsapp_template_preview_title') }}</h3>
                                <span class="text-[10px] text-muted-text ml-1">{{ __('app.whatsapp_template_preview_help') }}</span>
                            </div>

                            {{-- Side-by-side WhatsApp previews --}}
                            <div class="grid gap-4 md:grid-cols-2">
                                {{-- EN preview --}}
                                <div class="rounded-2xl overflow-hidden border border-border shadow-sm">
                                    <div class="bg-[#075e54] dark:bg-[#1f2c34] px-3 py-2 flex items-center gap-2.5">
                                        <div class="w-7 h-7 rounded-full bg-white/20 flex items-center justify-center">
                                            <svg class="w-3.5 h-3.5 text-white/80" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-white text-xs font-medium">{{ __('app.abiy_tsom_bot') }}</p>
                                        </div>
                                        <span class="flex h-5 items-center rounded bg-white/15 px-1.5 text-[10px] font-bold text-white/80">EN</span>
                                    </div>
                                    <div class="bg-[#ece5dd] dark:bg-[#0b141a] px-3 py-3 min-h-[180px]">
                                        <div class="ml-1.5">
                                            <div class="wa-bubble wa-bubble-en inline-block max-w-[95%] rounded-lg rounded-tl-none bg-[#dcf8c6] dark:bg-[#025144] px-3 py-2 shadow-sm">
                                                <p id="preview-en-{{ $template['key'] }}" class="whitespace-pre-wrap break-words text-[13px] leading-[1.45] text-[#111b21] dark:text-[#e9edef]"></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- AM preview --}}
                                <div class="rounded-2xl overflow-hidden border border-border shadow-sm">
                                    <div class="bg-[#075e54] dark:bg-[#1f2c34] px-3 py-2 flex items-center gap-2.5">
                                        <div class="w-7 h-7 rounded-full bg-white/20 flex items-center justify-center">
                                            <svg class="w-3.5 h-3.5 text-white/80" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-white text-xs font-medium">{{ __('app.abiy_tsom_bot') }}</p>
                                        </div>
                                        <span class="flex h-5 items-center rounded bg-white/15 px-1.5 text-[10px] font-bold text-white/80">AM</span>
                                    </div>
                                    <div class="bg-[#ece5dd] dark:bg-[#0b141a] px-3 py-3 min-h-[180px]">
                                        <div class="ml-1.5">
                                            <div class="wa-bubble wa-bubble-am inline-block max-w-[95%] rounded-lg rounded-tl-none bg-white dark:bg-[#1f2c34] px-3 py-2 shadow-sm">
                                                <p id="preview-am-{{ $template['key'] }}" class="whitespace-pre-wrap break-words text-[13px] leading-[1.45] text-[#111b21] dark:text-[#e9edef]"></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                @endforeach

                {{-- Bottom save --}}
                <div class="flex justify-end pt-2">
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-accent px-5 py-2.5 text-sm font-semibold text-on-accent shadow-sm transition hover:bg-accent-hover active:scale-[0.97]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ __('app.whatsapp_template_save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function whatsappTemplateEditor(initialTemplate, activeMemberCount, initialRecipientMode, initialSelectedMembers, initialWorkspace, bulkTemplateKeys) {
    return {
        activeTemplate: initialTemplate || '',
        activeFieldId: initialTemplate ? `tpl-en-${initialTemplate}` : null,
        activeMemberCount: Number(activeMemberCount || 0),
        bulkRecipientMode: initialRecipientMode || 'all_active',
        bulkSelectedMembers: Array.isArray(initialSelectedMembers) ? initialSelectedMembers : [],
        activeWorkspace: initialWorkspace || 'main',
        bulkTemplateKeys: Array.isArray(bulkTemplateKeys) ? bulkTemplateKeys : [],
        selectTemplate(key) {
            this.activeTemplate = key;
            this.activeWorkspace = this.bulkTemplateKeys.includes(key) ? 'bulk' : 'main';
            this.$nextTick(() => {
                const input = document.getElementById(`tpl-en-${key}`) || document.getElementById(`tpl-am-${key}`);
                if (key === 'whatsapp_bulk_message_final') {
                    this.ensureBulkFinalDefaults();
                }
                if (input) {
                    input.focus();
                    this.activeFieldId = input.id;
                }
            });
        },
        rememberField(id) {
            this.activeFieldId = id;
        },
        insertPlaceholder(token) {
            const fallbackId = this.activeTemplate ? `tpl-en-${this.activeTemplate}` : null;
            const input = document.getElementById(this.activeFieldId || fallbackId || '');
            if (!input) {
                return;
            }

            const start = input.selectionStart ?? input.value.length;
            const end = input.selectionEnd ?? input.value.length;
            const nextValue = `${input.value.slice(0, start)}${token}${input.value.slice(end)}`;
            input.value = nextValue;
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.focus();
            const caret = start + token.length;
            input.setSelectionRange(caret, caret);
            this.activeFieldId = input.id;
        },
        applyRecommendedFinalTemplate(locale) {
            const input = document.getElementById(`tpl-${locale}-whatsapp_daily_reminder_content`);
            if (!input) {
                return;
            }

            input.value = locale === 'am'
                ? ':header_am\n\n:commemorations_block_am\n\n:footer_am'
                : ':header_en\n\n:commemorations_block_en\n\n:footer_en';
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.focus();
            this.activeTemplate = 'whatsapp_daily_reminder_content';
            this.activeFieldId = input.id;
        },
        applyRecommendedBulkFinalTemplate(mode) {
            const enInput = document.getElementById('tpl-en-whatsapp_bulk_message_final');
            const amInput = document.getElementById('tpl-am-whatsapp_bulk_message_final');
            if (!enInput || !amInput) {
                return;
            }

            const enValue = mode === 'explicit'
                ? ':name\n\n:header_en\n\n:content_en\n\n:url_1'
                : ':name\n\n:header\n\n:content\n\n:url_1';
            const amValue = mode === 'explicit'
                ? ':name\n\n:header_am\n\n:content_am\n\n:url_1'
                : ':name\n\n:header\n\n:content\n\n:url_1';

            enInput.value = enValue;
            amInput.value = amValue;
            enInput.dispatchEvent(new Event('input', { bubbles: true }));
            amInput.dispatchEvent(new Event('input', { bubbles: true }));
            enInput.focus();
            this.activeTemplate = 'whatsapp_bulk_message_final';
            this.activeFieldId = enInput.id;
        },
        ensureBulkFinalDefaults() {
            const enInput = document.getElementById('tpl-en-whatsapp_bulk_message_final');
            const amInput = document.getElementById('tpl-am-whatsapp_bulk_message_final');
            if (!enInput || !amInput) {
                return;
            }

            if (String(enInput.value || '').trim() !== '' || String(amInput.value || '').trim() !== '') {
                return;
            }

            this.applyRecommendedBulkFinalTemplate('current');
        },
        switchWorkspace(workspace) {
            this.activeWorkspace = workspace;
            if (workspace === 'main' && this.bulkTemplateKeys.includes(this.activeTemplate)) {
                this.selectTemplate('whatsapp_daily_reminder_header');
            }
        },
        shouldShowGroup(groupKey) {
            return this.activeWorkspace === 'main' && groupKey !== 'bulk';
        },
        workspaceAllowsTemplate(key) {
            return this.activeWorkspace === 'main' && !this.bulkTemplateKeys.includes(key);
        },
        isBulkSelectedMode() {
            return this.bulkRecipientMode === 'selected_active';
        },
        bulkRecipientCount() {
            return this.isBulkSelectedMode()
                ? this.bulkSelectedMembers.length
                : this.activeMemberCount;
        },
    };
}

(() => {
    const DAILY_TEMPLATE_IDS = {
        header: 'whatsapp_daily_reminder_header',
        yearly: 'whatsapp_daily_reminder_yearly_block',
        monthly: 'whatsapp_daily_reminder_monthly_block',
        footer: 'whatsapp_daily_reminder_footer',
        content: 'whatsapp_daily_reminder_content',
    };
    const samples = {
        en: {
            name: 'Abel',
            baptism_name: 'Abel',
            day: '17',
            day_title: 'Day 17',
            date: '2026-03-05',
            gregorian_date: 'March 5',
            ethiopian_date: 'Yekatit 26',
            url: 'https://abiytsom.abuneteklehaymanot.org/share/day/15',
            url_1: 'https://abiytsom.abuneteklehaymanot.org/share/day/15',
            url_2: 'https://abiytsom.abuneteklehaymanot.org/member/calendar',
            url_3: 'https://abiytsom.abuneteklehaymanot.org/member/progress',
            telegram_url: 'https://t.me/AbiyTsomBot',
            saint_commemoration: 'Synaxarium for March 05',
            annual_commemorations: 'St. Abba A, St. Martyr B',
            annual_commemorations_bullets: "- St. Abba A\n- St. Martyr B",
            yearly_commemorations: 'St. Abba A, St. Martyr B',
            yearly_commemorations_bullets: "- St. Abba A\n- St. Martyr B",
            monthly_commemorations: 'St. Monthly A, St. Monthly B, St. Monthly C',
            monthly_commemorations_bullets: "- St. Monthly A\n- St. Monthly B\n- St. Monthly C",
            header: 'Hello Abel. Today is day 17 of the 55 great lent days.',
            commemorations_block: "Today, on March 5 or Yekatit 26, the following yearly feasts are:\n\n- St. Abba A\n- St. Martyr B\n\nAlso, the following monthly feasts are:\n\n- St. Monthly A\n- St. Monthly B\n- St. Monthly C",
            footer: "You can find the day’s Bible reading, Mezmur, Gitsawe, Synaxarium, and other spiritual content at this link:\nhttps://abiytsom.abuneteklehaymanot.org/share/day/15",
            bible_reference: 'Acts 25:13-end',
            bulk_message_value: "Hello :name,\n\nThis is the English bulk message preview.",
        },
        am: {
            name: 'Abel',
            baptism_name: 'Abel',
            day: '17',
            day_title: 'Qen 17',
            date: '2026-03-05',
            gregorian_date: 'ማርች 5',
            ethiopian_date: 'Yekatit 26',
            url: 'https://abiytsom.abuneteklehaymanot.org/share/day/15',
            url_1: 'https://abiytsom.abuneteklehaymanot.org/share/day/15',
            url_2: 'https://abiytsom.abuneteklehaymanot.org/member/calendar',
            url_3: 'https://abiytsom.abuneteklehaymanot.org/member/progress',
            telegram_url: 'https://t.me/AbiyTsomBot',
            saint_commemoration: 'Sinksar for Megabit 05',
            annual_commemorations: 'Kidus A, Kidus B',
            annual_commemorations_bullets: "- Kidus A\n- Kidus B",
            yearly_commemorations: 'Kidus A, Kidus B',
            yearly_commemorations_bullets: "- Kidus A\n- Kidus B",
            monthly_commemorations: 'Werhawi Kidusan A, Werhawi Kidusan B, Werhawi Kidusan C',
            monthly_commemorations_bullets: "- Werhawi Kidusan A\n- Werhawi Kidusan B\n- Werhawi Kidusan C",
            header: 'Selam Abel. Zare 17egna yetsom qen new.',
            commemorations_block: "Zare March 5 weyim Yekatit 26 qen yemikeberu ametawi bealat:\n\n- Kidus A\n- Kidus B\n\nEndihum werhawi bealat:\n\n- Werhawi Kidusan A\n- Werhawi Kidusan B\n- Werhawi Kidusan C",
            footer: "Yeletun Metsihaf Kidus nibab, Mezmur, Gitsawe, Sinksar ena leloch menfesawi yizetoch bezih link yagegnalu:\nhttps://abiytsom.abuneteklehaymanot.org/share/day/15",
            bible_reference: 'Hawaryat Sira 25:13-f.m.',
            bulk_message_value: "ሰላም :name,\n\nይህ የአማርኛ የብዙ ሰዎች መልእክት ቅድመ እይታ ነው።",
        }
    };

    const normalizeRenderedText = (text) => String(text || '')
        .replace(/\r\n/g, '\n')
        .replace(/\r/g, '\n')
        .replace(/\n{3,}/g, '\n\n')
        .trim();

    const replacePlaceholders = (text, map, allowedKeys) => {
        return String(text || '').replace(/:([a-z_]+)/gi, (match, key) => {
            const normalized = String(key || '').toLowerCase();
            return allowedKeys.includes(normalized) && Object.prototype.hasOwnProperty.call(map, normalized)
                ? map[normalized]
                : match;
        });
    };

    const allowedKeysFor = (input) => {
        try {
            return JSON.parse(input.getAttribute('data-allowed-placeholders') || '[]');
        } catch (error) {
            return [];
        }
    };

    const renderInput = (input) => {
        if (!input) {
            return '';
        }

        const locale = input.getAttribute('data-locale') || 'en';
        const map = samples[locale] || samples.en;
        return normalizeRenderedText(replacePlaceholders(input.value, map, allowedKeysFor(input)));
    };

    const buildFinalDailyReminderText = (locale) => {
        const contentInput = document.getElementById(`tpl-${locale}-${DAILY_TEMPLATE_IDS.content}`);
        if (!contentInput) {
            return '';
        }

        const headerEn = renderInput(document.getElementById(`tpl-en-${DAILY_TEMPLATE_IDS.header}`));
        const headerAm = renderInput(document.getElementById(`tpl-am-${DAILY_TEMPLATE_IDS.header}`));
        const commemorationsBlockEn = buildCommemorationsBlock('en');
        const commemorationsBlockAm = buildCommemorationsBlock('am');
        const footerEn = renderInput(document.getElementById(`tpl-en-${DAILY_TEMPLATE_IDS.footer}`));
        const footerAm = renderInput(document.getElementById(`tpl-am-${DAILY_TEMPLATE_IDS.footer}`));

        const baseMap = {
            ...(samples[locale] || samples.en),
            header_en: headerEn,
            commemorations_block_en: commemorationsBlockEn,
            footer_en: footerEn,
            header_am: headerAm,
            commemorations_block_am: commemorationsBlockAm,
            footer_am: footerAm,
            header: locale === 'am' ? headerAm : headerEn,
            commemorations_block: locale === 'am' ? commemorationsBlockAm : commemorationsBlockEn,
            footer: locale === 'am' ? footerAm : footerEn,
        };

        return normalizeRenderedText(
            replacePlaceholders(contentInput.value, baseMap, allowedKeysFor(contentInput))
        );
    };

    const render = (input) => {
        const targetId = input.getAttribute('data-preview-target');
        if (!targetId) {
            return;
        }
        const target = document.getElementById(targetId);
        if (!target) {
            return;
        }
        const locale = input.getAttribute('data-locale') || 'en';
        if (input.id === `tpl-${locale}-${DAILY_TEMPLATE_IDS.content}`) {
            target.textContent = buildFinalDailyReminderText(locale);
            return;
        }

        target.textContent = renderInput(input);
    };

    const buildCommemorationsBlock = (locale) => {
        const yearly = renderInput(document.getElementById(`tpl-${locale}-${DAILY_TEMPLATE_IDS.yearly}`));
        const monthly = renderInput(document.getElementById(`tpl-${locale}-${DAILY_TEMPLATE_IDS.monthly}`));

        return normalizeRenderedText([yearly, monthly].filter(Boolean).join('\n\n'));
    };

    const buildBulkPreviewText = (locale) => {
        const input = document.getElementById(locale === 'am' ? 'bulk-message-am' : 'bulk-message-en');
        const template = String(input?.value || (samples[locale]?.bulk_message_value ?? ''));

        return normalizeRenderedText(
            replacePlaceholders(template, { name: samples[locale]?.name || samples.en.name }, ['name'])
        );
    };

    const renderFinalDailyReminder = (locale) => {
        const target = document.getElementById(`final-preview-${locale}`);
        if (!target) {
            return;
        }
        target.textContent = buildFinalDailyReminderText(locale);
    };

    const renderBulkPreview = (locale) => {
        const target = document.getElementById(`bulk-preview-${locale}`);
        if (!target) {
            return;
        }

        target.textContent = buildBulkPreviewText(locale);
    };

    document.querySelectorAll('textarea[data-preview-target]').forEach((input) => {
        render(input);
        input.addEventListener('input', () => {
            render(input);
            renderFinalDailyReminder('en');
            renderFinalDailyReminder('am');
        });
    });

    ['bulk-message-en', 'bulk-message-am'].forEach((id) => {
        const input = document.getElementById(id);
        if (!input) {
            return;
        }

        input.addEventListener('input', () => {
            renderBulkPreview('en');
            renderBulkPreview('am');
        });
    });

    renderFinalDailyReminder('en');
    renderFinalDailyReminder('am');
    renderBulkPreview('en');
    renderBulkPreview('am');
})();
</script>
@endpush
