@extends('layouts.admin')
@section('title', __('app.whatsapp_template_title'))

@section('content')
@include('admin.whatsapp._nav')

@php
    $firstTemplateKey = $templates[0]['key'] ?? '';
    $templateMeta = [
        'whatsapp_daily_reminder_header' => [
            'group' => 'daily',
            'description' => __('app.whatsapp_template_desc_daily_header'),
        ],
        'whatsapp_daily_reminder_content' => [
            'group' => 'daily',
            'description' => __('app.whatsapp_template_desc_daily_content'),
        ],
        'whatsapp_daily_reminder_yearly_block' => [
            'group' => 'daily',
            'description' => __('app.whatsapp_template_desc_daily_yearly_block'),
        ],
        'whatsapp_daily_reminder_monthly_block' => [
            'group' => 'daily',
            'description' => __('app.whatsapp_template_desc_daily_monthly_block'),
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

    $templateGroups = [
        'daily' => array_values(array_filter($templates, static fn (array $template): bool => ($templateMeta[$template['key']]['group'] ?? 'confirmation') === 'daily')),
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

<div x-data="whatsappTemplateEditor(@js($firstTemplateKey))" class="space-y-6">

    {{-- Page header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-primary">{{ __('app.whatsapp_template_title') }}</h1>
            <p class="mt-1 max-w-2xl text-sm text-muted-text">{{ __('app.whatsapp_template_help') }}</p>
        </div>
        <button type="submit" form="whatsapp-template-form"
            class="inline-flex items-center gap-2 rounded-xl bg-accent px-5 py-2.5 text-sm font-semibold text-on-accent shadow-sm transition hover:bg-accent-hover active:scale-[0.97] shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ __('app.whatsapp_template_save') }}
        </button>
    </div>

    {{-- Top cards: Workflow + Test --}}
    <div class="grid gap-4 lg:grid-cols-[1fr_380px]">

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

    {{-- Main editor area --}}
    <div class="grid gap-5 xl:grid-cols-[260px_minmax(0,1fr)]">

        {{-- Sidebar: template list --}}
        <aside class="space-y-3 xl:sticky xl:top-24 xl:self-start">
            @foreach(['daily' => __('app.whatsapp_template_group_daily'), 'confirmation' => __('app.whatsapp_template_group_confirmation')] as $groupKey => $groupLabel)
            <div class="rounded-2xl border border-border bg-card shadow-sm overflow-hidden">
                <div class="px-4 py-2.5 bg-surface/60 border-b border-border">
                    <h2 class="text-[11px] font-bold uppercase tracking-widest text-muted-text">{{ $groupLabel }}</h2>
                </div>
                <div class="p-2 space-y-1">
                    @foreach($templateGroups[$groupKey] as $template)
                        @php($meta = $templateMeta[$template['key']] ?? ['description' => ''])
                        <button type="button"
                            @click="selectTemplate('{{ $template['key'] }}')"
                            :class="activeTemplate === '{{ $template['key'] }}'
                                ? 'bg-accent/10 text-accent border-accent/20 shadow-sm'
                                : 'bg-transparent text-secondary border-transparent hover:bg-muted/50'"
                            class="w-full rounded-xl border px-3 py-2.5 text-left transition-all duration-150">
                            <div class="text-[13px] font-semibold leading-snug">{{ $template['title'] }}</div>
                            <div class="mt-0.5 text-[11px] leading-relaxed text-muted-text line-clamp-2">{{ $meta['description'] }}</div>
                        </button>
                    @endforeach
                </div>
            </div>
            @endforeach
        </aside>

        {{-- Editor panels --}}
        <div class="space-y-4">
            {{-- Warning --}}
            <div class="flex items-start gap-3 rounded-xl border border-amber-300/50 bg-amber-50/60 px-4 py-3 text-sm text-amber-800 dark:bg-amber-900/15 dark:text-amber-200 dark:border-amber-500/20">
                <svg class="w-5 h-5 shrink-0 mt-0.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                <span>{{ __('app.whatsapp_template_warning') }}</span>
            </div>

            <form id="whatsapp-template-form" method="POST" action="{{ route('admin.whatsapp.template.update') }}" class="space-y-4">
                @csrf
                @method('PUT')

                @foreach($templates as $template)
                <section x-cloak x-show="activeTemplate === '{{ $template['key'] }}'"
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
                                        {{ (($templateMeta[$template['key']]['group'] ?? 'confirmation') === 'daily') ? __('app.whatsapp_template_group_daily') : __('app.whatsapp_template_group_confirmation') }}
                                    </span>
                                    <code class="text-[11px] text-muted-text bg-muted/50 px-2 py-0.5 rounded-md">{{ $template['key'] }}</code>
                                </div>
                                <h2 class="text-lg font-bold text-primary">{{ $template['title'] }}</h2>
                                <p class="mt-1 text-sm text-muted-text">{{ $templateMeta[$template['key']]['description'] ?? '' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-5 space-y-4">

                        {{-- Placeholder toolbar --}}
                        <div class="rounded-xl bg-surface/60 border border-border px-4 py-3">
                            <div class="flex items-center justify-between gap-3 mb-2">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                    <h3 class="text-xs font-semibold text-primary uppercase tracking-wide">{{ __('app.whatsapp_template_placeholders') }}</h3>
                                </div>
                                <span class="text-[10px] text-muted-text hidden sm:inline">{{ __('app.whatsapp_template_insert_help') }}</span>
                            </div>
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
                        </div>

                        {{-- EN + AM editors side by side --}}
                        <div class="grid gap-4 md:grid-cols-2">
                            {{-- English editor --}}
                            <div>
                                <label for="tpl-en-{{ $template['key'] }}" class="flex items-center gap-2 mb-2 text-sm font-semibold text-primary">
                                    <span class="flex h-5 w-5 items-center justify-center rounded bg-blue-500/10 text-[10px] font-bold text-blue-600 dark:text-blue-400">EN</span>
                                    {{ __('app.whatsapp_template_en_label') }}
                                </label>
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
                                <label for="tpl-am-{{ $template['key'] }}" class="flex items-center gap-2 mb-2 text-sm font-semibold text-primary">
                                    <span class="flex h-5 w-5 items-center justify-center rounded bg-emerald-500/10 text-[10px] font-bold text-emerald-600 dark:text-emerald-400">AM</span>
                                    {{ __('app.whatsapp_template_am_label') }}
                                </label>
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
                                            <p class="text-white text-xs font-medium">Abiy Tsom Bot</p>
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
                                            <p class="text-white text-xs font-medium">Abiy Tsom Bot</p>
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
function whatsappTemplateEditor(initialTemplate) {
    return {
        activeTemplate: initialTemplate || '',
        activeFieldId: initialTemplate ? `tpl-en-${initialTemplate}` : null,
        selectTemplate(key) {
            this.activeTemplate = key;
            this.$nextTick(() => {
                const input = document.getElementById(`tpl-en-${key}`) || document.getElementById(`tpl-am-${key}`);
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
    };
}

(() => {
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
            telegram_url: 'https://t.me/AbiyTsomBot',
            saint_commemoration: 'Synaxarium for March 05',
            annual_commemorations: 'St. Abba A, St. Martyr B',
            annual_commemorations_bullets: "- St. Abba A\n- St. Martyr B",
            yearly_commemorations: 'St. Abba A, St. Martyr B',
            yearly_commemorations_bullets: "- St. Abba A\n- St. Martyr B",
            monthly_commemorations: 'St. Monthly A, St. Monthly B, St. Monthly C',
            monthly_commemorations_bullets: "- St. Monthly A\n- St. Monthly B\n- St. Monthly C",
            commemorations_block: "Today, on March 5 or Yekatit 26, the following yearly feasts are:\n\n- St. Abba A\n- St. Martyr B\n\nAlso, the following monthly feasts are:\n\n- St. Monthly A\n- St. Monthly B\n- St. Monthly C",
            bible_reference: 'Acts 25:13-end',
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
            telegram_url: 'https://t.me/AbiyTsomBot',
            saint_commemoration: 'Sinksar for Megabit 05',
            annual_commemorations: 'Kidus A, Kidus B',
            annual_commemorations_bullets: "- Kidus A\n- Kidus B",
            yearly_commemorations: 'Kidus A, Kidus B',
            yearly_commemorations_bullets: "- Kidus A\n- Kidus B",
            monthly_commemorations: 'Werhawi Kidusan A, Werhawi Kidusan B, Werhawi Kidusan C',
            monthly_commemorations_bullets: "- Werhawi Kidusan A\n- Werhawi Kidusan B\n- Werhawi Kidusan C",
            commemorations_block: "Zare March 5 weyim Yekatit 26 qen yemikeberu ametawi bealat:\n\n- Kidus A\n- Kidus B\n\nEndihum werhawi bealat:\n\n- Werhawi Kidusan A\n- Werhawi Kidusan B\n- Werhawi Kidusan C",
            bible_reference: 'Hawaryat Sira 25:13-f.m.',
        }
    };

    const replacePlaceholders = (text, locale, allowedKeys) => {
        const map = samples[locale] || samples.en;
        return String(text || '').replace(/:([a-z_]+)/gi, (match, key) => {
            const normalized = String(key || '').toLowerCase();
            return allowedKeys.includes(normalized) && Object.prototype.hasOwnProperty.call(map, normalized)
                ? map[normalized]
                : match;
        });
    };

    const render = (input) => {
        const targetId = input.getAttribute('data-preview-target');
        const locale = input.getAttribute('data-locale') || 'en';
        if (!targetId) {
            return;
        }
        const target = document.getElementById(targetId);
        if (!target) {
            return;
        }
        let allowedKeys = [];
        try {
            allowedKeys = JSON.parse(input.getAttribute('data-allowed-placeholders') || '[]');
        } catch (error) {
            allowedKeys = [];
        }
        target.textContent = replacePlaceholders(input.value, locale, allowedKeys);
    };

    document.querySelectorAll('textarea[data-preview-target]').forEach((input) => {
        render(input);
        input.addEventListener('input', () => render(input));
    });
})();
</script>
@endpush
