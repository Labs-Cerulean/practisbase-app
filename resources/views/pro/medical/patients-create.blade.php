@extends('layouts.app')

@section('page_title', 'New patient')

@section('content')
    <div style="max-width: 640px; margin: 0 auto; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; margin-bottom: 1.25rem;">
            <h2 style="margin: 0; color: var(--primary-navy);">New patient</h2>
            <a href="/pro/medical/patients" style="color: var(--text-muted); font-weight: 600; text-decoration: none;">Cancel</a>
        </div>

        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: var(--radius-md); padding: 0.85rem 1rem; margin-bottom: 1.25rem; color: #1e3a8a; font-size: 0.85rem; line-height: 1.45;">
            Prefer linking an existing <strong>billing Client</strong> when you already invoice this person — we prefill the display name. Clinical notes stay encrypted in the vault; invoices stay on the Client. One person, one create path.
        </div>

        @if($errors->any())
            <div style="background: #fef2f2; color: #991b1b; padding: 0.85rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
                @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif
        <form action="/pro/medical/patients" method="POST" id="patient-create-form">
            @csrf
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Link billing Client <span style="font-weight: 500; color: var(--text-muted);">(optional)</span></label>
                @if($clients->isEmpty())
                    <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: var(--radius-md); padding: 0.85rem 1rem; color: #1e3a8a; font-size: 0.85rem; line-height: 1.45; margin-bottom: 0.5rem;">
                        You have no Clients yet. To invoice this person later:
                        <a href="/clients/create" style="color: #1e3a8a; font-weight: 700;">create a Client first</a>,
                        then create/link the patient. You can also save this patient clinical-only now and link later.
                    </div>
                    <input type="hidden" name="billing_client_id" value="">
                @else
                    <select name="billing_client_id" id="billing_client_id" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                        <option value="">No link — clinical patient only</option>
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
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.35rem;">
                        Pick a Client to link (name prefills). Missing someone?
                        <a href="/clients/create" style="color: var(--primary-cerulean); font-weight: 600;">Create Client</a> first.
                    </div>
                @endif
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Display name</label>
                <input type="text" name="display_name" id="display_name" value="{{ old('display_name') }}" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Date of birth <span style="font-weight: 500; color: var(--text-muted);">(optional, clinical)</span></label>
                <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" max="{{ date('Y-m-d') }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Private notes <span style="font-weight: 500; color: var(--text-muted);">(encrypted)</span></label>
                <textarea name="notes" rows="3" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ old('notes') }}</textarea>
            </div>
            <button type="submit" style="width: 100%; padding: 0.85rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Save encrypted patient</button>
        </form>
    </div>

    <script>
        (function () {
            var select = document.getElementById('billing_client_id');
            var nameInput = document.getElementById('display_name');
            if (!select || !nameInput) return;
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
        })();
    </script>
@endsection
