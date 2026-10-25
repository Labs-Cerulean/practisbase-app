<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #0f172a; }
        h1 { font-size: 20px; margin: 0 0 4px; color: #0c4a6e; }
        .muted { color: #64748b; }
        .box { margin-top: 18px; padding: 12px; border: 1px solid #e2e8f0; background: #f8fafc; }
    </style>
</head>
<body>
    <h1>Official receipt</h1>
    <div class="muted">Issued by {{ $profile->legal_name }}</div>
    <div class="muted">Company Reg. No. {{ $profile->registration_number }}</div>
    <div class="muted" style="white-space: pre-line; margin-top: 4px;">{{ $profile->registered_office }}</div>
    @if($profile->vat_number)
        <div style="margin-top: 6px;">Supplier VAT identification number: {{ $profile->vat_number }}</div>
    @endif

    <div class="box">
        <div>Receipt for tax invoice <strong>{{ $document->document_number }}</strong></div>
        <div style="margin-top: 8px;">Received from <strong>{{ $document->client->name ?? '' }}</strong></div>
        @if(!empty($document->client->billing_address))
            <div class="muted" style="white-space: pre-line; margin-top: 4px;">{{ $document->client->billing_address }}</div>
        @endif
        @if(!empty($document->client->vat_number))
            <div style="margin-top: 4px;">Customer VAT identification number: {{ $document->client->vat_number }}</div>
        @endif
        <div style="margin-top: 10px;">Date: {{ $payment->payment_date->format('j M Y') }}</div>
        <div>Method: Card (Stripe)</div>
        <div style="margin-top: 10px; font-size: 16px;"><strong>€{{ number_format((float) $payment->amount, 2) }}</strong></div>
    </div>

    <p class="muted" style="margin-top: 18px;">This receipt confirms payment of the tax invoice issued by {{ $profile->legal_name }}.</p>
</body>
</html>
