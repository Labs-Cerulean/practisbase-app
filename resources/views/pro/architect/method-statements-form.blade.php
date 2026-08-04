@extends('layouts.app')

@section('page_title', $statement ? 'Edit method statement' : 'New method statement')

@section('content')
    @include('pro.shared.field-styles')
    @php
        $isEdit = $statement !== null;
        $action = $isEdit ? '/pro/architect/method-statements/'.$statement->id : '/pro/architect/method-statements';
        $context = $context ?? [];
        $projectOptions = $projectOptions ?? [];
        $oldPayload = old('payload');
        $p = is_array($oldPayload) ? \App\Support\ArchitectMethodStatementBlueprint::normalize($oldPayload) : $payload;
        if (count($p['sections']) === 0) {
            $p['sections'] = [['heading' => '', 'body' => '']];
        }
    @endphp
    <div style="margin-bottom: 1.25rem;">
        <a href="{{ $isEdit ? '/pro/architect/method-statements/'.$statement->id : ($prefill['project_id'] ? '/pro/architect/projects/'.$prefill['project_id'] : '/pro/architect/method-statements') }}" style="color: var(--text-muted); text-decoration: none; font-weight: 600;">← Back</a>
        <h1 style="margin: 0.5rem 0 0; color: var(--primary-navy); font-size: 1.5rem;">{{ $isEdit ? 'Edit method statement' : 'Method statement builder' }}</h1>
        <p style="margin: 0.35rem 0 0; color: var(--text-muted); font-size: 0.88rem;">Lives under a project — client, site, and filing ref auto-fill. Schedule starters seed section structure only.</p>
    </div>

    @unless($isEdit)
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1rem;">
            @foreach($starters as $key => $starter)
                <a href="/pro/architect/method-statements/create?starter={{ $key }}&amp;project_id={{ $prefill['project_id'] }}{{ ($prefill['pa_id'] ?? null) ? '&pa_id='.$prefill['pa_id'] : '' }}"
                   style="padding: 0.45rem 0.75rem; border-radius: var(--radius-md); text-decoration: none; font-size: 0.82rem; font-weight: 600; {{ $starterKey === $key ? 'background: #3f6212; color: white;' : 'background: white; color: var(--primary-navy); border: 1px solid var(--border-light);' }}">
                    {{ $starter['label'] }}
                </a>
            @endforeach
        </div>
    @endunless

    @if($errors->any())
        <div style="background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="eng-field-form" style="display: grid; gap: 1.1rem; max-width: 920px;">
        @csrf
        @if($isEdit) @method('PUT') @endif
        <input type="hidden" name="statement_type" value="{{ old('statement_type', $starterKey ?? $statement->statement_type ?? 'excavation') }}">

        <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm); display: grid; gap: 0.75rem;">
            <h2 style="margin: 0; font-size: 1.05rem; color: var(--primary-navy);">Project &amp; header</h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Project *</label>
                    <select name="architect_project_id" id="archProjectSelect" required style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        @foreach($projects as $pRow)
                            <option value="{{ $pRow->id }}" @selected(old('architect_project_id', $prefill['project_id'] ?? '') == $pRow->id)>{{ $pRow->name }}@if($pRow->client) ({{ $pRow->client->name }})@endif</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">PA (optional)</label>
                    <select name="architect_pa_application_id" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        <option value="">None</option>
                        @foreach($pas as $pa)
                            <option value="{{ $pa->id }}" @selected(old('architect_pa_application_id', $prefill['pa_id'] ?? '') == $pa->id)>{{ $pa->displayLabel() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Title *</label>
                <input type="text" name="title" value="{{ old('title', $statement->title ?? $defaultTitle) }}" required style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 0.75rem;">
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Filing ref</label>
                    <input type="text" name="statement_number" id="docRefField" value="{{ old('statement_number', $statement->statement_number ?? ($context['suggested_ref'] ?? '')) }}" placeholder="Auto from project" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.25rem;">Generated from project reference + DMS/EMS/CMS sequence.</div>
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Issue date *</label>
                    <input type="date" name="issued_on" max="{{ date('Y-m-d') }}" value="{{ old('issued_on', optional($statement->issued_on ?? null)->format('Y-m-d') ?: date('Y-m-d')) }}" required style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Commencement note</label>
                    <input type="text" name="commencement_note" id="fieldCommencement" value="{{ old('commencement_note', $statement->commencement_note ?? ($context['commencement_note'] ?? '')) }}" placeholder="Optional" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>
        </section>

        <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm); display: grid; gap: 0.75rem;">
            <h2 style="margin: 0; font-size: 1.05rem; color: var(--primary-navy);">Client &amp; site</h2>
            <p style="margin: 0; font-size: 0.8rem; color: var(--text-muted);">Pulled from the project/client — adjust if needed.</p>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Client</label>
                    <input type="text" name="client_name" id="fieldClientName" value="{{ old('client_name', $statement->client_name ?? ($context['client_name'] ?? '')) }}" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Client address</label>
                    <input type="text" name="client_address" id="fieldClientAddress" value="{{ old('client_address', $statement->client_address ?? ($context['client_address'] ?? '')) }}" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>
            <div>
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Project description</label>
                <textarea name="project_description" id="fieldProjectDescription" rows="2" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ old('project_description', $statement->project_description ?? ($context['project_description'] ?? '')) }}</textarea>
            </div>
            <div>
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Site address</label>
                <input type="text" name="site_address" id="fieldSiteAddress" value="{{ old('site_address', $statement->site_address ?? ($context['site_address'] ?? '')) }}" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
        </section>

        <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm); display: grid; gap: 0.75rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                <h2 style="margin: 0; font-size: 1.05rem; color: var(--primary-navy);">Sections</h2>
                <button type="button" id="addSection" class="eng-touch-btn" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-weight: 600; cursor: pointer;">+ Section</button>
            </div>
            <div id="sectionRows" style="display: grid; gap: 0.75rem;">
                @foreach($p['sections'] as $i => $row)
                    <div class="section-row" style="border: 1px solid #e2e8f0; border-radius: var(--radius-md); padding: 0.75rem; display: grid; gap: 0.45rem;">
                        <div style="display: flex; gap: 0.45rem;">
                            <input type="text" name="payload[sections][{{ $i }}][heading]" value="{{ $row['heading'] }}" placeholder="Heading" style="flex: 1; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                            <button type="button" class="rm" style="background: none; border: none; color: #b91c1c; font-weight: 700; cursor: pointer;">×</button>
                        </div>
                        <textarea name="payload[sections][{{ $i }}][body]" rows="4" placeholder="Body text" style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ $row['body'] }}</textarea>
                    </div>
                @endforeach
            </div>
            <div>
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Appendix / annex references</label>
                <input type="text" name="payload[appendix_ref]" value="{{ $p['appendix_ref'] }}" placeholder="Optional annex note" style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div>
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Legal / disclaimer footer</label>
                <textarea name="payload[legal_footer]" rows="3" style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ $p['legal_footer'] }}</textarea>
            </div>
        </section>

        @include('pro.shared.field-photos', [
            'photoLinkOptions' => [],
            'existingPhotos' => $statement->photos ?? collect(),
            'existingPhotoBase' => $isEdit ? '/pro/architect/method-statements/'.$statement->id.'/photos' : null,
        ])

        <div class="eng-field-savebar">
            <button type="submit" style="background: #3f6212;">{{ $isEdit ? 'Save draft' : 'Save draft method statement' }}</button>
            <div class="eng-field-hint">Draft stays editable until you Stamp &amp; issue. Photo notes can annotate annexes.</div>
        </div>
    </form>

    <script>
        (function () {
            var projectOptions = @json($projectOptions);
            var projectSelect = document.getElementById('archProjectSelect');
            var refField = document.getElementById('docRefField');
            var sectionBox = document.getElementById('sectionRows');
            var refDirty = {{ $isEdit ? 'true' : 'false' }};

            function applyProject(id, forceRef) {
                var data = projectOptions[String(id)];
                if (!data) return;
                var map = {
                    fieldClientName: data.client_name,
                    fieldClientAddress: data.client_address,
                    fieldProjectDescription: data.project_description,
                    fieldSiteAddress: data.site_address,
                    fieldCommencement: data.commencement_note
                };
                Object.keys(map).forEach(function (fid) {
                    var el = document.getElementById(fid);
                    if (el) el.value = map[fid] || '';
                });
                if (refField && (forceRef || !refDirty)) {
                    refField.value = data.suggested_ref || '';
                }
            }

            if (refField) refField.addEventListener('input', function () { refDirty = true; });
            if (projectSelect) {
                projectSelect.addEventListener('change', function () {
                    applyProject(projectSelect.value, true);
                    refDirty = false;
                });
            }

            sectionBox.addEventListener('click', function (e) {
                if (!e.target.classList.contains('rm')) return;
                var row = e.target.closest('.section-row');
                if (!row) return;
                if (sectionBox.children.length <= 1) {
                    row.querySelectorAll('input, textarea').forEach(function (el) { el.value = ''; });
                    return;
                }
                row.remove();
            });
            document.getElementById('addSection').addEventListener('click', function () {
                var i = sectionBox.children.length;
                var div = document.createElement('div');
                div.className = 'section-row';
                div.style.cssText = 'border: 1px solid #e2e8f0; border-radius: var(--radius-md); padding: 0.75rem; display: grid; gap: 0.45rem;';
                div.innerHTML = '<div style="display: flex; gap: 0.45rem;"><input type="text" name="payload[sections][' + i + '][heading]" placeholder="Heading" style="flex: 1; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);"><button type="button" class="rm" style="background: none; border: none; color: #b91c1c; font-weight: 700; cursor: pointer;">×</button></div>' +
                    '<textarea name="payload[sections][' + i + '][body]" rows="4" placeholder="Body text" style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);"></textarea>';
                sectionBox.appendChild(div);
            });
        })();
    </script>
@endsection
