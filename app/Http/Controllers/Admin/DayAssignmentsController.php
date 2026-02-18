<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyContent;
use App\Models\LentSeason;
use App\Models\User;
use App\Services\WriterReminderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Assign writers/editors/admins to each day of the Lent season.
 */
class DayAssignmentsController extends Controller
{
    /**
     * Show the day assignments page for the active season.
     */
    public function index(): View
    {
        $season = LentSeason::active();
        $contents = $season
            ? $season->dailyContents()
                ->with(['weeklyTheme', 'assignedTo'])
                ->orderBy('day_number')
                ->get()
            : collect();

        $assignableUsers = User::query()
            ->whereIn('role', ['admin', 'editor', 'writer'])
            ->orderBy('name')
            ->get(['id', 'name', 'role', 'whatsapp_phone']);

        return view('admin.day-assignments.index', compact('season', 'contents', 'assignableUsers'));
    }

    /**
     * Update the assigned writer for a day.
     */
    public function update(Request $request, DailyContent $daily): JsonResponse
    {
        $validated = $request->validate([
            'assigned_to_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $userId = $validated['assigned_to_id'] ?? null;

        $daily->update(['assigned_to_id' => $userId]);

        $assigned = $daily->assignedTo;

        return response()->json([
            'success' => true,
            'assigned_name' => $assigned?->name,
            'assigned_role' => $assigned?->role,
            'has_whatsapp' => ! empty($assigned?->whatsapp_phone),
        ]);
    }

    /**
     * Send reminder for a specific day to its assigned writer.
     */
    public function sendReminder(WriterReminderService $writerReminderService, DailyContent $daily): JsonResponse
    {
        $result = $writerReminderService->sendReminderForDay($daily, dryRun: false);

        return response()->json([
            'success' => $result['sent'],
            'message' => $result['message'],
        ], $result['sent'] ? 200 : 400);
    }
}
