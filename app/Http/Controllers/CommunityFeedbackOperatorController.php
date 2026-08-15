<?php

namespace App\Http\Controllers;

use App\Models\CommunityFeedback;
use App\Models\CommunityFeedbackMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Cerulean Labs operator inbox for PractisBase community feedback.
 * Gated by company_books_enabled (same staff flag as the Ltd desk).
 */
class CommunityFeedbackOperatorController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $query = CommunityFeedback::query()
            ->with('user')
            ->orderByDesc('staff_unread')
            ->orderByDesc('updated_at');

        if ($status && array_key_exists($status, CommunityFeedback::STATUSES)) {
            $query->where('status', $status);
        }

        if ($request->boolean('unread')) {
            $query->where('staff_unread', true);
        }

        return view('community.feedback.operator-index', [
            'items' => $query->paginate(30)->withQueryString(),
            'statuses' => CommunityFeedback::STATUSES,
            'categories' => CommunityFeedback::CATEGORIES,
            'filterStatus' => $status,
            'filterUnread' => $request->boolean('unread'),
            'unreadCount' => CommunityFeedback::query()->where('staff_unread', true)->count(),
        ]);
    }

    public function show(int $id): View
    {
        $feedback = CommunityFeedback::query()
            ->with(['user', 'messages.user'])
            ->where('id', $id)
            ->firstOrFail();

        if ($feedback->staff_unread) {
            $feedback->update(['staff_unread' => false]);
        }

        return view('community.feedback.operator-show', [
            'feedback' => $feedback,
            'statuses' => CommunityFeedback::STATUSES,
            'categories' => CommunityFeedback::CATEGORIES,
        ]);
    }

    public function reply(Request $request, int $id): RedirectResponse
    {
        $feedback = CommunityFeedback::query()->where('id', $id)->firstOrFail();

        $data = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        CommunityFeedbackMessage::create([
            'feedback_id' => $feedback->id,
            'user_id' => Auth::id(),
            'body' => $data['body'],
            'is_staff' => true,
        ]);

        $updates = [
            'staff_unread' => false,
            'user_unread' => true,
            'updated_at' => now(),
        ];

        if ($feedback->status === 'open') {
            $updates['status'] = 'acknowledged';
        }

        $feedback->update($updates);

        return redirect('/community/feedback/inbox/'.$feedback->id)
            ->with('success', 'Reply posted to the community member.');
    }

    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        $feedback = CommunityFeedback::query()->where('id', $id)->firstOrFail();

        $data = $request->validate([
            'status' => 'required|in:'.implode(',', array_keys(CommunityFeedback::STATUSES)),
            'status_note' => 'nullable|string|max:1000',
        ]);

        $feedback->update([
            'status' => $data['status'],
            'status_note' => $data['status_note'] ?: null,
            'user_unread' => true,
            'staff_unread' => false,
            'updated_at' => now(),
        ]);

        return redirect('/community/feedback/inbox/'.$feedback->id)
            ->with('success', 'Status updated to '.$feedback->statusLabel().'.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $feedback = CommunityFeedback::query()->where('id', $id)->firstOrFail();
        $subject = $feedback->subject;

        $feedback->messages()->delete();
        $feedback->delete();

        return redirect('/community/feedback/inbox')
            ->with('success', 'Feedback deleted: '.$subject);
    }
}
