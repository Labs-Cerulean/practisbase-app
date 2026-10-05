<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Transaction history</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #0f172a; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .muted { color: #64748b; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        th { text-align: left; border-bottom: 1px solid #cbd5e1; padding: 6px 4px; font-size: 10px; text-transform: uppercase; color: #64748b; }
        td { padding: 6px 4px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        .num { text-align: right; }
        .note { color: #64748b; font-size: 9px; }
    </style>
</head>
<body>
    <h1>{{ $profile->legal_name }}</h1>
    <div class="muted">Transaction history · {{ $client->name }} · printed {{ date('d M Y') }}</div>
    <p class="muted" style="margin-top: 8px; line-height: 1.4;">
        Full record, including paid invoices, converted proformas, credits, and payments.
        Proforma balances are requests for payment until the proforma becomes a tax invoice.
    </p>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Event</th>
                <th class="num">Billed</th>
                <th class="num">Paid / credit</th>
                <th class="num">Invoice bal.</th>
                <th class="num">RFP bal.</th>
            </tr>
        </thead>
        <tbody>
            @forelse($history['rows'] as $row)
                <tr>
                    <td>{{ $row['date']->format('d M Y') }}</td>
                    <td>
                        {{ $row['label'] }}
                        @if(!empty($row['note']))
                            <div class="note">{{ $row['note'] }}</div>
                        @endif
                    </td>
                    <td class="num">{{ $row['debit'] > 0 ? '€'.number_format((float) $row['debit'], 2) : '' }}</td>
                    <td class="num">{{ $row['credit'] > 0 ? '€'.number_format((float) $row['credit'], 2) : '' }}</td>
                    <td class="num">€{{ number_format((float) $row['official_balance'], 2) }}</td>
                    <td class="num">€{{ number_format((float) $row['rfp_balance'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="muted">No documents yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p style="margin-top: 14px;">
        Running tax-invoice balance <strong>€{{ number_format((float) $history['official_owed'], 2) }}</strong>
        · Running proforma balance <strong>€{{ number_format((float) $history['rfp_owed'], 2) }}</strong>
    </p>
</body>
</html>
