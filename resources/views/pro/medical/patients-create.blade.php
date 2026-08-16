@extends('layouts.app')

@section('page_title', 'New patient')

@section('content')
    <div style="max-width: 640px; margin: 0 auto; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; gap: 1rem; flex-wrap: wrap;">
            <h2 style="margin: 0; color: var(--primary-navy);">New patient</h2>
            <div style="display: flex; gap: 0.75rem; align-items: center;">
                <a href="/clients/create" style="font-size: 0.85rem; font-weight: 600; color: var(--primary-cerulean); text-decoration: none; border-bottom: 1px dotted var(--primary-cerulean);">Make client</a>
                <a href="/pro/medical/patients" style="color: var(--text-muted); font-weight: 600; text-decoration: none;">Cancel</a>
            </div>
        </div>

        @if($errors->any())
            <div style="background: #fef2f2; color: #991b1b; padding: 0.85rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
                @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif
        <form action="/pro/medical/patients" method="POST" id="patient-create-form">
            @csrf
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">
                    Display name <span style="color: #b91c1c;">*</span>
                    @include('partials.help-tip', ['text' => 'Link a billing client if you already invoice this person — name prefills. Clinical data stays in the vault.'])
                </label>
                <input type="text" name="display_name" id="display_name" value="{{ old('display_name') }}" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            @if($clients->isNotEmpty())
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Billing client</label>
                    <select name="billing_client_id" id="billing_client_id" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                        <option value="">None</option>
                        @foreach($clients as $client)
                            @php $already = in_array($client->id, $linkedClientIds, true); @endphp
                            <option value="{{ $client->id }}"
                                    data-name="{{ $client->name }}"
                                    {{ (string) old('billing_client_id') === (string) $client->id ? 'selected' : '' }}
                                    {{ $already ? 'disabled' : '' }}>
                                {{ $client->name }}{{ $already ? ' (already linked)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @else
                <input type="hidden" name="billing_client_id" value="">
            @endif
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">ID card</label>
                    <input type="text" name="id_card" value="{{ old('id_card') }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Tel</label>
                    <input type="text" name="tel" value="{{ old('tel') }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Address</label>
                <textarea name="address" rows="2" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ old('address') }}</textarea>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Date of birth</label>
                    <input type="date" name="date_of_birth" id="patient-dob" value="{{ old('date_of_birth') }}" max="{{ date('Y-m-d') }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Age</label>
                    <input type="text" name="age" id="patient-age" value="{{ old('age') }}" placeholder="e.g. 34" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>
            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Private notes</label>
                <textarea name="notes" rows="3" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ old('notes') }}</textarea>
            </div>
            <button type="submit" style="width: 100%; padding: 0.85rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Save patient</button>
        </form>
    </div>

    <script>
        (function () {
            var select = document.getElementById('billing_client_id');
            var nameInput = document.getElementById('display_name');
            var dob = document.getElementById('patient-dob');
            var age = document.getElementById('patient-age');

            if (select && nameInput) {
                select.addEventListener('change', function () {
                    var opt = select.options[select.selectedIndex];
                    if (!opt || !opt.value) return;
                    var clientName = opt.getAttribute('data-name') || '';
                    if (clientName && (!nameInput.value || nameInput.dataset.autofilled === '1')) {
                        nameInput.value = clientName;
                        nameInput.dataset.autofilled = '1';
                    }
                });
                nameInput.addEventListener('input', function () {
                    nameInput.dataset.autofilled = '0';
                });
            }

            function ageFromDob(value) {
                if (!value) return '';
                var parts = value.split('-');
                if (parts.length !== 3) return '';
                var y = parseInt(parts[0], 10);
                var m = parseInt(parts[1], 10) - 1;
                var d = parseInt(parts[2], 10);
                var birth = new Date(y, m, d);
                if (isNaN(birth.getTime())) return '';
                var today = new Date();
                var years = today.getFullYear() - birth.getFullYear();
                var md = today.getMonth() - birth.getMonth();
                if (md < 0 || (md === 0 && today.getDate() < birth.getDate())) years -= 1;
                return years >= 0 ? String(years) : '';
            }

            if (dob && age) {
                dob.addEventListener('change', function () {
                    var computed = ageFromDob(dob.value);
                    if (computed !== '') age.value = computed;
                });
            }
        })();
    </script>
@endsection
