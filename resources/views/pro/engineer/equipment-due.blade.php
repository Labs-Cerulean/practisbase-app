@extends('layouts.app')

@section('page_title', 'Equipment due board')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem;">
        <div>
            <a href="/pro/engineer/equipment" style="color: var(--primary-cerulean); font-weight: 600; text-decoration: none; font-size: 0.9rem;">← Equipment</a>
            <h1 style="margin: 0.4rem 0 0.25rem; color: var(--primary-navy); font-size: 1.5rem;">Due board</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.95rem;">Active assets due within the next {{ $days }} days, or already overdue.</p>
        </div>
        <form method="GET" action="/pro/engineer/equipment/due" style="display: flex; gap: 0.5rem; align-items: center;">
            <label style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted);">Window</label>
            <select name="days" onchange="this.form.submit()" style="padding: 0.5rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                @foreach([14, 30, 60, 90] as $option)
                    <option value="{{ $option }}" @selected($days === $option)>{{ $option }} days</option>
                @endforeach
            </select>
        </form>
    </div>

    @if($items->isEmpty())
        <div style="padding: 2.5rem; border: 2px dashed var(--border-light); border-radius: var(--radius-md); text-align: center; background: white; color: var(--text-muted);">
            Nothing due in this window. Nice work.
        </div>
    @else
        <div style="display: grid; gap: 0.65rem;">
            @foreach($items as $item)
                @php $tone = $item->dueTone(); @endphp
                <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); padding: 1rem 1.15rem; display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; align-items: center;">
                    <div>
                        <a href="/pro/engineer/equipment/{{ $item->id }}" style="color: var(--primary-navy); font-weight: 700; text-decoration: none; font-size: 1.05rem;">{{ $item->name }}</a>
                        <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.2rem;">
                            {{ $item->client->name ?? '—' }} · {{ $item->categoryLabel() }} · {{ $item->asset_code }}
                        </div>
                    </div>
                    <div style="display: flex; gap: 0.55rem; flex-wrap: wrap; align-items: center;">
                        <span style="display: inline-block; background: {{ $tone['bg'] }}; color: {{ $tone['fg'] }}; border: 1px solid {{ $tone['border'] }}; padding: 0.25rem 0.65rem; border-radius: 999px; font-size: 0.78rem; font-weight: 700;">
                            {{ $tone['label'] }} · {{ $item->next_due_on->format('d M Y') }}
                        </span>
                        <form method="POST" action="/pro/engineer/equipment/{{ $item->id }}/renew" style="margin: 0;">
                            @csrf
                            <button type="submit" style="background: var(--primary-cerulean); color: white; padding: 0.5rem 0.9rem; border-radius: var(--radius-md); font-weight: 600; border: none; cursor: pointer; font-size: 0.85rem;">Renew / re-inspect</button>
                        </form>
                        <a href="/pro/engineer/equipment/{{ $item->id }}" style="background: white; border: 1px solid var(--border-light); color: var(--primary-navy); padding: 0.5rem 0.9rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none; font-size: 0.85rem;">Open</a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
