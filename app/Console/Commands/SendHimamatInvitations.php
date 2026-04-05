<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SendHimamatInvitationJob;
use App\Models\LentSeason;
use App\Models\Member;
use App\Services\HimamatInvitationTemplateService;
use App\Services\UltraMsgService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendHimamatInvitations extends Command
{
    protected $signature = 'himamat:send-invitations
        {--campaign= : Override the campaign key used for duplicate protection}
        {--dry-run : Preview recipients without sending}
        {--sample-member-id= : Send a single sample using the selected member token}
        {--sample-phone= : Override the destination phone for sample mode}
        {--send-now : Send inline instead of dispatching to the queue}';

    protected $description = 'Send the initial Himamat re-entry invitation campaign';

    public function handle(
        UltraMsgService $ultraMsgService,
        HimamatInvitationTemplateService $templateService
    ): int {
        $season = LentSeason::active();
        if (! $season) {
            $this->error('No active Lent season. Nothing to send.');

            return self::FAILURE;
        }

        $campaignKey = trim((string) ($this->option('campaign') ?: 'himamat-invitation-'.$season->year));
        $lock = Cache::lock('himamat:send-invitations:'.$campaignKey, 3600);

        if (! $lock->get()) {
            $this->warn('This Himamat invitation campaign is already running.');

            return self::SUCCESS;
        }

        try {
            if (! $ultraMsgService->isConfigured()) {
                $this->error('UltraMsg is not configured. Set ULTRAMSG_INSTANCE_ID and ULTRAMSG_TOKEN.');

                return self::FAILURE;
            }

            $dryRun = (bool) $this->option('dry-run');
            $sendNow = (bool) $this->option('send-now');
            $sampleMemberId = (int) ($this->option('sample-member-id') ?: 0);
            $samplePhone = trim((string) ($this->option('sample-phone') ?: ''));

            if (! $sendNow && config('queue.default') === 'sync') {
                $this->warn('Queue mode is enabled, but QUEUE_CONNECTION is sync. Invitations will run inline until a queue worker is configured.');
            }

            if ($samplePhone !== '' && $sampleMemberId <= 0) {
                $this->error('Sample phone override requires --sample-member-id so the launcher can build a real personalized Himamat link.');

                return self::FAILURE;
            }

            if ($sampleMemberId > 0) {
                return $this->sendSample($sampleMemberId, $samplePhone, $campaignKey, $dryRun, $sendNow, $templateService, $ultraMsgService);
            }

            $recipients = Member::query()
                ->where('whatsapp_confirmation_status', 'confirmed')
                ->whereNotNull('whatsapp_phone')
                ->where('whatsapp_phone', '!=', '')
                ->whereNotNull('token')
                ->where('token', '!=', '')
                ->whereDoesntHave('himamatInvitationDeliveries', function ($query) use ($campaignKey): void {
                    $query->where('campaign_key', $campaignKey)
                        ->where('channel', 'whatsapp')
                        ->where('status', 'sent');
                })
                ->orderBy('id');

            $recipientCount = (clone $recipients)->count();
            if ($recipientCount === 0) {
                $this->line(sprintf(
                    'No eligible members are waiting for Himamat invitation campaign [%s].',
                    $campaignKey
                ));

                return self::SUCCESS;
            }

            $this->line(sprintf(
                '%s %d Himamat invitation(s) for campaign [%s].',
                $dryRun ? 'Previewing' : ($sendNow ? 'Sending' : 'Dispatching'),
                $recipientCount,
                $campaignKey
            ));

            $processed = 0;

            $recipients->chunkById(200, function ($members) use ($campaignKey, $dryRun, $sendNow, &$processed, $templateService, $ultraMsgService): void {
                foreach ($members as $member) {
                    if ($dryRun) {
                        $processed++;

                        continue;
                    }

                    if ($sendNow || config('queue.default') === 'sync') {
                        (new SendHimamatInvitationJob((int) $member->id, $campaignKey))->handle(
                            $ultraMsgService,
                            $templateService
                        );
                    } else {
                        SendHimamatInvitationJob::dispatch((int) $member->id, $campaignKey)
                            ->onQueue(SendHimamatInvitationJob::QUEUE_NAME);
                    }

                    $processed++;
                }
            });

            $this->line(sprintf(
                '%s Himamat invitation campaign [%s]. %s: %d',
                $dryRun ? 'Finished preview for' : 'Finished',
                $campaignKey,
                $dryRun ? 'Eligible recipients' : ($sendNow || config('queue.default') === 'sync' ? 'Processed' : 'Dispatched'),
                $processed
            ));

            return self::SUCCESS;
        } finally {
            $lock->release();
        }
    }

    private function sendSample(
        int $sampleMemberId,
        string $samplePhone,
        string $campaignKey,
        bool $dryRun,
        bool $sendNow,
        HimamatInvitationTemplateService $templateService,
        UltraMsgService $ultraMsgService
    ): int {
        $member = Member::query()->find($sampleMemberId);
        if (! $member || ! $member->token || trim((string) $member->token) === '') {
            $this->error('Sample member was not found or does not have a valid token.');

            return self::FAILURE;
        }

        $destinationPhone = $samplePhone !== '' ? $samplePhone : trim((string) ($member->whatsapp_phone ?? ''));
        if ($destinationPhone === '') {
            $this->error('Sample member does not have a WhatsApp phone. Pass --sample-phone to override the destination.');

            return self::FAILURE;
        }

        if ($dryRun) {
            $preview = $templateService->render($member, url('/himamat/access/'.$member->token))['message'];
            $this->line(sprintf(
                "Sample preview for member %d to %s:\n\n%s",
                $member->id,
                $destinationPhone,
                $preview
            ));

            return self::SUCCESS;
        }

        if ($sendNow || config('queue.default') === 'sync') {
            (new SendHimamatInvitationJob($member->id, $campaignKey, $destinationPhone, false))->handle(
                $ultraMsgService,
                $templateService
            );

            $this->line(sprintf(
                'Sample Himamat invitation sent inline to %s using member %d.',
                $destinationPhone,
                $member->id
            ));

            return self::SUCCESS;
        }

        SendHimamatInvitationJob::dispatch($member->id, $campaignKey, $destinationPhone, false)
            ->onQueue(SendHimamatInvitationJob::QUEUE_NAME);

        $this->line(sprintf(
            'Sample Himamat invitation dispatched to %s using member %d.',
            $destinationPhone,
            $member->id
        ));

        return self::SUCCESS;
    }
}
