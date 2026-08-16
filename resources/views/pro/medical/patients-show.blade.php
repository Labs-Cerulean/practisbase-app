@extends('layouts.app')

@section('page_title', 'Patient')

@section('content')
    <a href="/pro/medical/patients" style="color: var(--text-muted); font-weight: 600; text-decoration: none; font-size: 0.85rem;">&larr; Patients</a>
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin: 0.75rem 0 1.25rem; flex-wrap: wrap;">
        <div>
            <h1 style="margin: 0; color: var(--primary-navy);">{{ $payload['display_name'] ?? 'Patient' }}</h1>
            <div style="font-size: 0.85rem; color: var(--text-muted);">Patient ref {{ $patient->public_ref }}</div>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="/pro/medical/patients/{{ $patient->id }}/edit" style="background: white; border: 1px solid var(--border-light); color: var(--primary-navy); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">Edit patient</a>
            @php
                $journalChrome = \App\Models\ClinicalEntry::typeChrome('journal');
                $rxChrome = \App\Models\ClinicalEntry::typeChrome('prescription');
                $refChrome = \App\Models\ClinicalEntry::typeChrome('referral');
                $certChrome = \App\Models\ClinicalEntry::typeChrome('certificate');
            @endphp
            <a href="/pro/medical/patients/{{ $patient->id }}/entries/create?type=journal" style="background: {{ $journalChrome['soft'] }}; border: 1px solid {{ $journalChrome['border'] }}; color: {{ $journalChrome['badge_fg'] }}; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 700; text-decoration: none;">+ Patient notes</a>
            <a href="/pro/medical/patients/{{ $patient->id }}/entries/create?type=prescription" style="background: {{ $rxChrome['badge_bg'] }}; color: {{ $rxChrome['badge_fg'] }}; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 700; text-decoration: none;">+ Prescription</a>
            <a href="/pro/medical/patients/{{ $patient->id }}/entries/create?type=referral" style="background: {{ $refChrome['badge_bg'] }}; color: {{ $refChrome['badge_fg'] }}; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 700; text-decoration: none;">+ Referral</a>
            <a href="/pro/medical/patients/{{ $patient->id }}/entries/create?type=certificate" style="background: {{ $certChrome['badge_bg'] }}; color: {{ $certChrome['badge_fg'] }}; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 700; text-decoration: none;">+ Certificate</a>
        </div>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div style="background: #fef2f2; color: #991b1b; padding: 0.85rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    @php
        $demoDob = !empty($payload['date_of_birth']) ? \Illuminate\Support\Carbon::parse($payload['date_of_birth'])->format('d M Y') : '';
        $demoAge = trim((string) ($payload['age'] ?? ''));
        $demoId = trim((string) ($payload['id_card'] ?? ''));
        $demoTel = trim((string) ($payload['tel'] ?? ''));
        $demoEmail = trim((string) ($payload['email'] ?? ''));
        $demoAddress = trim((string) ($payload['address'] ?? ''));
        $demoNotes = trim((string) ($payload['notes'] ?? ''));
        $hasDemoGrid = $demoDob !== '' || $demoAge !== '' || $demoId !== '' || $demoTel !== '' || $demoEmail !== '';
        $hasDemoExtras = $hasDemoGrid || $demoAddress !== '' || $demoNotes !== '';
    @endphp
    <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.25rem; margin-bottom: 1.25rem; box-shadow: var(--shadow-sm);">
        @if($hasDemoExtras)
            @if($hasDemoGrid)
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 0.85rem; margin-bottom: 0.85rem;">
                @if($demoDob !== '')
                    <div>
                        <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">Date of birth</div>
                        <div>{{ $demoDob }}</div>
                    </div>
                @endif
                @if($demoAge !== '')
                    <div>
                        <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">Age</div>
                        <div>{{ $demoAge }}</div>
                    </div>
                @endif
                @if($demoId !== '')
                    <div>
                        <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">ID card</div>
                        <div>{{ $demoId }}</div>
                    </div>
                @endif
                @if($demoTel !== '')
                    <div>
                        <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">Tel</div>
                        <div>{{ $demoTel }}</div>
                    </div>
                @endif
                @if($demoEmail !== '')
                    <div>
                        <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">Email</div>
                        <div>{{ $demoEmail }}</div>
                    </div>
                @endif
            </div>
            @endif
            @if($demoAddress !== '')
                <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">Address</div>
                <div style="white-space: pre-wrap; margin-bottom: 0.75rem;">{{ $demoAddress }}</div>
            @endif
            @if($demoNotes !== '')
                <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">Notes</div>
                <div style="white-space: pre-wrap;">{{ $demoNotes }}</div>
            @endif
        @endif
    </div>

    @php
        $billingFormOpen = $errors->has('name') || $errors->has('type') || $errors->has('billing_client_id')
            || $errors->has('email') || $errors->has('phone') || $errors->has('billing_address');
    @endphp
    <div style="margin: -0.5rem 0 1.25rem; display: flex; justify-content: flex-end;">
        <details id="billing-client-panel" style="width: 100%; max-width: 28rem;" @if($billingFormOpen) open @endif>
            <summary style="cursor: pointer; list-style: none; display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.85rem; font-weight: 600; color: var(--primary-cerulean);">
                @if($patient->billingClient)
                    Billing: {{ $patient->billingClient->name }}
                @else
                    Make / link client
                @endif
            </summary>
            <div style="margin-top: 0.65rem; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 1rem; box-shadow: var(--shadow-sm);">
                @if($patient->billingClient)
                    <div style="margin-bottom: 0.75rem; font-size: 0.9rem;">
                        Linked to <a href="/clients/{{ $patient->billingClient->id }}" style="color: var(--primary-cerulean); font-weight: 700; text-decoration: none; border-bottom: 1px dotted var(--primary-navy);">{{ $patient->billingClient->name }}</a>
                    </div>
                    <form action="/pro/medical/patients/{{ $patient->id }}/billing-link" method="POST" style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
                        @csrf
                        @method('PUT')
                        <select name="billing_client_id" style="flex: 1; min-width: 160px; padding: 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
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
                        <button type="submit" style="background: var(--primary-navy); color: white; border: none; padding: 0.55rem 0.9rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer; font-size: 0.85rem;">Update</button>
                    </form>
                @else
                    @if(!($canAddClient ?? false))
                        <div style="font-size: 0.85rem; color: #92400e; margin-bottom: 0.75rem;">Client cap reached. Upgrade to create another.</div>
                    @else
                        <form action="/pro/medical/patients/{{ $patient->id }}/billing-client" method="POST" id="create-client-from-patient" style="margin-bottom: 0.85rem;">
                            @csrf
                            <div style="display: grid; gap: 0.55rem;">
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
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.55rem;">
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
                                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">ID card</label>
                                    <input type="text" name="id_card_number" value="{{ old('id_card_number') }}" style="width: 100%; padding: 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                                </div>
                                <div id="company-extra" style="display: none; gap: 0.55rem;">
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
                                    Create &amp; link
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

                    @if($clients->isNotEmpty())
                        <div style="border-top: 1px solid var(--border-light); padding-top: 0.75rem; margin-top: 0.25rem;">
                            <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.45rem;">Or link existing</div>
                            <form action="/pro/medical/patients/{{ $patient->id }}/billing-link" method="POST" style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
                                @csrf
                                @method('PUT')
                                <select name="billing_client_id" style="flex: 1; min-width: 160px; padding: 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                                    <option value="">Choose client…</option>
                                    @foreach($clients as $client)
                                        @php $taken = in_array($client->id, $linkedClientIds, true); @endphp
                                        <option value="{{ $client->id }}" {{ $taken ? 'disabled' : '' }}>
                                            {{ $client->name }}{{ $taken ? ' (linked elsewhere)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <button type="submit" style="background: var(--primary-navy); color: white; border: none; padding: 0.55rem 0.9rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer; font-size: 0.85rem;">Link</button>
                            </form>
                        </div>
                    @endif
                @endif
            </div>
        </details>
    </div>

    <h3 style="color: var(--primary-navy); margin-bottom: 0.35rem;">
        Clinical entries
        @include('partials.help-tip', ['text' => 'Prescriptions, referrals, and certificates stay editable until Stamp & issue. Patient notes stay editable. Browse all documents across patients in Documents.'])
    </h3>
    <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0 0 0.75rem;">
        <a href="/pro/medical/stampables" style="color: var(--primary-cerulean); font-weight: 700; text-decoration: none; border-bottom: 1px dotted var(--primary-navy);">Documents</a>
    </p>
    @include('pro.medical._type-colour-key', ['includeJournal' => true, 'margin' => '0 0 1rem'])
    @if($entries->isEmpty())
        <p style="color: var(--text-muted);">No entries yet.</p>
    @else
        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1rem 1.15rem; margin-bottom: 1rem; box-shadow: var(--shadow-sm);">
            <div style="display: grid; gap: 0.65rem;">
                <div style="display: flex; gap: 0.65rem; flex-wrap: wrap; align-items: flex-end;">
                    <div style="flex: 1; min-width: 180px;">
                        <label for="entry-search" style="display: block; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.3rem;">Search</label>
                        <input id="entry-search" type="search" placeholder="Search entries…"
                               style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    </div>
                    <button type="button" id="entry-advanced-toggle" aria-expanded="false" aria-controls="entry-advanced-filters"
                            style="padding: 0.65rem 1rem; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-weight: 700; color: var(--primary-navy); cursor: pointer; font-size: 0.85rem;">
                        Advanced
                    </button>
                </div>
                <div id="entry-advanced-filters" style="display: none;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.65rem;">
                        <div>
                            <label for="entry-filter-type" style="display: block; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.3rem;">Type</label>
                            <select id="entry-filter-type" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                                <option value="all">All types</option>
                                @foreach(($entryTypes ?? []) as $typeKey => $typeLabel)
                                    <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="entry-filter-status" style="display: block; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.3rem;">Status</label>
                            <select id="entry-filter-status" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                                <option value="all">All</option>
                                <option value="draft">Draft</option>
                                <option value="issued">Issued</option>
                                <option value="journal">Patient notes</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div style="font-size: 0.8rem; color: var(--text-muted);">
                    Showing <strong id="entry-count-visible" style="color: var(--primary-navy);">{{ $entries->count() }}</strong> of {{ $entries->count() }}
                    <button type="button" id="entry-filter-reset" style="margin-left: 0.75rem; background: none; border: none; color: var(--primary-cerulean); font-weight: 700; cursor: pointer; padding: 0;">Reset</button>
                </div>
            </div>
        </div>

        <div id="entry-list" style="display: grid; gap: 0.75rem;">
            @foreach($entries as $entry)
                @php
                    $type = $entry['model']->entry_type;
                    $chrome = \App\Models\ClinicalEntry::typeChrome($type);
                @endphp
                <div class="entry-row"
                     data-title="{{ strtolower($entry['title']) }}"
                     data-body="{{ strtolower($entry['body'] . ' ' . collect($entry['medicines'] ?? [])->pluck('name')->implode(' ')) }}"
                     data-type="{{ $type }}"
                     data-code="{{ strtolower($entry['issue_code'] ?? '') }}"
                     data-status="{{ $entry['is_stampable'] ? ($entry['is_issued'] ? 'issued' : 'draft') : 'journal' }}"
                     style="background: {{ $chrome['card_bg'] }}; border: 1px solid {{ $chrome['border'] }}; border-left: 6px solid {{ $chrome['accent'] }}; border-radius: var(--radius-md); padding: 1rem; box-shadow: var(--shadow-sm);">
                    <div style="display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; align-items: flex-start;">
                        <div style="flex: 1; min-width: 180px;">
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center; margin-bottom: 0.35rem;">
                                <span style="display: inline-block; background: {{ $chrome['badge_bg'] }}; color: {{ $chrome['badge_fg'] }}; font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; padding: 0.28rem 0.65rem; border-radius: 4px;">
                                    {{ $entry['type_label'] }}
                                </span>
                                @if($entry['is_stampable'])
                                    @if($entry['is_issued'])
                                        <span style="font-size: 0.7rem; font-weight: 800; color: #065f46; text-transform: uppercase; background: #d1fae5; padding: 0.2rem 0.5rem; border-radius: 4px;">Issued</span>
                                    @else
                                        <span style="font-size: 0.7rem; font-weight: 800; color: #92400e; text-transform: uppercase; background: #fef3c7; padding: 0.2rem 0.5rem; border-radius: 4px;">Draft</span>
                                    @endif
                                @endif
                            </div>
                            <strong style="color: var(--primary-navy); font-size: 1.05rem;">{{ $entry['title'] }}</strong>
                            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">
                                {{ $entry['model']->entry_date->format('d M Y') }}
                                @if($type === 'certificate' && !empty($entry['certificate_kind_label']))
                                    · {{ $entry['certificate_kind_label'] }}
                                @endif
                                @if($type === 'certificate' && !empty($entry['subject_name']))
                                    · Subject: {{ $entry['subject_name'] }}
                                @endif
                                @if($type === 'referral' && !empty($entry['referred_to']))
                                    · To: {{ $entry['referred_to'] }}
                                @endif
                                @if(!empty($entry['expires_on']))
                                    · Expires {{ \Illuminate\Support\Carbon::parse($entry['expires_on'])->format('d M Y') }}
                                @endif
                                @if($entry['is_issued'])
                                    · Issued {{ $entry['issued_at']->format('d M Y H:i') }}
                                    @if(!empty($entry['issue_code']))
                                        · <span style="font-family: ui-monospace, monospace; letter-spacing: 0.04em; color: var(--primary-navy); font-weight: 700;">{{ $entry['issue_code'] }}</span>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($type === 'prescription' && ! empty($entry['medicines']))
                        <div style="margin-top: 0.75rem; display: grid; gap: 0.55rem;">
                            @foreach($entry['medicines'] as $mi => $med)
                                <div style="background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.7rem 0.85rem;">
                                    <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">{{ $mi + 1 }}. Medicine</div>
                                    <div style="font-weight: 700; color: var(--primary-navy); margin-top: 0.15rem;">
                                        {{ $med['name'] }}
                                        @if(($med['strength'] ?? '') !== '')
                                            <span style="font-weight: 600; color: var(--text-muted);"> · {{ $med['strength'] }}</span>
                                        @endif
                                    </div>
                                    @if(($med['dose'] ?? '') !== '')
                                        <div style="font-size: 0.85rem; margin-top: 0.2rem;"><span style="color: var(--text-muted);">Dose:</span> {{ $med['dose'] }}</div>
                                    @endif
                                    @if(($med['quantity'] ?? '') !== '')
                                        <div style="font-size: 0.85rem;"><span style="color: var(--text-muted);">Qty:</span> {{ $med['quantity'] }}</div>
                                    @endif
                                    @if(($med['instructions'] ?? '') !== '')
                                        <div style="font-size: 0.85rem; white-space: pre-wrap;"><span style="color: var(--text-muted);">Directions:</span> {{ $med['instructions'] }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        @if(! empty($entry['has_structured_medicines']) && trim((string) $entry['body']) !== '')
                            <div style="margin-top: 0.65rem; color: var(--text-main); white-space: pre-wrap; font-size: 0.9rem;">
                                <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Notes</span><br>
                                {{ $entry['body'] }}
                            </div>
                        @endif
                    @elseif(!empty($entry['fields']) && is_array($entry['fields']))
                        @php
                            $fieldDefs = [];
                            if (!empty($entry['field_defs']) && is_array($entry['field_defs'])) {
                                $fieldDefs = $entry['field_defs'];
                            } else {
                                $fieldDefs = \App\Support\ClinicalNoteTemplates::fieldsListFromMap(
                                    \App\Support\ClinicalNoteTemplates::fields($entry['template'] ?? 'general')
                                );
                            }
                            $templateLabel = $entry['template_name']
                                ?? (\App\Support\ClinicalNoteTemplates::builtinOptions()[$entry['template'] ?? ''] ?? 'Consult');
                        @endphp
                        <div style="margin-top: 0.65rem; display: grid; gap: 0.55rem;">
                            <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                {{ $templateLabel }}
                            </div>
                            @foreach($fieldDefs as $def)
                                @php $fieldKey = is_array($def) ? ($def['key'] ?? '') : ''; @endphp
                                @if($fieldKey !== '' && trim((string) ($entry['fields'][$fieldKey] ?? '')) !== '')
                                    <div>
                                        <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">{{ $def['label'] ?? $fieldKey }}</div>
                                        <div style="white-space: pre-wrap; font-size: 0.9rem;">{{ $entry['fields'][$fieldKey] }}</div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <div style="margin-top: 0.65rem; color: var(--text-main); white-space: pre-wrap; font-size: 0.9rem;">{{ $entry['body'] }}</div>
                    @endif

                    @if($entry['is_stampable'])
                        <div style="margin-top: 0.75rem; padding: 0.65rem 0.85rem; background: {{ $chrome['soft'] }}; border: 1px solid {{ $chrome['border'] }}; border-radius: var(--radius-md); font-size: 0.8rem; color: var(--text-main);">
                            @if($entry['is_issued'])
                                Official PDF ready with issue code and date.
                            @else
                                After Stamp &amp; issue this locks and downloads as PDF.
                            @endif
                        </div>
                    @endif

                    <div style="margin-top: 0.75rem; display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
                        @if($entry['is_editable'])
                            <a href="/pro/medical/patients/{{ $patient->id }}/entries/{{ $entry['model']->id }}/edit"
                               style="display: inline-block; padding: 0.4rem 0.75rem; border: 1px solid var(--border-light); color: var(--primary-navy); border-radius: var(--radius-md); font-size: 0.8rem; font-weight: 700; text-decoration: none;">
                                Edit
                            </a>
                        @endif

                        @if($entry['is_stampable'] && ! $entry['is_issued'])
                            <form action="/pro/medical/patients/{{ $patient->id }}/entries/{{ $entry['model']->id }}/issue"
                                  method="post"
                                  style="margin: 0; display: inline;">
                                @csrf
                                <button type="submit"
                                        formmethod="post"
                                        formaction="/pro/medical/patients/{{ $patient->id }}/entries/{{ $entry['model']->id }}/issue"
                                        onclick="return confirm('Stamp and issue this document? A unique code and date will be printed on the PDF. It cannot be edited afterwards.');"
                                        style="padding: 0.4rem 0.75rem; background: {{ $chrome['badge_bg'] }}; color: {{ $chrome['badge_fg'] }}; border: none; border-radius: var(--radius-md); font-size: 0.8rem; font-weight: 700; cursor: pointer;">
                                    Stamp &amp; issue
                                </button>
                            </form>
                        @endif

                        @if($entry['is_stampable'] && $entry['is_issued'])
                            <a href="/pro/medical/patients/{{ $patient->id }}/entries/{{ $entry['model']->id }}/pdf"
                               style="display: inline-block; padding: 0.4rem 0.75rem; border: 1px solid {{ $chrome['accent'] }}; background: {{ $chrome['badge_bg'] }}; color: {{ $chrome['badge_fg'] }}; border-radius: var(--radius-md); font-size: 0.8rem; font-weight: 700; text-decoration: none;">
                                Download issued PDF
                            </a>
                        @endif
                    </div>

                    @if(!empty($entry['attachments']) && count($entry['attachments']))
                        <div style="margin-top: 0.85rem; padding-top: 0.75rem; border-top: 1px solid var(--border-light);">
                            <div style="font-size: 0.7rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 0.4rem;">Attachments</div>
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

                    @if($entry['is_editable'])
                        <form action="/pro/medical/patients/{{ $patient->id }}/entries/{{ $entry['model']->id }}/attachments"
                              method="POST"
                              enctype="multipart/form-data"
                              style="margin-top: 0.85rem; padding-top: 0.75rem; border-top: 1px solid var(--border-light);">
                            @csrf
                            <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.35rem;">
                                Add photo / scan
                                @include('partials.help-tip', ['text' => 'JPEG, PNG, WebP, or PDF · max 10 MB. Stored encrypted in your vault.'])
                            </label>
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
                                <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf" required
                                       style="font-size: 0.85rem;">
                                <button type="submit" style="background: var(--primary-navy); color: white; border: none; padding: 0.45rem 0.85rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer; font-size: 0.8rem;">
                                    Attach
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
        <div id="entry-empty-filter" style="display: none; padding: 2rem; text-align: center; color: var(--text-muted); border: 2px dashed var(--border-light); border-radius: var(--radius-md); background: white; margin-top: 0.75rem;">
            No entries match these filters.
        </div>

        <script>
            (function () {
                var search = document.getElementById('entry-search');
                var type = document.getElementById('entry-filter-type');
                var status = document.getElementById('entry-filter-status');
                var reset = document.getElementById('entry-filter-reset');
                var list = document.getElementById('entry-list');
                var countEl = document.getElementById('entry-count-visible');
                var empty = document.getElementById('entry-empty-filter');
                var advancedToggle = document.getElementById('entry-advanced-toggle');
                var advancedPanel = document.getElementById('entry-advanced-filters');
                if (!search || !list) return;

                if (advancedToggle && advancedPanel) {
                    advancedToggle.addEventListener('click', function () {
                        var open = advancedPanel.style.display !== 'none';
                        advancedPanel.style.display = open ? 'none' : 'block';
                        advancedToggle.setAttribute('aria-expanded', open ? 'false' : 'true');
                        advancedToggle.textContent = open ? 'Advanced' : 'Hide filters';
                    });
                }

                function apply() {
                    var q = (search.value || '').trim().toLowerCase();
                    var typeMode = type.value;
                    var statusMode = status.value;
                    var items = Array.prototype.slice.call(list.querySelectorAll('.entry-row'));
                    var visible = 0;
                    items.forEach(function (el) {
                        var hay = [el.dataset.title, el.dataset.body, el.dataset.type, el.dataset.code || ''].join(' ');
                        var matchQ = !q || hay.indexOf(q) !== -1;
                        var matchType = typeMode === 'all' || el.dataset.type === typeMode;
                        var matchStatus = statusMode === 'all' || el.dataset.status === statusMode;
                        var show = matchQ && matchType && matchStatus;
                        el.style.display = show ? 'block' : 'none';
                        if (show) visible++;
                    });
                    countEl.textContent = String(visible);
                    empty.style.display = visible === 0 ? 'block' : 'none';
                }

                search.addEventListener('input', apply);
                type.addEventListener('change', apply);
                status.addEventListener('change', apply);
                reset.addEventListener('click', function () {
                    search.value = '';
                    type.value = 'all';
                    status.value = 'all';
                    apply();
                });
                apply();
            })();
        </script>
    @endif
@endsection
