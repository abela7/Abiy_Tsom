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
        $phpPath = env('CRON_PHP_PATH', '/usr/bin/php');
        $artisanPath = base_path('artisan');
        $projectPath = base_path();
        $appUrl = config('app.url');

        return view('admin.whatsapp.cron', compact(
            'phpPath',
            'artisanPath',
            'projectPath',
            'appUrl'
        ));
    }
}
