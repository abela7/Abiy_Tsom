<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Member settings — theme, language, identity, and WhatsApp reminders.
 */
class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        $member = $request->attributes->get('member');
        $customActivities = $member
            ? $member->customActivities()->orderBy('sort_order')->get()
            : collect();

        return view('member.settings', compact('member', 'customActivities'));
    }

    /**
     * Update member preferences (theme, locale).
     */
    public function update(Request $request): JsonResponse
    {
        if ($request->exists('whatsapp_phone')) {
            $request->merge([
                'whatsapp_phone' => normalizeUkWhatsAppPhone((string) $request->input('whatsapp_phone')),
            ]);
        }

        $request->validate([
            'locale' => ['nullable', 'string', 'in:en,am'],
            'theme' => ['nullable', 'string', 'in:light,dark'],
            'baptism_name' => ['nullable', 'string', 'min:1', 'max:255'],
            'whatsapp_reminder_enabled' => ['nullable', 'boolean'],
            'whatsapp_phone' => ['nullable', 'string', 'regex:/^\+447\d{9}$/'],
            'whatsapp_reminder_time' => ['nullable', 'date_format:H:i'],
        ]);

        /** @var \App\Models\Member $member */
        $member = $request->attributes->get('member');

        $updates = [];
        if ($request->filled('locale')) {
            $updates['locale'] = $request->input('locale');
            session(['locale' => $request->input('locale')]);
        }
        if ($request->filled('theme')) {
            $updates['theme'] = $request->input('theme');
        }
        if ($request->filled('baptism_name')) {
            $trimmed = trim($request->input('baptism_name'));
            if ($trimmed !== '') {
                $updates['baptism_name'] = $trimmed;
            }
        }

        $hasReminderPayload = $request->exists('whatsapp_reminder_enabled')
            || $request->exists('whatsapp_phone')
            || $request->exists('whatsapp_reminder_time');

        if ($hasReminderPayload) {
            $nextEnabled = $request->exists('whatsapp_reminder_enabled')
                ? $request->boolean('whatsapp_reminder_enabled')
                : (bool) $member->whatsapp_reminder_enabled;

            $nextPhone = $request->exists('whatsapp_phone')
                ? normalizeUkWhatsAppPhone((string) $request->input('whatsapp_phone'))
                : $member->whatsapp_phone;

            $nextTime = $request->exists('whatsapp_reminder_time')
                ? $this->normalizeReminderTime($request->input('whatsapp_reminder_time'))
                : $member->whatsapp_reminder_time;

            if ($nextEnabled && (! $nextPhone || ! $nextTime)) {
                return response()->json([
                    'success' => false,
                    'message' => __('app.whatsapp_reminder_requires_phone_and_time'),
                ], 422);
            }

            if ($request->exists('whatsapp_reminder_enabled')) {
                $updates['whatsapp_reminder_enabled'] = $nextEnabled;
            }

            if ($request->exists('whatsapp_phone')) {
                $updates['whatsapp_phone'] = $nextPhone;
            }

            if ($request->exists('whatsapp_reminder_time')) {
                $updates['whatsapp_reminder_time'] = $nextTime;
            }
        }

        if (! empty($updates)) {
            $member->update($updates);
        }

        return response()->json(['success' => true, 'member' => $member->fresh()]);
    }

    private function normalizeReminderTime(mixed $time): ?string
    {
        if (! is_string($time)) {
            return null;
        }

        $trimmed = trim($time);
        if ($trimmed === '') {
            return null;
        }

        return $trimmed.':00';
    }
}
