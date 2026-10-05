@extends('layouts.app')

@section('page_title', 'Compliance calendar')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
        <div>
            <div style="font-size: 0.75rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.25rem;">{{ $profile->legal_name }}</div>
            <h1 style="font-size: 1.45rem; color: var(--primary-navy); margin: 0 0 0.25rem;">Compliance calendar</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem; line-height: 1.45; max-width: 40rem;">
                Year-1 aware: provisional tax with no prior-year base, incorporation-month MBR, and year-end cutoff are softened or deferred.
                Real VAT and tax-return filings stay on the alarm list. Dates are advisory — confirm with your accountant / CFR / MBR.
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
            <a href="/company/compliance?year={{ $year - 1 }}" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.5rem 0.85rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">← {{ $year - 1 }}</a>
            <span style="font-weight: 700; color: var(--primary-navy); padding: 0 0.35rem;">{{ $year }}</span>
            <a href="/company/compliance?year={{ $year + 1 }}" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.5rem 0.85rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">{{ $year + 1 }} →</a>
            <a href="/company" style="color: var(--text-muted); font-weight: 600; font-size: 0.85rem; text-decoration: none; margin-left: 0.5rem;">Desk</a>
        </div>
    </div>

    @if($vatReturn)
        @php
            $positionLabel = match ($vatReturn['position']) {
                'pay' => 'VAT to pay MTCA',
                'reclaim' => 'VAT to reclaim or carry forward',
                default => 'Nil return',
            };
            $positionColor = match ($vatReturn['position']) {
                'pay' => '#b45309',
                'reclaim' => '#1d4ed8',
                default => '#059669',
            };
        @endphp
        <div id="vat-return" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.25rem 1.35rem; box-shadow: var(--shadow-sm); margin-bottom: 1.25rem;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 0.85rem;">
                <div>
                    <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em;">Next VAT return</div>
                    <h2 style="margin: 0.2rem 0 0.25rem; font-size: 1.2rem; color: var(--primary-navy);">{{ $vatReturn['label'] }}</h2>
                    <p style="margin: 0; color: var(--text-muted); font-size: 0.85rem; line-height: 1.45; max-width: 40rem;">
                        Period {{ \Illuminate\Support\Carbon::parse($vatReturn['from'])->format('d M Y') }} – {{ \Illuminate\Support\Carbon::parse($vatReturn['to'])->format('d M Y') }}.
                        File around {{ \Illuminate\Support\Carbon::parse($vatReturn['due'])->format('d M Y') }}.
                        @if($vatReturn['first_return']) This is the first return, from incorporation. @endif
                        @if($vatReturn['period_open']) The period is still open, so these figures can still change. @endif
                    </p>
                </div>
                <a href="/company/compliance/vat.pdf" style="background: var(--primary-cerulean); color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">Download PDF</a>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 0.75rem; margin-bottom: 1rem;">
                <div style="padding: 0.75rem 0.85rem; background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Taxable supplies</div>
                    <div style="font-size: 1.15rem; font-weight: 700; color: var(--primary-navy);">€{{ number_format($vatReturn['taxable_supplies'], 2) }}</div>
                    <div style="font-size: 0.72rem; color: var(--text-muted);">{{ $vatReturn['sales_count'] }} tax invoices, {{ $vatReturn['credit_count'] }} credit notes</div>
                </div>
                <div style="padding: 0.75rem 0.85rem; background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Output VAT</div>
                    <div style="font-size: 1.15rem; font-weight: 700; color: var(--primary-navy); border-bottom: 1px dotted var(--primary-navy); display: inline-block;">€{{ number_format($vatReturn['output_vat'], 2) }}</div>
                    <div style="font-size: 0.72rem; color: var(--text-muted);">Sales €{{ number_format($vatReturn['sales_output_vat'], 2) }} + reverse charge €{{ number_format($vatReturn['reverse_charge_vat'], 2) }}</div>
                </div>
                <div style="padding: 0.75rem 0.85rem; background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Input VAT</div>
                    <div style="font-size: 1.15rem; font-weight: 700; color: var(--primary-navy); border-bottom: 1px dotted var(--primary-navy); display: inline-block;">€{{ number_format($vatReturn['input_vat'], 2) }}</div>
                    <div style="font-size: 0.72rem; color: var(--text-muted);">Local €{{ number_format($vatReturn['local_input_vat'], 2) }} + reverse charge €{{ number_format($vatReturn['reverse_charge_vat'], 2) }}</div>
                </div>
                <div style="padding: 0.75rem 0.85rem; background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">{{ $positionLabel }}</div>
                    <div style="font-size: 1.15rem; font-weight: 700; color: {{ $positionColor }}; border-bottom: 1px dotted {{ $positionColor }}; display: inline-block;">€{{ number_format(abs($vatReturn['net']), 2) }}</div>
                </div>
            </div>

            @if($vatReturn['reverse_charge_net'] > 0.009)
                <p style="margin: 0 0 0.75rem; font-size: 0.82rem; color: var(--text-muted); line-height: 1.45;">
                    Reverse charge purchases in the period: €{{ number_format($vatReturn['reverse_charge_net'], 2) }} ex VAT.
                    That VAT is added to what you declare and to what you claim, so it does not change the amount you pay unless some of it is not deductible.
                </p>
            @endif
            @if($vatReturn['open_rfp_count'] > 0)
                <p style="margin: 0 0 0.75rem; font-size: 0.82rem; color: #9a3412; line-height: 1.45;">
                    {{ $vatReturn['open_rfp_count'] }} proforma(s) in this period, totalling €{{ number_format($vatReturn['open_rfp_total'], 2) }}, are left off the return. VAT starts when a proforma becomes a tax invoice.
                </p>
            @endif

            <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.4rem;">What to keep for the filing</div>
            <ol style="margin: 0 0 0.75rem; padding-left: 1.15rem; color: var(--primary-navy); font-size: 0.85rem; line-height: 1.45;">
                @foreach($vatReturn['evidence'] as $line)
                    <li style="margin-bottom: 0.35rem;">{{ $line }}</li>
                @endforeach
            </ol>
            <p style="margin: 0; font-size: 0.75rem; color: var(--text-muted); line-height: 1.4;">
                These figures are from your company books. Confirm the online form with your accountant. The due date is the usual CFR window, not a substitute for the date MTCA shows you.
            </p>
        </div>
    @elseif(! $profile->isArticle10())
        <div style="background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1rem 1.25rem; margin-bottom: 1.25rem; font-size: 0.85rem; color: var(--text-muted); line-height: 1.45;">
            No VAT return while the company is Article 11. Output VAT starts only if you register under Article 10. Keep an eye on billed revenue against the €35,000 threshold.
        </div>
    @endif

    @php
        $catColors = [
            'vat' => ['bg' => '#eff6ff', 'fg' => '#1e40af', 'border' => '#bfdbfe'],
            'tax' => ['bg' => '#fff7ed', 'fg' => '#9a3412', 'border' => '#fed7aa'],
            'books' => ['bg' => '#f0fdf4', 'fg' => '#166534', 'border' => '#bbf7d0'],
            'corporate' => ['bg' => '#f5f3ff', 'fg' => '#5b21b6', 'border' => '#ddd6fe'],
        ];
    @endphp

    @if(count($upcoming))
        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.15rem 1.35rem; box-shadow: var(--shadow-sm); margin-bottom: 1.25rem;">
            <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.85rem;">Next ~60 days</div>
            <div style="display: grid; gap: 0.55rem;">
                @foreach($upcoming as $item)
                    @php $c = $catColors[$item['category']] ?? $catColors['books']; @endphp
                    <a href="{{ $item['href'] }}" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; text-decoration: none; padding: 0.7rem 0.85rem; border-radius: var(--radius-md); background: {{ $item['overdue'] ? '#fef2f2' : ($item['urgent'] ? $c['bg'] : '#f8fafc') }}; border: 1px solid {{ $item['overdue'] ? '#fecaca' : ($item['urgent'] ? $c['border'] : 'var(--border-light)') }};">
                        <div>
                            <div style="font-weight: 700; color: {{ $item['overdue'] ? '#991b1b' : 'var(--primary-navy)' }}; font-size: 0.95rem;">{{ $item['label'] }}</div>
                            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem; line-height: 1.4;">{{ $item['hint'] }}</div>
                        </div>
                        <div style="text-align: right; flex-shrink: 0;">
                            <div style="font-weight: 700; font-variant-numeric: tabular-nums; color: {{ $item['overdue'] ? '#991b1b' : $c['fg'] }};">{{ \Illuminate\Support\Carbon::parse($item['due'])->format('d M Y') }}</div>
                            <div style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; color: {{ $c['fg'] }}; margin-top: 0.2rem;">{{ $item['category'] }}{{ $item['overdue'] ? ' · overdue' : ($item['urgent'] ? ' · due soon' : '') }}</div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.15rem 1.35rem; box-shadow: var(--shadow-sm);">
        <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.85rem;">Full {{ $year }} calendar</div>
        @if(empty($events))
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">No compliance items for this year (e.g. Article 11 — no VAT returns).</p>
        @else
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 1px solid var(--border-light); color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.03em;">
                            <th style="padding: 0.5rem 0.4rem; font-weight: 700;">Due</th>
                            <th style="padding: 0.5rem 0.4rem; font-weight: 700;">Item</th>
                            <th style="padding: 0.5rem 0.4rem; font-weight: 700;">Type</th>
                            <th style="padding: 0.5rem 0.4rem; font-weight: 700;">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($events as $item)
                            @php
                                $c = $catColors[$item['category']] ?? $catColors['books'];
                                $severity = $item['severity'] ?? 'filing';
                                $isNote = $severity === 'note';
                                $isInfo = $severity === 'info';
                                $rowBg = $item['overdue'] ? '#fef2f2;' : (($isNote || $isInfo) ? 'background:#f8fafc;' : '');
                            @endphp
                            <tr style="border-bottom: 1px solid var(--border-light); {{ $rowBg }}">
                                <td style="padding: 0.65rem 0.4rem; font-weight: 700; font-variant-numeric: tabular-nums; white-space: nowrap; color: {{ $item['overdue'] ? '#991b1b' : (($isNote || $isInfo) ? 'var(--text-muted)' : 'var(--primary-navy)') }};">
                                    {{ \Illuminate\Support\Carbon::parse($item['due'])->format('d M Y') }}
                                </td>
                                <td style="padding: 0.65rem 0.4rem;">
                                    <a href="{{ $item['href'] }}" style="color: {{ ($isNote || $isInfo) ? 'var(--text-muted)' : 'var(--primary-navy)' }}; font-weight: 600; text-decoration: none; border-bottom: 1px dotted {{ ($isNote || $isInfo) ? 'var(--text-muted)' : 'var(--primary-navy)' }};">{{ $item['label'] }}</a>
                                </td>
                                <td style="padding: 0.65rem 0.4rem;">
                                    <span style="display: inline-block; padding: 0.15rem 0.45rem; border-radius: 4px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; background: {{ $c['bg'] }}; color: {{ $c['fg'] }}; border: 1px solid {{ $c['border'] }};">{{ $item['category'] }}</span>
                                    @if($isNote)
                                        <span style="display: inline-block; margin-left: 0.25rem; padding: 0.15rem 0.45rem; border-radius: 4px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0;">Year 1</span>
                                    @elseif($isInfo)
                                        <span style="display: inline-block; margin-left: 0.25rem; padding: 0.15rem 0.45rem; border-radius: 4px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0;">Info</span>
                                    @endif
                                </td>
                                <td style="padding: 0.65rem 0.4rem; color: var(--text-muted); font-size: 0.82rem; line-height: 1.4;">{{ $item['hint'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <p style="margin: 1rem 0 0; font-size: 0.75rem; color: var(--text-muted); line-height: 1.4;">
            Billing tip: company sales stay as proforma (RFP) until paid — output VAT only lands when the RFP converts to a tax invoice.
        </p>
    </div>
@endsection
