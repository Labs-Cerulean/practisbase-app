@php
    $template = $noteTemplate ?? \App\Support\ClinicalNoteTemplates::GENERAL;
    $allFields = [];
    foreach (array_keys(\App\Support\ClinicalNoteTemplates::options()) as $tplKey) {
        foreach (\App\Support\ClinicalNoteTemplates::fields($tplKey) as $key => $meta) {
            if (! isset($allFields[$key])) {
                $allFields[$key] = $meta;
            }
        }
    }
    $fieldValues = $fieldValues ?? [];
    $activeKeys = array_keys(\App\Support\ClinicalNoteTemplates::fields($template));
@endphp
<div id="journal-template-fields" style="{{ ($visible ?? true) ? '' : 'display:none;' }} margin-bottom: 1rem; padding: 1rem; background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
    <div style="display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; align-items: flex-end; margin-bottom: 0.85rem;">
        <div>
            <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.35rem;">Consult template</div>
            <select name="note_template" id="note_template" style="min-width: 220px; padding: 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                @foreach(\App\Support\ClinicalNoteTemplates::options() as $key => $label)
                    <option value="{{ $key }}" {{ $template === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div style="font-size: 0.75rem; color: var(--text-muted); max-width: 18rem; line-height: 1.4;">
            Specialty fields stay inside the encrypted note. Change default in Settings.
        </div>
    </div>

    <div id="journal-structured-fields" style="display: grid; gap: 0.75rem;">
        @foreach($allFields as $key => $meta)
            <div data-template-field="{{ $key }}" style="{{ in_array($key, $activeKeys, true) ? '' : 'display:none;' }}">
                <label style="display: block; font-weight: 600; margin-bottom: 0.35rem; font-size: 0.9rem;">{{ $meta['label'] }}</label>
                <textarea name="fields[{{ $key }}]" rows="{{ $meta['rows'] }}"
                          style="width: 100%; padding: 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ old('fields.'.$key, $fieldValues[$key] ?? '') }}</textarea>
            </div>
        @endforeach
    </div>
</div>
