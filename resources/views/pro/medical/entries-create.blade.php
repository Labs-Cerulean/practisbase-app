@extends('layouts.app')

@section('page_title', 'New entry')

@section('content')
    <div style="max-width: 820px; margin: 0 auto; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; margin-bottom: 1.25rem;">
            <h2 style="margin: 0; color: var(--primary-navy);">New clinical entry</h2>
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
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Type</label>
                <select name="entry_type" id="entry_type" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                    @foreach($types as $key => $label)
                        <option value="{{ $key }}" {{ ($defaultType ?? 'journal') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <div id="type-guide" style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.45rem;"></div>
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;" id="date-label">Date</label>
                <input type="date" name="entry_date" id="entry_date" value="{{ old('entry_date', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>

            <div id="certificate-fields" style="display: none;">
                <div style="margin-bottom: 1rem; padding: 1rem; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: var(--radius-md);">
                    <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #14532d; margin-bottom: 0.75rem;">Certificate / declaration details</div>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Kind</label>
                        <select name="certificate_kind" id="certificate_kind" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                            @foreach($certificateKinds as $key => $label)
                                <option value="{{ $key }}" {{ old('certificate_kind', 'medical_certificate') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Subject / recipient</label>
                        <input type="text" name="subject_name" value="{{ old('subject_name', $patientPayload['display_name'] ?? '') }}"
                               style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.35rem;">Defaults to this patient. Change if the certificate is for another named party.</div>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Expires on <span style="font-weight: 500; color: var(--text-muted);">(optional)</span></label>
                        <input type="date" name="expires_on" value="{{ old('expires_on') }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    </div>
                </div>
            </div>

            <div id="referral-fields" style="display: none; margin-bottom: 1rem; padding: 1rem; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: var(--radius-md);">
                <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #1e3a5f; margin-bottom: 0.75rem;">Referral details</div>
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Referred to <span style="font-weight: 500; color: var(--text-muted);">(optional)</span></label>
                <input type="text" name="referred_to" value="{{ old('referred_to') }}" placeholder="Clinician, clinic, or specialty"
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
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;" id="title-label">Title</label>
                <input type="text" name="title" id="title" value="{{ old('title') }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                <div id="title-hint" style="display: none; font-size: 0.75rem; color: var(--text-muted); margin-top: 0.35rem;">Optional label for this prescription (defaults to the first medicine or “Prescription (N medicines)”).</div>
            </div>
            <div id="body-wrap" style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;" id="body-label">Body (encrypted at rest)</label>
                <textarea name="body" id="body" rows="8" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ old('body') }}</textarea>
                <div id="journal-body-hint" style="display: none; font-size: 0.75rem; color: var(--text-muted); margin-top: 0.35rem;">Optional free-text in addition to the structured consult fields above.</div>
            </div>

            <div id="attachment-fields" style="display: none; margin-bottom: 1.25rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Photo / scan <span style="font-weight: 500; color: var(--text-muted);">(optional, encrypted)</span></label>
                <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf">
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.35rem;">Stored encrypted in your medical vault (JPEG, PNG, WebP, PDF · max 10 MB).</div>
            </div>

            <button type="submit" id="submit-btn" style="width: 100%; padding: 0.85rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Save encrypted entry</button>
            <div id="stamp-note" style="display: none; font-size: 0.75rem; color: var(--text-muted); margin-top: 0.5rem; text-align: center;">
                Editable until you Stamp &amp; issue on the patient record. Official PDF uses the type-specific template with authenticity code.
            </div>
        </form>
    </div>

    <script>
        (function () {
            var typeEl = document.getElementById('entry_type');
            var cert = document.getElementById('certificate-fields');
            var referral = document.getElementById('referral-fields');
            var rxFields = document.getElementById('prescription-fields');
            var journalFields = document.getElementById('journal-template-fields');
            var journalBodyHint = document.getElementById('journal-body-hint');
            var noteTemplate = document.getElementById('note_template');
            var fieldsHost = document.getElementById('journal-structured-fields');
            var attach = document.getElementById('attachment-fields');
            var guide = document.getElementById('type-guide');
            var dateLabel = document.getElementById('date-label');
            var titleLabel = document.getElementById('title-label');
            var bodyLabel = document.getElementById('body-label');
            var titleInput = document.getElementById('title');
            var bodyInput = document.getElementById('body');
            var titleHint = document.getElementById('title-hint');
            var submitBtn = document.getElementById('submit-btn');
            var stampNote = document.getElementById('stamp-note');
            var kindEl = document.getElementById('certificate_kind');

            var guides = {
                journal: 'Private clinical note using your chosen template. Stays editable. Not stamped.',
                prescription: 'Add one or more medicines below. Stamp & issue locks the prescription and prints a unique RX code + date on the PDF.',
                referral: 'Referral letter for a receiving clinician. Stamp & issue locks it and prints a unique RF code + date.',
                certificate: 'Certificate, declaration, attestation, or fitness clearance. Stamp & issue locks it and prints a unique MC code + date.'
            };

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
                fieldsHost.querySelectorAll('textarea[data-field-key]').forEach(function (el) {
                    valueStore[el.getAttribute('data-field-key')] = el.value;
                });
            }

            function findTemplate(key) {
                for (var i = 0; i < catalogue.length; i++) {
                    if (catalogue[i].key === key) return catalogue[i];
                }
                return catalogue[0] || null;
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
                        '<textarea name="fields[' + escapeHtml(field.key) + ']" data-field-key="' + escapeHtml(field.key) + '" rows="' + (field.rows || 2) + '" ' +
                        'style="width:100%;padding:0.65rem;border:1px solid var(--border-light);border-radius:var(--radius-md);">' +
                        escapeHtml(val) + '</textarea></div>';
                }).join('');
            }

            function sync() {
                var t = typeEl.value;
                cert.style.display = t === 'certificate' ? 'block' : 'none';
                referral.style.display = t === 'referral' ? 'block' : 'none';
                rxFields.style.display = t === 'prescription' ? 'block' : 'none';
                if (journalFields) journalFields.style.display = t === 'journal' ? 'block' : 'none';
                if (journalBodyHint) journalBodyHint.style.display = t === 'journal' ? 'block' : 'none';
                attach.style.display = (t === 'certificate' || t === 'referral' || t === 'prescription' || t === 'journal') ? 'block' : 'none';
                stampNote.style.display = (t === 'certificate' || t === 'referral' || t === 'prescription') ? 'block' : 'none';
                guide.textContent = guides[t] || '';
                kindEl.required = t === 'certificate';
                titleInput.required = t !== 'prescription';
                bodyInput.required = (t !== 'prescription' && t !== 'journal');
                titleHint.style.display = t === 'prescription' ? 'block' : 'none';
                bodyInput.rows = t === 'prescription' || t === 'journal' ? 3 : 8;

                if (t === 'certificate') {
                    dateLabel.textContent = 'Document / issued date';
                    titleLabel.textContent = 'Certificate title';
                    bodyLabel.textContent = 'Details / clinical statement (encrypted)';
                    submitBtn.textContent = 'Save certificate draft';
                } else if (t === 'prescription') {
                    dateLabel.textContent = 'Prescription date';
                    titleLabel.textContent = 'Prescription label (optional)';
                    bodyLabel.textContent = 'General notes for pharmacist / patient (optional, encrypted)';
                    submitBtn.textContent = 'Save prescription draft';
                } else if (t === 'referral') {
                    dateLabel.textContent = 'Referral date';
                    titleLabel.textContent = 'Referral title';
                    bodyLabel.textContent = 'Clinical details for receiving clinician (encrypted)';
                    submitBtn.textContent = 'Save referral draft';
                } else {
                    dateLabel.textContent = 'Date';
                    titleLabel.textContent = 'Title';
                    bodyLabel.textContent = 'Additional notes (optional, encrypted)';
                    submitBtn.textContent = 'Save encrypted journal note';
                }
                syncTemplateFields();
            }

            typeEl.addEventListener('change', sync);
            if (noteTemplate) noteTemplate.addEventListener('change', syncTemplateFields);
            sync();
        })();
    </script>
@endsection
