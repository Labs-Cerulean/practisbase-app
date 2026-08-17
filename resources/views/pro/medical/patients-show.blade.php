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
            <a href="/pro/medical/stampables" style="background: white; border: 1px solid var(--border-light); color: var(--primary-navy); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">Documents</a>
        </div>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
            {{ session('success') }}
        </div>
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

    @php
        $hubTabs = [
            'journal' => ['label' => 'Patient notes', 'new' => '+ Note'],
            'prescription' => ['label' => 'Prescriptions', 'new' => '+ Prescription'],
            'referral' => ['label' => 'Referrals', 'new' => '+ Referral'],
            'certificate' => ['label' => 'Certificates', 'new' => '+ Certificate'],
        ];
        $issuedTypeFlash = session('issued_type');
        $initialHubTab = in_array($issuedTypeFlash, array_keys($hubTabs), true)
            ? $issuedTypeFlash
            : 'journal';
        $entriesByType = $entries->groupBy(fn ($row) => $row['model']->entry_type);
    @endphp

    <div style="margin-top: 0.25rem; margin-bottom: 1rem;">
        <div class="patient-hub-tabs" role="tablist" aria-label="Clinical document types"
             style="display: flex; flex-wrap: wrap; gap: 0.15rem; border-bottom: 1px solid var(--border-light);">
            @foreach($hubTabs as $tabKey => $tabMeta)
                @php
                    $tabChrome = \App\Models\ClinicalEntry::typeChrome($tabKey);
                    $tabCount = ($entriesByType->get($tabKey) ?? collect())->count();
                @endphp
                <button type="button"
                        class="patient-hub-tab"
                        role="tab"
                        data-tab="{{ $tabKey }}"
                        aria-selected="{{ $initialHubTab === $tabKey ? 'true' : 'false' }}"
                        style="padding: 0.7rem 0.95rem; background: none; border: none; border-bottom: 2px solid {{ $initialHubTab === $tabKey ? $tabChrome['accent'] : 'transparent' }}; margin-bottom: -1px; font-weight: 700; font-size: 0.85rem; color: {{ $initialHubTab === $tabKey ? 'var(--primary-navy)' : 'var(--text-muted)' }}; cursor: pointer;">
                    {{ $tabMeta['label'] }}
                    <span style="font-weight: 600; color: var(--text-muted); margin-left: 0.25rem;">{{ $tabCount }}</span>
                </button>
            @endforeach
        </div>
    </div>

    @foreach($hubTabs as $tabKey => $tabMeta)
        @php
            $tabChrome = \App\Models\ClinicalEntry::typeChrome($tabKey);
            $tabEntries = $entriesByType->get($tabKey) ?? collect();
        @endphp
        <div class="patient-hub-panel" data-tab-panel="{{ $tabKey }}" role="tabpanel"
             style="display: {{ $initialHubTab === $tabKey ? 'block' : 'none' }};">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 0.85rem;">
                <div style="font-size: 0.85rem; color: var(--text-muted);">
                    @if($tabKey === 'journal')
                        Notes stay editable.
                    @else
                        Drafts stay editable until Stamp &amp; issue.
                    @endif
                </div>
                <a href="/pro/medical/patients/{{ $patient->id }}/entries/create?type={{ $tabKey }}"
                   style="background: {{ $tabChrome['badge_bg'] }}; color: {{ $tabChrome['badge_fg'] }}; border: 1px solid {{ $tabChrome['border'] }}; padding: 0.5rem 0.95rem; border-radius: var(--radius-md); font-weight: 700; text-decoration: none; font-size: 0.85rem;">
                    {{ $tabMeta['new'] }}
                </a>
            </div>

            @if($tabEntries->isEmpty())
                <p style="color: var(--text-muted); margin: 0 0 1.5rem;">Nothing here yet.</p>
            @else
                <div style="display: grid; gap: 0.75rem; margin-bottom: 1.5rem;">
                    @foreach($tabEntries as $entry)
                        @include('pro.medical._patient-entry-card', [
                            'entry' => $entry,
                            'patient' => $patient,
                            'payload' => $payload,
                            'entryTypes' => $entryTypes ?? \App\Models\ClinicalEntry::TYPES,
                            'expandShare' => (int) session('issued_entry_id') === (int) $entry['model']->id,
                        ])
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach

    <script>
        (function () {
            var tabChrome = {
                journal: '#475569',
                prescription: '#0f766e',
                referral: '#1d4ed8',
                certificate: '#15803d'
            };

            function activateTab(tab) {
                document.querySelectorAll('.patient-hub-panel').forEach(function (panel) {
                    panel.style.display = panel.getAttribute('data-tab-panel') === tab ? 'block' : 'none';
                });
                document.querySelectorAll('.patient-hub-tab').forEach(function (btn) {
                    var on = btn.getAttribute('data-tab') === tab;
                    var accent = tabChrome[btn.getAttribute('data-tab')] || 'var(--primary-cerulean)';
                    btn.setAttribute('aria-selected', on ? 'true' : 'false');
                    btn.style.color = on ? 'var(--primary-navy)' : 'var(--text-muted)';
                    btn.style.borderBottomColor = on ? accent : 'transparent';
                });
                if (history.replaceState) {
                    history.replaceState(null, '', '#tab-' + tab);
                }
            }

            document.querySelectorAll('.patient-hub-tab').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    activateTab(btn.getAttribute('data-tab'));
                });
            });

            var hash = (location.hash || '').replace(/^#/, '');
            if (hash.indexOf('tab-') === 0) {
                var fromHash = hash.slice(4);
                if (document.querySelector('.patient-hub-tab[data-tab="' + fromHash + '"]')) {
                    activateTab(fromHash);
                }
            }

            async function fetchPdfBlob(url) {
                var res = await fetch(url, { credentials: 'same-origin' });
                if (!res.ok) throw new Error('PDF fetch failed');
                return await res.blob();
            }

            async function shareViaMessenger(url, label, code) {
                try {
                    var blob = await fetchPdfBlob(url);
                    var file = new File([blob], (code || 'document').replace(/[^\w\-]+/g, '_') + '.pdf', { type: 'application/pdf' });
                    if (navigator.canShare && navigator.canShare({ files: [file] })) {
                        await navigator.share({
                            files: [file],
                            title: label || 'Issued document',
                            text: code ? (label + ' · ' + code) : (label || 'Issued document')
                        });
                        return;
                    }
                } catch (err) {
                    console.warn(err);
                }
                // Fallback: download PDF, then open Messenger so the doctor can attach it.
                window.open(url, '_blank');
                window.open('https://www.messenger.com/', '_blank', 'noopener');
            }

            async function printPdf(url) {
                try {
                    var blob = await fetchPdfBlob(url);
                    var objUrl = URL.createObjectURL(blob);
                    var frame = document.createElement('iframe');
                    frame.style.position = 'fixed';
                    frame.style.right = '0';
                    frame.style.bottom = '0';
                    frame.style.width = '0';
                    frame.style.height = '0';
                    frame.style.border = '0';
                    frame.src = objUrl;
                    document.body.appendChild(frame);
                    frame.onload = function () {
                        try {
                            frame.contentWindow.focus();
                            frame.contentWindow.print();
                        } catch (e) {
                            window.open(objUrl, '_blank');
                        }
                        setTimeout(function () {
                            try { frame.remove(); } catch (e2) {}
                            URL.revokeObjectURL(objUrl);
                        }, 60000);
                    };
                } catch (err) {
                    console.warn(err);
                    window.open(url, '_blank');
                }
            }

            document.querySelectorAll('.issued-share').forEach(function (root) {
                var url = root.getAttribute('data-pdf-url') || '';
                var code = root.getAttribute('data-issue-code') || '';
                var label = root.getAttribute('data-doc-label') || 'Document';
                var toggle = root.querySelector('.share-toggle');
                var menu = root.querySelector('.share-menu');

                if (toggle && menu) {
                    toggle.addEventListener('click', function () {
                        var open = menu.style.display !== 'none' && !menu.hasAttribute('hidden');
                        if (open) {
                            menu.style.display = 'none';
                            menu.setAttribute('hidden', 'hidden');
                            toggle.setAttribute('aria-expanded', 'false');
                        } else {
                            menu.style.display = 'flex';
                            menu.removeAttribute('hidden');
                            toggle.setAttribute('aria-expanded', 'true');
                        }
                    });
                }

                var messenger = root.querySelector('.share-messenger');
                if (messenger) {
                    messenger.addEventListener('click', function () {
                        if (!url) return;
                        shareViaMessenger(url, label, code);
                    });
                }

                var printBtn = root.querySelector('.share-print');
                if (printBtn) {
                    printBtn.addEventListener('click', function () {
                        if (!url) return;
                        printPdf(url);
                    });
                }

                var emailBtn = root.querySelector('.share-email');
                if (emailBtn && url) {
                    emailBtn.addEventListener('click', function () {
                        // Kick off PDF download so the doctor can attach it to the draft email.
                        var frame = document.createElement('iframe');
                        frame.style.display = 'none';
                        frame.src = url;
                        document.body.appendChild(frame);
                        setTimeout(function () { try { frame.remove(); } catch (e) {} }, 60000);
                    });
                }
            });

            @if(session('issued_entry_id'))
            (function () {
                var el = document.getElementById('entry-{{ (int) session('issued_entry_id') }}');
                if (el) {
                    setTimeout(function () {
                        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }, 120);
                }
            })();
            @endif
        })();
    </script>
@endsection
