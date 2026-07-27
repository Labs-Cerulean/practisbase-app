@extends('layouts.app')

@section('page_title', 'Patient')

@section('content')
    <a href="/pro/medical/patients" style="color: var(--text-muted); font-weight: 600; text-decoration: none; font-size: 0.85rem;">&larr; Patients</a>
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin: 0.75rem 0 1.25rem;">
        <div>
            <h1 style="margin: 0; color: var(--primary-navy);">{{ $payload['display_name'] ?? 'Patient' }}</h1>
            <div style="font-size: 0.85rem; color: var(--text-muted);">Patient ref {{ $patient->public_ref }}</div>
        </div>
        <a href="/pro/medical/patients/{{ $patient->id }}/entries/create" style="background: var(--primary-cerulean); color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">+ Clinical entry</a>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div style="background: #fef2f2; color: #991b1b; padding: 0.85rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.25rem; margin-bottom: 1.25rem; box-shadow: var(--shadow-sm);">
        <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">Date of birth</div>
        <div style="margin-bottom: 0.75rem;">{{ !empty($payload['date_of_birth']) ? \Illuminate\Support\Carbon::parse($payload['date_of_birth'])->format('d M Y') : '—' }}</div>
        <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">Notes</div>
        <div style="white-space: pre-wrap; margin-bottom: 1rem;">{{ $payload['notes'] ?: '—' }}</div>

        <div style="border-top: 1px solid var(--border-light); padding-top: 1rem;">
            <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 0.4rem;">Billing Client link</div>
            @if($patient->billingClient)
                <div style="margin-bottom: 0.65rem; font-size: 0.9rem;">
                    Linked to <a href="/clients/{{ $patient->billingClient->id }}" style="color: var(--primary-cerulean); font-weight: 700; text-decoration: none; border-bottom: 1px dotted var(--primary-navy);">{{ $patient->billingClient->name }}</a>
                    — use that Client for invoices; keep clinical work here.
                </div>
            @else
                <div style="margin-bottom: 0.65rem; font-size: 0.85rem; color: var(--text-muted);">Not linked to a billing Client yet.</div>

                <div style="background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 1rem; margin-bottom: 1rem;">
                    <div style="font-weight: 700; color: var(--primary-navy); margin-bottom: 0.35rem;">Create Client from this patient</div>
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0 0 0.75rem; line-height: 1.4;">
                        Patient was created first? Add invoice details here. We prefill the name; clinical DOB/notes stay in the vault only.
                    </p>
                    @if(!($canAddClient ?? false))
                        <div style="background: #fffbeb; color: #92400e; padding: 0.65rem 0.85rem; border-radius: var(--radius-md); font-size: 0.85rem;">
                            Free lifetime client cap reached. Upgrade to Standard/Pro to create another Client.
                        </div>
                    @else
                        <form action="/pro/medical/patients/{{ $patient->id }}/billing-client" method="POST" id="create-client-from-patient">
                            @csrf
                            <div style="display: grid; gap: 0.65rem;">
                                <div>
                                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Type</label>
                                    <select name="type" id="client_type_from_patient" required style="width: 100%; padding: 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                                        <option value="individual" {{ old('type', 'individual') === 'individual' ? 'selected' : '' }}>Individual</option>
                                        <option value="company" {{ old('type') === 'company' ? 'selected' : '' }}>Company</option>
                                    </select>
                                </div>
                                <div>
                                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Billing name</label>
                                    <input type="text" name="name" value="{{ old('name', $payload['display_name'] ?? '') }}" required style="width: 100%; padding: 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.65rem;">
                                    <div>
                                        <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Email</label>
                                        <input type="email" name="email" value="{{ old('email') }}" style="width: 100%; padding: 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                                    </div>
                                    <div>
                                        <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Phone</label>
                                        <input type="text" name="phone" value="{{ old('phone') }}" style="width: 100%; padding: 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                                    </div>
                                </div>
                                <div>
                                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Billing address</label>
                                    <textarea name="billing_address" rows="2" style="width: 100%; padding: 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ old('billing_address') }}</textarea>
                                </div>
                                <div id="individual-extra" style="{{ old('type', 'individual') === 'company' ? 'display:none;' : '' }}">
                                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">ID card (optional)</label>
                                    <input type="text" name="id_card_number" value="{{ old('id_card_number') }}" style="width: 100%; padding: 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                                </div>
                                <div id="company-extra" style="display: none; gap: 0.65rem;">
                                    <div>
                                        <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">VAT number</label>
                                        <input type="text" name="vat_number" value="{{ old('vat_number') }}" style="width: 100%; padding: 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                                    </div>
                                    <div>
                                        <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Registration number</label>
                                        <input type="text" name="registration_number" value="{{ old('registration_number') }}" style="width: 100%; padding: 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                                    </div>
                                    <div>
                                        <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Contact person</label>
                                        <input type="text" name="contact_person" value="{{ old('contact_person') }}" style="width: 100%; padding: 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                                    </div>
                                </div>
                                <button type="submit" style="background: var(--primary-cerulean); color: white; border: none; padding: 0.65rem 1rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">
                                    Create Client &amp; link
                                </button>
                            </div>
                        </form>
                        <script>
                            (function () {
                                var type = document.getElementById('client_type_from_patient');
                                var ind = document.getElementById('individual-extra');
                                var co = document.getElementById('company-extra');
                                if (!type || !ind || !co) return;
                                function sync() {
                                    var isCo = type.value === 'company';
                                    ind.style.display = isCo ? 'none' : 'block';
                                    co.style.display = isCo ? 'grid' : 'none';
                                }
                                type.addEventListener('change', sync);
                                sync();
                            })();
                        </script>
                    @endif
                </div>
            @endif

            @if(! $patient->billingClient)
                @if($clients->isEmpty())
                    <div style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.4;">
                        Or create a Client under <a href="/clients/create" style="color: var(--primary-cerulean); font-weight: 600;">Clients Directory</a> and link it below later.
                    </div>
                @else
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0.75rem 0 0.5rem; line-height: 1.4;">
                        Or link an <strong>existing</strong> Client:
                    </p>
                    <form action="/pro/medical/patients/{{ $patient->id }}/billing-link" method="POST" style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
                        @csrf
                        @method('PUT')
                        <select name="billing_client_id" style="flex: 1; min-width: 180px; padding: 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                            <option value="">No billing link</option>
                            @foreach($clients as $client)
                                @php $taken = in_array($client->id, $linkedClientIds, true); @endphp
                                <option value="{{ $client->id }}" {{ $taken ? 'disabled' : '' }}>
                                    {{ $client->name }}{{ $taken ? ' (linked elsewhere)' : '' }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" style="background: var(--primary-navy); color: white; border: none; padding: 0.55rem 0.9rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer; font-size: 0.85rem;">Update link</button>
                    </form>
                @endif
            @else
                <form action="/pro/medical/patients/{{ $patient->id }}/billing-link" method="POST" style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center; margin-top: 0.75rem;">
                    @csrf
                    @method('PUT')
                    <select name="billing_client_id" style="flex: 1; min-width: 180px; padding: 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                        <option value="">No billing link</option>
                        @foreach($clients as $client)
                            @php $taken = in_array($client->id, $linkedClientIds, true); @endphp
                            <option value="{{ $client->id }}"
                                {{ (int) $patient->billing_client_id === (int) $client->id ? 'selected' : '' }}
                                {{ $taken ? 'disabled' : '' }}>
                                {{ $client->name }}{{ $taken ? ' (linked elsewhere)' : '' }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" style="background: var(--primary-navy); color: white; border: none; padding: 0.55rem 0.9rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer; font-size: 0.85rem;">Update link</button>
                </form>
            @endif
        </div>
    </div>

    <h3 style="color: var(--primary-navy);">Clinical entries</h3>
    @if($entries->isEmpty())
        <p style="color: var(--text-muted);">No journal / prescription / referral entries yet.</p>
    @else
        <div style="display: grid; gap: 0.75rem;">
            @foreach($entries as $entry)
                <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 1rem; box-shadow: var(--shadow-sm);">
                    <div style="display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                        <strong style="color: var(--primary-navy);">{{ $entry['title'] }}</strong>
                        <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted);">{{ $entry['type_label'] }} · {{ $entry['model']->entry_date->format('d M Y') }}</span>
                    </div>
                    <div style="margin-top: 0.5rem; color: var(--text-main); white-space: pre-wrap; font-size: 0.9rem;">{{ $entry['body'] }}</div>

                    @if(in_array($entry['model']->entry_type, ['prescription', 'referral'], true))
                        <div style="margin-top: 0.75rem;">
                            <a href="/pro/medical/patients/{{ $patient->id }}/entries/{{ $entry['model']->id }}/pdf"
                               style="display: inline-block; padding: 0.4rem 0.75rem; border: 1px solid var(--primary-navy); color: var(--primary-navy); border-radius: var(--radius-md); font-size: 0.8rem; font-weight: 700; text-decoration: none;">
                                Download signed PDF
                            </a>
                        </div>
                    @endif

                    @if(!empty($entry['attachments']))
                        <div style="margin-top: 0.85rem; padding-top: 0.75rem; border-top: 1px solid var(--border-light);">
                            <div style="font-size: 0.7rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 0.4rem;">Encrypted attachments</div>
                            <ul style="margin: 0; padding-left: 1.1rem;">
                                @foreach($entry['attachments'] as $att)
                                    <li style="margin-bottom: 0.25rem;">
                                        <a href="/pro/medical/patients/{{ $patient->id }}/attachments/{{ $att['id'] }}/download"
                                           style="color: var(--primary-cerulean); font-weight: 600; text-decoration: none; border-bottom: 1px dotted var(--primary-navy);">
                                            {{ $att['name'] }}
                                        </a>
                                        <span style="font-size: 0.75rem; color: var(--text-muted);"> · {{ $att['mime'] }} · {{ number_format($att['byte_size'] / 1024, 1) }} KB ciphertext</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="/pro/medical/patients/{{ $patient->id }}/entries/{{ $entry['model']->id }}/attachments"
                          method="POST"
                          enctype="multipart/form-data"
                          style="margin-top: 0.85rem; padding-top: 0.75rem; border-top: 1px solid var(--border-light);">
                        @csrf
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.35rem;">Add encrypted file (JPEG, PNG, WebP, PDF · max 10 MB)</label>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
                            <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf" required
                                   style="font-size: 0.85rem;">
                            <button type="submit" style="background: var(--primary-navy); color: white; border: none; padding: 0.45rem 0.85rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer; font-size: 0.8rem;">
                                Encrypt &amp; attach
                            </button>
                        </div>
                    </form>
                </div>
            @endforeach
        </div>
    @endif
@endsection
