<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #0f172a; }
        h1 { font-size: 20px; margin: 0 0 4px; color: #0c4a6e; }
        .muted { color: #64748b; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        th, td { padding: 8px 6px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        th { font-size: 11px; text-transform: uppercase; color: #64748b; }
        .totals { margin-top: 16px; width: 300px; margin-left: auto; }
        .totals td { border: none; padding: 4px 0; }
        .totals .grand { font-size: 14px; font-weight: bold; }
        .banner { margin-top: 14px; padding: 8px 10px; background: #f8fafc; border: 1px solid #e2e8f0; font-size: 11px; }
    </style>
</head>
<body>
    @php
        $typeLabel = match ($document->type) {
            'rfp' => 'Request for Payment',
            'invoice' => 'Tax Invoice',
            'credit_note' => 'Credit Note',
            default => strtoupper($document->type),
        };
        $supplyDate = $document->effectiveSupplyDate();
        $showSupplySeparately = $supplyDate->format('Y-m-d') !== $document->issue_date->format('Y-m-d');
        $creditedInvoice = $creditedInvoice ?? null;
    @endphp

    <table style="border: none; margin: 0;">
        <tr>
            <td style="border: none; vertical-align: top;">
                @if($profile->logoDataUri())
                    <img src="{{ $profile->logoDataUri() }}" alt="Logo" style="max-height: 70px; max-width: 200px; margin-bottom: 10px;"><br>
                @endif
                <h1>{{ $profile->legal_name }}</h1>
                <div class="muted">Company Reg. No. {{ $profile->registration_number }}</div>
                <div class="muted" style="margin-top: 6px; white-space: pre-line;">{{ $profile->registered_office }}</div>
                @if($profile->vat_number)
                    <div style="margin-top: 6px;">Supplier VAT identification number: {{ $profile->vat_number }}</div>
                @endif
            </td>
            <td style="border: none; vertical-align: top; text-align: right;">
                <div style="font-size: 16px; font-weight: bold;">{{ $typeLabel }}</div>
                <div>{{ $document->document_number }}</div>
                <div class="muted">Date of issue: {{ $document->issue_date->format('d M Y') }}</div>
                <div class="muted">
                    Date of supply: {{ $supplyDate->format('d M Y') }}
                    @unless($showSupplySeparately) (same as issue date) @endunless
                </div>
                @if($document->due_date && $document->type !== 'credit_note')
                    <div class="muted">Due: {{ $document->due_date->format('d M Y') }}</div>
                @endif
                <div class="muted">Currency: EUR</div>
            </td>
        </tr>
    </table>

    @if($document->type === 'credit_note' && $creditedInvoice)
        <div class="banner">
            <strong>This credit note amends tax invoice {{ $creditedInvoice->document_number }}</strong>
            (issued {{ $creditedInvoice->issue_date->format('d M Y') }}).
            It reverses the taxable amount and VAT shown below for that invoice.
        </div>
    @endif

    <div style="margin-top: 24px;">
        <div class="muted" style="font-size: 11px; text-transform: uppercase;">Bill to</div>
        <div style="font-weight: bold; margin-top: 4px;">{{ $document->client->name ?? '' }}</div>
        @if(!empty($document->client->billing_address))
            <div class="muted" style="white-space: pre-line; margin-top: 4px;">{{ $document->client->billing_address }}</div>
        @endif
        @if(!empty($document->client->vat_number))
            <div style="margin-top: 4px;">Customer VAT identification number: {{ $document->client->vat_number }}</div>
        @endif
        @if(!empty($document->client->registration_number))
            <div class="muted" style="margin-top: 2px;">Customer Reg. No. {{ $document->client->registration_number }}</div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Description of goods / services</th>
                <th style="text-align: right;">Qty</th>
                <th style="text-align: right;">Unit price (ex-VAT)</th>
                <th style="text-align: right;">Amount (ex-VAT)</th>
            </tr>
        </thead>
        <tbody>
            @foreach(($document->items ?? []) as $item)
                <tr>
                    <td>{{ $item['description'] ?? '' }}</td>
                    <td style="text-align: right;">{{ number_format((float) ($item['quantity'] ?? 0), 2) }}</td>
                    <td style="text-align: right;">€{{ number_format((float) ($item['unit_price'] ?? 0), 2) }}</td>
                    <td style="text-align: right;">€{{ number_format((float) ($item['row_total'] ?? 0), 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td>Taxable amount (ex-VAT)</td>
            <td style="text-align: right;">€{{ number_format((float) $document->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td>VAT rate</td>
            <td style="text-align: right;">{{ (float) $document->vat_total > 0 ? '18%' : '0%' }}</td>
        </tr>
        <tr>
            <td>VAT amount</td>
            <td style="text-align: right;">€{{ number_format((float) $document->vat_total, 2) }}</td>
        </tr>
        <tr class="grand">
            <td>Total payable (EUR)</td>
            <td style="text-align: right;">€{{ number_format((float) $document->total, 2) }}</td>
        </tr>
    </table>

    @if($document->notes)
        <div style="margin-top: 20px;">
            <div class="muted" style="font-size: 11px; text-transform: uppercase;">Notes</div>
            <div style="margin-top: 4px; white-space: pre-line;">{{ $document->notes }}</div>
        </div>
    @endif

    @if($profile->payment_instructions && $document->type !== 'credit_note')
        <div style="margin-top: 20px;">
            <div class="muted" style="font-size: 11px; text-transform: uppercase;">Payment</div>
            <div style="margin-top: 4px; white-space: pre-line;">{{ $profile->payment_instructions }}</div>
            @if($profile->bank_iban)
                <div style="margin-top: 4px;">IBAN: {{ $profile->bank_iban }}</div>
            @endif
        </div>
    @endif

    @if($document->type === 'rfp')
        <p class="muted" style="margin-top: 24px; font-size: 10px;">
            This is a request for payment (pro-forma). It is not a VAT tax invoice and must not be used for input VAT reclaim until converted to a tax invoice.
        </p>
    @endif

    @if($document->type === 'invoice')
        <p class="muted" style="margin-top: 24px; font-size: 10px;">
            Tax invoice issued under Article 10 of the Maltese VAT Act. Keep this document for your records.
        </p>
    @endif
</body>
</html>
