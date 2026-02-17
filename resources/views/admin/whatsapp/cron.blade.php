@extends('layouts.admin')
@section('title', __('app.whatsapp_cron_tab'))

@section('content')
@include('admin.whatsapp._nav')

<div class="mb-6">
    <h1 class="text-2xl font-bold text-primary">{{ __('app.whatsapp_cron_tab') }}</h1>
    <p class="text-sm text-muted-text mt-1">{{ __('app.whatsapp_cron_help') }}</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="space-y-6">
        <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
            <h2 class="text-base font-semibold text-primary mb-4">{{ __('app.whatsapp_cron_cpanel_title') }}</h2>
            <ol class="text-sm text-secondary space-y-3 list-decimal list-inside">
                <li>{{ __('app.whatsapp_cron_step_1') }}</li>
                <li>{{ __('app.whatsapp_cron_step_2') }}</li>
                <li>{{ __('app.whatsapp_cron_step_3') }}</li>
                <li>{{ __('app.whatsapp_cron_step_4') }}</li>
            </ol>
        </div>

        <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
            <h2 class="text-base font-semibold text-primary mb-3">{{ __('app.whatsapp_cron_command_label') }}</h2>
            <p class="text-xs text-muted-text mb-2">{{ __('app.whatsapp_cron_command_help') }}</p>
            <pre class="p-4 bg-muted rounded-lg text-sm font-mono overflow-x-auto break-all">{{ $phpPath }} {{ $artisanPath }} schedule:run >> /dev/null 2>&1</pre>
            <p class="text-xs text-muted-text mt-2">{{ __('app.whatsapp_cron_php_path_note') }}</p>
        </div>
    </div>

    <div class="space-y-6">
        <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
            <h2 class="text-base font-semibold text-primary mb-3">{{ __('app.whatsapp_cron_frequency') }}</h2>
            <p class="text-sm text-secondary">{{ __('app.whatsapp_cron_frequency_help') }}</p>
        </div>

        <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
            <h2 class="text-base font-semibold text-primary mb-3">{{ __('app.whatsapp_cron_test_title') }}</h2>
            <p class="text-sm text-secondary mb-3">{{ __('app.whatsapp_cron_test_help') }}</p>
            <pre class="p-4 bg-muted rounded-lg text-sm font-mono overflow-x-auto">php artisan reminders:send-whatsapp --dry-run</pre>
            <p class="text-xs text-muted-text mt-2">{{ __('app.whatsapp_cron_dry_run_note') }}</p>
        </div>

        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-4">
            <p class="text-sm text-yellow-800 dark:text-yellow-300">
                <strong>{{ __('app.note') }}:</strong> {{ __('app.whatsapp_cron_path_note') }}
            </p>
        </div>
    </div>
</div>
@endsection
