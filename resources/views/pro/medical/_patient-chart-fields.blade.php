@php
    $payload = $payload ?? [];
    $isOg = $isOg ?? false;
    $historyLabels = \App\Support\MedicalSpecialty::standingHistoryLabels($isOg);
@endphp
<div style="margin-bottom: 1rem;">
    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">ID number <span style="font-weight: 500; color: var(--text-muted);">(optional, encrypted)</span></label>
    <input type="text" name="id_number" value="{{ old('id_number', $payload['id_number'] ?? '') }}" maxlength="100"
           placeholder="ID card / passport"
           style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
</div>
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem; margin-bottom: 1rem;">
    <div>
        <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Tel no. <span style="font-weight: 500; color: var(--text-muted);">(optional)</span></label>
        <input type="text" name="phone" value="{{ old('phone', $payload['phone'] ?? '') }}" maxlength="50"
               style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
    </div>
    <div>
        <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Age <span style="font-weight: 500; color: var(--text-muted);">(if no DOB)</span></label>
        <input type="text" name="approx_age" value="{{ old('approx_age', $payload['approx_age'] ?? '') }}" maxlength="40"
               placeholder="e.g. 34"
               style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.3rem;">Ignored on the chart when date of birth is set — age is then calculated.</div>
    </div>
</div>
<div style="margin-bottom: 1rem;">
    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Address <span style="font-weight: 500; color: var(--text-muted);">(optional, encrypted)</span></label>
    <textarea name="address" rows="2" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ old('address', $payload['address'] ?? '') }}</textarea>
</div>

<div style="margin: 1.25rem 0 0.85rem; padding: 1rem; background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
    <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--primary-navy); margin-bottom: 0.35rem;">Standing history</div>
    <p style="margin: 0 0 0.85rem; font-size: 0.8rem; color: var(--text-muted); line-height: 1.4;">
        Stays on this patient chart across visits. Leave blank if not needed.
        @if($isOg)
            Gynae Hx and Obs Hx are shown because your specialty is Obstetrics &amp; Gynaecology.
        @endif
    </p>
    @if($isOg)
        <div style="margin-bottom: 0.85rem;">
            <label style="display: block; font-weight: 600; margin-bottom: 0.35rem;">LMP <span style="font-weight: 500; color: var(--text-muted);">(latest)</span></label>
            <input type="text" name="lmp" value="{{ old('lmp', $payload['lmp'] ?? '') }}" maxlength="255"
                   placeholder="Date or free text, e.g. 12/03/2026"
                   style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
        </div>
    @endif
    @foreach($historyLabels as $histKey => $histLabel)
        <div style="margin-bottom: 0.85rem;">
            <label style="display: block; font-weight: 600; margin-bottom: 0.35rem;">{{ $histLabel }}</label>
            <textarea name="{{ $histKey }}" rows="3" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ old($histKey, $payload[$histKey] ?? '') }}</textarea>
        </div>
    @endforeach
</div>
