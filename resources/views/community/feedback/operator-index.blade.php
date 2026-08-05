@extends('layouts.app')

@section('page_title', 'Feedback inbox')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem;">
        <div>
            <h1 style="font-size: 1.5rem; color: var(--primary-navy); margin: 0 0 0.25rem;">Community inbox</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">
                Operator desk for PractisBase community feedback.
                @if($unreadCount > 0)
                    <strong style="color: var(--primary-navy);">{{ $unreadCount }} unread</strong>
                @endif
            </p>
        </div>
        <a href="/community/feedback" style="color: var(--primary-cerulean); font-weight: 600; text-decoration: none; font-size: 0.9rem;">My own threads →</a>
    </div>

    <form method="GET" action="/community/feedback/inbox" style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center; margin-bottom: 1.25rem;">
        <select name="status" onchange="this.form.submit()" style="padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font: inherit;">
            <option value="">All statuses</option>
            @foreach($statuses as $key => $label)
                <option value="{{ $key }}" @selected($filterStatus === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <label style="display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.9rem; color: var(--primary-navy); font-weight: 600;">
            <input type="checkbox" name="unread" value="1" @checked($filterUnread) onchange="this.form.submit()">
            Unread only
        </label>
    </form>

    @if(session('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.25rem; border: 1px solid #a7f3d0;">{{ session('success') }}</div>
    @endif

    @if($items->isEmpty())
        <div style="padding: 2.5rem; border: 2px dashed var(--border-light); border-radius: var(--radius-md); text-align: center; background: white; color: var(--text-muted);">
            No threads match this filter.
        </div>
    @else
        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                <thead>
                    <tr style="background: #f8fafc; text-align: left;">
                        <th style="padding: 0.85rem 1rem; border-bottom: 1px solid var(--border-light);">Subject</th>
                        <th style="padding: 0.85rem 1rem; border-bottom: 1px solid var(--border-light);">Member</th>
                        <th style="padding: 0.85rem 1rem; border-bottom: 1px solid var(--border-light);">Type</th>
                        <th style="padding: 0.85rem 1rem; border-bottom: 1px solid var(--border-light);">Status</th>
                        <th style="padding: 0.85rem 1rem; border-bottom: 1px solid var(--border-light);">Updated</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        @php $tone = $item->statusTone(); @endphp
                        <tr style="{{ $item->staff_unread ? 'background: #f0f9ff;' : '' }}">
                            <td style="padding: 0.85rem 1rem; border-bottom: 1px solid #f1f5f9;">
                                <a href="/community/feedback/inbox/{{ $item->id }}" style="color: var(--primary-navy); font-weight: 600; text-decoration: none;">
                                    {{ $item->subject }}
                                    @if($item->staff_unread)
                                        <span style="display: inline-block; margin-left: 0.35rem; background: var(--primary-cerulean); color: white; font-size: 0.65rem; font-weight: 700; padding: 0.15rem 0.4rem; border-radius: 999px;">NEW</span>
                                    @endif
                                </a>
                            </td>
                            <td style="padding: 0.85rem 1rem; border-bottom: 1px solid #f1f5f9; color: var(--text-muted);">
                                {{ $item->user->name ?? '—' }}
                                <div style="font-size: 0.75rem;">{{ $item->user->email ?? '' }}</div>
                            </td>
                            <td style="padding: 0.85rem 1rem; border-bottom: 1px solid #f1f5f9; color: var(--text-muted);">{{ $item->categoryLabel() }}</td>
                            <td style="padding: 0.85rem 1rem; border-bottom: 1px solid #f1f5f9;">
                                <span style="display: inline-block; background: {{ $tone['bg'] }}; color: {{ $tone['fg'] }}; border: 1px solid {{ $tone['border'] }}; padding: 0.2rem 0.55rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700;">
                                    {{ $item->statusLabel() }}
                                </span>
                            </td>
                            <td style="padding: 0.85rem 1rem; border-bottom: 1px solid #f1f5f9; color: var(--text-muted); white-space: nowrap;">
                                {{ optional($item->updated_at)->format('d M Y H:i') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="margin-top: 1rem;">{{ $items->links() }}</div>
    @endif
@endsection
