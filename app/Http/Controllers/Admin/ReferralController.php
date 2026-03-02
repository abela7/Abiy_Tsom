<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\ReferralClick;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ReferralController extends Controller
{
    public function index(): View
    {
        // Affiliates = admin users with a referral code
        $affiliates = User::whereNotNull('referral_code')
            ->withCount([
                'referralClicks as total_clicks',
                'referralClicks as unique_clicks' => fn ($q) => $q->where('is_unique', true),
                'referrals as total_registrations',
            ])
            ->orderByDesc('total_registrations')
            ->orderByDesc('unique_clicks')
            ->get()
            ->map(function (User $affiliate) {
                $affiliate->conversion_rate = $affiliate->unique_clicks > 0
                    ? round(($affiliate->total_registrations / $affiliate->unique_clicks) * 100, 1)
                    : 0;
                $affiliate->bounces = max(0, $affiliate->unique_clicks - $affiliate->total_registrations);

                return $affiliate;
            });

        $totalClicks = ReferralClick::count();
        $totalUniqueClicks = ReferralClick::where('is_unique', true)->count();
        $totalReferredMembers = Member::whereNotNull('referred_by')->count();
        $overallConversionRate = $totalUniqueClicks > 0
            ? round(($totalReferredMembers / $totalUniqueClicks) * 100, 1)
            : 0;

        // Click trend (last 14 days)
        $clickTrend = ReferralClick::query()
            ->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date');

        $trendData = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $trendData[$date] = $clickTrend[$date] ?? 0;
        }

        // Admin users without referral codes (for the "enable" dropdown)
        $availableAdmins = User::whereNull('referral_code')
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        return view('admin.referrals.index', compact(
            'affiliates',
            'totalClicks',
            'totalUniqueClicks',
            'totalReferredMembers',
            'overallConversionRate',
            'trendData',
            'availableAdmins',
        ));
    }

    public function enable(User $user): RedirectResponse
    {
        if ($user->referral_code) {
            return redirect()->route('admin.referrals.index')
                ->with('error', $user->name . ' already has a referral code.');
        }

        $user->update([
            'referral_code' => $this->generateUniqueCode(),
        ]);

        return redirect()->route('admin.referrals.index')
            ->with('success', __('app.referral_enabled_for', ['name' => $user->name]));
    }

    public function disable(User $user): RedirectResponse
    {
        $user->update(['referral_code' => null]);

        return redirect()->route('admin.referrals.index')
            ->with('success', __('app.referral_disabled_for', ['name' => $user->name]));
    }

    public function regenerate(User $user): RedirectResponse
    {
        $user->update([
            'referral_code' => $this->generateUniqueCode(),
        ]);

        return redirect()->route('admin.referrals.index')
            ->with('success', __('app.referral_regenerated_for', ['name' => $user->name]));
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = strtolower(Str::random(8));
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }
}
