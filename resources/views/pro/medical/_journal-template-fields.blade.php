@php
    $templateCatalogue = $templateCatalogue ?? [];
    $selectedKey = $noteTemplate ?? ($templateCatalogue[0]['key'] ?? 'general');
    $fieldValues = $fieldValues ?? [];
@endphp
<div id="journal-template-fields" style="{{ ($visible ?? true) ? '' : 'display:none;' }} margin-bottom: 1rem; padding: 1rem; background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
    <div style="display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; align-items: flex-end; margin-bottom: 0.85rem;">
        <div>
            <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.35rem;">Consult template</div>
            <select name="note_template" id="note_template" style="min-width: 240px; padding: 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                @foreach($templateCatalogue as $row)
                    <option value="{{ $row['key'] }}" {{ $selectedKey === $row['key'] ? 'selected' : '' }}>
                        {{ $row['name'] }}{{ !empty($row['builtin']) ? '' : '' }}
                    </option>
                @endforeach
            </select>
        </div>
        <div style="font-size: 0.75rem; color: var(--text-muted); max-width: 18rem; line-height: 1.4;">
            Manage your own templates in
            <a href="/pro/medical/templates" style="color: var(--primary-cerulean); font-weight: 700; text-decoration: none; border-bottom: 1px dotted var(--primary-navy);">Journal templates</a>.
        </div>
    </div>

    <div id="journal-structured-fields" style="display: grid; gap: 0.75rem;"
         data-initial-values='@json($fieldValues)'
         data-catalogue='@json($templateCatalogue)'></div>
</div>
