<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt - {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #334155; line-height: 1.5; font-size: 14px; margin: 0; padding: 20px; }
        .header { border-bottom: 2px solid #e2e8f0; padding-bottom: 20px; margin-bottom: 30px; }
        .title { color: #0f172a; font-size: 28px; font-weight: bold; margin: 0; text-transform: uppercase; letter-spacing: 1px; }
        .subtitle { color: #64748b; font-size: 14px; margin-top: 5px; }
        .flex-container { width: 100%; margin-bottom: 30px; }
        .col { width: 48%; display: inline-block; vertical-align: top; }
        .box { background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 6px; }
        .amount-row { border-top: 2px solid #e2e8f0; padding-top: 10px; margin-top: 20px; }
        .text-right { text-align: right; }
        .label { color: #64748b; font-size: 12px; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; display: block; }
        .value { color: #0f172a; font-size: 16px; font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <h1 class="title">Official Receipt</h1>
        <div class="subtitle">Receipt for Payment against {{ $invoice->type === 'invoice' ? 'Tax Invoice' : 'Document' }} <strong>{{ $invoice->invoice_number }}</strong></div>
    </div>

    <div class="flex-container">
        <div class="col">
            <span class="label">Issued By:</span>
            @if($user->logoDataUriForPdf())
                <img src="{{ $user->logoDataUriForPdf() }}" alt="Logo" style="max-height: 50px; max-width: 140px; margin-bottom: 8px;"><br>
            @endif
            <strong>{{ $user->name }}</strong><br>
            @if($user->profession){{ $user->profession }}<br>@endif
            @if($user->vat_number)VAT: {{ $user->vat_number }}<br>@endif
            {{ $user->email }}
        </div>
        <div class="col text-right">
            <span class="label">Received From:</span>
            <strong>{{ $client->name }}</strong><br>
            @if(!empty($client->billing_address)){!! nl2br(e($client->billing_address)) !!}<br>@endif
            @if(!empty($client->profile_data['vat_number']))VAT: {{ $client->profile_data['vat_number'] }}@endif
        </div>
    </div>

    <div class="box">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding-bottom: 15px;">
                    <span class="label">Payment Date</span>
                    <span class="value">{{ $payment->payment_date->format('d F Y') }}</span>
                </td>
                <td style="padding-bottom: 15px; text-align: right;">
                    <span class="label">Payment ID</span>
                    <span class="value">REC-{{ str_pad($payment->id, 5, '0', STR_PAD_LEFT) }}</span>
                </td>
            </tr>
        </table>
        
        <div class="amount-row">
            <table style="width: 100%;">
                <tr>
                    <td style="font-size: 18px; color: #64748b;">Amount Received:</td>
                    <td class="text-right" style="font-size: 24px; font-weight: bold; color: #10b981;">
                        &euro;{{ number_format($payment->amount, 2) }}
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div style="margin-top: 30px; font-size: 13px; color: #64748b;">
        <p>This receipt confirms the partial/full payment of <strong>&euro;{{ number_format($payment->amount, 2) }}</strong> towards Invoice <strong>{{ $invoice->invoice_number }}</strong> (Total: &euro;{{ number_format($invoice->total, 2) }}).</p>
        <p>Remaining Balance Due: <strong>&euro;{{ number_format($invoice->total - $invoice->amount_paid, 2) }}</strong></p>
        <p style="margin-top: 40px; font-style: italic;">Thank you for your business.</p>
    </div>

</body>
</html>