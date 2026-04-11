<?php

declare(strict_types=1);

return [
    'timezone' => 'Europe/London',

    'reminders' => [
        'legacy_scheduler_enabled' => (bool) env('HIMAMAT_LEGACY_SCHEDULER_ENABLED', false),
        'dispatch_grace_minutes' => (int) env('HIMAMAT_REMINDER_DISPATCH_GRACE_MINUTES', 20),
        'test_mode_member_id' => env('HIMAMAT_REMINDER_TEST_MEMBER_ID'),
        'queues' => [
            'reminders' => env('HIMAMAT_REMINDER_QUEUE', 'whatsapp-himamat-reminders'),
            'invitations' => env('HIMAMAT_INVITATION_QUEUE', 'whatsapp-himamat-invitations'),
        ],
        'rate_limits' => [
            'reminders_per_minute' => (int) env('HIMAMAT_REMINDER_RATE_PER_MINUTE', 240),
            'invitations_per_minute' => (int) env('HIMAMAT_INVITATION_RATE_PER_MINUTE', 120),
        ],
    ],

    'days' => [
        [
            'slug' => 'holy-monday',
            'title_en' => 'Holy Monday',
            'title_am' => null,
        ],
        [
            'slug' => 'holy-tuesday',
            'title_en' => 'Holy Tuesday',
            'title_am' => null,
        ],
        [
            'slug' => 'holy-wednesday',
            'title_en' => 'Holy Wednesday',
            'title_am' => null,
        ],
        [
            'slug' => 'holy-thursday',
            'title_en' => 'Holy Thursday',
            'title_am' => null,
        ],
        [
            'slug' => 'good-friday',
            'title_en' => 'Good Friday',
            'title_am' => null,
        ],
        [
            'slug' => 'holy-saturday',
            'title_en' => 'Holy Saturday',
            'title_am' => null,
        ],
        [
            'slug' => 'fasika',
            'title_en' => 'Fasika (Easter Sunday)',
            'title_am' => 'ፋሲካ',
        ],
    ],

    'slots' => [
        [
            'key' => 'intro',
            'order' => 1,
            'time' => '07:00:00',
            'label_en' => 'Daily Introduction',
            'label_am' => null,
            'default_slot_header_en' => 'Daily Introduction',
            'default_slot_header_am' => null,
            'default_reminder_header_en' => 'Daily Introduction: Enter Holy Week with watchfulness and prayer.',
            'default_reminder_header_am' => null,
        ],
        [
            'key' => 'third',
            'order' => 2,
            'time' => '09:00:00',
            'label_en' => "Sa'ate Shalist",
            'label_am' => null,
            'default_slot_header_en' => "Sa'ate Shalist",
            'default_slot_header_am' => null,
            'default_reminder_header_en' => "Sa'ate Shalist: Stand with the prayer of the Third Hour.",
            'default_reminder_header_am' => null,
        ],
        [
            'key' => 'sixth',
            'order' => 3,
            'time' => '12:00:00',
            'label_en' => "Sa'ate Qat r",
            'label_am' => null,
            'default_slot_header_en' => "Sa'ate Qat r",
            'default_slot_header_am' => null,
            'default_reminder_header_en' => "Sa'ate Qat r: The hour our Lord was crucified. Remain near the Cross.",
            'default_reminder_header_am' => null,
        ],
        [
            'key' => 'ninth',
            'order' => 4,
            'time' => '15:00:00',
            'label_en' => "Sa'ate Tas'at",
            'label_am' => null,
            'default_slot_header_en' => "Sa'ate Tas'at",
            'default_slot_header_am' => null,
            'default_reminder_header_en' => "Sa'ate Tas'at: Remember the suffering of the Ninth Hour.",
            'default_reminder_header_am' => null,
        ],
        [
            'key' => 'eleventh',
            'order' => 5,
            'time' => '17:00:00',
            'label_en' => "Sa'ate As rtu Wa-Ahadu",
            'label_am' => null,
            'default_slot_header_en' => "Sa'ate As rtu Wa-Ahadu",
            'default_slot_header_am' => null,
            'default_reminder_header_en' => "Sa'ate As rtu Wa-Ahadu: Stay close in the final prayer of the day.",
            'default_reminder_header_am' => null,
        ],
    ],
];
