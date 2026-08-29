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
