@extends('layouts.app')

@section('page_title', 'Customer statement')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
        <div>
            <h1 style="font-size: 1.4rem; color: var(--primary-navy); margin: 0 0 0.25rem;">Customer statement</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">Trade receivables (1100) by client</p>
        </div>
        <a href="/company/accounts" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">← Accounts</a>
    </div>

    <form method="GET" action="/company/accounts/customer-statement" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1rem 1.25rem; box-shadow: var(--shadow-sm); margin-bottom: 1.25rem; display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: end;">
        <div style="min-width: 14rem; flex: 1;">
            <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem;">Client</label>
            <select name="client_id" required style="width: 100%; padding: 0.5rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                <option value="">Select client…</option>
                @foreach($clients as $c)
                    <option value="{{ $c->id }}" @selected($clientId === $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem;">From</label>
            <input type="date" name="from" value="{{ $from }}" style="padding: 0.5rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
        </div>
        <div>
            <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem;">To</label>
            <input type="date" name="to" value="{{ $to }}" max="{{ date('Y-m-d') }}" style="padding: 0.5rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
        </div>
        <button type="submit" style="background: var(--primary-cerulean); color: white; border: none; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; cursor: pointer;">Show</button>
    </form>

    @if($client)
        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.25rem 1.4rem; box-shadow: var(--shadow-sm);">
            <div style="display: flex; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1rem;">
                <div>
                    <div style="font-weight: 700; color: var(--primary-navy); font-size: 1.05rem;">{{ $client->name }}</div>
                    <div style="font-size: 0.8rem; color: var(--text-muted);">{{ \Carbon\Carbon::parse($from)->format('d M Y') }} – {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 0.75rem; color: var(--text-muted);">Closing balance</div>
                    <div style="font-size: 1.35rem; font-weight: 700; color: {{ $closing > 0 ? '#b45309' : '#059669' }};">€{{ number_format($closing, 2) }}</div>
                </div>
            </div>
            <table style="width: 100%; border-collapse: collapse; font-size: 0.88rem;">
                <thead>
                    <tr style="text-align: left; border-bottom: 2px solid var(--border-light); color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase;">
                        <th style="padding: 0.5rem 0.35rem;">Date</th>
                        <th style="padding: 0.5rem 0.35rem;">Reference</th>
                        <th style="padding: 0.5rem 0.35rem; text-align: right;">Debit</th>
                        <th style="padding: 0.5rem 0.35rem; text-align: right;">Credit</th>
                        <th style="padding: 0.5rem 0.35rem; text-align: right;">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 0.45rem 0.35rem; white-space: nowrap;">{{ $row['date']->format('d M Y') }}</td>
                            <td style="padding: 0.45rem 0.35rem;">{{ $row['reference'] }}</td>
                            <td style="padding: 0.45rem 0.35rem; text-align: right; font-variant-numeric: tabular-nums;">{{ $row['debit'] > 0 ? '€'.number_format($row['debit'], 2) : '' }}</td>
                            <td style="padding: 0.45rem 0.35rem; text-align: right; font-variant-numeric: tabular-nums;">{{ $row['credit'] > 0 ? '€'.number_format($row['credit'], 2) : '' }}</td>
                            <td style="padding: 0.45rem 0.35rem; text-align: right; font-variant-numeric: tabular-nums; font-weight: 600;">€{{ number_format($row['balance'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="padding: 1.5rem; text-align: center; color: var(--text-muted);">No AR movements for this client in the period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
@endsection
