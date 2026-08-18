@extends('layouts.app')

@section('page_title', 'Edit entry')

@section('content')
    @php
        $isRx = $entry->entry_type === 'prescription';
        $isJournal = $entry->entry_type === 'journal';
        $hasStructuredMeds = $isRx && ! empty($payload['medicines']) && is_array($payload['medicines']);
        $medicines = $isRx
            ? \App\Models\ClinicalEntry::medicinesFromPayload($payload)
            : [];
        if ($isRx && $medicines === []) {
            $medicines = [['name' => '', 'strength' => '', 'dose' => '', 'quantity' => '', 'instructions' => '']];
        }
        $rxTitle = $hasStructuredMeds ? ($payload['title'] ?? '') : '';
        $rxNotes = $hasStructuredMeds ? ($payload['body'] ?? '') : '';
        $certKind = old('certificate_kind', $payload['certificate_kind'] ?? 'medical_certificate');
        $subjectDefault = old('subject_name', $payload['subject_name'] ?? ($patientPayload['display_name'] ?? ''));
    @endphp
    <div style="max-width: {{ $isRx ? '820px' : '720px' }}; margin: 0 auto; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; margin-bottom: 1.25rem;">
            <h2 style="margin: 0; color: var(--primary-navy);">Edit {{ $types[$entry->entry_type] ?? 'entry' }}</h2>
            <a href="/pro/medical/patients/{{ $patient->id }}" style="color: var(--text-muted); font-weight: 600; text-decoration: none;">Cancel</a>
        </div>
        @if($entry->isStampable())
            <div style="background: #fffbeb; color: #92400e; padding: 0.75rem 1rem; border-radius: var(--radius-md); margin-bottom: 1rem; font-size: 0.85rem;">
                Draft — use <strong>Stamp &amp; issue</strong> when ready to confirm and share.
            </div>
        @endif
        @if($errors->any())
            <div style="background: #fef2f2; color: #991b1b; padding: 0.85rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
                @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif
        <form action="/pro/medical/patients/{{ $patient->id }}/entries/{{ $entry->id }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <input type="hidden" name="entry_type" value="{{ $entry->entry_type }}">

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Type</label>
                <input type="text" value="{{ $types[$entry->entry_type] ?? $entry->entry_type }}" disabled style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: #f8fafc; color: var(--text-muted);">
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Date *</label>
                <input type="date" name="entry_date" value="{{ old('entry_date', $entry->entry_date->format('Y-m-d')) }}" max="{{ date('Y-m-d') }}" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>

            @if($entry->entry_type === 'certificate')
                <div style="margin-bottom: 1rem; padding: 1rem; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: var(--radius-md);">
                    <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #14532d; margin-bottom: 0.75rem;">Certificate</div>
                    <input type="hidden" name="certificate_kind" value="{{ $certKind }}">
                    <input type="hidden" name="subject_name" value="{{ $subjectDefault }}">
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Expires on</label>
                        <input type="date" name="expires_on" value="{{ old('expires_on', $payload['expires_on'] ?? '') }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    </div>
                </div>
            @endif

            @if($entry->entry_type === 'referral')
                <div style="margin-bottom: 1rem; padding: 1rem; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: var(--radius-md);">
                    <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #1e3a5f; margin-bottom: 0.75rem;">Referral</div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Referred to</label>
                    <input type="text" name="referred_to" value="{{ old('referred_to', $payload['referred_to'] ?? '') }}" placeholder="Clinician or clinic"
                           style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            @endif

            @if($isRx)
                @include('pro.medical._prescription-medicines', [
                    'medicines' => old('medicines', $medicines),
                    'dispenseMode' => $payload['dispense_mode'] ?? 'single',
                    'visible' => true,
                ])
            @endif

            @if($isJournal)
                @include('pro.medical._journal-template-fields', [
                    'noteTemplate' => $noteTemplate ?? 'general',
                    'fieldValues' => old('fields', $fieldValues ?? []),
                    'templateCatalogue' => $templateCatalogue ?? [],
                    'visible' => true,
                ])
            @endif

            @if($isJournal)
                <input type="hidden" name="title" value="{{ old('title', $payload['title'] ?? 'Patient note') }}">
            @elseif($isRx)
                {{-- Title auto-derived from medicines on save --}}
                <input type="hidden" name="title" value="{{ old('title', $rxTitle) }}">
            @else
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Title *</label>
                    <input type="text" name="title" value="{{ old('title', $payload['title'] ?? '') }}" required
                           style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            @endif

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">
                    @if($entry->entry_type === 'certificate') Details *
                    @elseif($isRx) Notes
                    @elseif($isJournal) Additional notes
                    @else Details *
                    @endif
                </label>
                <textarea name="body" rows="{{ ($isRx || $isJournal) ? 3 : 8 }}" {{ ($isRx || $isJournal) ? '' : 'required' }}
                          style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ old('body', $isRx ? $rxNotes : ($isJournal ? ($payload['extra'] ?? '') : ($payload['body'] ?? ''))) }}</textarea>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label style="display: inline-flex; align-items: center; font-weight: 600; margin-bottom: 0.4rem;">
                    Add photo / scan
                    @include('partials.help-tip', ['text' => 'JPEG, PNG, WebP, or PDF · max 10 MB. Stored encrypted in your vault.'])
                </label>
                <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf">
            </div>

            <div style="display: grid; gap: 0.55rem;">
                @if($entry->isStampable())
                    <button type="submit" name="issue_now" value="1"
                            onclick="return confirm('Confirm, stamp, and lock this document? It cannot be edited afterwards. Share opens next.');"
                            style="width: 100%; padding: 0.9rem; background: var(--primary-navy); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">
                        Stamp &amp; issue
                    </button>
                    <button type="submit" name="issue_now" value="0"
                            style="width: 100%; padding: 0.75rem; background: white; color: var(--primary-navy); border: 1px solid var(--border-light); border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">
                        Save draft
                    </button>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0; text-align: center;">
                        Stamp &amp; issue confirms now — Share is ready on the patient page.
                    </p>
                @else
                    <button type="submit" style="width: 100%; padding: 0.85rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Save changes</button>
                @endif
            </div>
        </form>
    </div>

    @if($isJournal)
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
            } catch (e) {}

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
    @endif
@endsection
