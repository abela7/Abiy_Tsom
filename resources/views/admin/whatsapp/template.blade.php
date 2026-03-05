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
                <div class="mb-3">
                    <h2 class="text-base font-semibold text-primary">{{ $template['title'] }}</h2>
                    <p class="text-xs text-muted-text mt-1">
                        <span class="font-medium">{{ __('app.whatsapp_template_placeholders') }}:</span>
                        <code>{{ $template['placeholders'] }}</code>
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-secondary mb-1.5">
                            {{ __('app.whatsapp_template_en_label') }}
                        </label>
                        <textarea
                            name="templates[{{ $template['key'] }}][en]"
                            rows="4"
                            class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none resize-y"
                        >{{ old("templates.{$template['key']}.en", $template['en']) }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-secondary mb-1.5">
                            {{ __('app.whatsapp_template_am_label') }}
                        </label>
                        <textarea
                            name="templates[{{ $template['key'] }}][am]"
                            rows="4"
                            class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none resize-y"
                        >{{ old("templates.{$template['key']}.am", $template['am']) }}</textarea>
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

