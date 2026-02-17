<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Cron job setup instructions for WhatsApp reminders.
 */
class WhatsAppCronController extends Controller
{
    /**
     * Show cPanel cron setup instructions.
     */
    public function index(): View
    {
        $phpPath = '/usr/local/bin/ea-php82';
        $artisanPath = base_path('artisan');
        $appUrl = config('app.url');

        return view('admin.whatsapp.cron', compact(
            'phpPath',
            'artisanPath',
            'appUrl'
        ));
    }
}
