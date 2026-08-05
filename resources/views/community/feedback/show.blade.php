@extends('layouts.app')

@section('page_title', $feedback->subject)

@section('content')
    @php $tone = $feedback->statusTone(); @endphp

    <div style="margin-bottom: 1.25rem;">
        <a href="/community/feedback" style="color: var(--primary-cerulean); font-weight: 600; text-decoration: none; font-size: 0.9rem;">← All feedback</a>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem;">
        <div>
            <h1 style="font-size: 1.4rem; color: var(--primary-navy); margin: 0 0 0.4rem;">{{ $feedback->subject }}</h1>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center; font-size: 0.85rem; color: var(--text-muted);">
                <span>{{ $feedback->categoryLabel() }}</span>
                <span>·</span>
                <span style="display: inline-block; background: {{ $tone['bg'] }}; color: {{ $tone['fg'] }}; border: 1px solid {{ $tone['border'] }}; padding: 0.2rem 0.55rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700;">
                    {{ $feedback->statusLabel() }}
                </span>
                <span>·</span>
                <span>Opened {{ optional($feedback->created_at)->format('d M Y') }}</span>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.25rem; border: 1px solid #a7f3d0;">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div style="background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.25rem; border: 1px solid #fecaca;">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div style="background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.25rem; border: 1px solid #fecaca;">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    @if($feedback->status_note)
        <div style="background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.85rem 1rem; margin-bottom: 1.25rem; font-size: 0.9rem; color: #334155;">
            <strong style="color: var(--primary-navy);">Status note from PractisBase:</strong> {{ $feedback->status_note }}
        </div>
    @endif

    <div style="display: flex; flex-direction: column; gap: 0.85rem; margin-bottom: 1.5rem;">
        @foreach($feedback->messages as $message)
            <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); padding: 1rem 1.15rem; {{ $message->is_staff ? 'border-left: 3px solid var(--primary-cerulean);' : '' }}">
                <div style="display: flex; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 0.5rem; font-size: 0.8rem; color: var(--text-muted);">
                    <strong style="color: var(--primary-navy);">
                        {{ $message->is_staff ? 'PractisBase team' : 'You' }}
                    </strong>
                    <span>{{ optional($message->created_at)->format('d M Y H:i') }}</span>
                </div>
                <div style="white-space: pre-wrap; color: #334155; font-size: 0.95rem; line-height: 1.55;">{{ $message->body }}</div>
            </div>
        @endforeach
    </div>

    @if($feedback->isOpenForReply())
        <form method="POST" action="/community/feedback/{{ $feedback->id }}/reply" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); padding: 1.25rem; max-width: 720px;">
            @csrf
            <label style="display: block; font-weight: 600; margin-bottom: 0.45rem; color: var(--primary-navy); font-size: 0.9rem;">Add a reply</label>
            <textarea name="body" required maxlength="5000" rows="5" style="width: 100%; padding: 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font: inherit; resize: vertical; margin-bottom: 0.85rem;">{{ old('body') }}</textarea>
            <button type="submit" style="background: var(--primary-cerulean); color: white; border: none; padding: 0.65rem 1.15rem; border-radius: var(--radius-md); font-weight: 600; cursor: pointer;">Send reply</button>
        </form>
    @else
        <div style="background: #f8fafc; border: 1px dashed var(--border-light); border-radius: var(--radius-md); padding: 1rem; color: var(--text-muted); font-size: 0.9rem;">
            This thread is closed. <a href="/community/feedback/create" style="color: var(--primary-cerulean); font-weight: 600;">Open a new note</a> if you still need something.
        </div>
    @endif
@endsection
