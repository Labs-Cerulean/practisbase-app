<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $document->invoice_number }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; color: #334155; font-size: 14px; margin: 0; padding: 20px; }
        .header { width: 100%; margin-bottom: 40px; }
        .header td { vertical-align: top; }
        .doc-title { font-size: 28px; font-weight: bold; color: #0284c7; text-transform: uppercase; margin-bottom: 15px; }
        .provider-details, .client-details { line-height: 1.5; }
        .client-details { text-align: right; }
        
        .meta-table { width: 100%; margin-bottom: 30px; border-collapse: collapse; background: #f8fafc; }
        .meta-table td { padding: 12px; border: 1px solid #e2e8f0; }
        
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .items-table th { background: #0f172a; color: white; padding: 12px; text-align: left; font-size: 13px; text-transform: uppercase; }
        .items-table td { padding: 12px; border-bottom: 1px solid #e2e8f0; }
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        
        .totals-table { width: 100%; border-collapse: collapse; }
        .totals-table td { padding: 8px 12px; text-align: right; }
        .totals-table .grand-total { font-size: 18px; font-weight: bold; color: #0f172a; border-top: 2px solid #cbd5e1; padding-top: 15px; }
        
        .footer-notes { margin-top: 50px; padding-top: 20px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b; line-height: 1.5; }
    </style>
</head>
<body>

    <table class="header">
        <tr>
            <td style="width: 50%;">
                <div class="doc-title">
                    @if($document->type === 'invoice') TAX INVOICE
                    @elseif($document->type === 'rfp') REQUEST FOR PAYMENT
                    @elseif($document->type === 'credit_note') CREDIT NOTE
                    @endif
                </div>
                <div class="provider-details">
                    <strong>{{ $user->name }}</strong><br>
                    {{ $user->profession }}<br>
                    @if($user->warrant_number) Warrant No: {{ $user->warrant_number }}<br> @endif
                    @if($user->vat_number) VAT No: {{ $user->vat_number }} @endif
                </div>
            </td>
            <td style="width: 50%;" class="client-details">
                <div style="color: #64748b; font-size: 12px; text-transform: uppercase; font-weight: bold; margin-bottom: 5px;">Billed To</div>
                <strong>{{ $document->client->name }}</strong><br>
                {!! nl2br(e($document->client->billing_address ?? '')) !!}<br>
                @if(isset($document->client->profile_data['vat_number']) && $document->client->profile_data['vat_number'])
                    VAT No: {{ $document->client->profile_data['vat_number'] }}
                @endif
            </td>
        </tr>
    </table>

    <table class="meta-table">
        <tr>
            <td><strong>Document No:</strong><br>{{ $document->invoice_number }}</td>
            <td><strong>Issue Date:</strong><br>{{ $document->issue_date->format('d M Y') }}</td>
            @if($document->type !== 'credit_note')
                <td><strong>Due Date:</strong><br>{{ $document->due_date->format('d M Y') }}</td>
            @endif
            <td><strong>Status:</strong><br>{{ strtoupper($document->status) }}</td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 50%;">Description</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($document->items as $item)
            <tr>
                <td>{{ $item['description'] }}</td>
                <td class="text-center">{{ $item['quantity'] }}</td>
                <td class="text-right">€{{ number_format($item['unit_price'], 2) }}</td>
                <td class="text-right">€{{ number_format($item['row_total'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td style="width: 60%;"></td>
            <td style="width: 20%; color: #64748b;">Subtotal:</td>
            <td style="width: 20%;">€{{ number_format($document->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td></td>
            <td style="color: #64748b;">VAT (18%):</td>
            <td>€{{ number_format($document->vat_total, 2) }}</td>
        </tr>
        <tr>
            <td></td>
            <td class="grand-total">Total:</td>
            <td class="grand-total">€{{ number_format($document->total, 2) }}</td>
        </tr>
    </table>

    @if($document->notes)
    <div class="footer-notes">
        <strong>Notes & Information:</strong><br>
        {!! nl2br(e($document->notes)) !!}
    </div>
    @endif

</body>
</html>