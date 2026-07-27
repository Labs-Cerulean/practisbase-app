{{-- Letterhead for clinical PDFs. Requires $user, $documentTitle. --}}
@if($user->logoDataUri())
    <img src="{{ $user->logoDataUri() }}" style="max-height: 56px; max-width: 170px; margin-bottom: 12px;">
@endif
<div class="practice">{{ $user->name }}</div>
@if($user->profession)
    <div class="practice-sub">{{ $user->profession }}</div>
@endif
@if($user->warrant_type || $user->warrant_number)
    <div class="practice-sub">Warrant: {{ trim(($user->warrant_type ?? '') . ' ' . ($user->warrant_number ?? '')) }}</div>
@endif
@if(!empty($user->address))
    <div class="practice-sub">{{ $user->address }}</div>
@endif
@if($user->vat_number)
    <div class="practice-sub">VAT: {{ $user->vat_number }}</div>
@endif

<h1>{{ $documentTitle }}</h1>
