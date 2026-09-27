<form action="/pro/medical/patients/{{ $patient->id }}/entries" method="POST" enctype="multipart/form-data" id="journal-compose-form"
      style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.1rem 1.2rem; box-shadow: var(--shadow-sm);">
    @csrf
    <input type="hidden" name="entry_type" value="journal">
    <input type="hidden" name="title" value="">

    <div style="display: flex; justify-content: flex-end; margin-bottom: 0.75rem;">
        <button type="button" id="journal-compose-cancel" style="background: none; border: none; color: var(--text-muted); font-weight: 600; cursor: pointer; font-size: 0.85rem;">Cancel</button>
    </div>

    <div style="margin-bottom: 1rem;">
        <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;" for="journal_entry_date">Date *</label>
        <input type="date" name="entry_date" id="journal_entry_date" value="{{ old('entry_date', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
    </div>

    @include('pro.medical._journal-template-fields', [
        'noteTemplate' => $noteTemplate ?? 'general',
        'fieldValues' => old('fields', []),
        'templateCatalogue' => $templateCatalogue ?? [],
        'visible' => true,
    ])

    <div style="margin-bottom: 1rem;">
        <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;" for="journal_extra_body">Additional notes</label>
        <textarea name="body" id="journal_extra_body" rows="3" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ old('body') }}</textarea>
    </div>

    <div style="margin-bottom: 1rem;">
        <label style="display: inline-flex; align-items: center; font-weight: 600; margin-bottom: 0.4rem;">
            Photo / scan
            @include('partials.help-tip', ['text' => 'JPEG, PNG, WebP, or PDF · max 10 MB. Stored encrypted in your vault.'])
        </label>
        <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf">
    </div>

    <button type="submit" style="width: 100%; padding: 0.85rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Save</button>
</form>

<script>
    (function () {
        var noteTemplate = document.getElementById('note_template');
        var fieldsHost = document.getElementById('journal-structured-fields');
        if (!noteTemplate || !fieldsHost) return;

        var catalogue = [];
        var valueStore = {};
        try {
            catalogue = JSON.parse(fieldsHost.getAttribute('data-catalogue') || '[]');
            valueStore = JSON.parse(fieldsHost.getAttribute('data-initial-values') || '{}');
        } catch (e) {
            catalogue = [];
            valueStore = {};
        }

        function escapeHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function captureValues() {
            fieldsHost.querySelectorAll('[data-field-key]').forEach(function (el) {
                valueStore[el.getAttribute('data-field-key')] = el.value;
            });
        }

        function findTemplate(key) {
            for (var i = 0; i < catalogue.length; i++) {
                if (catalogue[i].key === key) return catalogue[i];
            }
            return catalogue[0] || null;
        }

        function stripBulletPrefix(line) {
            return String(line || '')
                .replace(/^\s*\d+[\.\)]\s*/, '')
                .replace(/^\s*[-*•]\s*/, '');
        }

        function renumberBulletTextarea(el) {
            var sel = el.selectionStart;
            var value = el.value;
            var lines = value.split('\n');
            var out = [];
            var n = 1;
            var newSel = sel;
            var offset = 0;

            for (var i = 0; i < lines.length; i++) {
                var line = lines[i];
                var lineStart = offset;
                var trailingEmpty = (i === lines.length - 1 && line === '');
                var nextLine;

                if (trailingEmpty || line.trim() === '') {
                    nextLine = '';
                } else {
                    var stripped = stripBulletPrefix(line);
                    nextLine = n + '. ' + stripped;
                    n++;
                }

                if (sel >= lineStart && sel <= lineStart + line.length) {
                    var oldPrefix = line.length - stripBulletPrefix(line).length;
                    var newPrefix = nextLine === '' ? 0 : nextLine.length - stripBulletPrefix(nextLine).length;
                    var inLine = sel - lineStart;
                    if (inLine <= oldPrefix) {
                        newSel = lineStart + newPrefix;
                    } else {
                        newSel = lineStart + newPrefix + (inLine - oldPrefix);
                    }
                }

                out.push(nextLine);
                offset += line.length + 1;
            }

            var next = out.join('\n');
            if (next === value) return;
            el.value = next;
            try { el.setSelectionRange(newSel, newSel); } catch (err) {}
        }

        function bindBulletFields() {
            fieldsHost.querySelectorAll('textarea[data-bullet-field]').forEach(function (el) {
                if (el.getAttribute('data-bullet-bound') === '1') return;
                el.setAttribute('data-bullet-bound', '1');
                el.addEventListener('input', function () { renumberBulletTextarea(el); });
                el.addEventListener('blur', function () { renumberBulletTextarea(el); });
            });
        }

        function fieldControl(field, val) {
            var type = field.type || 'text';
            var name = 'fields[' + escapeHtml(field.key) + ']';
            var keyAttr = 'data-field-key="' + escapeHtml(field.key) + '"';
            var style = 'width:100%;padding:0.65rem;border:1px solid var(--border-light);border-radius:var(--radius-md);';
            if (type === 'date') {
                return '<input type="date" name="' + name + '" ' + keyAttr + ' value="' + escapeHtml(val) + '" max="{{ date('Y-m-d') }}" style="' + style + '">';
            }
            if (type === 'bullets') {
                var seed = val && String(val).trim() !== '' ? val : '1. ';
                return '<textarea name="' + name + '" ' + keyAttr + ' data-bullet-field="1" rows="4" placeholder="1. First item" style="' + style + '">' +
                    escapeHtml(seed) + '</textarea>' +
                    '<div style="font-size:0.75rem;color:var(--text-muted);margin-top:0.25rem;">Numbers update as you type. Enter for the next item.</div>';
            }
            return '<textarea name="' + name + '" ' + keyAttr + ' rows="3" style="' + style + '">' + escapeHtml(val) + '</textarea>';
        }

        function syncTemplateFields() {
            captureValues();
            var tpl = findTemplate(noteTemplate.value);
            var fields = (tpl && tpl.fields) ? tpl.fields : [];
            fieldsHost.innerHTML = fields.map(function (field) {
                var val = valueStore[field.key] || '';
                return '<div data-template-field="' + escapeHtml(field.key) + '">' +
                    '<label style="display:block;font-weight:600;margin-bottom:0.35rem;font-size:0.9rem;">' + escapeHtml(field.label) + '</label>' +
                    fieldControl(field, val) + '</div>';
            }).join('');
            bindBulletFields();
        }

        noteTemplate.addEventListener('change', syncTemplateFields);
        syncTemplateFields();
    })();
</script>
