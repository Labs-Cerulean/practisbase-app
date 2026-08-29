<div style="font-family: Georgia, 'Times New Roman', serif; color: #0f172a; line-height: 1.5; max-width: 640px;">
    <p style="margin: 0 0 1rem; font-size: 14px;">Dear {{ $clientName }},</p>

    @if($kind === 'proforma')
        <p style="margin: 0 0 1rem; font-size: 14px;">
            Please find your monthly Estate Hub proforma <strong>{{ $documentNumber }}</strong>
            for <strong>{{ $packageLabel }}</strong>
            (total €{{ $documentTotal }}, due {{ $documentDue }}).
        </p>
        <p style="margin: 0 0 1rem; font-size: 13px; color: #475569;">
            This is a request for payment (proforma). A tax invoice with VAT is issued once payment is received.
        </p>
    @elseif($kind === 'reminder')
        <p style="margin: 0 0 1rem; font-size: 14px;">
            This is a friendly reminder regarding open balances on your Estate Hub account with {{ $companyName }}.
        </p>
    @else
        <p style="margin: 0 0 1rem; font-size: 14px;">
            Please find your account statement summarised below.
        </p>
    @endif

    @if(! is_null($officialOwed))
        <table style="width: 100%; border-collapse: collapse; font-size: 13px; margin: 1rem 0;">
            <tr>
                <td style="padding: 0.35rem 0; color: #64748b;">Tax invoices owed</td>
                <td style="padding: 0.35rem 0; text-align: right; font-weight: 700;">€{{ $officialOwed }}</td>
            </tr>
            <tr>
                <td style="padding: 0.35rem 0; color: #64748b;">Proforma (RFP) owed</td>
                <td style="padding: 0.35rem 0; text-align: right; font-weight: 700;">€{{ $rfpOwed }}</td>
            </tr>
            <tr>
                <td style="padding: 0.5rem 0 0; border-top: 1px solid #e2e8f0; font-weight: 700;">Total open</td>
                <td style="padding: 0.5rem 0 0; border-top: 1px solid #e2e8f0; text-align: right; font-weight: 700;">€{{ $totalOwed }}</td>
            </tr>
        </table>
    @endif

    <p style="margin: 1.25rem 0 0; font-size: 13px; color: #64748b;">
        Kind regards,<br>
        {{ $companyName }}
        @if($vatNumber !== '')
            <br>VAT {{ $vatNumber }}
        @endif
    </p>
</div>
