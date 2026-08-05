@extends('layouts.app')

@section('page_title', 'Community feedback')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem;">
        <div>
            <h1 style="font-size: 1.5rem; color: var(--primary-navy); margin: 0 0 0.25rem;">Community feedback</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0; max-width: 40rem;">
                Tell us what to build next. Threads are two-way — we reply, and you can follow up. Status updates stay visible on each item.
            </p>
        </div>
        <a href="/community/feedback/create" style="background: var(--primary-cerulean); color: white; padding: 0.6rem 1.25rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.9rem; text-decoration: none;">+ New suggestion</a>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; border: 1px solid #a7f3d0;">{{ session('success') }}</div>
    @endif

    @if(auth()->user()->canAccessCompanyBooks())
        <div style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: var(--radius-md); padding: 0.85rem 1rem; margin-bottom: 1.5rem; font-size: 0.9rem;">
            Staff inbox:
            <a href="/community/feedback/inbox" style="color: var(--primary-cerulean); font-weight: 700; text-decoration: none;">Open operator desk →</a>
        </div>
    @endif

    @if($items->isEmpty())
        <div style="padding: 3rem; border: 2px dashed var(--border-light); border-radius: var(--radius-md); text-align: center; background: white;">
            <p style="color: var(--text-muted); margin-bottom: 1rem;">No feedback yet. Ideas, friction, and bugs all help shape PractisBase.</p>
            <a href="/community/feedback/create" style="color: var(--primary-cerulean); font-weight: 600;">Send the first note →</a>
        </div>
    @else
        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                <thead>
                    <tr style="background: #f8fafc; text-align: left;">
                        <th style="padding: 0.85rem 1rem; border-bottom: 1px solid var(--border-light);">Subject</th>
                        <th style="padding: 0.85rem 1rem; border-bottom: 1px solid var(--border-light);">Type</th>
                        <th style="padding: 0.85rem 1rem; border-bottom: 1px solid var(--border-light);">Status</th>
                        <th style="padding: 0.85rem 1rem; border-bottom: 1px solid var(--border-light);">Updated</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        @php $tone = $item->statusTone(); @endphp
                        <tr>
                            <td style="padding: 0.85rem 1rem; border-bottom: 1px solid #f1f5f9;">
                                <a href="/community/feedback/{{ $item->id }}" style="color: var(--primary-navy); font-weight: 600; text-decoration: none;">
                                    {{ $item->subject }}
                                    @if($item->user_unread)
                                        <span style="display: inline-block; margin-left: 0.35rem; background: var(--primary-cerulean); color: white; font-size: 0.65rem; font-weight: 700; padding: 0.15rem 0.4rem; border-radius: 999px; vertical-align: middle;">NEW</span>
                                    @endif
                                </a>
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
