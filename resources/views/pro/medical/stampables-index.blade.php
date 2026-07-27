@extends('layouts.app')

@section('page_title', 'Stampables')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem;">
        <div style="min-width: 200px; flex: 1;">
            <h1 style="font-size: 1.35rem; color: var(--primary-navy); margin: 0 0 0.25rem;">Stampables</h1>
            <p style="color: var(--text-muted); margin: 0; font-size: 0.9rem;">
                Search prescriptions, referrals, and certificates. Create and stamp them from the patient record.
            </p>
            @if($hasLegacy ?? false)
                <p style="color: #92400e; margin: 0.5rem 0 0; font-size: 0.85rem;">
                    Legacy rows from the old shared Certificates screen are listed read-only. New certificates should be created under the patient.
                </p>
            @endif
        </div>
        <a href="/pro/medical/patients" style="background: var(--primary-cerulean); color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">Open patients</a>
    </div>

    @include('pro.medical._type-colour-key', ['includeLegacy' => ($hasLegacy ?? false), 'margin' => '0 0 1.25rem'])

    @if(session('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
            {{ session('success') }}
            @if(session('highlight_patient_id'))
                <a href="/pro/medical/patients/{{ session('highlight_patient_id') }}" style="margin-left: 0.5rem; color: #065f46; font-weight: 700; border-bottom: 1px dotted #065f46; text-decoration: none;">Open patient &rarr;</a>
            @endif
        </div>
    @endif
    @if($errors->any())
        <div style="background: #fef2f2; color: #991b1b; padding: 0.85rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1rem 1.15rem; margin-bottom: 1rem; box-shadow: var(--shadow-sm);">
        <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.35rem;">Verify issue code</div>
        <p style="margin: 0 0 0.65rem; color: var(--text-muted); font-size: 0.85rem;">
            Match a printed code against this register. No match flags a possible reprint or reuse.
        </p>
        <form action="/pro/medical/issue-codes/lookup" method="POST" style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
            @csrf
            <input type="text" name="issue_code" required placeholder="e.g. RX-7F3K-9M2P" maxlength="32"
                   style="flex: 1; min-width: 180px; padding: 0.65rem 0.85rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-family: ui-monospace, monospace; letter-spacing: 0.04em; text-transform: uppercase;">
            <button type="submit" style="background: var(--primary-navy); color: white; border: none; padding: 0.65rem 1rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Look up</button>
        </form>
    </div>

    @if($rows->isEmpty())
        <div style="padding: 3rem; border: 2px dashed var(--border-light); border-radius: var(--radius-md); text-align: center; background: white;">
            <p style="color: var(--text-muted); margin: 0 0 0.75rem;">No prescriptions, referrals, or certificates yet.</p>
            <a href="/pro/medical/patients" style="color: var(--primary-cerulean); font-weight: 600;">Create one from a patient record &rarr;</a>
        </div>
    @else
        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1rem 1.15rem; margin-bottom: 1rem; box-shadow: var(--shadow-sm);">
            <div style="display: grid; gap: 0.65rem;">
                <div>
                    <label for="stampable-search" style="display: block; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.3rem;">Search</label>
                    <input id="stampable-search" type="search" placeholder="Title, patient, ref, issue code…"
                           style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.65rem;">
                    <div>
                        <label for="stampable-filter-type" style="display: block; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.3rem;">Type</label>
                        <select id="stampable-filter-type" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                            <option value="all">All stampables</option>
                            @foreach($types as $typeKey => $typeLabel)
                                <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="stampable-filter-status" style="display: block; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.3rem;">Status</label>
                        <select id="stampable-filter-status" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                            <option value="all">All</option>
                            <option value="draft">Draft</option>
                            <option value="issued">Stamped &amp; issued</option>
                        </select>
                    </div>
                </div>
                <div style="font-size: 0.8rem; color: var(--text-muted);">
                    Showing <strong id="stampable-count-visible" style="color: var(--primary-navy);">{{ $rows->count() }}</strong> of {{ $rows->count() }}
                    <button type="button" id="stampable-filter-reset" style="margin-left: 0.75rem; background: none; border: none; color: var(--primary-cerulean); font-weight: 700; cursor: pointer; padding: 0;">Reset</button>
                </div>
            </div>
        </div>

        <div id="stampable-list" style="display: grid; gap: 0.75rem;">
            @foreach($rows as $row)
                @php $chrome = \App\Models\ClinicalEntry::typeChrome($row['entry_type']); @endphp
                <div class="stampable-row"
                     id="stampable-{{ $row['id'] }}"
                     data-title="{{ strtolower($row['title']) }}"
                     data-patient="{{ strtolower($row['patient_name']) }}"
                     data-ref="{{ strtolower($row['patient_ref']) }}"
                     data-code="{{ strtolower($row['issue_code'] ?? '') }}"
                     data-type="{{ $row['entry_type'] }}"
                     data-status="{{ $row['status'] }}"
                     style="background: {{ $chrome['card_bg'] }}; border: 1px solid {{ $chrome['border'] }}; border-left: 6px solid {{ $chrome['accent'] }}; border-radius: var(--radius-md); padding: 1rem 1.15rem; box-shadow: var(--shadow-sm); {{ session('highlight_entry_id') == $row['id'] ? 'outline: 2px solid ' . $chrome['accent'] . ';' : '' }}">
                    <div style="display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                        <div>
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center; margin-bottom: 0.4rem;">
                                <span style="display: inline-block; background: {{ $chrome['badge_bg'] }}; color: {{ $chrome['badge_fg'] }}; font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; padding: 0.28rem 0.65rem; border-radius: 4px;">
                                    {{ $row['type_label'] }}
                                </span>
                                @if($row['is_issued'])
                                    <span style="font-size: 0.7rem; font-weight: 800; color: #065f46; text-transform: uppercase; background: #d1fae5; padding: 0.2rem 0.5rem; border-radius: 4px;">Issued</span>
                                @else
                                    <span style="font-size: 0.7rem; font-weight: 800; color: #92400e; text-transform: uppercase; background: #fef3c7; padding: 0.2rem 0.5rem; border-radius: 4px;">Draft</span>
                                @endif
                            </div>
                            <div style="font-weight: 700; color: var(--primary-navy); font-size: 1.05rem;">{{ $row['title'] }}</div>
                            <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem;">
                                {{ $row['patient_name'] }}
                                @if($row['patient_ref'])
                                    · <span style="font-family: ui-monospace, monospace;">{{ $row['patient_ref'] }}</span>
                                @endif
                                @if(!empty($row['meta_line']))
                                    · {{ $row['meta_line'] }}
                                @endif
                                · dated {{ $row['entry_date']->format('d M Y') }}
                                @if($row['is_issued'])
                                    · Issued {{ $row['issued_at']->format('d M Y H:i') }}
                                    @if($row['issue_code'])
                                        · <span style="font-family: ui-monospace, monospace; letter-spacing: 0.04em; color: {{ $chrome['accent'] }}; font-weight: 700;">{{ $row['issue_code'] }}</span>
                                    @endif
                                @endif
                            </div>
                        </div>
                        <div style="display: flex; gap: 0.5rem; align-items: flex-start; flex-wrap: wrap;">
                            @if($row['source'] === 'clinical' && $row['patient_id'])
                                <a href="/pro/medical/patients/{{ $row['patient_id'] }}"
                                   style="display: inline-block; padding: 0.35rem 0.7rem; border: 1px solid {{ $chrome['border'] }}; background: white; color: var(--primary-navy); border-radius: var(--radius-md); font-weight: 700; font-size: 0.8rem; text-decoration: none;">Open patient</a>
                                @if($row['is_editable'])
                                    <a href="/pro/medical/patients/{{ $row['patient_id'] }}/entries/{{ $row['id'] }}/edit"
                                       style="display: inline-block; padding: 0.35rem 0.7rem; border: 1px solid {{ $chrome['border'] }}; background: white; color: var(--primary-navy); border-radius: var(--radius-md); font-weight: 700; font-size: 0.8rem; text-decoration: none;">Edit draft</a>
                                @endif
                                @if($row['is_issued'])
                                    <a href="/pro/medical/patients/{{ $row['patient_id'] }}/entries/{{ $row['id'] }}/pdf"
                                       style="display: inline-block; padding: 0.35rem 0.7rem; border: 1px solid {{ $chrome['accent'] }}; background: {{ $chrome['badge_bg'] }}; color: {{ $chrome['badge_fg'] }}; border-radius: var(--radius-md); font-weight: 700; font-size: 0.8rem; text-decoration: none;">PDF</a>
                                @endif
                            @elseif($row['legacy_certificate_id'])
                                @if($row['is_issued'])
                                    <a href="/pro/certificates/{{ $row['legacy_certificate_id'] }}/pdf"
                                       style="display: inline-block; padding: 0.35rem 0.7rem; border: 1px solid {{ $chrome['accent'] }}; background: {{ $chrome['badge_bg'] }}; color: {{ $chrome['badge_fg'] }}; border-radius: var(--radius-md); font-weight: 700; font-size: 0.8rem; text-decoration: none;">PDF</a>
                                @else
                                    <span style="font-size: 0.8rem; color: var(--text-muted);">Legacy draft — recreate under a patient to stamp</span>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div id="stampable-empty-filter" style="display: none; padding: 2rem; text-align: center; color: var(--text-muted); border: 2px dashed var(--border-light); border-radius: var(--radius-md); background: white; margin-top: 0.75rem;">
            No stampables match these filters.
        </div>

        <script>
            (function () {
                var search = document.getElementById('stampable-search');
                var type = document.getElementById('stampable-filter-type');
                var status = document.getElementById('stampable-filter-status');
                var reset = document.getElementById('stampable-filter-reset');
                var list = document.getElementById('stampable-list');
                var countEl = document.getElementById('stampable-count-visible');
                var empty = document.getElementById('stampable-empty-filter');
                if (!search || !list) return;

                function apply() {
                    var q = (search.value || '').trim().toLowerCase();
                    var typeMode = type.value;
                    var statusMode = status.value;
                    var items = Array.prototype.slice.call(list.querySelectorAll('.stampable-row'));
                    var visible = 0;
                    items.forEach(function (el) {
                        var hay = [el.dataset.title, el.dataset.patient, el.dataset.ref, el.dataset.code || '', el.dataset.type].join(' ');
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

                @if(session('highlight_entry_id'))
                var highlight = document.getElementById('stampable-{{ session('highlight_entry_id') }}');
                if (highlight) {
                    highlight.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                @endif
            })();
        </script>
    @endif
@endsection
