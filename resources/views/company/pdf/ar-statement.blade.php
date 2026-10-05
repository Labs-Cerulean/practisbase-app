<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Customer statement</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #0f172a; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .muted { color: #64748b; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        th { text-align: left; border-bottom: 1px solid #cbd5e1; padding: 6px 4px; font-size: 10px; text-transform: uppercase; color: #64748b; }
        td { padding: 6px 4px; border-bottom: 1px solid #e2e8f0; }
        .num { text-align: right; }
    </style>
</head>
<body>
    <h1>{{ $profile->legal_name }}</h1>
    <div class="muted">Customer statement · trade receivables · {{ $client->name }}</div>
    <div class="muted">{{ \Carbon\Carbon::parse($from)->format('d M Y') }} – {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Reference</th>
                <th class="num">Debit</th>
                <th class="num">Credit</th>
                <th class="num">Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['date']->format('d M Y') }}</td>
                    <td>{{ $row['reference'] }}</td>
                    <td class="num">{{ $row['debit'] > 0 ? '€'.number_format((float) $row['debit'], 2) : '' }}</td>
                    <td class="num">{{ $row['credit'] > 0 ? '€'.number_format((float) $row['credit'], 2) : '' }}</td>
                    <td class="num">€{{ number_format((float) $row['balance'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="muted">No receivables movements in this period.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p style="margin-top: 14px;">Closing balance <strong>€{{ number_format((float) $closing, 2) }}</strong></p>
</body>
</html>
