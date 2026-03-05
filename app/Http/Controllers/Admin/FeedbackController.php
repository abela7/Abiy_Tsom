<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    public function index(): View
    {
        $feedbacks = Feedback::latest()->paginate(25);

        return view('admin.feedback.index', compact('feedbacks'));
    }

    public function toggleRead(Feedback $feedback): RedirectResponse
    {
        $feedback->update(['is_read' => ! $feedback->is_read]);

        return back();
    }

    public function destroy(Feedback $feedback): RedirectResponse
    {
        $feedback->delete();

        return back()->with('success', 'Feedback deleted.');
    }
}
