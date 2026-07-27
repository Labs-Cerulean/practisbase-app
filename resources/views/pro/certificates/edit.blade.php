@extends('layouts.app')

@section('page_title', 'Edit certificate')

@section('content')
    <div style="max-width: 640px; margin: 0 auto; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; margin-bottom: 1.25rem;">
            <h2 style="margin: 0; color: var(--primary-navy);">Edit draft certificate</h2>
            <a href="/pro/certificates" style="color: var(--text-muted); font-weight: 600; text-decoration: none;">Cancel</a>
        </div>
        <div style="background: #fffbeb; color: #92400e; padding: 0.75rem 1rem; border-radius: var(--radius-md); margin-bottom: 1rem; font-size: 0.85rem;">
            Editable until you press <strong>Stamp &amp; issue</strong> on the register.
        </div>
        @if($errors->any())
            <div style="background: #fef2f2; color: #991b1b; padding: 0.85rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
                @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif
        <form action="/pro/certificates/{{ $certificate->id }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Title</label>
                <input type="text" name="title" value="{{ old('title', $certificate->title) }}" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Kind</label>
                <select name="kind" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                    @foreach($kinds as $key => $label)
                        <option value="{{ $key }}" {{ old('kind', $certificate->kind) === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Subject / recipient / site</label>
                <input type="text" name="subject_name" value="{{ old('subject_name', $certificate->subject_name) }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Document date</label>
                    <input type="date" name="issued_on" value="{{ old('issued_on', $certificate->issued_on->format('Y-m-d')) }}" max="{{ date('Y-m-d') }}" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Expires on</label>
                    <input type="date" name="expires_on" value="{{ old('expires_on', optional($certificate->expires_on)->format('Y-m-d')) }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Replace photo / scan</label>
                <input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp">
            </div>
            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Notes</label>
                <textarea name="notes" rows="3" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ old('notes', $certificate->notes) }}</textarea>
            </div>
            <button type="submit" style="width: 100%; padding: 0.85rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Save draft</button>
        </form>
    </div>
@endsection
