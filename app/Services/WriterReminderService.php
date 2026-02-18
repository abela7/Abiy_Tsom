<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DailyContent;
use App\Models\LentSeason;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Lang;

/**
 * Send WhatsApp reminders to writers assigned to prepare content.
 */
final class WriterReminderService
{
    public function __construct(
        private UltraMsgService $ultraMsgService
    ) {}

    /**
     * Send reminder for a specific day to its assigned writer.
     * Returns: ['sent' => bool, 'message' => string]
     */
    public function sendReminderForDay(DailyContent $daily, bool $dryRun = false): array
    {
        if (! $this->ultraMsgService->isConfigured()) {
            return [
                'sent' => false,
                'message' => __('app.writer_reminder_ultramsg_not_configured'),
            ];
        }

        if (! $daily->assigned_to_id) {
            return [
                'sent' => false,
                'message' => __('app.writer_reminder_no_assignment'),
            ];
        }

        $assignee = $daily->assignedTo;
        if (empty($assignee->whatsapp_phone)) {
            return [
                'sent' => false,
                'message' => __('app.writer_reminder_no_whatsapp', ['name' => $assignee->name]),
            ];
        }

        $dateStr = $daily->date->format('d/m/Y');

        $message = __('app.writer_reminder_for_day_message', [
            'name' => $assignee->name,
            'day' => $daily->day_number,
            'date' => $dateStr,
        ]);

        if ($dryRun) {
            return [
                'sent' => false,
                'message' => __('app.writer_reminder_dry_run', ['name' => $assignee->name]),
            ];
        }

        $sent = $this->ultraMsgService->sendTextMessage($assignee->whatsapp_phone, $message);

        return [
            'sent' => $sent,
            'message' => $sent
                ? __('app.writer_reminder_sent')
                : __('app.writer_reminder_send_failed'),
        ];
    }

    /**
     * Attempt to send reminder for tomorrow. Returns result array:
     * - sent: bool
     * - message: string (user-facing)
     * - assignee_name: ?string
     */
    public function sendReminderForTomorrow(bool $dryRun = false): array
    {
        if (! $this->ultraMsgService->isConfigured()) {
            return [
                'sent' => false,
                'message' => __('app.writer_reminder_ultramsg_not_configured'),
                'assignee_name' => null,
            ];
        }

        $tomorrow = CarbonImmutable::now('Europe/London')->addDay()->toDateString();

        $season = LentSeason::active();
        if (! $season) {
            return [
                'sent' => false,
                'message' => __('app.writer_reminder_no_season'),
                'assignee_name' => null,
            ];
        }

        $dailyContent = DailyContent::query()
            ->where('lent_season_id', $season->id)
            ->whereDate('date', $tomorrow)
            ->with('assignedTo')
            ->first();

        if (! $dailyContent || ! $dailyContent->assigned_to_id) {
            return [
                'sent' => false,
                'message' => __('app.writer_reminder_no_assignment'),
                'assignee_name' => null,
            ];
        }

        $assignee = $dailyContent->assignedTo;

        if (empty($assignee->whatsapp_phone)) {
            return [
                'sent' => false,
                'message' => __('app.writer_reminder_no_whatsapp', ['name' => $assignee->name]),
                'assignee_name' => $assignee->name,
            ];
        }

        $message = Lang::get('app.writer_reminder_message', [
            'name' => $assignee->name,
            'day' => $dailyContent->day_number,
            'date' => $dailyContent->date->format('l, M j'),
        ]);

        if ($dryRun) {
            return [
                'sent' => false,
                'message' => __('app.writer_reminder_dry_run', ['name' => $assignee->name]),
                'assignee_name' => $assignee->name,
            ];
        }

        $sent = $this->ultraMsgService->sendTextMessage($assignee->whatsapp_phone, $message);

        if (! $sent) {
            return [
                'sent' => false,
                'message' => __('app.writer_reminder_send_failed'),
                'assignee_name' => $assignee->name,
            ];
        }

        return [
            'sent' => true,
            'message' => __('app.writer_reminder_sent'),
            'assignee_name' => $assignee->name,
        ];
    }
}
