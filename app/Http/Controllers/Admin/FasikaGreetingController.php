<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FasikaGreetingShare;
use Illuminate\Http\RedirectResponse;
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

    public function destroy(FasikaGreetingShare $share): RedirectResponse
    {
        $senderName = $share->sender_name;

        $share->delete();

        return redirect()
            ->route('admin.fasika-greetings.index')
            ->with('success', __('app.fasika_greeting_delete_success', ['name' => $senderName]));
    }

    public function clearAll(): RedirectResponse
    {
        $deletedCount = FasikaGreetingShare::query()->count();

        FasikaGreetingShare::query()->delete();

        return redirect()
            ->route('admin.fasika-greetings.index')
            ->with('success', __('app.fasika_greeting_clear_all_success', ['count' => $deletedCount]));
    }
}
