@extends('layouts.app')

@section('page_title', 'Edit patient')

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
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Display name *</label>
                <input type="text" name="display_name" value="{{ old('display_name', $payload['display_name'] ?? '') }}" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">ID card</label>
                    <input type="text" name="id_card" value="{{ old('id_card', $payload['id_card'] ?? '') }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Tel</label>
                    <input type="text" name="tel" value="{{ old('tel', $payload['tel'] ?? '') }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Email</label>
                <input type="email" name="email" value="{{ old('email', $payload['email'] ?? '') }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Address</label>
                <textarea name="address" rows="2" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ old('address', $payload['address'] ?? '') }}</textarea>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Date of birth</label>
                    <input type="date" name="date_of_birth" id="patient-dob" value="{{ old('date_of_birth', $payload['date_of_birth'] ?? '') }}" max="{{ date('Y-m-d') }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Age</label>
                    <input type="text" name="age" id="patient-age" value="{{ old('age', $payload['age'] ?? '') }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>
            <div style="margin-bottom: 1.25rem;">
                <label style="display: inline-flex; align-items: center; font-weight: 600; margin-bottom: 0.4rem;">
                    Private notes
                    @include('partials.help-tip', ['text' => 'Stored encrypted in your medical vault.'])
                </label>
                <textarea name="notes" rows="3" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ old('notes', $payload['notes'] ?? '') }}</textarea>
            </div>
            <button type="submit" style="width: 100%; padding: 0.85rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Save patient</button>
        </form>
    </div>

    <script>
        (function () {
            var dob = document.getElementById('patient-dob');
            var age = document.getElementById('patient-age');
            if (!dob || !age) return;
            function ageFromDob(value) {
                if (!value) return '';
                var parts = value.split('-');
                if (parts.length !== 3) return '';
                var birth = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
                if (isNaN(birth.getTime())) return '';
                var today = new Date();
                var years = today.getFullYear() - birth.getFullYear();
                var md = today.getMonth() - birth.getMonth();
                if (md < 0 || (md === 0 && today.getDate() < birth.getDate())) years -= 1;
                return years >= 0 ? String(years) : '';
            }
            dob.addEventListener('change', function () {
                var computed = ageFromDob(dob.value);
                if (computed !== '') age.value = computed;
            });
        })();
    </script>
@endsection
