@extends('layouts.app')

@section('page_title', 'Company accounts')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
        <div>
            <h1 style="font-size: 1.4rem; color: var(--primary-navy); margin: 0 0 0.25rem;">Accounts</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">
                {{ $profile->legal_name }} · {{ $accountCount }} GL accounts · {{ $journalCount }} journals
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="/company/accounts/chart" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">Chart of accounts</a>
            <a href="/company/accounts/journals" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">Journals</a>
            <a href="/company/accounts/customer-statement" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">Customer statement</a>
        </div>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; border-radius: var(--radius-lg); padding: 0.85rem 1.1rem; margin-bottom: 1rem; font-size: 0.9rem;">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; border-radius: var(--radius-lg); padding: 0.85rem 1.1rem; margin-bottom: 1rem; font-size: 0.9rem;">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="GET" action="/company/accounts" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1rem 1.25rem; box-shadow: var(--shadow-sm); margin-bottom: 1.25rem; display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: end;">
        <div>
            <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem;">As of</label>
            <input type="date" name="as_of" value="{{ $asOf }}" max="{{ date('Y-m-d') }}" style="padding: 0.5rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
        </div>
        <div>
            <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem;">P&amp;L from</label>
            <input type="date" name="from" value="{{ $from }}" style="padding: 0.5rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
        </div>
        <div>
            <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem;">P&amp;L to</label>
            <input type="date" name="to" value="{{ $to }}" max="{{ date('Y-m-d') }}" style="padding: 0.5rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
        </div>
        <button type="submit" style="background: var(--primary-cerulean); color: white; border: none; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; cursor: pointer;">Refresh</button>
    </form>

    <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.15rem 1.35rem; box-shadow: var(--shadow-sm); margin-bottom: 1.25rem;">
        <div style="display: flex; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem; align-items: center; margin-bottom: 0.75rem;">
            <div>
                <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em;">Books lock</div>
                <div style="font-size: 0.9rem; color: var(--primary-navy); margin-top: 0.2rem;">
                    @if($lock)
                        Locked through <strong>{{ $lock->locked_through->format('d M Y') }}</strong>
                        @if($lock->note) · {{ $lock->note }} @endif
                    @else
                        Not locked — postings allowed for any past date
                    @endif
                </div>
            </div>
            @if($lock)
                <form method="POST" action="/company/accounts/unlock">
                    @csrf
                    <button type="submit" style="background: white; color: #b91c1c; border: 1px solid #fecaca; padding: 0.45rem 0.85rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.8rem; cursor: pointer;">Unlock</button>
                </form>
            @endif
        </div>
        <form method="POST" action="/company/accounts/lock" style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: end;">
            @csrf
            <div>
                <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem;">Lock through</label>
                <input type="date" name="locked_through" value="{{ $lock?->locked_through?->format('Y-m-d') ?? date('Y-m-d', strtotime('last day of previous month')) }}" max="{{ date('Y-m-d') }}" required style="padding: 0.45rem 0.6rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div style="flex: 1; min-width: 12rem;">
                <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem;">Note</label>
                <input type="text" name="note" value="{{ $lock?->note }}" placeholder="e.g. VAT period closed" style="width: 100%; padding: 0.45rem 0.6rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <button type="submit" style="background: var(--primary-navy); color: white; border: none; padding: 0.5rem 0.9rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.8rem; cursor: pointer;">Set lock</button>
        </form>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.15rem 1.35rem; box-shadow: var(--shadow-sm);">
            <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.5rem;">Net profit</div>
            <div style="font-size: 1.6rem; font-weight: 700; color: {{ $profitAndLoss['net_profit'] >= 0 ? '#059669' : '#b91c1c' }};">€{{ number_format($profitAndLoss['net_profit'], 2) }}</div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.35rem;">{{ \Carbon\Carbon::parse($from)->format('d M Y') }} – {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</div>
        </div>
        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.15rem 1.35rem; box-shadow: var(--shadow-sm);">
            <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.5rem;">Balance sheet</div>
            <div style="font-size: 1.35rem; font-weight: 700; color: {{ $balanceSheet['balanced'] ? '#059669' : '#b91c1c' }};">
                {{ $balanceSheet['balanced'] ? 'Balanced' : 'Out by €'.number_format(abs($balanceSheet['difference']), 2) }}
            </div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.35rem;">Assets €{{ number_format($balanceSheet['totals']['assets'], 2) }} · Equity+liab €{{ number_format($balanceSheet['totals']['equity_and_liabilities'], 2) }}</div>
        </div>
        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.15rem 1.35rem; box-shadow: var(--shadow-sm);">
            <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.5rem;">Trial balance</div>
            <div style="font-size: 1.35rem; font-weight: 700; color: {{ $trialBalance['balanced'] ? '#059669' : '#b91c1c' }};">
                {{ $trialBalance['balanced'] ? 'Balanced' : 'Unbalanced' }}
            </div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.35rem;">Dr €{{ number_format($trialBalance['total_debit'], 2) }} · Cr €{{ number_format($trialBalance['total_credit'], 2) }}</div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.25rem;">
        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.25rem 1.4rem; box-shadow: var(--shadow-sm);">
            <h2 style="font-size: 1.05rem; color: var(--primary-navy); margin: 0 0 1rem;">Profit &amp; loss</h2>
            @php
                $plSections = [
                    ['label' => 'Revenue', 'rows' => $profitAndLoss['revenue'], 'total' => $profitAndLoss['revenue_total']],
                    ['label' => 'Cost of sales', 'rows' => $profitAndLoss['cost_of_sales'], 'total' => $profitAndLoss['cost_of_sales_total']],
                    ['label' => 'Operating expenses', 'rows' => $profitAndLoss['operating'], 'total' => $profitAndLoss['operating_total']],
                    ['label' => 'Tax', 'rows' => $profitAndLoss['tax'], 'total' => $profitAndLoss['tax_total']],
                ];
            @endphp
            @foreach($plSections as $section)
                @if(count($section['rows']) || abs($section['total']) >= 0.005)
                    <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.03em; margin: 0.85rem 0 0.4rem;">{{ $section['label'] }}</div>
                    @foreach($section['rows'] as $row)
                        <div style="display: flex; justify-content: space-between; gap: 1rem; font-size: 0.88rem; padding: 0.25rem 0; border-bottom: 1px solid #f1f5f9;">
                            <span style="color: var(--primary-navy);">{{ $row['account_code'] }} · {{ $row['name'] }}</span>
                            <span style="font-variant-numeric: tabular-nums; white-space: nowrap;">€{{ number_format($row['amount'], 2) }}</span>
                        </div>
                    @endforeach
                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; font-weight: 700; padding: 0.35rem 0; color: var(--primary-navy);">
                        <span>Total</span>
                        <span>€{{ number_format($section['total'], 2) }}</span>
                    </div>
                @endif
            @endforeach
            <div style="margin-top: 1rem; padding-top: 0.85rem; border-top: 2px solid var(--primary-navy); display: flex; justify-content: space-between; font-weight: 700; color: var(--primary-navy);">
                <span>Gross profit</span>
                <span>€{{ number_format($profitAndLoss['gross_profit'], 2) }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; font-weight: 700; margin-top: 0.35rem; color: var(--primary-navy);">
                <span>Profit before tax</span>
                <span>€{{ number_format($profitAndLoss['profit_before_tax'], 2) }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; font-weight: 700; margin-top: 0.35rem; font-size: 1.05rem; color: {{ $profitAndLoss['net_profit'] >= 0 ? '#059669' : '#b91c1c' }};">
                <span>Net profit</span>
                <span>€{{ number_format($profitAndLoss['net_profit'], 2) }}</span>
            </div>
        </div>

        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.25rem 1.4rem; box-shadow: var(--shadow-sm);">
            <h2 style="font-size: 1.05rem; color: var(--primary-navy); margin: 0 0 1rem;">Balance sheet · {{ \Carbon\Carbon::parse($asOf)->format('d M Y') }}</h2>
            @php
                $bsLabels = [
                    'non_current_assets' => 'Non-current assets',
                    'current_assets' => 'Current assets',
                    'capital_reserves' => 'Capital & reserves',
                    'non_current_liabilities' => 'Non-current liabilities',
                    'current_liabilities' => 'Current liabilities',
                ];
            @endphp
            @foreach($bsLabels as $key => $label)
                @php $rows = $balanceSheet['groups'][$key] ?? []; @endphp
                @if(count($rows) || abs($balanceSheet['totals'][$key] ?? 0) >= 0.005)
                    <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.03em; margin: 0.85rem 0 0.4rem;">{{ $label }}</div>
                    @foreach($rows as $row)
                        <div style="display: flex; justify-content: space-between; gap: 1rem; font-size: 0.88rem; padding: 0.25rem 0; border-bottom: 1px solid #f1f5f9;">
                            <span style="color: var(--primary-navy);">{{ $row['account_code'] }} · {{ $row['name'] }}</span>
                            <span style="font-variant-numeric: tabular-nums; white-space: nowrap;">€{{ number_format($row['amount'], 2) }}</span>
                        </div>
                    @endforeach
                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; font-weight: 700; padding: 0.35rem 0; color: var(--primary-navy);">
                        <span>Total</span>
                        <span>€{{ number_format($balanceSheet['totals'][$key], 2) }}</span>
                    </div>
                @endif
            @endforeach
            <div style="margin-top: 1rem; padding-top: 0.85rem; border-top: 2px solid var(--primary-navy); display: flex; justify-content: space-between; font-weight: 700;">
                <span>Total assets</span>
                <span>€{{ number_format($balanceSheet['totals']['assets'], 2) }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; font-weight: 700; margin-top: 0.35rem;">
                <span>Equity &amp; liabilities</span>
                <span>€{{ number_format($balanceSheet['totals']['equity_and_liabilities'], 2) }}</span>
            </div>
        </div>
    </div>

    <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.25rem 1.4rem; box-shadow: var(--shadow-sm); margin-top: 1.25rem;">
        <h2 style="font-size: 1.05rem; color: var(--primary-navy); margin: 0 0 1rem;">Trial balance · {{ \Carbon\Carbon::parse($asOf)->format('d M Y') }}</h2>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.88rem;">
                <thead>
                    <tr style="text-align: left; border-bottom: 2px solid var(--border-light); color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.03em;">
                        <th style="padding: 0.5rem 0.35rem;">Code</th>
                        <th style="padding: 0.5rem 0.35rem;">Account</th>
                        <th style="padding: 0.5rem 0.35rem; text-align: right;">Debit</th>
                        <th style="padding: 0.5rem 0.35rem; text-align: right;">Credit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($trialBalance['rows'] as $row)
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 0.45rem 0.35rem; font-variant-numeric: tabular-nums;">{{ $row['account_code'] }}</td>
                            <td style="padding: 0.45rem 0.35rem;">{{ $row['name'] }}</td>
                            <td style="padding: 0.45rem 0.35rem; text-align: right; font-variant-numeric: tabular-nums;">{{ $row['debit'] > 0 ? '€'.number_format($row['debit'], 2) : '—' }}</td>
                            <td style="padding: 0.45rem 0.35rem; text-align: right; font-variant-numeric: tabular-nums;">{{ $row['credit'] > 0 ? '€'.number_format($row['credit'], 2) : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="padding: 1.5rem; text-align: center; color: var(--text-muted);">No posted balances yet. Issue an invoice, log capital, or post an expense.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($trialBalance['rows']))
                    <tfoot>
                        <tr style="font-weight: 700; border-top: 2px solid var(--primary-navy);">
                            <td colspan="2" style="padding: 0.65rem 0.35rem;">Totals</td>
                            <td style="padding: 0.65rem 0.35rem; text-align: right;">€{{ number_format($trialBalance['total_debit'], 2) }}</td>
                            <td style="padding: 0.65rem 0.35rem; text-align: right;">€{{ number_format($trialBalance['total_credit'], 2) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
@endsection
