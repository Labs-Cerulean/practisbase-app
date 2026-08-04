@extends('layouts.app')

@section('page_title', 'Journals')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
        <div>
            <h1 style="font-size: 1.4rem; color: var(--primary-navy); margin: 0 0 0.25rem;">Journal entries</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">Posted double-entry · immutable source keys · latest 200</p>
        </div>
        <a href="/company/accounts" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">← Accounts</a>
    </div>

    <form method="GET" action="/company/accounts/journals" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1rem 1.25rem; box-shadow: var(--shadow-sm); margin-bottom: 1.25rem; display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: end;">
        <div>
            <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem;">From</label>
            <input type="date" name="from" value="{{ request('from') }}" style="padding: 0.5rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
        </div>
        <div>
            <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem;">To</label>
            <input type="date" name="to" value="{{ request('to') }}" max="{{ date('Y-m-d') }}" style="padding: 0.5rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
        </div>
        <button type="submit" style="background: var(--primary-cerulean); color: white; border: none; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; cursor: pointer;">Filter</button>
    </form>

    <div style="display: grid; gap: 0.85rem;">
        @forelse($entries as $entry)
            <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1rem 1.15rem; box-shadow: var(--shadow-sm);">
                <div style="display: flex; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.65rem;">
                    <div>
                        <div style="font-weight: 700; color: var(--primary-navy);">{{ $entry->description }}</div>
                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">
                            {{ $entry->entry_date->format('d M Y') }}
                            · {{ $entry->status }}
                            @if($entry->source_type) · {{ $entry->source_type }} #{{ $entry->source_id }} @endif
                            @if($entry->source_key) · <span style="font-family: ui-monospace, monospace; font-size: 0.75rem;">{{ $entry->source_key }}</span> @endif
                        </div>
                    </div>
                </div>
                <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                    <thead>
                        <tr style="text-align: left; color: var(--text-muted); font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.03em; border-bottom: 1px solid var(--border-light);">
                            <th style="padding: 0.35rem 0.25rem;">Account</th>
                            <th style="padding: 0.35rem 0.25rem; text-align: right;">Debit</th>
                            <th style="padding: 0.35rem 0.25rem; text-align: right;">Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($entry->lines as $line)
                            <tr style="border-bottom: 1px solid #f8fafc;">
                                <td style="padding: 0.4rem 0.25rem;">
                                    <span style="font-weight: 600; color: var(--primary-navy);">{{ $line->account->account_code ?? '—' }}</span>
                                    {{ $line->account->name ?? '' }}
                                    @if($line->memo)<span style="color: var(--text-muted);"> · {{ $line->memo }}</span>@endif
                                </td>
                                <td style="padding: 0.4rem 0.25rem; text-align: right; font-variant-numeric: tabular-nums;">
                                    {{ $line->side === 'debit' ? '€'.number_format((float) $line->amount, 2) : '' }}
                                </td>
                                <td style="padding: 0.4rem 0.25rem; text-align: right; font-variant-numeric: tabular-nums;">
                                    {{ $line->side === 'credit' ? '€'.number_format((float) $line->amount, 2) : '' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @empty
            <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; text-align: center; color: var(--text-muted);">
                No journal entries yet.
            </div>
        @endforelse
    </div>
@endsection
