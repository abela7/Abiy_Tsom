<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Translation;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets the application locale from session or query parameter.
 * When locale is Amharic, loads admin-edited translations from DB.
 */
class SetLocale
{
    /** @var list<string> */
    private const SUPPORTED_LOCALES = ['en', 'am'];

    public function handle(Request $request, Closure $next): Response
    {
        // Allow switching locale via ?lang=am
        if ($request->has('lang') && in_array($request->query('lang'), self::SUPPORTED_LOCALES, true)) {
            $locale = $request->query('lang');
            session(['locale' => $locale]);
        }

        $locale = session('locale', config('app.locale'));

        if (in_array($locale, self::SUPPORTED_LOCALES, true)) {
            app()->setLocale($locale);
            Carbon::setLocale($locale);
            Translation::loadFromDb($locale);
        }

        return $next($request);
    }
}
