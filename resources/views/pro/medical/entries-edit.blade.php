@extends('layouts.app')

@section('page_title', 'Edit Clinical Entry')

@section('content')
    <div style="max-width: 720px; margin: 0 auto; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; margin-bottom: 1.25rem;">
            <h2 style="margin: 0; color: var(--primary-navy);">Edit {{ $types[$entry->entry_type] ?? 'entry' }}</h2>
            <a href="/pro/medical/patients/{{ $patient->id }}" style="color: var(--text-muted); font-weight: 600; text-decoration: none;">Cancel</a>
        </div>
        @if($entry->isStampable())
            <div style="background: #fffbeb; color: #92400e; padding: 0.75rem 1rem; border-radius: var(--radius-md); margin-bottom: 1rem; font-size: 0.85rem;">
                Draft document — still editable. After <strong>Stamp &amp; issue</strong> it locks permanently and the official PDF template prints the authenticity code + date.
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
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">
                    {{ $entry->entry_type === 'certificate' ? 'Document / issued date' : 'Date' }}
                </label>
                <input type="date" name="entry_date" value="{{ old('entry_date', $entry->entry_date->format('Y-m-d')) }}" max="{{ date('Y-m-d') }}" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>

            @if($entry->entry_type === 'certificate')
                <div style="margin-bottom: 1rem; padding: 1rem; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: var(--radius-md);">
                    <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #14532d; margin-bottom: 0.75rem;">Certificate / declaration details</div>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Kind</label>
                        <select name="certificate_kind" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                            @foreach($certificateKinds as $key => $label)
                                <option value="{{ $key }}" {{ old('certificate_kind', $payload['certificate_kind'] ?? 'medical_certificate') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Subject / recipient</label>
                        <input type="text" name="subject_name" value="{{ old('subject_name', $payload['subject_name'] ?? ($patientPayload['display_name'] ?? '')) }}"
                               style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Expires on <span style="font-weight: 500; color: var(--text-muted);">(optional)</span></label>
                        <input type="date" name="expires_on" value="{{ old('expires_on', $payload['expires_on'] ?? '') }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    </div>
                </div>
            @endif

            @if($entry->entry_type === 'referral')
                <div style="margin-bottom: 1rem; padding: 1rem; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: var(--radius-md);">
                    <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #1e3a5f; margin-bottom: 0.75rem;">Referral details</div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Referred to <span style="font-weight: 500; color: var(--text-muted);">(optional)</span></label>
                    <input type="text" name="referred_to" value="{{ old('referred_to', $payload['referred_to'] ?? '') }}" placeholder="Clinician, clinic, or specialty"
                           style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            @endif

            @if($entry->entry_type === 'prescription')
                <div style="margin-bottom: 1rem; padding: 0.85rem 1rem; background: #f8fafc; border-left: 4px solid #0f172a; border-radius: var(--radius-md); font-size: 0.85rem; color: var(--text-muted);">
                    Title = medication / item. Body = dose, quantity, directions, repeats.
                </div>
            @endif

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">
                    @if($entry->entry_type === 'certificate') Certificate title
                    @elseif($entry->entry_type === 'prescription') Medication / item
                    @elseif($entry->entry_type === 'referral') Referral title
                    @else Title
                    @endif
                </label>
                <input type="text" name="title" value="{{ old('title', $payload['title'] ?? '') }}" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">
                    @if($entry->entry_type === 'certificate') Details / clinical statement (encrypted)
                    @elseif($entry->entry_type === 'prescription') Dose, quantity, directions (encrypted)
                    @else Body (encrypted at rest)
                    @endif
                </label>
                <textarea name="body" rows="8" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ old('body', $payload['body'] ?? '') }}</textarea>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Add photo / scan <span style="font-weight: 500; color: var(--text-muted);">(optional, encrypted)</span></label>
                <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf">
            </div>

            <button type="submit" style="width: 100%; padding: 0.85rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Save changes</button>
        </form>
    </div>
@endsection
