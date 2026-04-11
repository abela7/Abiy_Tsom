<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FasikaGreetingShare;
use Illuminate\View\View;

class FasikaGreetingController extends Controller
{
    public function index(): View
    {
        $shares = FasikaGreetingShare::query()
            ->latest()
            ->paginate(30);

        $stats = [
            'created' => FasikaGreetingShare::query()->count(),
            'active' => FasikaGreetingShare::query()->where('open_count', '>', 0)->count(),
            'opens' => (int) FasikaGreetingShare::query()->sum('open_count'),
            'unique_senders' => FasikaGreetingShare::query()
                ->where('sender_name_normalized', '!=', '')
                ->distinct()
                ->count('sender_name_normalized'),
        ];

        return view('admin.fasika-greetings.index', compact('shares', 'stats'));
    }
}
