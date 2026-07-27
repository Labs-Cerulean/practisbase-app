@extends('layouts.app')

@section('page_title', 'Patients')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem;">
        <div style="min-width: 200px; flex: 1;">
            <h1 style="font-size: 1.35rem; color: var(--primary-navy); margin: 0 0 0.25rem;">Patients</h1>
            <p style="color: var(--text-muted); margin: 0; font-size: 0.9rem;">Encrypted clinical store. Optionally link a billing Client so you do not retype the same person for invoices.</p>
        </div>
        <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.55rem;">
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; justify-content: flex-end;">
                <a href="/pro/medical/vault/backup" style="background: white; border: 1px solid var(--border-light); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none; color: var(--primary-navy);">Weekly backup</a>
                <a href="/pro/medical/patients/create" style="background: var(--primary-cerulean); color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">+ Patient</a>
            </div>
            <form action="/pro/medical/vault/lock" method="POST" style="margin: 0;">
                @csrf
                <button type="submit" title="Lock medical vault"
                        style="display: inline-flex; align-items: center; gap: 0.4rem; background: #334155; color: #f8fafc; border: none; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer; font-size: 0.85rem;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M7 11V8a5 5 0 0 1 10 0v3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <rect x="5" y="11" width="14" height="10" rx="2" stroke="currentColor" stroke-width="2"/>
                        <circle cx="12" cy="16" r="1.5" fill="currentColor"/>
                    </svg>
                    Lock vault
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">{{ session('success') }}</div>
    @endif

    <div id="device-trust-banner" style="display: none; margin-bottom: 1rem; padding: 0.9rem 1rem; background: #eff6ff; border-left: 4px solid #2563eb; border-radius: var(--radius-md); color: #1e3a8a;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 200px;">
                <div style="font-weight: 700; margin-bottom: 0.25rem;">Trust this device for quicker unlock?</div>
                <p style="margin: 0; font-size: 0.85rem; line-height: 1.4; color: var(--text-muted);">
                    Next time use Face ID, fingerprint, Touch ID, or Windows Hello instead of pasting the recovery code.
                    Your recovery code stays required for new devices and full recovery — login password stays separate.
                </p>
                <div id="device-trust-status" style="display: none; margin-top: 0.5rem; font-size: 0.85rem;"></div>
            </div>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <button type="button" id="device-trust-enable" style="padding: 0.55rem 1rem; background: #1d4ed8; color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Enable quick unlock</button>
                <button type="button" id="device-trust-dismiss" style="padding: 0.55rem 1rem; background: white; color: var(--primary-navy); border: 1px solid var(--border-light); border-radius: var(--radius-md); font-weight: 600; cursor: pointer;">Not now</button>
            </div>
        </div>
    </div>

    <div id="trusted-devices-card" style="display: none; margin-bottom: 1rem; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1rem 1.15rem; box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 0.65rem;">
            <div>
                <div style="font-weight: 700; color: var(--primary-navy);">Trusted devices</div>
                <div style="font-size: 0.8rem; color: var(--text-muted);">Revoke a device if you lose it. You will need the recovery code on that browser again.</div>
            </div>
            <button type="button" id="device-trust-enable-secondary" style="display: none; padding: 0.45rem 0.85rem; background: white; color: #1d4ed8; border: 1px solid #93c5fd; border-radius: var(--radius-md); font-weight: 700; cursor: pointer; font-size: 0.85rem;">Trust this browser</button>
        </div>
        <div id="trusted-devices-list" style="font-size: 0.9rem; color: var(--text-muted);">Loading…</div>
    </div>

    @if($backupOverdue ?? false)
        <div style="margin-bottom: 1rem; padding: 0.85rem 1rem; background: #fef2f2; border-left: 4px solid #ef4444; border-radius: var(--radius-md); color: #991b1b; font-size: 0.9rem;">
            <strong>Weekly backup overdue.</strong>
            Last backup: {{ ($vault && $vault->last_backup_at) ? $vault->last_backup_at->format('d M Y') : 'never' }}.
            <a href="/pro/medical/vault/backup" style="color: #991b1b; font-weight: 700; border-bottom: 1px dotted #991b1b; text-decoration: none; margin-left: 0.35rem;">Download encrypted vault backup now</a>
        </div>
    @else
        <div style="margin-bottom: 1rem; padding: 0.75rem 1rem; background: #ecfdf5; border-left: 4px solid #10b981; border-radius: var(--radius-md); color: #065f46; font-size: 0.85rem;">
            Last medical backup: {{ $vault->last_backup_at->format('d M Y H:i') }}. Keep a weekly offline copy — Labs cannot restore your vault without your code and backup.
        </div>
    @endif

    <div style="margin-bottom: 1rem; padding: 0.75rem 1rem; background: #fffbeb; border-left: 4px solid #f59e0b; border-radius: var(--radius-md); color: #92400e; font-size: 0.85rem;">
        Closed beta: treat this as a test vault unless Cerulean Labs has confirmed legal go-live for real patient data on your account.
    </div>

    <div style="margin-bottom: 1rem; padding: 0.75rem 1rem; background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.85rem; color: var(--text-muted);">
        Looking for a prescription, referral, or certificate?
        <a href="/pro/medical/stampables" style="color: var(--primary-cerulean); font-weight: 700; text-decoration: none; border-bottom: 1px dotted var(--primary-navy);">Open Stampables</a>
        to search issue codes. Create new ones from a patient record.
    </div>

    @if($rows->isEmpty())
        <div style="padding: 3rem; border: 2px dashed var(--border-light); border-radius: var(--radius-md); text-align: center; background: white;">
            <p style="color: var(--text-muted);">No patients in this vault yet.</p>
            <a href="/pro/medical/patients/create" style="color: var(--primary-cerulean); font-weight: 600;">Add first patient &rarr;</a>
        </div>
    @else
        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1rem 1.15rem; margin-bottom: 1rem; box-shadow: var(--shadow-sm);">
            <div style="display: grid; gap: 0.75rem;">
                <div>
                    <label for="patient-search" style="display: block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.35rem;">Search</label>
                    <input id="patient-search" type="search" placeholder="Name, patient ref, linked client, notes…"
                           style="width: 100%; padding: 0.75rem 0.9rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.95rem;">
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 0.65rem;">
                    <div>
                        <label for="filter-link" style="display: block; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.3rem;">Billing link</label>
                        <select id="filter-link" style="width: 100%; padding: 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                            <option value="all">All patients</option>
                            <option value="linked">Linked to a Client</option>
                            <option value="unlinked">Clinical only (no Client)</option>
                        </select>
                    </div>
                    <div>
                        <label for="filter-entry" style="display: block; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.3rem;">Has entry type</label>
                        <select id="filter-entry" style="width: 100%; padding: 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                            <option value="all">Any</option>
                            <option value="journal">Journal</option>
                            <option value="prescription">Prescription</option>
                            <option value="referral">Referral</option>
                            <option value="certificate">Certificate</option>
                            <option value="attachment">Attachment</option>
                            <option value="none">No entries yet</option>
                        </select>
                    </div>
                    <div>
                        <label for="filter-sort" style="display: block; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.3rem;">Sort</label>
                        <select id="filter-sort" style="width: 100%; padding: 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                            <option value="newest">Newest first</option>
                            <option value="oldest">Oldest first</option>
                            <option value="name-asc">Name A–Z</option>
                            <option value="name-desc">Name Z–A</option>
                            <option value="dob">Date of birth</option>
                        </select>
                    </div>
                </div>
                <div style="font-size: 0.8rem; color: var(--text-muted);">
                    Showing <strong id="patient-count-visible" style="color: var(--primary-navy);">{{ $rows->count() }}</strong> of {{ $rows->count() }}
                    <button type="button" id="patient-filter-reset" style="margin-left: 0.75rem; background: none; border: none; color: var(--primary-cerulean); font-weight: 700; cursor: pointer; padding: 0;">Reset</button>
                </div>
            </div>
        </div>

        <div id="patient-list" style="display: grid; gap: 0.75rem;">
            @foreach($rows as $row)
                <a href="/pro/medical/patients/{{ $row['model']->id }}"
                   class="patient-row"
                   data-name="{{ strtolower($row['display_name']) }}"
                   data-ref="{{ strtolower($row['public_ref']) }}"
                   data-client="{{ strtolower($row['client_name'] ?? '') }}"
                   data-notes="{{ strtolower($row['notes'] ?? '') }}"
                   data-linked="{{ $row['linked'] ? '1' : '0' }}"
                   data-journal="{{ $row['journal_count'] }}"
                   data-prescription="{{ $row['prescription_count'] }}"
                   data-referral="{{ $row['referral_count'] }}"
                   data-certificate="{{ $row['certificate_count'] }}"
                   data-attachment="{{ $row['attachment_count'] }}"
                   data-created="{{ $row['created_ts'] }}"
                   data-dob="{{ $row['date_of_birth'] ?? '' }}"
                   style="display: flex; justify-content: space-between; gap: 1rem; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 1rem 1.25rem; text-decoration: none; box-shadow: var(--shadow-sm);">
                    <div>
                        <div style="font-weight: 700; color: var(--primary-navy);">{{ $row['display_name'] }}</div>
                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.15rem;">
                            Patient ref {{ $row['public_ref'] }}
                            @if($row['date_of_birth'])
                                · DOB {{ \Illuminate\Support\Carbon::parse($row['date_of_birth'])->format('d M Y') }}
                            @endif
                            @if($row['linked'])
                                · Linked: {{ $row['client_name'] }}
                            @endif
                        </div>
                        <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.35rem;">
                            J {{ $row['journal_count'] }} · Rx {{ $row['prescription_count'] }} · Ref {{ $row['referral_count'] }} · Cert {{ $row['certificate_count'] }} · Files {{ $row['attachment_count'] }}
                        </div>
                    </div>
                    <div style="color: var(--primary-cerulean); font-weight: 600; font-size: 0.85rem; align-self: center;">Open</div>
                </a>
            @endforeach
        </div>
        <div id="patient-empty-filter" style="display: none; padding: 2rem; text-align: center; color: var(--text-muted); border: 2px dashed var(--border-light); border-radius: var(--radius-md); background: white;">
            No patients match these filters.
        </div>
    @endif

    @unless($rows->isEmpty())
    <script>
        (function () {
            var search = document.getElementById('patient-search');
            var link = document.getElementById('filter-link');
            var entry = document.getElementById('filter-entry');
            var sort = document.getElementById('filter-sort');
            var reset = document.getElementById('patient-filter-reset');
            var list = document.getElementById('patient-list');
            var countEl = document.getElementById('patient-count-visible');
            var empty = document.getElementById('patient-empty-filter');
            if (!search || !list) return;

            function rows() {
                return Array.prototype.slice.call(list.querySelectorAll('.patient-row'));
            }

            function apply() {
                var q = (search.value || '').trim().toLowerCase();
                var linkMode = link.value;
                var entryMode = entry.value;
                var items = rows();
                var visible = 0;

                items.forEach(function (el) {
                    var hay = [el.dataset.name, el.dataset.ref, el.dataset.client, el.dataset.notes].join(' ');
                    var matchQ = !q || hay.indexOf(q) !== -1;
                    var matchLink = linkMode === 'all'
                        || (linkMode === 'linked' && el.dataset.linked === '1')
                        || (linkMode === 'unlinked' && el.dataset.linked === '0');
                    var matchEntry = true;
                    if (entryMode === 'journal') matchEntry = parseInt(el.dataset.journal, 10) > 0;
                    else if (entryMode === 'prescription') matchEntry = parseInt(el.dataset.prescription, 10) > 0;
                    else if (entryMode === 'referral') matchEntry = parseInt(el.dataset.referral, 10) > 0;
                    else if (entryMode === 'certificate') matchEntry = parseInt(el.dataset.certificate, 10) > 0;
                    else if (entryMode === 'attachment') matchEntry = parseInt(el.dataset.attachment, 10) > 0;
                    else if (entryMode === 'none') {
                        matchEntry = parseInt(el.dataset.journal, 10) + parseInt(el.dataset.prescription, 10) + parseInt(el.dataset.referral, 10) + parseInt(el.dataset.certificate, 10) === 0;
                    }

                    var show = matchQ && matchLink && matchEntry;
                    el.style.display = show ? 'flex' : 'none';
                    if (show) visible++;
                });

                var sorted = items.slice().sort(function (a, b) {
                    var mode = sort.value;
                    if (mode === 'name-asc') return (a.dataset.name || '').localeCompare(b.dataset.name || '');
                    if (mode === 'name-desc') return (b.dataset.name || '').localeCompare(a.dataset.name || '');
                    if (mode === 'oldest') return parseInt(a.dataset.created, 10) - parseInt(b.dataset.created, 10);
                    if (mode === 'dob') return (a.dataset.dob || '9999').localeCompare(b.dataset.dob || '9999');
                    return parseInt(b.dataset.created, 10) - parseInt(a.dataset.created, 10);
                });
                sorted.forEach(function (el) { list.appendChild(el); });

                countEl.textContent = String(visible);
                empty.style.display = visible === 0 ? 'block' : 'none';
            }

            search.addEventListener('input', apply);
            link.addEventListener('change', apply);
            entry.addEventListener('change', apply);
            sort.addEventListener('change', apply);
            reset.addEventListener('click', function () {
                search.value = '';
                link.value = 'all';
                entry.value = 'all';
                sort.value = 'newest';
                apply();
            });
            apply();
        })();
    </script>
    @endunless

    @include('pro.medical._vault-device-js')
    <script>
        (function () {
            if (!window.PractisVaultDevice) return;

            var offerTrust = @json((bool) (session('offer_device_trust') || request()->boolean('offer_trust')));
            var banner = document.getElementById('device-trust-banner');
            var enableBtn = document.getElementById('device-trust-enable');
            var enableSecondary = document.getElementById('device-trust-enable-secondary');
            var dismissBtn = document.getElementById('device-trust-dismiss');
            var statusEl = document.getElementById('device-trust-status');
            var devicesCard = document.getElementById('trusted-devices-card');
            var devicesList = document.getElementById('trusted-devices-list');

            if (offerTrust && banner) {
                banner.style.borderLeftColor = '#1d4ed8';
            }

            function setStatus(msg, ok) {
                if (!statusEl) return;
                statusEl.style.display = 'block';
                statusEl.style.color = ok ? '#065f46' : '#991b1b';
                statusEl.textContent = msg;
            }

            function formatWhen(iso) {
                if (!iso) return 'never';
                try {
                    var d = new Date(iso);
                    if (isNaN(d.getTime())) return 'unknown';
                    return d.toLocaleString();
                } catch (e) {
                    return 'unknown';
                }
            }

            function renderDevices(devices) {
                if (!devicesCard || !devicesList) return;
                devicesCard.style.display = 'block';
                if (!devices.length) {
                    devicesList.innerHTML = '<span style="color: var(--text-muted);">No trusted devices yet. Enable quick unlock on this phone or laptop after unlocking with your recovery code.</span>';
                    return;
                }
                var html = '<div style="display: grid; gap: 0.55rem;">';
                devices.forEach(function (d) {
                    html += '<div style="display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; flex-wrap: wrap; padding: 0.65rem 0.75rem; background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md);">';
                    html += '<div><div style="font-weight: 700; color: var(--primary-navy);">' + (d.device_label || 'Trusted device') + '</div>';
                    html += '<div style="font-size: 0.75rem; color: var(--text-muted);">Last used: ' + formatWhen(d.last_used_at) + '</div></div>';
                    html += '<button type="button" data-revoke-id="' + d.id + '" data-credential-id="' + (d.credential_id || '') + '" style="padding: 0.4rem 0.75rem; background: white; border: 1px solid #fecaca; color: #991b1b; border-radius: var(--radius-md); font-weight: 700; cursor: pointer; font-size: 0.8rem;">Revoke</button>';
                    html += '</div>';
                });
                html += '</div>';
                devicesList.innerHTML = html;
                devicesList.querySelectorAll('[data-revoke-id]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        if (!confirm('Revoke quick unlock for this device?')) return;
                        btn.disabled = true;
                        PractisVaultDevice.revokeDevice(btn.getAttribute('data-revoke-id'), btn.getAttribute('data-credential-id')).then(function () {
                            return PractisVaultDevice.listDevices().then(renderDevices);
                        }).catch(function (e) {
                            alert(e.message || 'Could not revoke device.');
                            btn.disabled = false;
                        });
                    });
                });
            }

            function runRegister(btn) {
                if (statusEl) statusEl.style.display = 'none';
                if (btn) {
                    btn.disabled = true;
                    btn.textContent = 'Waiting for device…';
                }
                PractisVaultDevice.registerDevice().then(function (result) {
                    setStatus((result && result.message) ? result.message : 'Quick unlock enabled on this device.', true);
                    if (banner) banner.style.display = 'none';
                    if (enableSecondary) enableSecondary.style.display = 'none';
                    return PractisVaultDevice.listDevices().then(renderDevices);
                }).catch(function (e) {
                    setStatus(e.message || 'Could not enable quick unlock.', false);
                    if (banner) banner.style.display = 'block';
                }).finally(function () {
                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = btn === enableSecondary ? 'Trust this browser' : 'Enable quick unlock';
                    }
                });
            }

            PractisVaultDevice.listDevices().then(renderDevices).catch(function () {
                if (devicesCard) devicesCard.style.display = 'none';
            });

            PractisVaultDevice.platformAvailable().then(function (ok) {
                if (!ok) return;
                return PractisVaultDevice.hasLocalWrapKey().then(function (hasKey) {
                    if (enableSecondary) enableSecondary.style.display = hasKey ? 'none' : 'inline-block';
                    if (!hasKey && banner) banner.style.display = 'block';
                });
            });

            if (enableBtn) enableBtn.addEventListener('click', function () { runRegister(enableBtn); });
            if (enableSecondary) enableSecondary.addEventListener('click', function () { runRegister(enableSecondary); });
            if (dismissBtn) dismissBtn.addEventListener('click', function () {
                if (banner) banner.style.display = 'none';
            });
        })();
    </script>
@endsection
