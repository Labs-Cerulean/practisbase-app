<?php

namespace App\Http\Controllers;

use App\Models\CommunityFeedback;
use App\Models\CommunityFeedbackMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CommunityFeedbackController extends Controller
{
    public function index(): View
    {
        $items = CommunityFeedback::query()
            ->where('user_id', Auth::id())
            ->orderByDesc('updated_at')
            ->paginate(20);

        return view('community.feedback.index', [
            'items' => $items,
            'categories' => CommunityFeedback::CATEGORIES,
            'statuses' => CommunityFeedback::STATUSES,
        ]);
    }

    public function create(): View
    {
        return view('community.feedback.create', [
            'categories' => CommunityFeedback::CATEGORIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category' => 'required|in:'.implode(',', array_keys(CommunityFeedback::CATEGORIES)),
            'subject' => 'required|string|max:200',
            'body' => 'required|string|max:5000',
        ]);

        $feedback = CommunityFeedback::create([
            'user_id' => Auth::id(),
            'category' => $data['category'],
            'subject' => $data['subject'],
            'status' => 'open',
            'staff_unread' => true,
            'user_unread' => false,
        ]);

        CommunityFeedbackMessage::create([
            'feedback_id' => $feedback->id,
            'user_id' => Auth::id(),
            'body' => $data['body'],
            'is_staff' => false,
        ]);

        return redirect('/community/feedback/'.$feedback->id)
            ->with('success', 'Thanks — your note is with the PractisBase community desk.');
    }

    public function show(int $id): View|RedirectResponse
    {
        $feedback = CommunityFeedback::query()
            ->where('user_id', Auth::id())
            ->where('id', $id)
            ->with(['messages.user'])
            ->firstOrFail();

        if ($feedback->user_unread) {
            $feedback->update(['user_unread' => false]);
        }

        return view('community.feedback.show', [
            'feedback' => $feedback,
            'statuses' => CommunityFeedback::STATUSES,
        ]);
    }

    public function reply(Request $request, int $id): RedirectResponse
    {
        $feedback = CommunityFeedback::query()
            ->where('user_id', Auth::id())
            ->where('id', $id)
            ->firstOrFail();

        if (! $feedback->isOpenForReply()) {
            return back()->with('error', 'This thread is closed. Open a new suggestion if you still need help.');
        }

        $data = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        CommunityFeedbackMessage::create([
            'feedback_id' => $feedback->id,
            'user_id' => Auth::id(),
            'body' => $data['body'],
            'is_staff' => false,
        ]);

        $feedback->update([
            'staff_unread' => true,
            'user_unread' => false,
            'updated_at' => now(),
        ]);

        return redirect('/community/feedback/'.$feedback->id)
            ->with('success', 'Reply sent.');
    }
}
