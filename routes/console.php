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
