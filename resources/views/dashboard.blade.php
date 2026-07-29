@extends('layouts.app')

@section('page_title', 'Overview')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
        <div>
            <h1 style="font-size: 1.5rem; color: var(--primary-navy); margin: 0 0 0.25rem;">Hello{{ $user->name ? ', '.explode(' ', $user->name)[0] : '' }}</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">
                @if($user->isPracticeOnly())
                    Practice tools are on — invoices use the Free financial layer until you add Tax &amp; VAT.
                @else
                    Your {{ $year }} practice at a glance — official invoices only count for tax.
                @endif
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="/ledger/create" style="background: var(--primary-cerulean); color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">+ Invoice / RFP</a>
            <a href="/clients/create" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">+ Client</a>
            @if($user->canAccessReports())
                <a href="/expenses/create" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">+ Expense</a>
            @endif
        </div>
    </div>

    @if($user->isPracticeOnly())
        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: var(--radius-lg); padding: 1.1rem 1.35rem; margin-bottom: 1.25rem; display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; align-items: center;">
            <div>
                <div style="font-weight: 700; color: #1e3a8a;">Ready for Tax &amp; VAT?</div>
                <div style="font-size: 0.85rem; color: #1e40af; line-height: 1.45; margin-top: 0.2rem;">
                    Practice keeps Free invoicing ({{ $user->freeClientCap() }} lifetime clients). Full Pro unlocks unlimited clients, expenses, and your Tax &amp; VAT report.
                </div>
            </div>
            <a href="/settings#plan" style="background: var(--primary-cerulean); color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 700; font-size: 0.85rem; text-decoration: none; white-space: nowrap;">Upgrade to Full Pro</a>
        </div>
    @endif

    @if(!empty($deadlines))
        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1.25rem;">
            @foreach($deadlines as $chip)
                <a href="{{ $chip['href'] }}" style="display: inline-flex; align-items: center; gap: 0.45rem; text-decoration: none; background: {{ $chip['urgent'] ? '#fffbeb' : 'white' }}; border: 1px solid {{ $chip['urgent'] ? '#fde68a' : 'var(--border-light)' }}; color: {{ $chip['urgent'] ? '#92400e' : 'var(--primary-navy)' }}; padding: 0.45rem 0.75rem; border-radius: var(--radius-md); font-size: 0.8rem; box-shadow: var(--shadow-sm);">
                    <strong style="font-weight: 700;">{{ $chip['label'] }}</strong>
                    <span style="opacity: 0.85;">{{ $chip['hint'] }}</span>
                </a>
            @endforeach
        </div>
    @endif

    @if($glance)
        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.35rem 1.5rem; box-shadow: var(--shadow-sm); margin-bottom: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: baseline; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem;">
                <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em;">{{ $year }} at a glance</div>
                <a href="/reports" style="font-size: 0.8rem; font-weight: 600; color: var(--primary-cerulean); text-decoration: none;">Open Tax &amp; VAT →</a>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 1rem;">
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted);">Billed (fiscal)</div>
                    <div style="font-size: 1.35rem; font-weight: 700; color: #0369a1;">€{{ number_format($glance['fiscal_revenue'], 2) }}</div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted);">Profit so far</div>
                    <div style="font-size: 1.35rem; font-weight: 700; color: var(--primary-navy);">€{{ number_format($glance['net_profit'], 2) }}</div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted);">Tax &amp; SSC still to set aside</div>
                    <div style="font-size: 1.35rem; font-weight: 700; color: {{ $glance['tax_set_aside'] > 0 ? '#b45309' : '#059669' }};">€{{ number_format($glance['tax_set_aside'], 2) }}</div>
                </div>
                @if($glance['has_article_10'])
                    <div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">VAT balance</div>
                        <div style="font-size: 1.35rem; font-weight: 700; color: {{ $glance['vat_balance'] > 0 ? '#dc2626' : '#059669' }};">
                            {{ $glance['vat_balance'] < 0 ? 'Refund ' : '' }}€{{ number_format(abs($glance['vat_balance']), 2) }}
                        </div>
                    </div>
                @endif
            </div>
            <p style="margin: 0.85rem 0 0; font-size: 0.75rem; color: var(--text-muted); line-height: 1.4;">
                Live draft from your invoices and expenses. Click Tax &amp; VAT for the full breakdown.
            </p>
        </div>
    @endif

    @if(!($checklist['all_done'] ?? true))
        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: var(--radius-lg); padding: 1.25rem 1.5rem; margin-bottom: 1.5rem;">
            <div style="display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin-bottom: 0.75rem;">
                <div>
                    <div style="font-weight: 700; color: #1e3a8a;">Get set this week</div>
                    <div style="font-size: 0.8rem; color: #1e40af;">{{ $checklist['complete'] }} of {{ $checklist['total'] }} done</div>
                </div>
            </div>
            <ul style="list-style: none; margin: 0; padding: 0; display: grid; gap: 0.45rem;">
                @foreach($checklist['items'] as $item)
                    <li>
                        <a href="{{ $item['href'] }}" style="display: flex; align-items: center; gap: 0.65rem; text-decoration: none; color: {{ $item['done'] ? '#64748b' : '#1e3a8a' }}; font-size: 0.9rem; font-weight: {{ $item['done'] ? '500' : '600' }};">
                            <span style="width: 1.15rem; height: 1.15rem; border-radius: 999px; border: 2px solid {{ $item['done'] ? '#86efac' : '#93c5fd' }}; background: {{ $item['done'] ? '#dcfce7' : 'white' }}; display: inline-flex; align-items: center; justify-content: center; font-size: 0.7rem; color: #166534;">{{ $item['done'] ? '✓' : '' }}</span>
                            <span style="{{ $item['done'] ? 'text-decoration: line-through; opacity: 0.75;' : '' }}">{{ $item['label'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.35rem; box-shadow: var(--shadow-sm);">
            <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.85rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.45rem;">Clients</div>
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 0.35rem;">
                <div style="font-size: 0.85rem; color: var(--text-main); font-weight: 600;">Active</div>
                <div style="font-size: 1.45rem; font-weight: 700; color: var(--primary-navy);">{{ $clientCount }}</div>
            </div>
            @if($archivedCount > 0)
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.5rem;">{{ $archivedCount }} archived</div>
            @endif
            <a href="/clients" style="font-size: 0.8rem; color: var(--primary-cerulean); text-decoration: none; font-weight: 600;">View clients →</a>
        </div>

        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.35rem; box-shadow: var(--shadow-sm);">
            <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.85rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.45rem;">{{ $year }} invoiced</div>
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 0.75rem;">
                <div style="font-size: 0.85rem; color: var(--text-main); font-weight: 600;">Net official</div>
                <div style="font-size: 1.45rem; font-weight: 700; color: #0369a1;">€{{ number_format($ytdNetInvoiced, 2) }}</div>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">
                <span>Cash received</span>
                <span style="font-weight: 600; color: #059669;">€{{ number_format($ytdInvoiceCash, 2) }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--text-muted);">
                <span>Still owed to you</span>
                <span style="font-weight: 600; color: #dc2626;">€{{ number_format($ytdOfficialDues, 2) }}</span>
            </div>
        </div>

        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.35rem; box-shadow: var(--shadow-sm);">
            <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.85rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.45rem;">Collections</div>
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 0.75rem;">
                <div style="font-size: 0.85rem; color: var(--text-main); font-weight: 600;">Overdue</div>
                <div style="font-size: 1.45rem; font-weight: 700; color: {{ $overdueCount > 0 ? '#dc2626' : 'var(--primary-navy)' }};">€{{ number_format($overdueTotal, 2) }}</div>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">
                <span>{{ $overdueCount }} overdue · {{ $unpaidCount }} unpaid</span>
                <span style="font-weight: 600;">€{{ number_format($unpaidTotal, 2) }} open</span>
            </div>
            <a href="/ledger?status=open&doc_type=invoice" style="font-size: 0.8rem; color: var(--primary-cerulean); text-decoration: none; font-weight: 600;">Review open invoices →</a>
        </div>

        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.35rem; box-shadow: var(--shadow-sm);">
            <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.85rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.45rem;">Pro-formas (RFP)</div>
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 0.75rem;">
                <div style="font-size: 0.85rem; color: var(--text-main); font-weight: 600;">Not yet invoiced</div>
                <div style="font-size: 1.45rem; font-weight: 700; color: #4338ca;">€{{ number_format($unbilledPipeline, 2) }}</div>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.5rem;">
                <span>RFP cash {{ $year }}</span>
                <span style="font-weight: 600;">€{{ number_format($ytdRfpCash, 2) }}</span>
            </div>
            <p style="margin: 0; font-size: 0.75rem; color: var(--text-muted); line-height: 1.4;">RFPs do not count for tax until you convert them to an official invoice.</p>
        </div>
    </div>

    @if($clientCount === 0 && $archivedCount === 0)
        <div style="padding: 3rem; border: 2px dashed var(--border-light); background: rgba(255,255,255,0.5); border-radius: var(--radius-md); text-align: center;">
            <h3 style="color: var(--primary-navy); margin-bottom: 0.5rem;">Start with a client</h3>
            <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Add who you bill, then create your first invoice or RFP.</p>
            <a href="/clients/create" style="display: inline-block; background: var(--primary-cerulean); color: white; text-decoration: none; padding: 0.75rem 1.5rem; border-radius: var(--radius-md); font-weight: 600;">
                + Add client
            </a>
        </div>
    @else
        <div class="dash-split" style="display: grid; grid-template-columns: 1.4fr 1fr; gap: 1.25rem;">
            <div style="background: white; border-radius: var(--radius-lg); border: 1px solid var(--border-light); box-shadow: var(--shadow-sm); padding: 1.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3 style="color: var(--primary-navy); margin: 0; font-size: 1.05rem;">Open invoices</h3>
                    <a href="/ledger?status=open&doc_type=invoice" style="font-size: 0.8rem; color: var(--primary-cerulean); font-weight: 600; text-decoration: none;">All invoices</a>
                </div>

                @if($recentOpen->isEmpty())
                    <p style="color: var(--text-muted); margin: 0; font-size: 0.9rem;">Nothing outstanding. Nice work.</p>
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
                <h3 style="color: var(--primary-navy); margin: 0 0 1rem; font-size: 1.05rem;">Shortcuts</h3>
                <div style="display: flex; flex-direction: column; gap: 0.65rem;">
                    <a href="/ledger/create" style="padding: 0.75rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.9rem;">Create invoice or RFP</a>
                    <a href="/clients" style="padding: 0.75rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.9rem;">Manage clients</a>
                    @if($user->canAccessReports())
                        <a href="/reports" style="padding: 0.75rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.9rem;">Tax &amp; VAT report</a>
                        <a href="/expenses" style="padding: 0.75rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.9rem;">Expense ledger</a>
                    @else
                        <a href="/settings#plan" style="padding: 0.75rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--text-muted); font-weight: 600; font-size: 0.9rem;">Upgrade for Tax &amp; VAT</a>
                    @endif
                    <a href="/settings#tax-setup" style="padding: 0.75rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.9rem;">Tax setup</a>
                </div>
            </div>
        </div>
    @endif

    <style>
        @media (max-width: 800px) {
            .dash-split { grid-template-columns: 1fr !important; }
        }
    </style>
@endsection
