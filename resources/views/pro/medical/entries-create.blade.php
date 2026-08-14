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

            @if($isOg ?? false)
                @include('pro.medical._og-consult-fields', [
                    'payload' => [
                        'consult_kind' => old('consult_kind'),
                        'lmp' => old('lmp'),
                        'presenting_complaint' => old('presenting_complaint'),
                        'exam' => old('exam'),
                        'ultrasound' => old('ultrasound'),
                        'plan' => old('plan'),
                        'consult_notes' => old('consult_notes'),
                    ],
                    'patientPayload' => $patientPayload ?? [],
                    'defaultConsultKind' => $defaultConsultKind ?? 'follow_up',
                    'showHistoryPrefill' => true,
                    'visible' => ($defaultType ?? 'journal') === 'journal',
                ])
            @endif

            <div id="title-wrap" style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;" id="title-label">Title</label>
                <input type="text" name="title" id="title" value="{{ old('title') }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                <div id="title-hint" style="display: none; font-size: 0.75rem; color: var(--text-muted); margin-top: 0.35rem;">Optional label for this prescription (defaults to the first medicine or “Prescription (N medicines)”).</div>
            </div>
            <div id="body-wrap" style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;" id="body-label">Body (encrypted at rest)</label>
                <textarea name="body" id="body" rows="8" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ old('body') }}</textarea>
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
            var ogFields = document.getElementById('og-consult-fields');
            var titleWrap = document.getElementById('title-wrap');
            var bodyWrap = document.getElementById('body-wrap');
            var isOg = {{ ($isOg ?? false) ? 'true' : 'false' }};

            var guides = {
                journal: isOg
                    ? 'O&G consult sheet. Full clerking copies standing history onto the patient chart. Follow-up is the dated progress note. Stays editable. Not stamped.'
                    : 'Private clinical note. Stays editable. Not stamped and not exported as an official PDF.',
                prescription: 'Add one or more medicines below. Stamp & issue locks the prescription and prints a unique RX code + date on the PDF.',
                referral: 'Referral letter for a receiving clinician. Stamp & issue locks it and prints a unique RF code + date.',
                certificate: 'Certificate, declaration, attestation, or fitness clearance. Stamp & issue locks it and prints a unique MC code + date.'
            };

            function syncOgKind() {
                var hist = document.getElementById('og-standing-history');
                var clerking = document.getElementById('consult_kind_clerking');
                var notesLabel = document.getElementById('og-notes-label');
                var guideEl = document.getElementById('consult-kind-guide');
                if (hist) {
                    hist.style.display = (clerking && clerking.checked) ? 'block' : 'none';
                }
                if (notesLabel) {
                    notesLabel.textContent = (clerking && clerking.checked) ? 'Extra notes' : 'Progress note';
                }
                if (guideEl) {
                    guideEl.textContent = (clerking && clerking.checked)
                        ? 'First-visit clerking: standing history + LMP, complaint, exam, US, and plan.'
                        : 'Later visit: dated note. Exam, US, and plan are optional.';
                }
            }

            function sync() {
                var t = typeEl.value;
                var ogJournal = isOg && t === 'journal';
                cert.style.display = t === 'certificate' ? 'block' : 'none';
                referral.style.display = t === 'referral' ? 'block' : 'none';
                rxFields.style.display = t === 'prescription' ? 'block' : 'none';
                if (ogFields) ogFields.style.display = ogJournal ? 'block' : 'none';
                attach.style.display = (t === 'certificate' || t === 'referral' || t === 'prescription' || t === 'journal') ? 'block' : 'none';
                stampNote.style.display = (t === 'certificate' || t === 'referral' || t === 'prescription') ? 'block' : 'none';
                guide.textContent = guides[t] || '';
                kindEl.required = t === 'certificate';
                titleInput.required = t !== 'prescription' && !ogJournal;
                bodyInput.required = t !== 'prescription' && !ogJournal;
                titleHint.style.display = (t === 'prescription' || ogJournal) ? 'block' : 'none';
                bodyInput.rows = t === 'prescription' ? 3 : 8;
                if (bodyWrap) bodyWrap.style.display = ogJournal ? 'none' : 'block';

                if (t === 'certificate') {
                    dateLabel.textContent = 'Document / issued date';
                    titleLabel.textContent = 'Certificate title';
                    bodyLabel.textContent = 'Details / clinical statement (encrypted)';
                    submitBtn.textContent = 'Save certificate draft';
                    titleHint.textContent = '';
                } else if (t === 'prescription') {
                    dateLabel.textContent = 'Prescription date';
                    titleLabel.textContent = 'Prescription label (optional)';
                    bodyLabel.textContent = 'General notes for pharmacist / patient (optional, encrypted)';
                    submitBtn.textContent = 'Save prescription draft';
                    titleHint.textContent = 'Optional label for this prescription (defaults to the first medicine or “Prescription (N medicines)”).';
                } else if (t === 'referral') {
                    dateLabel.textContent = 'Referral date';
                    titleLabel.textContent = 'Referral title';
                    bodyLabel.textContent = 'Clinical details for receiving clinician (encrypted)';
                    submitBtn.textContent = 'Save referral draft';
                    titleHint.textContent = '';
                } else if (ogJournal) {
                    dateLabel.textContent = 'Date of consult';
                    titleLabel.textContent = 'Label (optional)';
                    bodyLabel.textContent = 'Body (encrypted at rest)';
                    submitBtn.textContent = 'Save encrypted consult';
                    titleHint.textContent = 'Defaults to the presenting complaint, or “Consult” / “Follow-up”.';
                } else {
                    dateLabel.textContent = 'Date';
                    titleLabel.textContent = 'Title';
                    bodyLabel.textContent = 'Body (encrypted at rest)';
                    submitBtn.textContent = 'Save encrypted journal note';
                    titleHint.textContent = '';
                }
                syncOgKind();
            }

            typeEl.addEventListener('change', sync);
            document.querySelectorAll('input[name="consult_kind"]').forEach(function (el) {
                el.addEventListener('change', syncOgKind);
            });
            sync();
        })();
    </script>
@endsection
