@extends('layouts.app')

@section('page_title', 'Edit Patient')

@section('content')
    <div style="max-width: 640px; margin: 0 auto; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; margin-bottom: 1.25rem;">
            <h2 style="margin: 0; color: var(--primary-navy);">Edit patient</h2>
            <a href="/pro/medical/patients/{{ $patient->id }}" style="color: var(--text-muted); font-weight: 600; text-decoration: none;">Cancel</a>
        </div>
        <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">Patient ref {{ $patient->public_ref }}</div>
        @if($errors->any())
            <div style="background: #fef2f2; color: #991b1b; padding: 0.85rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
                @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif
        <form action="/pro/medical/patients/{{ $patient->id }}" method="POST">
            @csrf
            @method('PUT')
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Display name</label>
                <input type="text" name="display_name" value="{{ old('display_name', $payload['display_name'] ?? '') }}" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Date of birth <span style="font-weight: 500; color: var(--text-muted);">(optional)</span></label>
                <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $payload['date_of_birth'] ?? '') }}" max="{{ date('Y-m-d') }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Private notes <span style="font-weight: 500; color: var(--text-muted);">(encrypted)</span></label>
                <textarea name="notes" rows="3" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ old('notes', $payload['notes'] ?? '') }}</textarea>
            </div>
            <button type="submit" style="width: 100%; padding: 0.85rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Save patient</button>
        </form>
    </div>
@endsection
