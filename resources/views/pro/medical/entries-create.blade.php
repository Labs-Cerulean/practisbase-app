@extends('layouts.app')

@section('page_title', 'New entry')

@section('content')
    @php
        $typeLocked = in_array($defaultType ?? '', array_keys($types), true);
        $pageTitle = $typeLocked
            ? ('New ' . strtolower($types[$defaultType] ?? 'entry'))
            : 'New clinical entry';
    @endphp
    <div style="max-width: 820px; margin: 0 auto; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; margin-bottom: 1.25rem;">
            <h2 style="margin: 0; color: var(--primary-navy);">{{ $pageTitle }}</h2>
            <a href="/pro/medical/patients/{{ $patient->id }}" style="color: var(--text-muted); font-weight: 600; text-decoration: none;">Cancel</a>
        </div>
        @if($errors->any())
            <div style="background: #fef2f2; color: #991b1b; padding: 0.85rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
                @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif
        <form action="/pro/medical/patients/{{ $patient->id }}/entries" method="POST" enctype="multipart/form-data" id="entry-form">
            @csrf
            <div style="margin-bottom: 1rem;">
                @if($typeLocked)
                    <label style="display: inline-flex; align-items: center; font-weight: 600; margin-bottom: 0.4rem;">
                        Type
                        @include('partials.help-tip', ['text' => 'Chosen from the patient page. Patient notes stay editable; stampables lock after Stamp & issue.'])
                    </label>
                    <input type="hidden" name="entry_type" id="entry_type" value="{{ $defaultType }}">
                    <div style="padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: #f8fafc; color: var(--primary-navy); font-weight: 600;">
                        {{ $types[$defaultType] ?? $defaultType }}
                    </div>
                @else
                    <label style="display: inline-flex; align-items: center; font-weight: 600; margin-bottom: 0.4rem;">
                        Type *
                        @include('partials.help-tip', ['text' => 'Patient notes stay editable. Prescriptions, referrals, and certificates can be stamped later.'])
                    </label>
                    <select name="entry_type" id="entry_type" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                        @foreach($types as $key => $label)
                            <option value="{{ $key }}" {{ ($defaultType ?? 'journal') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                @endif
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;" id="date-label">Date *</label>
                <input type="date" name="entry_date" id="entry_date" value="{{ old('entry_date', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>

            <div id="certificate-fields" style="display: none;">
                <div style="margin-bottom: 1rem; padding: 1rem; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: var(--radius-md);">
                    <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #14532d; margin-bottom: 0.75rem;">Certificate</div>
                    <input type="hidden" name="certificate_kind" id="certificate_kind" value="{{ old('certificate_kind', 'medical_certificate') }}">
                    <input type="hidden" name="subject_name" id="subject_name" value="{{ old('subject_name', $patientPayload['display_name'] ?? '') }}">
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Expires on</label>
                        <input type="date" name="expires_on" value="{{ old('expires_on') }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    </div>
                </div>
            </div>

            <div id="referral-fields" style="display: none; margin-bottom: 1rem; padding: 1rem; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: var(--radius-md);">
                <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #1e3a5f; margin-bottom: 0.75rem;">Referral</div>
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Referred to</label>
                <input type="text" name="referred_to" value="{{ old('referred_to') }}" placeholder="Clinician or clinic"
                       style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>

            @include('pro.medical._prescription-medicines', [
                'medicines' => old('medicines', [['name' => '', 'strength' => '', 'dose' => '', 'quantity' => '', 'instructions' => '']]),
                'visible' => false,
            ])

            @include('pro.medical._journal-template-fields', [
                'noteTemplate' => $noteTemplate ?? 'general',
                'fieldValues' => old('fields', []),
                'templateCatalogue' => $templateCatalogue ?? [],
                'visible' => ($defaultType ?? 'journal') === 'journal',
            ])

            <div id="title-wrap" style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;" id="title-label">Title *</label>
                <input type="text" name="title" id="title" value="{{ old('title') }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div id="body-wrap" style="margin-bottom: 1rem;">
                <label style="display: inline-flex; align-items: center; font-weight: 600; margin-bottom: 0.4rem;" id="body-label">
                    Details *
                </label>
                <textarea name="body" id="body" rows="8" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ old('body') }}</textarea>
            </div>

            <div id="attachment-fields" style="display: none; margin-bottom: 1.25rem;">
                <label style="display: inline-flex; align-items: center; font-weight: 600; margin-bottom: 0.4rem;">
                    Photo / scan
                    @include('partials.help-tip', ['text' => 'JPEG, PNG, WebP, or PDF · max 10 MB. Stored encrypted in your vault.'])
                </label>
                <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf">
            </div>

            <div id="stampable-actions" style="display: none;">
                <button type="submit" name="issue_now" value="1" id="submit-issue"
                        onclick="return confirm('Confirm, stamp, and lock this document? It cannot be edited afterwards. Share opens next.');"
                        style="width: 100%; padding: 0.9rem; background: var(--primary-navy); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer; margin-bottom: 0.55rem;">
                    Stamp &amp; issue
                </button>
                <button type="submit" name="issue_now" value="0" id="submit-draft"
                        style="width: 100%; padding: 0.75rem; background: white; color: var(--primary-navy); border: 1px solid var(--border-light); border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">
                    Save draft
                </button>
                <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0.65rem 0 0; text-align: center;">
                    Stamp &amp; issue confirms now — Share is ready on the patient page.
                </p>
            </div>
            <button type="submit" id="submit-btn" style="width: 100%; padding: 0.85rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Save entry</button>
            <p id="stamp-note" style="display: none; font-size: 0.8rem; color: var(--text-muted); margin: 0.65rem 0 0; text-align: center;"></p>
        </form>
    </div>

    <script>
        (function () {
            var typeEl = document.getElementById('entry_type');
            var cert = document.getElementById('certificate-fields');
            var referral = document.getElementById('referral-fields');
            var rxFields = document.getElementById('prescription-fields');
            var journalFields = document.getElementById('journal-template-fields');
            var noteTemplate = document.getElementById('note_template');
            var fieldsHost = document.getElementById('journal-structured-fields');
            var attach = document.getElementById('attachment-fields');
            var dateLabel = document.getElementById('date-label');
            var titleWrap = document.getElementById('title-wrap');
            var titleLabel = document.getElementById('title-label');
            var bodyLabel = document.getElementById('body-label');
            var titleInput = document.getElementById('title');
            var bodyInput = document.getElementById('body');
            var submitBtn = document.getElementById('submit-btn');
            var stampNote = document.getElementById('stamp-note');
            var stampableActions = document.getElementById('stampable-actions');
            var kindEl = document.getElementById('certificate_kind');
            var subjectEl = document.getElementById('subject_name');
            var defaultSubject = @json($patientPayload['display_name'] ?? '');

            var catalogue = [];
            var valueStore = {};
            try {
                catalogue = fieldsHost ? JSON.parse(fieldsHost.getAttribute('data-catalogue') || '[]') : [];
                valueStore = fieldsHost ? JSON.parse(fieldsHost.getAttribute('data-initial-values') || '{}') : {};
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
                if (!fieldsHost) return;
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
                return '<textarea name="' + name + '" ' + keyAttr + ' rows="3" style="' + style + '">' +
                    escapeHtml(val) + '</textarea>';
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

                    if (trailingEmpty) {
                        nextLine = '';
                    } else if (line.trim() === '') {
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
                try {
                    el.setSelectionRange(newSel, newSel);
                } catch (e) {}
            }

            function bindBulletFields() {
                if (!fieldsHost) return;
                fieldsHost.querySelectorAll('textarea[data-bullet-field]').forEach(function (el) {
                    if (el.getAttribute('data-bullet-bound') === '1') return;
                    el.setAttribute('data-bullet-bound', '1');
                    el.addEventListener('input', function () {
                        renumberBulletTextarea(el);
                    });
                    el.addEventListener('blur', function () {
                        renumberBulletTextarea(el);
                    });
                });
            }

            function syncTemplateFields() {
                if (!noteTemplate || !fieldsHost) return;
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

            function sync() {
                var t = typeEl.value;
                cert.style.display = t === 'certificate' ? 'block' : 'none';
                referral.style.display = t === 'referral' ? 'block' : 'none';
                rxFields.style.display = t === 'prescription' ? 'block' : 'none';
                if (journalFields) journalFields.style.display = t === 'journal' ? 'block' : 'none';
                attach.style.display = (t === 'certificate' || t === 'referral' || t === 'prescription' || t === 'journal') ? 'block' : 'none';
                var isStampable = (t === 'certificate' || t === 'referral' || t === 'prescription');
                if (stampableActions) stampableActions.style.display = isStampable ? 'block' : 'none';
                if (submitBtn) submitBtn.style.display = isStampable ? 'none' : 'block';
                stampNote.style.display = 'none';
                if (kindEl) {
                    kindEl.value = 'medical_certificate';
                    kindEl.required = false;
                }
                if (subjectEl && !subjectEl.value) {
                    subjectEl.value = defaultSubject;
                }
                bodyInput.required = (t !== 'prescription' && t !== 'journal');
                bodyInput.rows = t === 'prescription' || t === 'journal' ? 3 : 8;

                if (t === 'journal') {
                    titleWrap.style.display = 'none';
                    titleInput.required = false;
                    titleInput.value = 'Patient note';
                    dateLabel.textContent = 'Date *';
                    bodyLabel.innerHTML = 'Additional notes';
                    submitBtn.textContent = 'Save patient note';
                } else if (t === 'certificate') {
                    titleWrap.style.display = 'block';
                    titleInput.required = true;
                    if (titleInput.value === 'Patient note') titleInput.value = '';
                    dateLabel.textContent = 'Date *';
                    titleLabel.textContent = 'Title *';
                    bodyLabel.innerHTML = 'Details *';
                } else if (t === 'prescription') {
                    titleWrap.style.display = 'none';
                    titleInput.required = false;
                    if (titleInput.value === 'Patient note') titleInput.value = '';
                    dateLabel.textContent = 'Date *';
                    bodyLabel.innerHTML = 'Notes';
                } else if (t === 'referral') {
                    titleWrap.style.display = 'block';
                    titleInput.required = true;
                    if (titleInput.value === 'Patient note') titleInput.value = '';
                    dateLabel.textContent = 'Date *';
                    titleLabel.textContent = 'Title *';
                    bodyLabel.innerHTML = 'Details *';
                } else {
                    titleWrap.style.display = 'block';
                    titleInput.required = true;
                    dateLabel.textContent = 'Date *';
                    titleLabel.textContent = 'Title *';
                    bodyLabel.innerHTML = 'Details *';
                    submitBtn.textContent = 'Save entry';
                }
                syncTemplateFields();
            }

            if (typeEl && typeEl.tagName === 'SELECT') {
                typeEl.addEventListener('change', sync);
            }
            if (noteTemplate) noteTemplate.addEventListener('change', syncTemplateFields);
            sync();
        })();
    </script>
@endsection
