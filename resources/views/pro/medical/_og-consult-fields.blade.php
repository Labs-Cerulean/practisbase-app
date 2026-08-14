@php
    $payload = $payload ?? [];
    $patientPayload = $patientPayload ?? [];
    $consultKind = old('consult_kind', $payload['consult_kind'] ?? ($defaultConsultKind ?? 'follow_up'));
    $historyLabels = \App\Support\MedicalSpecialty::standingHistoryLabels(true);
    $showHistoryPrefill = $showHistoryPrefill ?? true;
@endphp
<div id="og-consult-fields" style="margin-bottom: 1.15rem; padding: 1rem; background: #fdf4ff; border: 1px solid #e9d5ff; border-radius: var(--radius-md); {{ ($visible ?? true) ? '' : 'display: none;' }}">
    <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #6b21a8; margin-bottom: 0.35rem;">Obstetrics &amp; Gynaecology consult</div>
    <p style="margin: 0 0 0.85rem; font-size: 0.8rem; color: var(--text-muted); line-height: 1.4;">
        Matches the clinic clerking sheet. Standing history lives on the patient chart; a follow-up is just the dated note.
    </p>

    <div style="margin-bottom: 1rem;">
        <div style="font-weight: 600; margin-bottom: 0.45rem;">Kind</div>
        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
            <label style="display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.9rem; cursor: pointer;">
                <input type="radio" name="consult_kind" value="clerking" {{ $consultKind === 'clerking' ? 'checked' : '' }} id="consult_kind_clerking">
                Full clerking
            </label>
            <label style="display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.9rem; cursor: pointer;">
                <input type="radio" name="consult_kind" value="follow_up" {{ $consultKind === 'follow_up' ? 'checked' : '' }} id="consult_kind_followup">
                Follow-up
            </label>
        </div>
        <div id="consult-kind-guide" style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.35rem;"></div>
    </div>

    @if($showHistoryPrefill)
            <div id="og-standing-history" style="{{ $consultKind === 'clerking' ? '' : 'display: none;' }} margin-bottom: 1rem; padding: 0.85rem; background: white; border: 1px solid #e9d5ff; border-radius: var(--radius-md);">
            <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: #6b21a8; margin-bottom: 0.35rem;">Standing history (saved on the patient)</div>
            <p style="margin: 0 0 0.75rem; font-size: 0.78rem; color: var(--text-muted); line-height: 1.4;">
                Prefills from this chart. Saving a full clerking updates the patient record so later visits still see it.
            </p>
            @foreach($historyLabels as $histKey => $histLabel)
                <div style="margin-bottom: 0.75rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.3rem;">{{ $histLabel }}</label>
                    <textarea name="{{ $histKey }}" rows="2" style="width: 100%; padding: 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ old($histKey, $patientPayload[$histKey] ?? '') }}</textarea>
                </div>
            @endforeach
        </div>
    @endif

    <div style="margin-bottom: 0.85rem;">
        <label style="display: block; font-weight: 600; margin-bottom: 0.35rem;">LMP</label>
        <input type="text" name="lmp" value="{{ old('lmp', $payload['lmp'] ?? ($patientPayload['lmp'] ?? '')) }}" maxlength="255"
               placeholder="Date or free text"
               style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
    </div>
    <div style="margin-bottom: 0.85rem;">
        <label style="display: block; font-weight: 600; margin-bottom: 0.35rem;">Presenting complaint</label>
        <textarea name="presenting_complaint" rows="3" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ old('presenting_complaint', $payload['presenting_complaint'] ?? '') }}</textarea>
    </div>
    <div style="margin-bottom: 0.85rem;">
        <label style="display: block; font-weight: 600; margin-bottom: 0.35rem;">Exam</label>
        <textarea name="exam" rows="3" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ old('exam', $payload['exam'] ?? '') }}</textarea>
    </div>
    <div style="margin-bottom: 0.85rem;">
        <label style="display: block; font-weight: 600; margin-bottom: 0.35rem;">US</label>
        <textarea name="ultrasound" rows="3" placeholder="Ultrasound findings. Attach the scan below if you have an image."
                  style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ old('ultrasound', $payload['ultrasound'] ?? '') }}</textarea>
    </div>
    <div style="margin-bottom: 0.85rem;">
        <label style="display: block; font-weight: 600; margin-bottom: 0.35rem;">Plan</label>
        <textarea name="plan" rows="3" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ old('plan', $payload['plan'] ?? '') }}</textarea>
    </div>
    <div>
        <label style="display: block; font-weight: 600; margin-bottom: 0.35rem;" id="og-notes-label">Progress / extra notes</label>
        <textarea name="consult_notes" id="consult_notes" rows="4" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ old('consult_notes', $payload['consult_notes'] ?? '') }}</textarea>
    </div>
</div>
