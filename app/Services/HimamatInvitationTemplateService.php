<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Member;
use App\Models\Translation;
use Illuminate\Support\Facades\Lang;

class HimamatInvitationTemplateService
{
    /**
     * @return array{locale: string, message: string}
     */
    public function render(Member $member, string $url): array
    {
        $locale = $this->preferredLocale($member);
        $message = Lang::get('app.himamat_invitation_message', [
            'name' => $this->firstName($member),
            'url' => $this->ensureHttpsUrl($url),
        ], $locale);

        return [
            'locale' => $locale,
            'message' => trim((string) $message),
        ];
    }

    private function preferredLocale(Member $member): string
    {
        $locale = trim((string) ($member->locale ?? ''));
        $locale = in_array($locale, ['en', 'am'], true) ? $locale : 'am';

        Translation::loadFromDb($locale);

        return $locale;
    }

    private function firstName(Member $member): string
    {
        $fullName = trim((string) ($member->baptism_name ?? ''));
        if ($fullName === '') {
            return '';
        }

        $parts = preg_split('/\s+/u', $fullName, -1, PREG_SPLIT_NO_EMPTY);

        return $parts[0] ?? $fullName;
    }

    private function ensureHttpsUrl(string $url): string
    {
        if (app()->environment('local')) {
            return $url;
        }

        return preg_replace('/^http:\/\//i', 'https://', $url) ?? $url;
    }
}
