<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FasikaGreetingShare;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
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

    public function update(Request $request, FasikaGreetingShare $share): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'sender_name' => ['required', 'string', 'max:120', 'regex:/.*\S.*/u'],
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput()
                ->with('fasika_greeting_failed_token', $share->share_token);
        }

        $senderName = Str::of((string) $validator->validated()['sender_name'])->squish()->value();

        $share->update([
            'sender_name' => $senderName,
            'sender_name_normalized' => Str::lower($senderName),
        ]);

        return redirect()
            ->route('admin.fasika-greetings.index')
            ->with('success', __('app.fasika_greeting_update_success', ['name' => $senderName]));
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
