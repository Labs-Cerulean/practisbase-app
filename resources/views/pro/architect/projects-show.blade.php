@extends('layouts.app')

@section('page_title', $project->name)

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem;">
        <div>
            <a href="{{ $project->client ? '/pro/architect/clients/'.$project->client->id : '/pro/architect/projects' }}" style="color: var(--text-muted); text-decoration: none; font-weight: 600;">← {{ $project->client->name ?? 'Projects' }}</a>
            <h1 style="margin: 0.4rem 0 0.25rem; color: var(--primary-navy); font-size: 1.5rem;">{{ $project->name }}</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">
                {{ $phases[$project->phase] ?? $project->phase }} · {{ $statuses[$project->status] ?? $project->status }}
                @if($project->siteAddressLine()) · {{ $project->siteAddressLine() }} @endif
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="/pro/architect/projects/{{ $project->id }}/edit" style="background: white; border: 1px solid var(--border-light); color: var(--primary-navy); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">Edit</a>
            <a href="/pro/architect/projects/{{ $project->id }}/pa/create" style="background: #3f6212; color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">+ PA</a>
            <a href="/pro/architect/documents/create?project_id={{ $project->id }}" style="background: white; border: 1px solid var(--border-light); color: var(--primary-navy); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">+ Document</a>
            <a href="/pro/architect/templates" style="background: white; border: 1px solid var(--border-light); color: var(--primary-navy); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">Templates</a>
        </div>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">{{ session('success') }}</div>
    @endif

    <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 1.25rem;" class="arch-split">
        <div style="display: grid; gap: 1.25rem;">
            <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm);">
                <h2 style="margin: 0 0 0.75rem; font-size: 1.05rem; color: var(--primary-navy);">PA applications</h2>
                @if($project->paApplications->isEmpty())
                    <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem; line-height: 1.45;">
                        No PA yet — that is fine. Start the project now and add the PA number when Planning Authority issues it.
                    </p>
                @else
                    <div style="display: grid; gap: 0.5rem;">
                        @foreach($project->paApplications as $pa)
                            <a href="/pro/architect/pa/{{ $pa->id }}" style="display: block; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.75rem 0.9rem; text-decoration: none;">
                                <div style="font-weight: 700; color: var(--primary-navy);">{{ $pa->displayLabel() }}</div>
                                <div style="font-size: 0.8rem; color: var(--text-muted);">{{ $pa->title ?: 'No title' }} · {{ $statuses[$pa->status] ?? $pa->status }}</div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>

            <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm);">
                <h2 style="margin: 0 0 0.75rem; font-size: 1.05rem; color: var(--primary-navy);">Project documents</h2>
                @if($project->documents->isEmpty())
                    <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">No project-level documents yet. PA-level docs appear under each PA.</p>
                @else
                    <div style="display: grid; gap: 0.5rem;">
                        @foreach($project->documents as $doc)
                            <a href="/pro/architect/documents/{{ $doc->id }}" style="display: block; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.75rem 0.9rem; text-decoration: none;">
                                <div style="font-weight: 650; color: var(--primary-navy);">{{ $doc->title }}</div>
                                <div style="font-size: 0.78rem; color: var(--text-muted);">Rev {{ $doc->current_revision }} · {{ $doc->category }} · {{ $doc->status }}</div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        <section id="site-team" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm);">
            <h2 style="margin: 0 0 0.35rem; font-size: 1.05rem; color: var(--primary-navy);">Site team</h2>
            <p style="margin: 0 0 0.85rem; font-size: 0.82rem; color: var(--text-muted); line-height: 1.45;">
                Save contractors, STOs and masons for reuse.
                Verify against BCA:
                <a href="https://bca.gov.mt/register-of-contractors/" target="_blank" rel="noopener">Contractors</a> ·
                <a href="https://bca.gov.mt/register-of-site-technical-officers/" target="_blank" rel="noopener">STOs</a> ·
                <a href="https://bca.gov.mt/register-of-licensed-masons/" target="_blank" rel="noopener">Masons</a>
            </p>

            @if($project->siteParties->isNotEmpty())
                <div style="display: grid; gap: 0.55rem; margin-bottom: 1rem;">
                    @foreach($project->siteParties as $party)
                        <div style="border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.7rem 0.8rem;">
                            <div style="display: flex; justify-content: space-between; gap: 0.5rem;">
                                <div>
                                    <div style="font-size: 0.72rem; font-weight: 700; color: #3f6212; text-transform: uppercase;">{{ $party->roleLabel() }}</div>
                                    <div style="font-weight: 700; color: var(--primary-navy);">{{ $party->full_name }}</div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted);">
                                        @if($party->licence_type) {{ $licenceTypes[$party->licence_type] ?? $party->licence_type }} {{ $party->licence_number }} · @endif
                                        {{ $party->mobile }}
                                    </div>
                                </div>
                                <form method="POST" action="/pro/architect/projects/{{ $project->id }}/parties/{{ $party->id }}" onsubmit="return confirm('Remove this site party?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="background: none; border: none; color: #b91c1c; font-weight: 700; cursor: pointer; font-size: 0.8rem;">Remove</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="/pro/architect/projects/{{ $project->id }}/parties" style="display: grid; gap: 0.65rem; border-top: 1px solid #e2e8f0; padding-top: 0.85rem;">
                @csrf
                <div style="font-size: 0.8rem; font-weight: 700; color: var(--primary-navy);">Add site party</div>
                <select name="role_key" required style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    @foreach($roles as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                <input type="text" name="full_name" id="partyName" required placeholder="Full name *" style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                <div id="licenceSuggest" hidden style="border: 1px solid #cbd5e1; border-radius: var(--radius-md); background: #fff; max-height: 180px; overflow: auto;"></div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.55rem;">
                    <select name="licence_type" id="partyLicenceType" style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        <option value="">Licence type</option>
                        @foreach($licenceTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="licence_number" id="partyLicenceNo" placeholder="Licence no." style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <input type="text" name="company_name" id="partyCompany" placeholder="Company" style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                <input type="text" name="mobile" id="partyMobile" placeholder="Mobile" style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                <button type="submit" style="background: #3f6212; color: white; border: none; padding: 0.6rem 0.9rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Add to site team</button>
            </form>
        </section>
    </div>

    <style>
        @media (max-width: 900px) { .arch-split { grid-template-columns: 1fr !important; } }
    </style>
    <script>
        (function () {
            var nameInput = document.getElementById('partyName');
            var box = document.getElementById('licenceSuggest');
            if (!nameInput || !box) return;
            var timer = null;
            nameInput.addEventListener('input', function () {
                clearTimeout(timer);
                var q = nameInput.value.trim();
                if (q.length < 2) { box.hidden = true; box.innerHTML = ''; return; }
                timer = setTimeout(function () {
                    var type = document.getElementById('partyLicenceType').value;
                    fetch('/pro/architect/licences/search?q=' + encodeURIComponent(q) + (type ? '&licence_type=' + encodeURIComponent(type) : ''), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin'
                    }).then(function (r) { return r.json(); }).then(function (data) {
                        box.innerHTML = '';
                        (data.items || []).forEach(function (item) {
                            var btn = document.createElement('button');
                            btn.type = 'button';
                            btn.style.cssText = 'display:block;width:100%;text-align:left;padding:0.55rem 0.7rem;border:none;border-bottom:1px solid #e2e8f0;background:#fff;cursor:pointer;';
                            btn.textContent = item.full_name + (item.licence_number ? ' · ' + item.licence_number : '') + (item.company_name ? ' · ' + item.company_name : '');
                            btn.addEventListener('click', function () {
                                nameInput.value = item.full_name || '';
                                document.getElementById('partyLicenceNo').value = item.licence_number || '';
                                document.getElementById('partyCompany').value = item.company_name || '';
                                document.getElementById('partyMobile').value = item.mobile || '';
                                if (item.licence_type) document.getElementById('partyLicenceType').value = item.licence_type;
                                box.hidden = true;
                            });
                            box.appendChild(btn);
                        });
                        box.hidden = !(data.items && data.items.length);
                    }).catch(function () { box.hidden = true; });
                }, 200);
            });
        })();
    </script>
@endsection
