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

        // WhatsApp's in-app browser includes "WhatsApp" in the UA but is a
        // real browser (e.g. "Mozilla/5.0 ... Chrome/91.0 ... WhatsApp/2.24").
        // The actual preview bot UA is just "WhatsApp/2.xx.xx A" with no
        // browser engine.  Only flag as bot when there is no real browser
        // engine present alongside the "whatsapp" token.
        if (str_contains($uaLower, 'whatsapp')) {
            return ! str_contains($uaLower, 'mozilla');
        }

        $botSignatures = [
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
