<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'email'   => ['nullable', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        // Honeypot: if the hidden field is filled, silently discard
        if ($request->filled('website')) {
            return response()->json(['success' => true]);
        }

        Feedback::create([
            'name'       => strip_tags($data['name']),
            'email'      => $data['email'] ?? null,
            'message'    => strip_tags($data['message']),
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['success' => true]);
    }
}
