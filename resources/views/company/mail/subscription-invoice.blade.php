<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Helvetica, Arial, sans-serif; color: #0f172a; font-size: 15px; line-height: 1.5;">
    <p>{{ $clientName !== '' ? $clientName : 'Hello' }},</p>
    <p>{{ $company }} has issued invoice {{ $documentNumber }} for €{{ $total }}.</p>
    <p>The tax invoice and the card receipt are attached. Both are issued by {{ $company }}.</p>
    @if($vatNumber !== '')
        <p>Supplier VAT identification number: {{ $vatNumber }}</p>
    @endif
    <p>Cerulean Labs Limited</p>
</body>
</html>
