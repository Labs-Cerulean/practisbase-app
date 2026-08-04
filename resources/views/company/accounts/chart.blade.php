@extends('layouts.app')

@section('page_title', 'Chart of accounts')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
        <div>
            <h1 style="font-size: 1.4rem; color: var(--primary-navy); margin: 0 0 0.25rem;">Chart of accounts</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">Cerulean Labs Ltd · GAPSME-oriented · seeded automatically</p>
        </div>
        <a href="/company/accounts" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">← Accounts</a>
    </div>

    <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.25rem 1.4rem; box-shadow: var(--shadow-sm); overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 0.88rem;">
            <thead>
                <tr style="text-align: left; border-bottom: 2px solid var(--border-light); color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.03em;">
                    <th style="padding: 0.5rem 0.35rem;">Code</th>
                    <th style="padding: 0.5rem 0.35rem;">Name</th>
                    <th style="padding: 0.5rem 0.35rem;">Type</th>
                    <th style="padding: 0.5rem 0.35rem;">BS category</th>
                    <th style="padding: 0.5rem 0.35rem;">P&amp;L group</th>
                </tr>
            </thead>
            <tbody>
                @foreach($accounts as $account)
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 0.5rem 0.35rem; font-weight: 700; font-variant-numeric: tabular-nums; color: var(--primary-navy);">{{ $account->account_code }}</td>
                        <td style="padding: 0.5rem 0.35rem;">{{ $account->name }}</td>
                        <td style="padding: 0.5rem 0.35rem; text-transform: capitalize;">{{ $account->type }}</td>
                        <td style="padding: 0.5rem 0.35rem; color: var(--text-muted);">{{ $account->balance_sheet_category ? str_replace('_', ' ', $account->balance_sheet_category) : '—' }}</td>
                        <td style="padding: 0.5rem 0.35rem; color: var(--text-muted);">{{ $account->pl_group ? str_replace('_', ' ', $account->pl_group) : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
