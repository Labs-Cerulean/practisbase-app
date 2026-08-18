@extends('layouts.app')

@section('page_title', $project ? 'Edit project' : 'New project')

@section('content')
    <div style="margin-bottom: 1.25rem;">
        <a href="{{ $project ? '/pro/architect/projects/'.$project->id : '/pro/architect/projects' }}" style="color: var(--text-muted); text-decoration: none; font-weight: 600;">← Back</a>
        <h1 style="margin: 0.5rem 0 0; color: var(--primary-navy); font-size: 1.5rem;">{{ $project ? 'Edit project' : 'New project' }}</h1>
        <p style="margin: 0.35rem 0 0; color: var(--text-muted); font-size: 0.88rem;">PA numbers are optional — add them on the project when issued.</p>
    </div>

    @if($clients->isEmpty())
        <div style="background: #fffbeb; border: 1px solid #fde68a; color: #92400e; padding: 1rem; border-radius: var(--radius-md);">
            Create a client first under General → Clients, then add a project.
            <a href="/clients/create" style="font-weight: 700; color: #92400e;">Add client</a>
        </div>
    @else
        @if($errors->any())
            <div style="background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
                <ul style="margin: 0; padding-left: 1.1rem;">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ $project ? '/pro/architect/projects/'.$project->id : '/pro/architect/projects' }}" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.35rem; box-shadow: var(--shadow-sm); max-width: 820px;">
            @csrf
            @if($project) @method('PUT') @endif
            <div style="display: grid; gap: 0.85rem;">
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Client *</label>
                    <select name="client_id" id="projectClientId" required style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        @foreach($clients as $c)
                            <option value="{{ $c->id }}" @selected(old('client_id', $preselectClientId) == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Project name *</label>
                    <input type="text" name="name" value="{{ old('name', $project->name ?? '') }}" required style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 0.85rem;">
                    <div>
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Internal reference</label>
                        <div style="display: flex; gap: 0.45rem; align-items: stretch;">
                            <input type="text" name="reference_code" id="referenceCodeField" value="{{ old('reference_code', $project->reference_code ?? '') }}" placeholder="CAMI-BORG-SLIE-001" maxlength="100" style="flex: 1; min-width: 0; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                            <button type="button" id="generateReferenceBtn" style="flex-shrink: 0; background: #f1f5f9; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.55rem 0.75rem; border-radius: var(--radius-md); font-weight: 700; font-size: 0.78rem; cursor: pointer; white-space: nowrap;">Generate</button>
                        </div>
                        <div id="referenceGuide" style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.25rem;">Practice + client + locality (4 letters each) + next number. Editable anytime.</div>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Engagement type</label>
                        <select name="engagement_type" required style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                            @foreach($engagementTypes as $key => $label)
                                <option value="{{ $key }}" @selected(old('engagement_type', $project->engagement_type ?? 'full_project') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.25rem;">Choose Other if none fit — add detail in Notes.</div>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Phase</label>
                        <select name="phase" id="project_phase" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                            @foreach($phases as $key => $label)
                                <option value="{{ $key }}" @selected(old('phase', $project->phase ?? 'concept') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div id="phase_hint" style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.25rem;">Natural order: Concept → Permit → BCA → Construction → Completion.</div>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Status</label>
                        <select name="status" id="project_status" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                            @foreach($statuses as $key => $label)
                                <option value="{{ $key }}" @selected(old('status', $project->status ?? 'active') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <script>
                (function () {
                    var phase = document.getElementById('project_phase');
                    var status = document.getElementById('project_status');
                    var hint = document.getElementById('phase_hint');
                    if (!phase || !status) return;
                    status.addEventListener('change', function () {
                        if (status.value === 'completed') {
                            phase.value = 'completion';
                            if (hint) hint.textContent = 'Completed projects sit in Completion. Construction starts when a PA case records works on site.';
                        }
                    });
                })();
                </script>
                <div style="padding-top: 0.35rem; border-top: 1px solid #e2e8f0;">
                    <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.55rem;">Site</div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 0.85rem;">
                        <div>
                            <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Premises / plot</label>
                            <input type="text" name="site_premises" value="{{ old('site_premises', $project->site_premises ?? '') }}" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Street</label>
                            <input type="text" name="site_street" value="{{ old('site_street', $project->site_street ?? '') }}" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Locality</label>
                            <input type="text" name="site_locality" id="projectSiteLocality" value="{{ old('site_locality', $project->site_locality ?? '') }}" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        </div>
                    </div>
                    <div style="margin-top: 0.85rem;">
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Full site address (optional override)</label>
                        <textarea name="site_address" rows="2" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ old('site_address', $project->site_address ?? '') }}</textarea>
                    </div>
                    @include('pro.architect.partials.site-pin-map', ['project' => $project, 'mapServerUrl' => $mapServerUrl ?? null])
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Notes</label>
                    <textarea name="notes" rows="3" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ old('notes', $project->notes ?? '') }}</textarea>
                </div>
                <button type="submit" style="background: #3f6212; color: white; border: none; padding: 0.75rem 1.1rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer; width: fit-content;">Save project</button>
            </div>
        </form>

        <script>
        (function () {
            var suggestUrl = @json($suggestReferenceUrl ?? url('/pro/architect/projects/suggest-reference'));
            var excludeId = @json($project->id ?? null);
            var isCreate = @json(!$project);
            var field = document.getElementById('referenceCodeField');
            var btn = document.getElementById('generateReferenceBtn');
            var guide = document.getElementById('referenceGuide');
            var clientSelect = document.getElementById('projectClientId');
            var localityInput = document.getElementById('projectSiteLocality');
            if (!field || !btn || !clientSelect) return;

            var lastGenerated = '';
            var manualEdit = false;
            var reqSeq = 0;
            var timer = null;

            function setGuide(text) {
                if (guide) guide.textContent = text;
            }

            function isAutoValue() {
                var current = field.value.trim();
                return current === '' || current === lastGenerated;
            }

            function buildQuery() {
                var params = new URLSearchParams();
                if (clientSelect.value) params.set('client_id', clientSelect.value);
                if (localityInput && localityInput.value.trim()) {
                    params.set('site_locality', localityInput.value.trim());
                }
                if (excludeId) params.set('exclude_project_id', String(excludeId));
                return params.toString();
            }

            function applySuggestion(data, seq) {
                if (seq !== reqSeq) return;
                if (!data || !data.ok || !data.reference_code) {
                    setGuide('Could not generate reference — enter one manually.');
                    return;
                }
                var next = String(data.reference_code);
                if (!manualEdit || isAutoValue()) {
                    field.value = next;
                    lastGenerated = next;
                    manualEdit = false;
                }
                setGuide('Next: ' + next + ' — updates with client/locality. Edit freely or click Generate.');
            }

            function fetchSuggestion() {
                var seq = ++reqSeq;
                btn.disabled = true;
                fetch(suggestUrl + '?' + buildQuery(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    cache: 'no-store'
                })
                    .then(function (r) {
                        if (!r.ok) throw new Error('suggest failed');
                        return r.json();
                    })
                    .then(function (data) { applySuggestion(data, seq); })
                    .catch(function () {
                        if (seq !== reqSeq) return;
                        setGuide('Could not generate reference — enter one manually.');
                    })
                    .finally(function () {
                        if (seq === reqSeq) btn.disabled = false;
                    });
            }

            function scheduleSuggest(force) {
                if (!force && manualEdit && !isAutoValue()) {
                    setGuide('Manual reference kept. Click Generate to replace (4-letter parts + number).');
                    return;
                }
                if (force) manualEdit = false;
                clearTimeout(timer);
                timer = setTimeout(fetchSuggestion, 180);
            }

            btn.addEventListener('click', function () {
                manualEdit = false;
                scheduleSuggest(true);
            });

            field.addEventListener('input', function () {
                var current = field.value.trim();
                manualEdit = current !== '' && current !== lastGenerated;
            });

            clientSelect.addEventListener('change', function () {
                scheduleSuggest(false);
            });
            if (localityInput) {
                localityInput.addEventListener('input', function () {
                    scheduleSuggest(false);
                });
                localityInput.addEventListener('change', function () {
                    scheduleSuggest(false);
                });
            }

            if (isCreate && !field.value.trim()) {
                manualEdit = false;
                fetchSuggestion();
            } else if (field.value.trim()) {
                lastGenerated = field.value.trim();
            }
        })();
        </script>
    @endif
@endsection
