@extends('layouts.app')

@section('page_title', 'Fill: '.$template['title'])

@section('content')
    @php
        $hasClient = collect($fields)->contains(fn ($f) => $f['name'] === 'client');
        $hasProject = collect($fields)->contains(fn ($f) => $f['name'] === 'project');
        $hasPa = collect($fields)->contains(fn ($f) => $f['name'] === 'pa');
        $otherFields = collect($fields)->reject(fn ($f) => in_array($f['name'], ['client', 'project', 'pa'], true))->values();
    @endphp

    <div style="margin-bottom: 1.25rem;">
        <a href="/pro/architect/templates" style="color: var(--text-muted); text-decoration: none; font-weight: 600;">← BCA templates</a>
        <h1 style="margin: 0.5rem 0 0.25rem; color: var(--primary-navy); font-size: 1.45rem;">{{ $template['title'] }}</h1>
        <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem; max-width: 40rem;">{{ $template['description'] }}</p>
    </div>

    @if($errors->any())
        <div style="background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
            <ul style="margin: 0; padding-left: 1.1rem;">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="/pro/architect/templates/{{ $template['key'] }}/generate" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.35rem; max-width: 760px; box-shadow: var(--shadow-sm);">
        @csrf

        @if($hasClient || $hasProject || $hasPa)
            <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.75rem;">Context for this template</div>
            <div style="display: grid; gap: 0.85rem; margin-bottom: 1.15rem;">
                @if($hasClient)
                    @php $clientField = collect($fields)->firstWhere('name', 'client'); @endphp
                    <div>
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">
                            {{ $clientField['label'] }}@if($clientField['required']) * @endif
                        </label>
                        <select name="client_id" id="fillClient" @required($clientField['required']) style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                            <option value="">Select client…</option>
                            @foreach($clients as $c)
                                <option value="{{ $c->id }}" @selected(old('client_id', $preselect['client_id'] ?? '') == $c->id)>{{ $c->name }}</option>
                            @endforeach
                        </select>
                        @if($clientField['help'])
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">{{ $clientField['help'] }}</div>
                        @endif
                    </div>
                @endif

                @if($hasProject)
                    @php $projectField = collect($fields)->firstWhere('name', 'project'); @endphp
                    <div>
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">
                            {{ $projectField['label'] }}@if($projectField['required']) * @endif
                        </label>
                        <select name="architect_project_id" id="fillProject" @required($projectField['required']) style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                            <option value="">Select project…</option>
                        </select>
                        @if($projectField['help'])
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">{{ $projectField['help'] }}</div>
                        @endif
                    </div>
                @endif

                @if($hasPa)
                    @php $paField = collect($fields)->firstWhere('name', 'pa'); @endphp
                    <div>
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">
                            {{ $paField['label'] }}@if($paField['required']) * @endif
                        </label>
                        <select name="architect_pa_application_id" id="fillPa" @required($paField['required']) style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                            <option value="">Select PA…</option>
                        </select>
                        @if($paField['help'])
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">{{ $paField['help'] }}</div>
                        @endif
                    </div>
                @endif
            </div>
            <div id="contextPreview" style="display: none; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: var(--radius-md); padding: 0.85rem 1rem; margin-bottom: 1.15rem; font-size: 0.85rem; color: var(--primary-navy);"></div>
        @endif

        @if($otherFields->isNotEmpty())
            <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.75rem; padding-top: 0.35rem; border-top: 1px solid #e2e8f0;">Fields for this form</div>
            <div style="display: grid; gap: 0.85rem; margin-bottom: 1.15rem;">
                @foreach($otherFields as $field)
                    <div>
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">
                            {{ $field['label'] }}@if($field['required']) * @endif
                        </label>
                        @if($field['type'] === 'textarea')
                            <textarea name="{{ $field['name'] }}" rows="3" @required($field['required']) style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ old($field['name']) }}</textarea>
                        @elseif($field['type'] === 'date')
                            <input type="date" name="{{ $field['name'] }}" value="{{ old($field['name']) }}" @if(in_array($field['name'], ['start_date', 'commencement_override'], true)) max="{{ date('Y-m-d') }}" @endif @required($field['required']) style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        @else
                            <input type="text" name="{{ $field['name'] }}" value="{{ old($field['name']) }}" @required($field['required']) style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        @endif
                        @if($field['help'])
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">{{ $field['help'] }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <div style="display: flex; gap: 0.55rem; flex-wrap: wrap; align-items: center;">
            <button type="submit" style="background: #3f6212; color: white; border: none; padding: 0.75rem 1.15rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Generate PDF</button>
            <a href="/pro/architect/templates" style="color: var(--text-muted); font-weight: 600; text-decoration: none; font-size: 0.9rem;">Cancel</a>
            @if($template['blank_file'])
                <a href="/pro/architect/templates/{{ $template['key'] }}/blank" style="margin-left: auto; color: #3f6212; font-weight: 600; text-decoration: none; font-size: 0.85rem;">Also download official blank</a>
            @endif
        </div>
    </form>

    <script>
        (function () {
            var projects = @json($projectCascade);
            var pas = @json($paCascade);

            var clientEl = document.getElementById('fillClient');
            var projectEl = document.getElementById('fillProject');
            var paEl = document.getElementById('fillPa');
            var preview = document.getElementById('contextPreview');
            var preProject = @json(old('architect_project_id', $preselect['project_id'] ?? ''));
            var prePa = @json(old('architect_pa_application_id', $preselect['pa_id'] ?? ''));
            var syncing = false;

            function setOptions(select, items, placeholder, selected) {
                if (!select) return;
                var html = '<option value="">' + placeholder + '</option>';
                items.forEach(function (item) {
                    html += '<option value="' + item.id + '"' + (String(selected) === String(item.id) ? ' selected' : '') + '>' + item.label + '</option>';
                });
                select.innerHTML = html;
            }

            function filteredProjects() {
                if (!clientEl || !clientEl.value) {
                    return projects.slice();
                }
                return projects.filter(function (p) { return String(p.client_id) === String(clientEl.value); });
            }

            function filteredPas() {
                var list = pas.slice();
                if (projectEl && projectEl.value) {
                    list = list.filter(function (p) { return String(p.project_id) === String(projectEl.value); });
                } else if (clientEl && clientEl.value) {
                    var projectIds = filteredProjects().map(function (p) { return String(p.id); });
                    list = list.filter(function (p) { return projectIds.indexOf(String(p.project_id)) !== -1; });
                }
                return list;
            }

            function projectById(id) {
                return projects.find(function (p) { return String(p.id) === String(id); });
            }

            function paById(id) {
                return pas.find(function (p) { return String(p.id) === String(id); });
            }

            function refreshPreview() {
                if (!preview) return;
                var bits = [];
                if (clientEl && clientEl.value) bits.push('Client selected');
                if (projectEl && projectEl.value) {
                    var p = projectById(projectEl.value);
                    bits.push(p ? p.label : 'Project selected');
                }
                if (paEl && paEl.value) {
                    var a = paById(paEl.value);
                    bits.push(a ? a.label : 'PA selected');
                }
                if (!bits.length) {
                    preview.style.display = 'none';
                    preview.textContent = '';
                    return;
                }
                preview.style.display = 'block';
                preview.textContent = bits.join(' · ');
            }

            function syncProjects(selected) {
                if (!projectEl) return;
                setOptions(projectEl, filteredProjects(), 'Select project…', selected || '');
            }

            function syncPas(selected) {
                if (!paEl) return;
                setOptions(paEl, filteredPas(), 'Select PA…', selected || '');
            }

            function onClientChange() {
                if (syncing) return;
                syncProjects('');
                syncPas('');
                refreshPreview();
            }

            function onProjectChange() {
                if (syncing) return;
                var p = projectEl && projectEl.value ? projectById(projectEl.value) : null;
                if (p && clientEl && String(clientEl.value) !== String(p.client_id || '')) {
                    syncing = true;
                    clientEl.value = p.client_id || '';
                    syncing = false;
                    syncProjects(p.id);
                }
                syncPas('');
                refreshPreview();
            }

            function onPaChange() {
                if (syncing) return;
                var a = paEl && paEl.value ? paById(paEl.value) : null;
                if (!a) {
                    refreshPreview();
                    return;
                }
                var p = projectById(a.project_id);
                syncing = true;
                if (p && clientEl) clientEl.value = p.client_id || '';
                if (projectEl) {
                    syncProjects(a.project_id);
                    projectEl.value = a.project_id;
                }
                syncing = false;
                syncPas(a.id);
                refreshPreview();
            }

            if (clientEl) clientEl.addEventListener('change', onClientChange);
            if (projectEl) projectEl.addEventListener('change', onProjectChange);
            if (paEl) paEl.addEventListener('change', onPaChange);

            syncProjects(preProject || '');
            syncPas(prePa || '');

            if (prePa) {
                onPaChange();
            } else if (preProject) {
                onProjectChange();
            } else {
                refreshPreview();
            }
        })();
    </script>
@endsection
