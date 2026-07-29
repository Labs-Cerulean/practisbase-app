<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>VAT Period Pack — {{ $vatPeriod['period_label'] }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; color: #334155; font-size: 12px; margin: 0; padding: 28px; }
        h1 { color: #0f172a; font-size: 20px; margin: 0 0 4px; }
        h2 { color: #0f172a; font-size: 13px; margin: 22px 0 8px; text-transform: uppercase; letter-spacing: 0.04em; }
        .muted { color: #64748b; margin-bottom: 18px; line-height: 1.45; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .meta td { padding: 3px 0; vertical-align: top; }
        .meta td:first-child { width: 34%; color: #64748b; }
        table.grid { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.grid th, table.grid td { border: 1px solid #e2e8f0; padding: 9px 11px; text-align: left; }
        table.grid th { background: #f8fafc; width: 55%; font-weight: 600; color: #0f172a; }
        table.grid td { text-align: right; font-weight: 600; color: #0f172a; }
        .note { margin-top: 26px; font-size: 10px; color: #64748b; line-height: 1.5; border-top: 1px solid #e2e8f0; padding-top: 12px; }
        .pill { display: inline-block; background: #f1f5f9; color: #475569; font-size: 10px; padding: 2px 7px; border-radius: 3px; margin-left: 6px; }
    </style>
</head>
<body>
    <h1>VAT Period Pack <span class="pill">PractisBase</span></h1>
    <div class="muted">{{ $vatPeriod['period_label'] }} · Generated {{ $generatedAt->format('d M Y H:i') }}</div>

    <table class="meta">
        <tr><td>Sole trader</td><td><strong>{{ $user->name }}</strong></td></tr>
        <tr><td>Email</td><td>{{ $user->email }}</td></tr>
        <tr><td>VAT number</td><td>{{ $vatPeriod['vat_number'] ?: 'Not on file' }}</td></tr>
        <tr><td>Period</td><td>{{ \Illuminate\Support\Carbon::parse($vatPeriod['from'])->format('d M Y') }} – {{ \Illuminate\Support\Carbon::parse($vatPeriod['to'])->format('d M Y') }}</td></tr>
    </table>

    <h2>VAT summary</h2>
    <table class="grid">
        <tr><th>Official invoices in period</th><td>{{ $vatPeriod['invoice_count'] }}</td></tr>
        <tr><th>Credit notes (on those invoices)</th><td>{{ $vatPeriod['credit_count'] }}</td></tr>
        <tr><th>Net sales (gross billed)</th><td>€{{ number_format($vatPeriod['sales_gross'], 2) }}</td></tr>
        <tr><th>Article 10 sales (ex-VAT)</th><td>€{{ number_format($vatPeriod['art10_sales_subtotal'], 2) }}</td></tr>
        <tr><th>Output VAT</th><td>€{{ number_format($vatPeriod['output_vat'], 2) }}</td></tr>
        <tr><th>Input VAT (reclaimable in Art 10 periods)</th><td>€{{ number_format($vatPeriod['input_vat'], 2) }}</td></tr>
        <tr><th>Net VAT for period</th><td>€{{ number_format($vatPeriod['net_vat'], 2) }}{{ $vatPeriod['net_vat'] < 0 ? ' (reclaim)' : '' }}</td></tr>
        <tr><th>VAT payments logged in period</th><td>€{{ number_format($vatPeriod['vat_paid'], 2) }}</td></tr>
        <tr><th>VAT balance</th><td>€{{ number_format($vatPeriod['vat_balance'], 2) }}{{ $vatPeriod['vat_balance'] < 0 ? ' (refund / overpaid)' : '' }}</td></tr>
    </table>

    <h2>Related costs in period</h2>
    <table class="grid">
        <tr><th>Expense rows logged</th><td>{{ $vatPeriod['expense_count'] }}</td></tr>
        <tr><th>Expense amounts (ex-VAT logged)</th><td>€{{ number_format($vatPeriod['expenses_ex_vat'], 2) }}</td></tr>
        <tr><th>Deductible share (cash / mixed costs)</th><td>€{{ number_format($vatPeriod['deductible_expenses'], 2) }}</td></tr>
        @if(($vatPeriod['wear_and_tear'] ?? 0) > 0.009)
            <tr><th>Wear &amp; tear attributed this period (income tax, not VAT)</th><td>€{{ number_format($vatPeriod['wear_and_tear'], 2) }}</td></tr>
        @endif
        @if(($vatPeriod['non_art10_sales_gross'] ?? 0) > 0.009)
            <tr><th>Non–Article 10 sales in period (excluded from output VAT)</th><td>€{{ number_format($vatPeriod['non_art10_sales_gross'], 2) }}</td></tr>
        @endif
    </table>

    <div class="note">
        Prepared for Maltese sole-trader VAT return prep and your accountant. Figures follow PractisBase dated tax setup (Article 10 periods only for output/input VAT) and expense treatments (including business-use / home-office shares). This is not a CFR e-form and does not replace official filings or professional advice.
        @unless($vatPeriod['show_vat_math'])
            No Article 10 activity was detected in this period — VAT lines may be zero.
        @endunless
    </div>
</body>
</html>
