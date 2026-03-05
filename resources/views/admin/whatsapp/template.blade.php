@extends('layouts.admin')
@section('title', __('app.whatsapp_template_title'))

@section('content')
@include('admin.whatsapp._nav')

<div class="mb-6">
    <h1 class="text-2xl font-bold text-primary">{{ __('app.whatsapp_template_title') }}</h1>
    <p class="text-sm text-muted-text mt-1">{{ __('app.whatsapp_template_help') }}</p>
</div>

<div class="bg-card rounded-xl p-6 shadow-sm border border-border">
    <div class="mb-5 rounded-lg border border-amber-300/60 bg-amber-50/70 dark:bg-amber-900/20 px-4 py-3 text-sm text-amber-900 dark:text-amber-200">
        {{ __('app.whatsapp_template_warning') }}
    </div>

    <form method="POST" action="{{ route('admin.whatsapp.template.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        @foreach($templates as $template)
            <section class="rounded-xl border border-border p-4 bg-surface">
                @php
                    $placeholderList = array_map(
                        static fn (string $key): string => ':'.$key,
                        $template['placeholder_keys']
                    );
                @endphp
                <div class="mb-3">
                    <h2 class="text-base font-semibold text-primary">{{ $template['title'] }}</h2>
                    <p class="text-xs text-muted-text mt-1">
                        <span class="font-medium">{{ __('app.whatsapp_template_placeholders') }}:</span>
                        <code>{{ $placeholderList !== [] ? implode(', ', $placeholderList) : __('app.whatsapp_template_none') }}</code>
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-secondary mb-1.5">
                            {{ __('app.whatsapp_template_en_label') }}
                        </label>
                        <textarea
                            id="tpl-en-{{ $template['key'] }}"
                            name="templates[{{ $template['key'] }}][en]"
                            rows="4"
                            data-preview-target="preview-en-{{ $template['key'] }}"
                            data-locale="en"
                            data-allowed-placeholders='@json($template['placeholder_keys'])'
                            class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none resize-y"
                        >{{ old("templates.{$template['key']}.en", $template['en']) }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-secondary mb-1.5">
                            {{ __('app.whatsapp_template_am_label') }}
                        </label>
                        <textarea
                            id="tpl-am-{{ $template['key'] }}"
                            name="templates[{{ $template['key'] }}][am]"
                            rows="4"
                            data-preview-target="preview-am-{{ $template['key'] }}"
                            data-locale="am"
                            data-allowed-placeholders='@json($template['placeholder_keys'])'
                            class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none resize-y"
                        >{{ old("templates.{$template['key']}.am", $template['am']) }}</textarea>
                    </div>
                </div>

                <p class="text-xs text-muted-text mt-3">{{ __('app.whatsapp_template_preview_help') }}</p>
                <div class="mt-2 grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="rounded-lg border border-border bg-muted/40 p-3">
                        <p class="text-xs font-semibold text-secondary mb-2">
                            {{ __('app.whatsapp_template_preview_title') }} ({{ __('app.whatsapp_template_en_label') }})
                        </p>
                        <p id="preview-en-{{ $template['key'] }}" class="text-sm text-primary whitespace-pre-wrap break-words"></p>
                    </div>
                    <div class="rounded-lg border border-border bg-muted/40 p-3">
                        <p class="text-xs font-semibold text-secondary mb-2">
                            {{ __('app.whatsapp_template_preview_title') }} ({{ __('app.whatsapp_template_am_label') }})
                        </p>
                        <p id="preview-am-{{ $template['key'] }}" class="text-sm text-primary whitespace-pre-wrap break-words"></p>
                    </div>
                </div>
            </section>
        @endforeach

        <div class="pt-2">
            <button
                type="submit"
                class="inline-flex items-center px-5 py-2.5 rounded-lg bg-accent text-on-accent text-sm font-semibold hover:bg-accent-hover transition"
            >
                {{ __('app.whatsapp_template_save') }}
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const samples = {
        en: {
            name: 'Abel',
            baptism_name: 'Abel',
            day: '17',
            day_title: 'Day 17',
            date: '2026-03-05',
            url: 'https://abiytsom.abuneteklehaymanot.org/share/day/15',
            telegram_url: 'https://t.me/AbiyTsomBot',
            saint_commemoration: 'Synaxarium for March 05',
            bible_reference: 'Acts 25:13-end',
        },
        am: {
            name: 'Abel',
            baptism_name: 'Abel',
            day: '17',
            day_title: 'ቀን 17',
            date: '2026-03-05',
            url: 'https://abiytsom.abuneteklehaymanot.org/share/day/15',
            telegram_url: 'https://t.me/AbiyTsomBot',
            saint_commemoration: 'ስንክሳር የመጋቢት 05',
            bible_reference: 'የሐዋርያት ሥራ 25:13-ፍ.ም',
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
