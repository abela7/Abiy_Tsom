<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SendWhatsAppFeedbackInvitationJob;
use App\Models\Member;
use App\Models\MemberFeedback;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

final class SendFeedbackInvitations extends Command
{
    protected $signature = 'feedback:send-invitations
                            {--phone=  : Send only to this WhatsApp number (for testing)}
                            {--dry-run : List eligible members without sending}
                            {--queue   : Dispatch jobs to the queue instead of running inline}';

    protected $description = 'Generate tokens and send Post-Fasika feedback survey invitations via WhatsApp';

    public function handle(): int
    {
        $isDryRun   = (bool) $this->option('dry-run');
        $useQueue   = (bool) $this->option('queue');
        $phoneFilter = trim((string) $this->option('phone'));

        $query = Member::query()->whereNotNull('whatsapp_phone');

        if ($phoneFilter !== '') {
            $query->where('whatsapp_phone', $phoneFilter);
        } else {
            $query
                ->where('whatsapp_confirmation_status', 'confirmed')
                ->where('whatsapp_reminder_enabled', true);
        }

        $members = $query->get();

        $this->info("Found {$members->count()} eligible members.");

        if ($isDryRun) {
            $this->table(['ID', 'Name', 'Phone'], $members->map(fn ($m) => [
                $m->id,
                $m->baptism_name ?? '-',
                $m->whatsapp_phone,
            ])->toArray());

            return self::SUCCESS;
        }

        $sent    = 0;
        $skipped = 0;

        foreach ($members as $member) {
            // Create survey row only if one doesn't exist yet
            $feedback = MemberFeedback::firstOrCreate(
                ['member_id' => $member->id],
                ['token' => Str::random(48), 'status' => 'pending']
            );

            // Skip members who already submitted
            if ($feedback->status === 'submitted') {
                $skipped++;
                continue;
            }

            if ($useQueue) {
                SendWhatsAppFeedbackInvitationJob::dispatch($feedback->id);
            } else {
                app(SendWhatsAppFeedbackInvitationJob::class, ['feedbackId' => $feedback->id])
                    ->handle(app(\App\Services\UltraMsgService::class));
            }

            $sent++;
            $this->line("  ✓ Queued invitation for member {$member->id} ({$member->baptism_name})");
        }

        $this->newLine();
        $this->info("Done. Sent: {$sent}, Skipped (already submitted): {$skipped}.");

        return self::SUCCESS;
    }
}
