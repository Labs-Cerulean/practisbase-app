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
        .totals-table .balance-due { font-size: 16px; font-weight: bold; color: #dc2626; border-top: 1px solid #e2e8f0; padding-top: 10px; }
        
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
                    @if($user->logoDataUri())
                        <img src="{{ $user->logoDataUri() }}" alt="Logo" style="max-height: 70px; max-width: 180px; margin-bottom: 10px;"><br>
                    @endif
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
            
            @if($document->parentDocument)
                <td><strong>Reference:</strong><br>{{ $document->parentDocument->invoice_number }}</td>
            @endif
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
                <td class="text-right">€{{ number_format($item['price'] ?? $item['unit_price'], 2) }}</td>
                <td class="text-right">€{{ number_format($item['amount'] ?? $item['row_total'], 2) }}</td>
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
            <td style="color: #64748b;">
                @if((float) $document->vat_total > 0)
                    VAT (18%):
                @elseif(($user->vat_status ?? '') === 'article_11')
                    VAT (Art. 11 exempt):
                @else
                    VAT:
                @endif
            </td>
            <td>€{{ number_format($document->vat_total, 2) }}</td>
        </tr>
        <tr>
            <td></td>
            <td class="grand-total">Total:</td>
            <td class="grand-total">€{{ number_format($document->total, 2) }}</td>
        </tr>
        
        @if(in_array($document->type, ['invoice', 'rfp']) && $document->amount_paid > 0)
        <tr>
            <td></td>
            <td style="color: #64748b; padding-top: 15px;">Less Amount Paid:</td>
            <td style="padding-top: 15px; color: #10b981;">- €{{ number_format($document->amount_paid, 2) }}</td>
        </tr>
        <tr>
            <td></td>
            <td class="balance-due">Balance Due:</td>
            <td class="balance-due">€{{ number_format($document->total - $document->amount_paid, 2) }}</td>
        </tr>
        @endif
    </table>

    @if($document->notes)
    <div class="footer-notes" style="margin-bottom: 20px;">
        <strong>Document Notes:</strong><br>
        {!! nl2br(e($document->notes)) !!}
    </div>
    @endif

    @php
        $pm = $user->payment_methods ?? [];
    @endphp

    @if(!empty($pm) && $document->type !== 'credit_note')
    <div class="footer-notes" style="border-top: none; margin-top: 0; padding-top: 0; padding-bottom: 20px; color: #334155;">
        <strong style="font-size: 13px; color: #0f172a; display: block; margin-bottom: 8px;">Payment Details</strong>
        
        <table style="width: 100%; font-size: 12px; border-collapse: collapse;">
            <tr>
                <td style="width: 50%; vertical-align: top; padding-right: 20px;">
                    @if(!empty($pm['banks']))
                        <div style="margin-bottom: 10px;">
                            <strong>Bank Transfer (IBAN)</strong><br>
                            @foreach($pm['banks'] as $bank)
                                {{ $bank['bank'] }}: {{ $bank['iban'] }}<br>
                            @endforeach
                        </div>
                    @endif

                    @if(isset($pm['cheque']))
                        <div style="margin-bottom: 10px;">
                            <strong>Cheque Payments</strong><br>
                            Payable to: {{ $pm['cheque']['name'] }}<br>
                            Post to: {{ $pm['cheque']['address'] }}
                        </div>
                    @endif
                </td>

                <td style="width: 50%; vertical-align: top;">
                    @if(isset($pm['bov_mobile']))
                        <div style="margin-bottom: 10px;">
                            <strong>BOV Mobile Pay</strong><br>
                            {{ $pm['bov_mobile'] }}
                        </div>
                    @endif

                    @if(isset($pm['revolut']))
                        <div style="margin-bottom: 10px;">
                            <strong>Revolut</strong><br>
                            {{ $pm['revolut'] }}
                        </div>
                    @endif
                </td>
            </tr>
        </table>
    </div>
    @endif

    <div style="text-align: center; color: #94a3b8; font-size: 10px; margin-top: 30px; border-top: 1px solid #e2e8f0; padding-top: 15px;">
        Generated securely by PractisBase.
        @if(! $user->canAccessStandardTools())
            Free-tier document — custom branding available on Standard+.
        @endif
        This document is a bookkeeping aid and not certified accounting advice.
    </div>

</body>
</html>