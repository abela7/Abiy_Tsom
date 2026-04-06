<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// WhatsApp reminders: run via direct cron (see admin/whatsapp/cron), not schedule:run.
// Direct cron is more reliable on shared hosting than Laravel's scheduler.

Schedule::command('reminders:send-writer')
    ->dailyAt('17:00')
    ->timezone('Europe/London')
    ->withoutOverlapping();

if (config('himamat.reminders.legacy_scheduler_enabled', false)) {
    Schedule::command('himamat:send-whatsapp-reminders --queue')
        ->everyMinute()
        ->timezone('Europe/London')
        ->withoutOverlapping();
}

// Email reminders: 04:00 London = 07:00 Ethiopia (fixed time, no per-member choice).
Schedule::command('reminders:send-email')
    ->dailyAt('04:00')
    ->timezone('Europe/London')
    ->withoutOverlapping();
