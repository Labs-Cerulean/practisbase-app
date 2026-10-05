<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>VAT return worksheet</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #0f172a; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        h2 { font-size: 13px; margin: 16px 0 6px; }
        .muted { color: #64748b; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        td { padding: 5px 4px; border-bottom: 1px solid #e2e8f0; }
        .num { text-align: right; }
        li { margin-bottom: 4px; }
    </style>
</head>
<body>
    <h1>{{ $profile->legal_name }}</h1>
    <div class="muted">VAT return worksheet · {{ $vatReturn['label'] }}</div>
    <p>
        Period {{ \Carbon\Carbon::parse($vatReturn['from'])->format('d M Y') }}
        – {{ \Carbon\Carbon::parse($vatReturn['to'])->format('d M Y') }}.
        File around {{ \Carbon\Carbon::parse($vatReturn['due'])->format('d M Y') }}.
        @if($vatReturn['first_return']) This is the first return, from incorporation. @endif
        @if($vatReturn['period_open']) The period is still open, so these figures can still change. @endif
    </p>

    <table>
        <tr>
            <td>Taxable supplies (ex VAT)</td>
            <td class="num">€{{ number_format((float) $vatReturn['taxable_supplies'], 2) }}</td>
        </tr>
        <tr>
            <td>Output VAT on those supplies</td>
            <td class="num">€{{ number_format((float) $vatReturn['sales_output_vat'], 2) }}</td>
        </tr>
        <tr>
            <td>Reverse charge purchases (ex VAT)</td>
            <td class="num">€{{ number_format((float) $vatReturn['reverse_charge_net'], 2) }}</td>
        </tr>
        <tr>
            <td>Reverse charge VAT (declared and claimed)</td>
            <td class="num">€{{ number_format((float) $vatReturn['reverse_charge_vat'], 2) }}</td>
        </tr>
        <tr>
            <td>Output VAT</td>
            <td class="num">€{{ number_format((float) $vatReturn['output_vat'], 2) }}</td>
        </tr>
        <tr>
            <td>Input VAT on local purchases</td>
            <td class="num">€{{ number_format((float) $vatReturn['local_input_vat'], 2) }}</td>
        </tr>
        <tr>
            <td>Input VAT</td>
            <td class="num">€{{ number_format((float) $vatReturn['input_vat'], 2) }}</td>
        </tr>
        <tr>
            <td>
                @if($vatReturn['position'] === 'pay')
                    VAT to pay MTCA
                @elseif($vatReturn['position'] === 'reclaim')
                    VAT to reclaim or carry forward
                @else
                    Nil return
                @endif
            </td>
            <td class="num"><strong>€{{ number_format(abs((float) $vatReturn['net']), 2) }}</strong></td>
        </tr>
    </table>

    @if($vatReturn['open_rfp_count'] > 0)
        <p>
            {{ $vatReturn['open_rfp_count'] }} proforma(s) totalling €{{ number_format((float) $vatReturn['open_rfp_total'], 2) }}
            are left off this return.
        </p>
    @endif

    <h2>What to keep</h2>
    <ol>
        @foreach($vatReturn['evidence'] as $line)
            <li>{{ $line }}</li>
        @endforeach
    </ol>
    <p class="muted">
        Figures come from the company books. Confirm the online form and the exact CFR date. Proformas are excluded until they become tax invoices.
    </p>
</body>
</html>
