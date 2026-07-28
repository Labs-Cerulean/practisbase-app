{{-- Letterhead for clinical PDFs. Requires $user. Optional $documentTitle. --}}
@php
    $showTitle = isset($documentTitle) && trim((string) $documentTitle) !== '';
    $clinicPhone = trim((string) ($user->clinic_phone ?? ''));
    $clinicAddress = trim((string) ($user->clinic_address ?? ''));
    $regNo = trim((string) ($user->warrant_number ?? ''));
    $warrantType = trim((string) ($user->warrant_type ?? ''));
@endphp
<div style="text-align: center; margin-bottom: 16px;">
    @if($user->logoDataUri())
        <img src="{{ $user->logoDataUri() }}" style="max-height: 52px; max-width: 160px; margin-bottom: 8px;">
    @endif
    <div class="practice" style="font-size: 16px; font-weight: bold; color: #0f172a; margin: 0;">{{ $user->name }}</div>
    @if($user->postnominalsLine())
        <div class="practice-sub" style="font-size: 10px; color: #1e293b; margin-top: 2px;">{{ $user->postnominalsLine() }}</div>
    @endif
    @if($user->profession)
        <div class="practice-sub" style="font-size: 10px; color: #64748b; margin-top: 2px;">{{ $user->profession }}</div>
    @endif
    @if($regNo !== '')
        <div class="practice-sub" style="font-size: 10px; color: #1e293b; margin-top: 2px;">
            Medical Reg Nº: {{ $regNo }}
            @if($warrantType !== '')
                <span style="color: #64748b;">({{ $warrantType }})</span>
            @endif
        </div>
    @elseif($warrantType !== '')
        <div class="practice-sub" style="font-size: 10px; color: #64748b; margin-top: 2px;">{{ $warrantType }}</div>
    @endif
    @if($user->email)
        <div class="practice-sub" style="font-size: 10px; color: #64748b; margin-top: 2px;">{{ $user->email }}</div>
    @endif
    @if($clinicPhone !== '')
        <div class="practice-sub" style="font-size: 10px; color: #64748b; margin-top: 2px;">Tel: {{ $clinicPhone }}</div>
    @endif
    @if($clinicAddress !== '')
        <div class="practice-sub" style="font-size: 10px; color: #64748b; margin-top: 2px; white-space: pre-line;">{{ $clinicAddress }}</div>
    @endif
</div>
@if($showTitle)
    <h1 style="color: #0f172a; font-size: 18px; margin: 12px 0 6px; border-bottom: 1.5px solid #0f172a; padding-bottom: 6px;">{{ $documentTitle }}</h1>
@endif
