{{-- Shared money desk: deadlines, glance, billing KPIs, open invoices. --}}
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
            <a href="/reports" style="font-size: 0.8rem; font-weight: 600; color: var(--primary-cerulean); text-decoration: none;">Open Tax and VAT →</a>
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
                <div style="font-size: 0.75rem; color: var(--text-muted);">Income tax still to set aside</div>
                <div style="font-size: 1.35rem; font-weight: 700; color: {{ $glance['tax_only_set_aside'] > 0 ? '#b45309' : '#059669' }};">€{{ number_format($glance['tax_only_set_aside'], 2) }}</div>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">SSC still to set aside</div>
                <div style="font-size: 1.35rem; font-weight: 700; color: {{ $glance['ssc_set_aside'] > 0 ? '#b45309' : '#059669' }};">€{{ number_format($glance['ssc_set_aside'], 2) }}</div>
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
            Live draft from your invoices and expenses. Open Tax and VAT for the full breakdown.
            @if(!empty($glance['ssc_minimum_band']))
                The SSC figure is the Class 2 minimum band (weekly rate × 52), which can apply even at €0 profit. If your maximum SSC is already paid through primary employment, tick that in Settings → tax setup.
            @endif
        </p>
    </div>
@endif

@if($practiceOnly && $billing)
    <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.15rem 1.35rem; box-shadow: var(--shadow-sm); margin-bottom: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: baseline; gap: 1rem; flex-wrap: wrap; margin-bottom: 0.85rem;">
            <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em;">Free billing</div>
            <a href="/ledger" style="font-size: 0.8rem; font-weight: 600; color: var(--primary-cerulean); text-decoration: none;">Open ledger →</a>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 0.85rem;">
            <div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">Clients</div>
                <div style="font-size: 1.15rem; font-weight: 700; color: var(--primary-navy);">{{ $clientsUsed }}/{{ $clientCap }}</div>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $year }} invoiced</div>
                <div style="font-size: 1.15rem; font-weight: 700; color: #0369a1;">€{{ number_format($billing['ytdNetInvoiced'], 2) }}</div>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">Open invoices</div>
                <div style="font-size: 1.15rem; font-weight: 700; color: {{ $billing['overdueCount'] > 0 ? '#dc2626' : 'var(--primary-navy)' }};">€{{ number_format($billing['unpaidTotal'], 2) }}</div>
            </div>
        </div>
    </div>
@endif

@if(($hasFinancial || $mode === 'free' || $practiceOnly) && $billing && ! $practiceOnly)
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.35rem; box-shadow: var(--shadow-sm);">
            <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.85rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.45rem;">Clients</div>
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 0.35rem;">
                <div style="font-size: 0.85rem; color: var(--text-main); font-weight: 600;">Active</div>
                <div style="font-size: 1.45rem; font-weight: 700; color: var(--primary-navy);">
                    {{ $clientCount }}@if($clientCap !== null)<span style="font-size: 0.9rem; color: var(--text-muted); font-weight: 600;"> / {{ $clientCap }}</span>@endif
                </div>
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
                <div style="font-size: 1.45rem; font-weight: 700; color: #0369a1;">€{{ number_format($billing['ytdNetInvoiced'], 2) }}</div>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">
                <span>Cash on tax invoices</span>
                <span style="font-weight: 600; color: #059669;">€{{ number_format($billing['ytdInvoiceCash'], 2) }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--text-muted);">
                <span>Still owed on tax invoices</span>
                <span style="font-weight: 600; color: #dc2626;">€{{ number_format($billing['ytdOfficialDues'], 2) }}</span>
            </div>
        </div>

        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.35rem; box-shadow: var(--shadow-sm);">
            <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.85rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.45rem;">Collections</div>
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 0.75rem;">
                <div style="font-size: 0.85rem; color: var(--text-main); font-weight: 600;">Overdue</div>
                <div style="font-size: 1.45rem; font-weight: 700; color: {{ $billing['overdueCount'] > 0 ? '#dc2626' : 'var(--primary-navy)' }};">€{{ number_format($billing['overdueTotal'], 2) }}</div>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">
                <span>{{ $billing['overdueCount'] }} overdue · {{ $billing['unpaidCount'] }} unpaid</span>
                <span style="font-weight: 600;">€{{ number_format($billing['unpaidTotal'], 2) }} open</span>
            </div>
            <a href="/ledger?status=open&doc_type=invoice" style="font-size: 0.8rem; color: var(--primary-cerulean); text-decoration: none; font-weight: 600;">Review open invoices →</a>
        </div>

        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.35rem; box-shadow: var(--shadow-sm);">
            <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.85rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.45rem;">Pro-formas (RFP)</div>
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 0.75rem;">
                <div style="font-size: 0.85rem; color: var(--text-main); font-weight: 600;">Not yet invoiced</div>
                <div style="font-size: 1.45rem; font-weight: 700; color: #4338ca;">€{{ number_format($billing['unbilledPipeline'], 2) }}</div>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.5rem;">
                <span>Cash sitting on RFPs {{ $year }}</span>
                <span style="font-weight: 600;">€{{ number_format($billing['ytdRfpCash'], 2) }}</span>
            </div>
            <p style="margin: 0; font-size: 0.75rem; color: var(--text-muted); line-height: 1.4;">RFP cash is real money received, but it does <strong>not</strong> count for tax until you convert the RFP into a tax invoice.</p>
        </div>
    </div>
@endif

@if($billing)
    <div class="dash-split" style="display: grid; grid-template-columns: 1.4fr 1fr; gap: 1.25rem;">
        <div style="background: white; border-radius: var(--radius-lg); border: 1px solid var(--border-light); box-shadow: var(--shadow-sm); padding: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3 style="color: var(--primary-navy); margin: 0; font-size: 1.05rem;">Open invoices</h3>
                <a href="/ledger?status=open&doc_type=invoice" style="font-size: 0.8rem; color: var(--primary-cerulean); font-weight: 600; text-decoration: none;">All invoices</a>
            </div>

            @if($billing['recentOpen']->isEmpty())
                <p style="color: var(--text-muted); margin: 0; font-size: 0.9rem;">Nothing outstanding. Nice work.</p>
            @else
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    @foreach($billing['recentOpen'] as $doc)
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
                @if($hasFinancial)
                    <a href="/reports" style="padding: 0.75rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.9rem;">Tax and VAT report</a>
                    <a href="/expenses" style="padding: 0.75rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.9rem;">Expense ledger</a>
                    <a href="/exports/accountant" style="padding: 0.75rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.9rem;">For accountant</a>
                    <a href="/settings#tax-setup" style="padding: 0.75rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.9rem;">Tax setup</a>
                @else
                    <a href="/settings#plan" style="padding: 0.75rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--text-muted); font-weight: 600; font-size: 0.9rem;">Upgrade for Tax and VAT</a>
                @endif
            </div>
        </div>
    </div>
    <style>
        @media (max-width: 800px) {
            .dash-split { grid-template-columns: 1fr !important; }
        }
    </style>
@endif
