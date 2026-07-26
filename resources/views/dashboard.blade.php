@extends('layouts.app')

@section('page_title', 'Dashboard')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
        <div>
            <h1 style="font-size: 1.5rem; color: var(--primary-navy); margin: 0 0 0.25rem;">Dashboard</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">{{ $year }} fiscal snapshot — official invoices only count toward tax liability.</p>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="/ledger/create" style="background: var(--primary-cerulean); color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">+ New Document</a>
            <a href="/clients/create" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">+ Client</a>
            <a href="/ledger?status=open" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">Open balances</a>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.35rem; box-shadow: var(--shadow-sm);">
            <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.85rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.45rem;">Directory</div>
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 0.35rem;">
                <div style="font-size: 0.85rem; color: var(--text-main); font-weight: 600;">Active clients</div>
                <div style="font-size: 1.45rem; font-weight: 700; color: var(--primary-navy);">{{ $clientCount }}</div>
            </div>
            @if($archivedCount > 0)
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.5rem;">{{ $archivedCount }} archived</div>
            @endif
            <a href="/clients" style="font-size: 0.8rem; color: var(--primary-cerulean); text-decoration: none; font-weight: 600;">View directory &rarr;</a>
        </div>

        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.35rem; box-shadow: var(--shadow-sm);">
            <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.85rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.45rem;">{{ $year }} Official</div>
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 0.75rem;">
                <div style="font-size: 0.85rem; color: var(--text-main); font-weight: 600;">Net invoiced</div>
                <div style="font-size: 1.45rem; font-weight: 700; color: #0369a1;">€{{ number_format($ytdNetInvoiced, 2) }}</div>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">
                <span>Invoice cash received</span>
                <span style="font-weight: 600; color: #059669;">€{{ number_format($ytdInvoiceCash, 2) }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--text-muted);">
                <span>Official dues (YTD)</span>
                <span style="font-weight: 600; color: #dc2626;">€{{ number_format($ytdOfficialDues, 2) }}</span>
            </div>
        </div>

        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.35rem; box-shadow: var(--shadow-sm);">
            <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.85rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.45rem;">Collections watch</div>
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 0.75rem;">
                <div style="font-size: 0.85rem; color: var(--text-main); font-weight: 600;">Overdue</div>
                <div style="font-size: 1.45rem; font-weight: 700; color: {{ $overdueCount > 0 ? '#dc2626' : 'var(--primary-navy)' }};">€{{ number_format($overdueTotal, 2) }}</div>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">
                <span>{{ $overdueCount }} overdue · {{ $unpaidCount }} unpaid</span>
                <span style="font-weight: 600;">€{{ number_format($unpaidTotal, 2) }} open</span>
            </div>
            <a href="/ledger?status=open&doc_type=invoice" style="font-size: 0.8rem; color: var(--primary-cerulean); text-decoration: none; font-weight: 600;">Review open invoices &rarr;</a>
        </div>

        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.35rem; box-shadow: var(--shadow-sm);">
            <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.85rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.45rem;">Non-fiscal (RFP)</div>
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 0.75rem;">
                <div style="font-size: 0.85rem; color: var(--text-main); font-weight: 600;">Unbilled pipeline</div>
                <div style="font-size: 1.45rem; font-weight: 700; color: #4338ca;">€{{ number_format($unbilledPipeline, 2) }}</div>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.5rem;">
                <span>RFP cash {{ $year }}</span>
                <span style="font-weight: 600;">€{{ number_format($ytdRfpCash, 2) }}</span>
            </div>
            <p style="margin: 0; font-size: 0.75rem; color: var(--text-muted); line-height: 1.4;">RFP amounts hold €0.00 official fiscal weight until converted to an invoice.</p>
        </div>
    </div>

    @if($clientCount === 0 && $archivedCount === 0)
        <div style="padding: 3rem; border: 2px dashed var(--border-light); background: rgba(255,255,255,0.5); border-radius: var(--radius-md); text-align: center;">
            <h3 style="color: var(--primary-navy); margin-bottom: 0.5rem;">Your dashboard is empty</h3>
            <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Start by adding your first client.</p>
            <a href="/clients/create" style="display: inline-block; background: var(--primary-cerulean); color: white; text-decoration: none; padding: 0.75rem 1.5rem; border-radius: var(--radius-md); font-weight: 600;">
                + Add New Client
            </a>
        </div>
    @else
        <div class="dash-split" style="display: grid; grid-template-columns: 1.4fr 1fr; gap: 1.25rem;">
            <div style="background: white; border-radius: var(--radius-lg); border: 1px solid var(--border-light); box-shadow: var(--shadow-sm); padding: 1.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3 style="color: var(--primary-navy); margin: 0; font-size: 1.05rem;">Open invoices</h3>
                    <a href="/ledger?status=open&doc_type=invoice" style="font-size: 0.8rem; color: var(--primary-cerulean); font-weight: 600; text-decoration: none;">Full ledger</a>
                </div>

                @if($recentOpen->isEmpty())
                    <p style="color: var(--text-muted); margin: 0; font-size: 0.9rem;">No outstanding tax invoices. Nice work.</p>
                @else
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        @foreach($recentOpen as $doc)
                            <div style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.75rem 0; border-bottom: 1px dashed var(--border-light);">
                                <div>
                                    <div style="font-weight: 700; color: var(--primary-navy);">{{ $doc->invoice_number }}</div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted);">
                                        {{ $doc->client->name ?? 'Client' }}
                                        · due {{ $doc->due_date?->format('d M Y') ?? '—' }}
                                        @if($doc->is_overdue)
                                            <span style="color: #dc2626; font-weight: 700;"> · OVERDUE</span>
                                        @endif
                                    </div>
                                </div>
                                <div style="font-weight: 700; color: {{ $doc->is_overdue ? '#dc2626' : 'var(--primary-navy)' }}; white-space: nowrap;">
                                    €{{ number_format($doc->open_balance, 2) }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div style="background: white; border-radius: var(--radius-lg); border: 1px solid var(--border-light); box-shadow: var(--shadow-sm); padding: 1.5rem;">
                <h3 style="color: var(--primary-navy); margin: 0 0 1rem; font-size: 1.05rem;">Quick actions</h3>
                <div style="display: flex; flex-direction: column; gap: 0.65rem;">
                    <a href="/ledger/create" style="padding: 0.75rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.9rem;">Create invoice or RFP</a>
                    <a href="/clients" style="padding: 0.75rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.9rem;">Manage clients</a>
                    @if(auth()->user()->canAccessStandardTools())
                        <a href="/reports" style="padding: 0.75rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.9rem;">Live Fiscal Report</a>
                    @else
                        <a href="/settings" style="padding: 0.75rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--text-muted); font-weight: 600; font-size: 0.9rem;">Upgrade for Live Fiscal Report</a>
                    @endif
                    <a href="/settings" style="padding: 0.75rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.9rem;">Settings &amp; fiscal profile</a>
                </div>
                <p style="margin: 1.25rem 0 0; font-size: 0.75rem; color: var(--text-muted); line-height: 1.4;">
                    Lifetime projected pipeline: €{{ number_format($totalPipeline, 2) }}
                    (official €{{ number_format($netInvoiced, 2) }} · unbilled RFP €{{ number_format($unbilledPipeline, 2) }}).
                </p>
            </div>
        </div>
    @endif

    <style>
        @media (max-width: 800px) {
            .dash-split { grid-template-columns: 1fr !important; }
        }
    </style>
@endsection
