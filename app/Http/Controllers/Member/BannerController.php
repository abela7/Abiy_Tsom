<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\BannerResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function respond(Request $request, Banner $banner): JsonResponse
    {
        $member = $request->attributes->get('member');

        if (! $member) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'contact_name'  => ['required', 'string', 'max:255'],
            'contact_phone' => ['required', 'string', 'max:50'],
        ]);

        BannerResponse::updateOrCreate(
            ['banner_id' => $banner->id, 'member_id' => $member->id],
            $data
        );

        return response()->json(['success' => true]);
    }
}
