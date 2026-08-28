<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Account statement</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #0f172a; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .muted { color: #64748b; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        th { text-align: left; border-bottom: 1px solid #cbd5e1; padding: 6px 4px; font-size: 10px; text-transform: uppercase; color: #64748b; }
        td { padding: 6px 4px; border-bottom: 1px solid #e2e8f0; }
        .num { text-align: right; font-variant-numeric: tabular-nums; }
        .totals { margin-top: 16px; }
        .totals td { border: none; padding: 3px 4px; }
    </style>
</head>
<body>
    <h1>{{ $profile->legal_name }}</h1>
    <div class="muted">Account statement · {{ $client->name }} · as of {{ date('d M Y') }}</div>
    <p class="muted" style="margin-top: 8px; line-height: 1.4;">
        Official balances are tax invoices. Proforma (RFP) balances are requests for payment until converted after settlement.
    </p>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Document</th>
                <th>Type</th>
                <th class="num">Billed</th>
                <th class="num">Paid / credits</th>
                <th class="num">Due</th>
            </tr>
        </thead>
        <tbody>
            @forelse($statement['rows'] as $row)
                <tr>
                    <td>{{ $row['date']->format('d M Y') }}</td>
                    <td>{{ $row['label'] }}</td>
                    <td>{{ $row['kind'] === 'rfp' ? 'Proforma' : 'Tax invoice' }}</td>
                    <td class="num">€{{ number_format((float) $row['billed'], 2) }}</td>
                    <td class="num">€{{ number_format((float) $row['paid'] + (float) $row['credits'], 2) }}</td>
                    <td class="num">€{{ number_format((float) $row['due'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="muted">No open balances.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td>Tax invoices owed</td>
            <td class="num"><strong>€{{ number_format((float) $statement['official_owed'], 2) }}</strong></td>
        </tr>
        <tr>
            <td>Proforma (RFP) owed</td>
            <td class="num"><strong>€{{ number_format((float) $statement['rfp_owed'], 2) }}</strong></td>
        </tr>
        <tr>
            <td>Total open</td>
            <td class="num"><strong>€{{ number_format((float) $statement['total_owed'], 2) }}</strong></td>
        </tr>
    </table>
</body>
</html>
