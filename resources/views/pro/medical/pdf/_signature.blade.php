{{-- Shared clinical signature / stamp block. Requires $user. --}}
@php
    $stampUri = $user->clinicalStampDataUriForPdf();
@endphp
<div class="sign" style="margin-top: 28px;">
    @if($stampUri)
        <div style="text-align: right; margin-bottom: 6px;">
            <img src="{{ $stampUri }}" alt="Clinical stamp" style="max-height: 95px; max-width: 240px;">
        </div>
    @else
        <div style="height: 48px;"></div>
    @endif
    <div style="margin-left: auto; width: 240px; text-align: right;">
        <div style="border-top: 1px solid #334155; padding-top: 4px; font-size: 10px; color: #475569;">Signature / stamp</div>
        <div style="font-size: 9px; color: #64748b; margin-top: 4px;">
            {{ $user->name }}
            @if($user->postnominalsLine())
                · {{ $user->postnominalsLine() }}
            @endif
        </div>
    </div>
</div>
