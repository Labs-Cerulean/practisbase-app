@extends('layouts.app')

@section('page_title', 'Inbox · '.$feedback->subject)

@section('content')
    @php $tone = $feedback->statusTone(); @endphp

    <div style="margin-bottom: 1.25rem;">
        <a href="/community/feedback/inbox" style="color: var(--primary-cerulean); font-weight: 600; text-decoration: none; font-size: 0.9rem;">← Inbox</a>
    </div>

    <div style="margin-bottom: 1.5rem;">
        <h1 style="font-size: 1.4rem; color: var(--primary-navy); margin: 0 0 0.4rem;">{{ $feedback->subject }}</h1>
        <div style="font-size: 0.9rem; color: var(--text-muted);">
            {{ $feedback->user->name ?? 'Member' }} · {{ $feedback->user->email ?? '' }} · {{ $feedback->categoryLabel() }}
            ·
            <span style="display: inline-block; background: {{ $tone['bg'] }}; color: {{ $tone['fg'] }}; border: 1px solid {{ $tone['border'] }}; padding: 0.2rem 0.55rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700;">
                {{ $feedback->statusLabel() }}
            </span>
        </div>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.25rem; border: 1px solid #a7f3d0;">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div style="background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.25rem; border: 1px solid #fecaca;">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <div class="feedback-operator-grid" style="display: grid; grid-template-columns: minmax(0, 1fr) minmax(260px, 320px); gap: 1.25rem; align-items: start;">
        <div>
            <div style="display: flex; flex-direction: column; gap: 0.85rem; margin-bottom: 1.25rem;">
                @foreach($feedback->messages as $message)
                    <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); padding: 1rem 1.15rem; {{ $message->is_staff ? 'border-left: 3px solid var(--primary-cerulean);' : 'border-left: 3px solid #94a3b8;' }}">
                        <div style="display: flex; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 0.5rem; font-size: 0.8rem; color: var(--text-muted);">
                            <strong style="color: var(--primary-navy);">
                                {{ $message->is_staff ? 'PractisBase team' : ($feedback->user->name ?? 'Member') }}
                            </strong>
                            <span>{{ optional($message->created_at)->format('d M Y H:i') }}</span>
                        </div>
                        <div style="white-space: pre-wrap; color: #334155; font-size: 0.95rem; line-height: 1.55;">{{ $message->body }}</div>
                    </div>
                @endforeach
            </div>

            <form method="POST" action="/community/feedback/inbox/{{ $feedback->id }}/reply" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); padding: 1.25rem;">
                @csrf
                <label style="display: block; font-weight: 600; margin-bottom: 0.45rem; color: var(--primary-navy); font-size: 0.9rem;">Reply to member</label>
                <textarea name="body" required maxlength="5000" rows="5" style="width: 100%; padding: 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font: inherit; resize: vertical; margin-bottom: 0.85rem;">{{ old('body') }}</textarea>
                <button type="submit" style="background: var(--primary-cerulean); color: white; border: none; padding: 0.65rem 1.15rem; border-radius: var(--radius-md); font-weight: 600; cursor: pointer;">Send reply</button>
            </form>
        </div>

        <aside style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); padding: 1.15rem;">
            <h2 style="font-size: 0.95rem; color: var(--primary-navy); margin: 0 0 0.85rem;">Workflow status</h2>
            <form method="POST" action="/community/feedback/inbox/{{ $feedback->id }}/status">
                @csrf
                @method('PUT')
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; color: var(--text-muted); font-size: 0.8rem;">Status</label>
                <select name="status" required style="width: 100%; padding: 0.6rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font: inherit; margin-bottom: 0.85rem;">
                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}" @selected(old('status', $feedback->status) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; color: var(--text-muted); font-size: 0.8rem;">Note to member (optional)</label>
                <textarea name="status_note" maxlength="1000" rows="4" placeholder="e.g. Shipping in next build / parked until after VAT module" style="width: 100%; padding: 0.6rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font: inherit; resize: vertical; margin-bottom: 0.85rem;">{{ old('status_note', $feedback->status_note) }}</textarea>
                <button type="submit" style="width: 100%; background: var(--primary-navy); color: white; border: none; padding: 0.65rem 1rem; border-radius: var(--radius-md); font-weight: 600; cursor: pointer;">Update status</button>
            </form>
            <p style="margin: 0.85rem 0 0; font-size: 0.78rem; color: var(--text-muted); line-height: 1.45;">
                Status changes mark the thread unread for the member. Replying from Open auto-moves to Acknowledged.
            </p>

            <div style="margin-top: 1.25rem; padding-top: 1.1rem; border-top: 1px solid var(--border-light);">
                <h2 style="font-size: 0.95rem; color: #991b1b; margin: 0 0 0.5rem;">Delete thread</h2>
                <p style="margin: 0 0 0.85rem; font-size: 0.78rem; color: var(--text-muted); line-height: 1.45;">
                    Permanently removes this feedback and all replies. Use when a note is spam, test noise, or clearly wrong.
                </p>
                <form method="POST" action="/community/feedback/inbox/{{ $feedback->id }}" onsubmit="return confirm('Delete this feedback thread permanently? This cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" style="width: 100%; background: white; color: #991b1b; border: 1px solid #fecaca; padding: 0.65rem 1rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">
                        Delete feedback
                    </button>
                </form>
            </div>
        </aside>
    </div>

    <style>
        @media (max-width: 900px) {
            .content-grid-fallback { }
        }
        @media (max-width: 900px) {
            div[style*="grid-template-columns: minmax(0, 1fr) minmax(260px, 320px)"] {
                display: flex !important;
                flex-direction: column !important;
            }
        }
    </style>
@endsection
