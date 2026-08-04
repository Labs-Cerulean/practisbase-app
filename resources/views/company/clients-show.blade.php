@extends('layouts.app')

@section('page_title', $client->name)

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
        <div>
            <a href="/company/clients" style="color: var(--text-muted); font-size: 0.85rem; font-weight: 600; text-decoration: none; margin-bottom: 0.5rem; display: inline-block;">&larr; Company clients</a>
            <h1 style="font-size: 1.5rem; color: var(--primary-navy); margin: 0 0 0.25rem;">{{ $client->name }}</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">
                {{ $client->vat_number ? 'VAT '.$client->vat_number : 'No VAT number' }}
                @if($client->email) · {{ $client->email }} @endif
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="/company/invoices/create" style="background: var(--primary-cerulean); color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">+ Invoice / RFP</a>
            <a href="/company/clients/{{ $client->id }}/edit" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">Edit</a>
        </div>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; border-radius: var(--radius-lg); padding: 0.85rem 1.1rem; margin-bottom: 1rem; font-size: 0.9rem;">{{ session('success') }}</div>
    @endif

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.15rem 1.25rem; box-shadow: var(--shadow-sm);">
            <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.75rem;">Contact</div>
            <div style="font-size: 0.9rem; color: var(--primary-navy); margin-bottom: 0.35rem;">{{ $client->phone ?: 'No phone' }}</div>
            <div style="font-size: 0.9rem; color: var(--text-muted); line-height: 1.4;">{!! nl2br(e($client->billing_address)) !!}</div>
        </div>
        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.15rem 1.25rem; box-shadow: var(--shadow-sm);">
            <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.75rem;">Billing profile</div>
            <div style="font-size: 0.9rem; color: var(--primary-navy); margin-bottom: 0.35rem;">{{ $client->vat_number ? 'VAT '.$client->vat_number : 'VAT not on file' }}</div>
            <div style="font-size: 0.9rem; color: var(--text-muted);">{{ $client->registration_number ? 'Reg '.$client->registration_number : 'No registration number' }}</div>
            @if($client->notes)
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.65rem; line-height: 1.4;">{{ $client->notes }}</div>
            @endif
        </div>
    </div>

    <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: var(--radius-lg); padding: 0.75rem 1rem; margin-bottom: 1.25rem; font-size: 0.8rem; color: #1e3a8a; line-height: 1.45;">
        Cerulean Labs Ltd company books only — separate from your sole-trader practice clients and ledger.
        GL AR statement remains under <a href="/company/accounts/customer-statement?client_id={{ $client->id }}" style="color: #1e3a8a; font-weight: 700;">Accounts → Customer statement</a>.
    </div>

    <div style="background: white; padding: 1.5rem; border-radius: var(--radius-lg); border: 1px solid var(--border-light); box-shadow: var(--shadow-sm); margin-bottom: 2rem;">
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1.25rem; border-bottom: 1px solid var(--border-light); padding-bottom: 0.75rem;">
            <a href="/company/clients/{{ $client->id }}?tab=statement"
               style="padding: 0.45rem 0.9rem; border-radius: 6px; font-size: 0.85rem; font-weight: 600; text-decoration: none; {{ $tab === 'statement' ? 'background: var(--primary-navy); color: white;' : 'background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;' }}">
                Open statement
            </a>
            <a href="/company/clients/{{ $client->id }}?tab=history"
               style="padding: 0.45rem 0.9rem; border-radius: 6px; font-size: 0.85rem; font-weight: 600; text-decoration: none; {{ $tab === 'history' ? 'background: var(--primary-navy); color: white;' : 'background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;' }}">
                Transaction history
            </a>
        </div>

        @if($tab === 'statement')
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <h3 style="color: var(--primary-navy); margin: 0 0 0.35rem; font-size: 1.1rem;">Open statement</h3>
                    <p style="margin: 0; font-size: 0.8rem; color: var(--text-muted); line-height: 1.4;">
                        Only amounts still owed. Fully paid invoices and fully converted RFPs are hidden.
                    </p>
                </div>
                <div style="display: flex; gap: 1.25rem; flex-wrap: wrap;">
                    <div>
                        <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">On tax invoices</div>
                        <div style="font-size: 1.2rem; font-weight: 700; color: {{ $statement['official_owed'] > 0 ? '#dc2626' : '#059669' }};">€{{ number_format($statement['official_owed'], 2) }}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">On RFPs</div>
                        <div style="font-size: 1.2rem; font-weight: 700; color: #4338ca;">€{{ number_format($statement['rfp_owed'], 2) }}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Total due</div>
                        <div style="font-size: 1.2rem; font-weight: 700; color: var(--primary-navy);">€{{ number_format($statement['total_owed'], 2) }}</div>
                    </div>
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 2px solid var(--border-light); color: var(--text-muted); font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.03em;">
                            <th style="padding: 0.5rem 0.35rem;">Date</th>
                            <th style="padding: 0.5rem 0.35rem;">Document</th>
                            <th style="padding: 0.5rem 0.35rem; text-align: right;">Billed</th>
                            <th style="padding: 0.5rem 0.35rem; text-align: right;">Credits</th>
                            <th style="padding: 0.5rem 0.35rem; text-align: right;">Paid</th>
                            <th style="padding: 0.5rem 0.35rem; text-align: right;">Still due</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($statement['rows'] as $row)
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 0.45rem 0.35rem; white-space: nowrap;">{{ $row['date']->format('d M Y') }}</td>
                                <td style="padding: 0.45rem 0.35rem; color: var(--primary-navy); font-weight: 600;">
                                    {{ $row['label'] }}
                                    @if($row['kind'] === 'rfp')
                                        <span style="font-weight: 500; color: #4338ca; font-size: 0.75rem;"> · not tax yet</span>
                                    @endif
                                </td>
                                <td style="padding: 0.45rem 0.35rem; text-align: right; font-variant-numeric: tabular-nums;">€{{ number_format($row['billed'], 2) }}</td>
                                <td style="padding: 0.45rem 0.35rem; text-align: right; font-variant-numeric: tabular-nums;">{{ $row['credits'] > 0 ? '€'.number_format($row['credits'], 2) : '—' }}</td>
                                <td style="padding: 0.45rem 0.35rem; text-align: right; font-variant-numeric: tabular-nums;">€{{ number_format($row['paid'], 2) }}</td>
                                <td style="padding: 0.45rem 0.35rem; text-align: right; font-variant-numeric: tabular-nums; font-weight: 700; color: #dc2626;">€{{ number_format($row['due'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="padding: 1.5rem; text-align: center; color: var(--text-muted);">Nothing outstanding. Check Transaction history for settled activity.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <h3 style="color: var(--primary-navy); margin: 0 0 0.35rem; font-size: 1.1rem;">Transaction history</h3>
                    <p style="margin: 0; font-size: 0.8rem; color: var(--text-muted); line-height: 1.4;">
                        Full record — including paid invoices, converted RFPs, credits, and payments.
                    </p>
                </div>
                <div style="display: flex; gap: 1.25rem; flex-wrap: wrap;">
                    <div>
                        <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Running invoice bal.</div>
                        <div style="font-size: 1.05rem; font-weight: 700; color: var(--primary-navy);">€{{ number_format($history['official_owed'], 2) }}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Running RFP bal.</div>
                        <div style="font-size: 1.05rem; font-weight: 700; color: #4338ca;">€{{ number_format($history['rfp_owed'], 2) }}</div>
                    </div>
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 2px solid var(--border-light); color: var(--text-muted); font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.03em;">
                            <th style="padding: 0.5rem 0.35rem;">Date</th>
                            <th style="padding: 0.5rem 0.35rem;">Event</th>
                            <th style="padding: 0.5rem 0.35rem; text-align: right;">Billed</th>
                            <th style="padding: 0.5rem 0.35rem; text-align: right;">Paid / credit</th>
                            <th style="padding: 0.5rem 0.35rem; text-align: right;">Invoice bal.</th>
                            <th style="padding: 0.5rem 0.35rem; text-align: right;">RFP bal.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($history['rows'] as $row)
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 0.45rem 0.35rem; white-space: nowrap;">{{ $row['date']->format('d M Y') }}</td>
                                <td style="padding: 0.45rem 0.35rem; color: var(--primary-navy);">
                                    {{ $row['label'] }}
                                    @if(!empty($row['note']))
                                        <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.15rem;">{{ $row['note'] }}</div>
                                    @endif
                                </td>
                                <td style="padding: 0.45rem 0.35rem; text-align: right; font-variant-numeric: tabular-nums;">{{ $row['debit'] > 0 ? '€'.number_format($row['debit'], 2) : '' }}</td>
                                <td style="padding: 0.45rem 0.35rem; text-align: right; font-variant-numeric: tabular-nums;">{{ $row['credit'] > 0 ? '€'.number_format($row['credit'], 2) : '' }}</td>
                                <td style="padding: 0.45rem 0.35rem; text-align: right; font-variant-numeric: tabular-nums; font-weight: 600;">€{{ number_format($row['official_balance'], 2) }}</td>
                                <td style="padding: 0.45rem 0.35rem; text-align: right; font-variant-numeric: tabular-nums; color: #4338ca;">€{{ number_format($row['rfp_balance'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="padding: 1.5rem; text-align: center; color: var(--text-muted);">No documents yet for this company client.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        <div style="margin-top: 1rem; display: flex; gap: 1rem; flex-wrap: wrap;">
            <a href="/company/invoices" style="font-size: 0.85rem; font-weight: 600; color: var(--primary-cerulean); text-decoration: none;">Company invoices →</a>
            <a href="/company/accounts/customer-statement?client_id={{ $client->id }}" style="font-size: 0.85rem; font-weight: 600; color: var(--primary-cerulean); text-decoration: none;">GL AR statement →</a>
        </div>
    </div>
@endsection
