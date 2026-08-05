@extends('layouts.app')

@section('page_title', 'Equipment')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem;">
        <div>
            <h1 style="margin: 0 0 0.25rem; color: var(--primary-navy); font-size: 1.5rem;">Equipment</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.95rem; max-width: 40rem;">
                Client plant register — forklifts, cranes, chains, and more. Certificates sit on the asset, not a project, so gear can move sites.
            </p>
        </div>
        <div style="display: flex; gap: 0.55rem; flex-wrap: wrap;">
            <a href="/pro/engineer/equipment/due" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none; box-shadow: var(--shadow-sm);">
                Due board
                @if($dueCount > 0)
                    <span style="margin-left: 0.35rem; background: #fee2e2; color: #991b1b; font-size: 0.72rem; font-weight: 700; padding: 0.15rem 0.45rem; border-radius: 999px;">{{ $dueCount }}</span>
                @endif
            </a>
            <a href="/pro/engineer/equipment/create" style="background: var(--primary-cerulean); color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">+ Register equipment</a>
        </div>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem; border: 1px solid #a7f3d0;">{{ session('success') }}</div>
    @endif

    <form method="GET" action="/pro/engineer/equipment" style="display: flex; gap: 0.65rem; flex-wrap: wrap; align-items: center; margin-bottom: 1.25rem;">
        <select name="client_id" onchange="this.form.submit()" style="padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font: inherit;">
            <option value="">All clients</option>
            @foreach($clients as $client)
                <option value="{{ $client->id }}" @selected($filterClientId == $client->id)>{{ $client->name }}</option>
            @endforeach
        </select>
        <select name="status" onchange="this.form.submit()" style="padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font: inherit;">
            <option value="all" @selected($filterStatus === 'all')>All statuses</option>
            @foreach($statuses as $key => $label)
                <option value="{{ $key }}" @selected($filterStatus === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </form>

    @if($items->isEmpty())
        <div style="padding: 3rem; border: 2px dashed var(--border-light); border-radius: var(--radius-md); text-align: center; background: white;">
            <p style="color: var(--text-muted); margin: 0 0 1rem;">No equipment registered yet.</p>
            <a href="/pro/engineer/equipment/create" style="color: var(--primary-cerulean); font-weight: 700; text-decoration: none;">Register the first asset →</a>
        </div>
    @else
        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                <thead>
                    <tr style="background: #f8fafc; text-align: left;">
                        <th style="padding: 0.85rem 1rem; border-bottom: 1px solid var(--border-light);">Asset</th>
                        <th style="padding: 0.85rem 1rem; border-bottom: 1px solid var(--border-light);">Client</th>
                        <th style="padding: 0.85rem 1rem; border-bottom: 1px solid var(--border-light);">Category</th>
                        <th style="padding: 0.85rem 1rem; border-bottom: 1px solid var(--border-light);">Next due</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        @php $tone = $item->dueTone(); @endphp
                        <tr>
                            <td style="padding: 0.85rem 1rem; border-bottom: 1px solid #f1f5f9;">
                                <a href="/pro/engineer/equipment/{{ $item->id }}" style="color: var(--primary-navy); font-weight: 700; text-decoration: none;">{{ $item->name }}</a>
                                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.15rem;">
                                    {{ $item->asset_code }}
                                    @if($item->serial_number) · S/N {{ $item->serial_number }} @endif
                                </div>
                            </td>
                            <td style="padding: 0.85rem 1rem; border-bottom: 1px solid #f1f5f9; color: var(--text-muted);">{{ $item->client->name ?? '—' }}</td>
                            <td style="padding: 0.85rem 1rem; border-bottom: 1px solid #f1f5f9; color: var(--text-muted);">{{ $item->categoryLabel() }}</td>
                            <td style="padding: 0.85rem 1rem; border-bottom: 1px solid #f1f5f9;">
                                <span style="display: inline-block; background: {{ $tone['bg'] }}; color: {{ $tone['fg'] }}; border: 1px solid {{ $tone['border'] }}; padding: 0.2rem 0.55rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700;">
                                    {{ $item->next_due_on ? $item->next_due_on->format('d M Y') : $tone['label'] }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="margin-top: 1rem;">{{ $items->links() }}</div>
    @endif
@endsection
