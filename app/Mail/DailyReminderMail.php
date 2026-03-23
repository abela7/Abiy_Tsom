<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\DailyContent;
use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailyReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Member $member,
        public readonly DailyContent $dailyContent,
        public readonly string $dayUrl,
    ) {}

    public function envelope(): Envelope
    {
        $locale = $this->member->locale ?? $this->member->whatsapp_language ?? 'am';

        return new Envelope(
            subject: __('app.email_reminder_subject', [
                'day' => $this->dailyContent->day_number,
            ], $locale),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.daily-reminder',
        );
    }
}
