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
</style>

<div x-data="whatsappTemplateEditor(@js($firstTemplateKey))" class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-primary">{{ __('app.whatsapp_template_title') }}</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-muted-text">{{ __('app.whatsapp_template_help') }}</p>
        </div>
        <button
            type="submit"
            form="whatsapp-template-form"
            class="inline-flex items-center justify-center rounded-xl bg-accent px-5 py-3 text-sm font-semibold text-on-accent shadow-sm transition hover:bg-accent-hover"
        >
            {{ __('app.whatsapp_template_save') }}
        </button>
    </div>

    <div class="grid gap-4 xl:grid-cols-[minmax(0,1.1fr)_minmax(320px,0.9fr)]">
        <section class="rounded-2xl border border-border bg-card p-5 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-accent/10 text-accent text-lg font-bold">1</div>
                <div>
                    <h2 class="text-base font-semibold text-primary">{{ __('app.whatsapp_template_workflow_title') }}</h2>
                    <p class="text-sm text-muted-text">{{ __('app.whatsapp_template_warning') }}</p>
                </div>
            </div>
            <div class="mt-5 grid gap-3 md:grid-cols-3">
                <div class="rounded-2xl border border-border bg-surface px-4 py-4">
                    <p class="text-sm font-semibold text-primary">{{ __('app.whatsapp_template_workflow_step_1') }}</p>
                </div>
                <div class="rounded-2xl border border-border bg-surface px-4 py-4">
                    <p class="text-sm font-semibold text-primary">{{ __('app.whatsapp_template_workflow_step_2') }}</p>
                </div>
                <div class="rounded-2xl border border-border bg-surface px-4 py-4">
                    <p class="text-sm font-semibold text-primary">{{ __('app.whatsapp_template_workflow_step_3') }}</p>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-border bg-card p-5 shadow-sm">
            <div class="mb-4">
                <h2 class="text-base font-semibold text-primary">{{ __('app.whatsapp_template_test_title') }}</h2>
                <p class="mt-1 text-sm text-muted-text">{{ __('app.whatsapp_template_test_help') }}</p>
            </div>

            <form method="POST" action="{{ route('admin.whatsapp.template.test') }}" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                @csrf
                <div>
                    <label for="template-test-member" class="mb-1.5 block text-sm font-medium text-secondary">
                        {{ __('app.whatsapp_template_test_member_label') }}
                    </label>
                    <select
                        id="template-test-member"
                        name="member_id"
                        class="w-full rounded-xl border border-border bg-surface px-3 py-3 text-sm text-primary outline-none transition focus:ring-2 focus:ring-accent"
                        required
                    >
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
                    <label for="template-test-language" class="mb-1.5 block text-sm font-medium text-secondary">
                        {{ __('app.whatsapp_template_test_language_label') }}
                    </label>
                    <select
                        id="template-test-language"
                        name="test_locale"
                        class="w-full rounded-xl border border-border bg-surface px-3 py-3 text-sm text-primary outline-none transition focus:ring-2 focus:ring-accent"
                    >
                        <option value="member" @selected((string) old('template_test_locale', 'member') === 'member')>{{ __('app.whatsapp_template_test_language_member') }}</option>
                        <option value="en" @selected((string) old('template_test_locale') === 'en')>{{ __('app.whatsapp_template_test_language_en') }}</option>
                        <option value="am" @selected((string) old('template_test_locale') === 'am')>{{ __('app.whatsapp_template_test_language_am') }}</option>
                    </select>
                </div>

                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-xl bg-primary px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                    @disabled($testMembers->isEmpty())
                >
                    {{ __('app.whatsapp_template_send_test') }}
                </button>
            </form>
        </section>
    </div>

    <div class="grid gap-6 xl:grid-cols-[280px_minmax(0,1fr)]">
        <aside class="space-y-4 xl:sticky xl:top-24 xl:self-start">
            <section class="rounded-2xl border border-border bg-card p-4 shadow-sm">
                <div class="mb-4">
                    <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-muted-text">{{ __('app.whatsapp_template_group_daily') }}</h2>
                </div>
                <div class="space-y-2">
                    @foreach($templateGroups['daily'] as $template)
                        @php($meta = $templateMeta[$template['key']] ?? ['description' => ''])
                        <button
                            type="button"
                            @click="selectTemplate('{{ $template['key'] }}')"
                            :class="activeTemplate === '{{ $template['key'] }}'
                                ? 'border-primary/30 bg-primary/10 text-primary shadow-sm'
                                : 'border-transparent bg-surface text-secondary hover:border-border hover:bg-muted/40'"
                            class="w-full rounded-2xl border px-4 py-3 text-left transition"
                        >
                            <div class="text-sm font-semibold">{{ $template['title'] }}</div>
                            <div class="mt-1 text-xs leading-5 text-muted-text">{{ $meta['description'] }}</div>
                        </button>
                    @endforeach
                </div>
            </section>

            <section class="rounded-2xl border border-border bg-card p-4 shadow-sm">
                <div class="mb-4">
                    <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-muted-text">{{ __('app.whatsapp_template_group_confirmation') }}</h2>
                </div>
                <div class="space-y-2">
                    @foreach($templateGroups['confirmation'] as $template)
                        @php($meta = $templateMeta[$template['key']] ?? ['description' => ''])
                        <button
                            type="button"
                            @click="selectTemplate('{{ $template['key'] }}')"
                            :class="activeTemplate === '{{ $template['key'] }}'
                                ? 'border-primary/30 bg-primary/10 text-primary shadow-sm'
                                : 'border-transparent bg-surface text-secondary hover:border-border hover:bg-muted/40'"
                            class="w-full rounded-2xl border px-4 py-3 text-left transition"
                        >
                            <div class="text-sm font-semibold">{{ $template['title'] }}</div>
                            <div class="mt-1 text-xs leading-5 text-muted-text">{{ $meta['description'] }}</div>
                        </button>
                    @endforeach
                </div>
            </section>
        </aside>

        <div class="space-y-4">
            <div class="rounded-2xl border border-amber-300/60 bg-amber-50/70 px-4 py-3 text-sm text-amber-900 shadow-sm dark:bg-amber-900/20 dark:text-amber-200">
                {{ __('app.whatsapp_template_warning') }}
            </div>

            <form id="whatsapp-template-form" method="POST" action="{{ route('admin.whatsapp.template.update') }}" class="space-y-4">
                @csrf
                @method('PUT')

                @foreach($templates as $template)
                    @php
                        $meta = $templateMeta[$template['key']] ?? ['group' => 'confirmation', 'description' => ''];
                        $placeholderList = array_map(
                            static fn (string $key): string => ':'.$key,
                            $template['placeholder_keys']
                        );
                    @endphp

                    <section
                        x-cloak
                        x-show="activeTemplate === '{{ $template['key'] }}'"
                        class="overflow-hidden rounded-3xl border border-border bg-card shadow-sm"
                    >
                        <div class="border-b border-border bg-surface/80 px-5 py-5 sm:px-6">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <span class="inline-flex items-center rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-primary">
                                        {{ $meta['group'] === 'daily' ? __('app.whatsapp_template_group_daily') : __('app.whatsapp_template_group_confirmation') }}
                                    </span>
                                    <h2 class="mt-3 text-2xl font-semibold tracking-tight text-primary">{{ $template['title'] }}</h2>
                                    <p class="mt-2 max-w-3xl text-sm leading-6 text-muted-text">{{ $meta['description'] }}</p>
                                </div>
                                <div class="rounded-2xl border border-border bg-card px-4 py-3 text-xs text-muted-text">
                                    <div class="font-semibold uppercase tracking-[0.18em]">{{ __('app.whatsapp_template_key_label') }}</div>
                                    <code class="mt-1 block text-primary">{{ $template['key'] }}</code>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-5 p-5 sm:p-6">
                            <section class="rounded-2xl border border-border bg-surface px-4 py-4">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                    <div>
                                        <h3 class="text-sm font-semibold text-primary">{{ __('app.whatsapp_template_placeholders') }}</h3>
                                        <p class="mt-1 text-sm text-muted-text">{{ __('app.whatsapp_template_insert_help') }}</p>
                                    </div>
                                </div>
                                <div class="mt-4 flex flex-wrap gap-2">
                                    @forelse($placeholderList as $placeholder)
                                        <button
                                            type="button"
                                            @click.prevent="insertPlaceholder('{{ $placeholder }}')"
                                            class="rounded-full border border-border bg-card px-3 py-1.5 text-xs font-semibold text-secondary transition hover:border-primary/30 hover:bg-primary/10 hover:text-primary"
                                        >
                                            {{ $placeholder }}
                                        </button>
                                    @empty
                                        <span class="text-sm text-muted-text">{{ __('app.whatsapp_template_none') }}</span>
                                    @endforelse
                                </div>
                            </section>

                            <div class="grid gap-4 2xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_320px]">
                                <section class="rounded-2xl border border-border bg-surface p-4">
                                    <div class="mb-3 flex items-center justify-between gap-3">
                                        <label for="tpl-en-{{ $template['key'] }}" class="text-sm font-semibold text-primary">
                                            {{ __('app.whatsapp_template_en_label') }}
                                        </label>
                                    </div>
                                    <textarea
                                        id="tpl-en-{{ $template['key'] }}"
                                        name="templates[{{ $template['key'] }}][en]"
                                        rows="14"
                                        data-preview-target="preview-en-{{ $template['key'] }}"
                                        data-locale="en"
                                        data-allowed-placeholders='@json($template['placeholder_keys'])'
                                        @focus="rememberField('tpl-en-{{ $template['key'] }}')"
                                        class="w-full rounded-2xl border border-border bg-card px-4 py-3 text-sm leading-6 text-primary outline-none transition focus:ring-2 focus:ring-accent"
                                    >{{ old("templates.{$template['key']}.en", $template['en']) }}</textarea>
                                </section>

                                <section class="rounded-2xl border border-border bg-surface p-4">
                                    <div class="mb-3 flex items-center justify-between gap-3">
                                        <label for="tpl-am-{{ $template['key'] }}" class="text-sm font-semibold text-primary">
                                            {{ __('app.whatsapp_template_am_label') }}
                                        </label>
                                    </div>
                                    <textarea
                                        id="tpl-am-{{ $template['key'] }}"
                                        name="templates[{{ $template['key'] }}][am]"
                                        rows="14"
                                        data-preview-target="preview-am-{{ $template['key'] }}"
                                        data-locale="am"
                                        data-allowed-placeholders='@json($template['placeholder_keys'])'
                                        @focus="rememberField('tpl-am-{{ $template['key'] }}')"
                                        class="w-full rounded-2xl border border-border bg-card px-4 py-3 text-sm leading-6 text-primary outline-none transition focus:ring-2 focus:ring-accent"
                                    >{{ old("templates.{$template['key']}.am", $template['am']) }}</textarea>
                                </section>

                                <section class="rounded-2xl border border-border bg-surface p-4 2xl:sticky 2xl:top-28 2xl:self-start">
                                    <div class="mb-4">
                                        <h3 class="text-sm font-semibold text-primary">{{ __('app.whatsapp_template_preview_title') }}</h3>
                                        <p class="mt-1 text-sm text-muted-text">{{ __('app.whatsapp_template_preview_help') }}</p>
                                    </div>
                                    <div class="space-y-3">
                                        <div class="rounded-2xl border border-border bg-card p-3">
                                            <p class="mb-2 text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.whatsapp_template_en_label') }}</p>
                                            <p id="preview-en-{{ $template['key'] }}" class="whitespace-pre-wrap break-words text-sm leading-6 text-primary"></p>
                                        </div>
                                        <div class="rounded-2xl border border-border bg-card p-3">
                                            <p class="mb-2 text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.whatsapp_template_am_label') }}</p>
                                            <p id="preview-am-{{ $template['key'] }}" class="whitespace-pre-wrap break-words text-sm leading-6 text-primary"></p>
                                        </div>
                                    </div>
                                </section>
                            </div>
                        </div>
                    </section>
                @endforeach

                <div class="flex justify-end">
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-accent px-5 py-3 text-sm font-semibold text-on-accent shadow-sm transition hover:bg-accent-hover"
                    >
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
