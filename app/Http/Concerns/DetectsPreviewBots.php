<?php

declare(strict_types=1);

namespace App\Http\Concerns;

use Illuminate\Http\Request;

/**
 * Detects social media preview bots (WhatsApp, Facebook, Telegram, etc.)
 * that fetch URLs to generate link preview cards.
 *
 * These bots do NOT execute JavaScript and should receive OG-tag-only
 * HTML without consuming any one-time auth codes.
 */
trait DetectsPreviewBots
{
    protected function isPreviewBot(Request $request): bool
    {
        $ua = (string) $request->userAgent();
        if ($ua === '') {
            return true;
        }

        $uaLower = strtolower($ua);

        $botSignatures = [
            'whatsapp',
            'facebookexternalhit',
            'facebot',
            'telegrambot',
            'twitterbot',
            'linkedinbot',
            'slackbot',
            'discordbot',
            'googlebot',
            'bingbot',
            'yandexbot',
        ];

        foreach ($botSignatures as $sig) {
            if (str_contains($uaLower, $sig)) {
                return true;
            }
        }

        return false;
    }
}
