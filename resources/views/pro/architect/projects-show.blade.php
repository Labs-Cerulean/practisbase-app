@extends('layouts.app')

@section('page_title', $project->name)

@section('content')
    @include('pro.shared.field-styles')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem;">
        <div>
            <a href="{{ $project->client ? '/clients/'.$project->client->id : '/pro/architect/projects' }}" style="color: var(--text-muted); text-decoration: none; font-weight: 600;">← {{ $project->client->name ?? 'Projects' }}</a>
            <h1 style="margin: 0.4rem 0 0.25rem; color: var(--primary-navy); font-size: 1.5rem;">{{ $project->name }}</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">
                {{ \App\Models\ArchitectProject::engagementLabel($project->engagement_type) }}
                · {{ $phases[$project->phase] ?? $project->phase }} · {{ $statuses[$project->status] ?? $project->status }}
                @if($project->siteAddressLine()) · {{ $project->siteAddressLine() }} @endif
            </p>
        </div>
        <div class="eng-desktop-actions" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="/pro/architect/projects/{{ $project->id }}/edit" style="background: white; border: 1px solid var(--border-light); color: var(--primary-navy); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">Edit</a>
            <a href="/pro/architect/projects/{{ $project->id }}/pa/create" style="background: #3f6212; color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">+ Case</a>
            <a href="{{ $mapServerUrl }}" target="_blank" rel="noopener noreferrer" style="background: white; border: 1px solid var(--border-light); color: var(--primary-navy); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">PA MapServer ↗</a>
            <a href="/pro/architect/templates" style="background: white; border: 1px solid var(--border-light); color: var(--primary-navy); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">Templates</a>
        </div>
    </div>

    <nav class="eng-field-strip" aria-label="On-site field actions">
        <div class="eng-field-strip-label">Field strip · on site</div>
        <a class="eng-field-primary" href="#neighbours" style="background: #3f6212; border-color: #3f6212;">
            Neighbours
            <span>Register + tracker</span>
        </a>
        <a href="/pro/architect/condition-reports/create?project_id={{ $project->id }}&amp;starter=seventh_schedule#photos">
            Condition report
            <span>Seventh Schedule</span>
        </a>
        <a href="/pro/architect/method-statements/create?project_id={{ $project->id }}&amp;starter=excavation">
            Method statement
            <span>DMS / EMS / CMS</span>
        </a>
        <a href="/pro/architect/documents/create?project_id={{ $project->id }}">
            Document
            <span>Drawing / upload</span>
        </a>
        <a href="/pro/architect/projects/{{ $project->id }}/pa/create">
            Case
            <span>PA / PC / DN</span>
        </a>
    </nav>

    @if(session('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">{{ session('success') }}</div>
    @endif

    <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 1.25rem;" class="arch-split">
        <div style="display: grid; gap: 1.25rem;">
            @if($project->hasMapPin())
                <section>
                    @include('pro.architect.partials.portfolio-map', [
                        'mapId' => 'arch-project-map',
                        'pins' => [array_merge($project->mapPinPayload(), ['name' => $project->name])],
                        'height' => '240px',
                        'mapServerUrl' => $mapServerUrl,
                    ])
                </section>
            @endif

            <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm);">
                <h2 style="margin: 0 0 0.75rem; font-size: 1.05rem; color: var(--primary-navy);">Planning cases</h2>
                @if($project->paApplications->isEmpty())
                    <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem; line-height: 1.45;">
                        No PA / PC / DN yet — that is fine. Start the project now and add the case number when Planning Authority issues it.
                    </p>
                @else
                    <div style="display: grid; gap: 0.5rem;">
                        @foreach($project->paApplications as $pa)
                            <a href="/pro/architect/pa/{{ $pa->id }}" style="display: block; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.75rem 0.9rem; text-decoration: none;">
                                <div style="font-weight: 700; color: var(--primary-navy);">{{ $pa->displayLabel() }}</div>
                                <div style="font-size: 0.8rem; color: var(--text-muted);">{{ $pa->title ?: 'No title' }} · {{ $paStatuses[$pa->status] ?? $pa->statusLabel() }}</div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>

            <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm);">
                <div style="display: flex; justify-content: space-between; align-items: baseline; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 0.75rem;">
                    <h2 style="margin: 0; font-size: 1.05rem; color: var(--primary-navy);">Project library</h2>
                    <a href="/pro/architect/documents/create?project_id={{ $project->id }}" style="font-size: 0.82rem; font-weight: 600; color: #3f6212; text-decoration: none;">+ Upload</a>
                </div>
                @include('pro.architect.partials.document-library', [
                    'documents' => $project->documents,
                    'emptyCopy' => 'No project-level files yet. Upload plans, surveys, or PA docs for this job.',
                ])
                @if(($project->paDocuments ?? collect())->isNotEmpty())
                    <div style="margin-top: 1rem; padding-top: 0.85rem; border-top: 1px solid #e2e8f0;">
                        <div style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.55rem;">Attached to PA cases</div>
                        @include('pro.architect.partials.document-library', [
                            'documents' => $project->paDocuments,
                            'emptyCopy' => '',
                        ])
                    </div>
                @endif
            </section>

            @include('pro.architect.partials.neighbour-register')

            <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm);">
                <div style="display: flex; justify-content: space-between; align-items: baseline; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 0.75rem;">
                    <h2 style="margin: 0; font-size: 1.05rem; color: var(--primary-navy);">Condition reports</h2>
                    <a href="/pro/architect/condition-reports/create?project_id={{ $project->id }}&amp;starter=seventh_schedule" style="font-size: 0.82rem; font-weight: 600; color: #3f6212; text-decoration: none;">+ Build report</a>
                </div>
                @if($project->conditionReports->isEmpty())
                    <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem; line-height: 1.45;">
                        No neighbour condition reports yet. Use the Seventh Schedule starter for Avoidance of Damage to Third Party Properties inspections.
                    </p>
                @else
                    <div style="display: grid; gap: 0.5rem;">
                        @foreach($project->conditionReports as $cr)
                            <a href="/pro/architect/condition-reports/{{ $cr->id }}" style="display: block; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.75rem 0.9rem; text-decoration: none;">
                                <div style="font-weight: 650; color: var(--primary-navy);">{{ $cr->title }}</div>
                                <div style="font-size: 0.78rem; color: var(--text-muted);">
                                    {{ $cr->typeLabel() }} · {{ $cr->isStamped() ? $cr->issue_code : 'Draft' }}
                                    @if($cr->inspected_address) · {{ \Illuminate\Support\Str::limit($cr->inspected_address, 36) }} @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>

            <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm);">
                <div style="display: flex; justify-content: space-between; align-items: baseline; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 0.75rem;">
                    <h2 style="margin: 0; font-size: 1.05rem; color: var(--primary-navy);">Method statements</h2>
                    <a href="/pro/architect/method-statements/create?project_id={{ $project->id }}&amp;starter=excavation" style="font-size: 0.82rem; font-weight: 600; color: #3f6212; text-decoration: none;">+ Build MS</a>
                </div>
                @if($project->methodStatements->isEmpty())
                    <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem; line-height: 1.45;">
                        No method statements yet. Starters cover demolition (DMS), excavation (Fifth Schedule EMS), and building works (Sixth Schedule CMS).
                    </p>
                @else
                    <div style="display: grid; gap: 0.5rem;">
                        @foreach($project->methodStatements as $ms)
                            <a href="/pro/architect/method-statements/{{ $ms->id }}" style="display: block; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.75rem 0.9rem; text-decoration: none;">
                                <div style="font-weight: 650; color: var(--primary-navy);">{{ $ms->title }}</div>
                                <div style="font-size: 0.78rem; color: var(--text-muted);">
                                    {{ $ms->typeLabel() }} · {{ $ms->isStamped() ? $ms->issue_code : 'Draft' }}
                                </div>
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
                <div>
                    <div style="font-size: 0.8rem; font-weight: 700; color: var(--primary-navy);">Add site party</div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.2rem;">Type a name to reuse someone you already used on another project.</div>
                </div>
                <select name="role_key" id="partyRole" required style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    @foreach($roles as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                <input type="text" name="full_name" id="partyName" required placeholder="Full name *" autocomplete="off" style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                <div id="licenceSuggest" hidden style="border: 1px solid #cbd5e1; border-radius: var(--radius-md); background: #fff; max-height: 220px; overflow: auto; box-shadow: var(--shadow-sm);"></div>
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
                <input type="email" name="email" id="partyEmail" placeholder="Email" style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
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
            var roleSelect = document.getElementById('partyRole');
            var box = document.getElementById('licenceSuggest');
            if (!nameInput || !box) return;
            var timer = null;

            function fillFrom(item) {
                nameInput.value = item.full_name || '';
                document.getElementById('partyLicenceNo').value = item.licence_number || '';
                document.getElementById('partyCompany').value = item.company_name || '';
                document.getElementById('partyMobile').value = item.mobile || '';
                var email = document.getElementById('partyEmail');
                if (email) email.value = item.email || '';
                if (item.licence_type) document.getElementById('partyLicenceType').value = item.licence_type;
                if (item.preferred_role_key && roleSelect) roleSelect.value = item.preferred_role_key;
                box.hidden = true;
            }

            function renderItems(items) {
                box.innerHTML = '';
                if (!items || !items.length) {
                    box.hidden = true;
                    return;
                }
                items.forEach(function (item) {
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.style.cssText = 'display:block;width:100%;text-align:left;padding:0.6rem 0.75rem;border:none;border-bottom:1px solid #e2e8f0;background:#fff;cursor:pointer;';
                    var meta = [];
                    if (item.licence_number) meta.push(item.licence_number);
                    if (item.company_name) meta.push(item.company_name);
                    if (item.mobile) meta.push(item.mobile);
                    if (item.source === 'past_project') meta.push('from past project');
                    btn.innerHTML = '<div style="font-weight:650;color:var(--primary-navy);">' + (item.full_name || '') + '</div>'
                        + (meta.length ? '<div style="font-size:0.75rem;color:var(--text-muted);margin-top:0.15rem;">' + meta.join(' · ') + '</div>' : '');
                    btn.addEventListener('click', function () { fillFrom(item); });
                    box.appendChild(btn);
                });
                box.hidden = false;
            }

            function search(q) {
                var type = document.getElementById('partyLicenceType').value;
                var role = roleSelect ? roleSelect.value : '';
                var url = '/pro/architect/licences/search?limit=12';
                if (q) url += '&q=' + encodeURIComponent(q);
                if (type) url += '&licence_type=' + encodeURIComponent(type);
                if (role) url += '&role_key=' + encodeURIComponent(role);
                fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                }).then(function (r) { return r.json(); }).then(function (data) {
                    renderItems(data.items || []);
                }).catch(function () { box.hidden = true; });
            }

            nameInput.addEventListener('focus', function () {
                var q = nameInput.value.trim();
                search(q);
            });
            nameInput.addEventListener('input', function () {
                clearTimeout(timer);
                var q = nameInput.value.trim();
                timer = setTimeout(function () { search(q); }, 180);
            });
            document.addEventListener('click', function (e) {
                if (!box.contains(e.target) && e.target !== nameInput) box.hidden = true;
            });
        })();
    </script>
@endsection
