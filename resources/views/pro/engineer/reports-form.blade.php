@extends('layouts.app')

@section('page_title', $report ? 'Edit report' : 'New report')

@section('content')
    @include('pro.shared.field-styles')
    @php
        $isEdit = $report !== null;
        $action = $isEdit ? '/pro/engineer/reports/'.$report->id : '/pro/engineer/reports';
        $context = $context ?? [];
        $projectOptions = $projectOptions ?? [];
        $commonChecklistItems = $commonChecklistItems ?? [];
        $oldPayload = old('payload');
        $p = is_array($oldPayload) ? \App\Support\EngineerReportBlueprint::normalize($oldPayload) : $payload;
        if (count($p['measurements']) === 0) {
            $p['measurements'] = [\App\Support\EngineerReportBlueprint::blankMeasurementRow()];
        }
        if (count($p['checklist']) === 0) {
            $p['checklist'] = [\App\Support\EngineerReportBlueprint::blankChecklistRow()];
        }
        if (count($p['sections']) === 0) {
            $p['sections'] = [['heading' => '', 'body' => '']];
        }
        $photoLinkOptions = \App\Support\EngineerReportBlueprint::photoLinkOptions($p);
    @endphp
    <div style="margin-bottom: 1.25rem;">
        <a href="{{ $isEdit ? '/pro/engineer/reports/'.$report->id : '/pro/engineer/projects/'.$prefill['project_id'] }}" style="color: var(--text-muted); text-decoration: none; font-weight: 600;">← Back</a>
        <h1 style="margin: 0.5rem 0 0; color: var(--primary-navy); font-size: 1.5rem;">{{ $isEdit ? 'Edit report' : 'Report builder' }}</h1>
        <p style="margin: 0.35rem 0 0; color: var(--text-muted); font-size: 0.88rem;">Same form for any specialised survey — attributes, checklist, measurements, and narrative. Built for phone on site.</p>
    </div>

    @unless($isEdit)
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1rem;">
            @foreach($starters as $key => $starter)
                <a href="/pro/engineer/reports/create?starter={{ $key }}{{ ($prefill['project_id'] ?? null) ? '&project_id='.$prefill['project_id'] : '' }}{{ ($prefill['pa_id'] ?? null) ? '&pa_id='.$prefill['pa_id'] : '' }}"
                   style="padding: 0.45rem 0.75rem; border-radius: var(--radius-md); text-decoration: none; font-size: 0.82rem; font-weight: 600; {{ $starterKey === $key ? 'background: var(--primary-cerulean); color: white;' : 'background: white; color: var(--primary-navy); border: 1px solid var(--border-light);' }}">
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
        <input type="hidden" name="report_type" value="{{ old('report_type', $starterKey ?? $report->report_type ?? 'blank') }}">

        <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm); display: grid; gap: 0.75rem;">
            <h2 style="margin: 0; font-size: 1.05rem; color: var(--primary-navy);">Project &amp; header</h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Project *</label>
                    <select name="engineer_project_id" id="engProjectSelect" required style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        @foreach($projects as $pRow)
                            <option value="{{ $pRow->id }}" @selected(old('engineer_project_id', $prefill['project_id'] ?? '') == $pRow->id)>{{ $pRow->name }}@if($pRow->client) ({{ $pRow->client->name }})@endif</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">PA (optional)</label>
                    <select name="engineer_pa_application_id" id="engPaSelect" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        <option value="">None</option>
                        @foreach($pas as $pa)
                            <option value="{{ $pa->id }}" data-project="{{ $pa->engineer_project_id }}" @selected(old('engineer_pa_application_id', $prefill['pa_id'] ?? '') == $pa->id)>{{ $pa->displayLabel() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Report title *</label>
                <input type="text" name="title" value="{{ old('title', $report->title ?? $defaultTitle) }}" required style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 0.75rem;">
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Filing ref</label>
                    <input type="text" name="report_number" id="docRefField" value="{{ old('report_number', $report->report_number ?? ($context['suggested_ref'] ?? '')) }}" placeholder="Auto from project" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.25rem;">Generated from project reference + ER sequence. Editable.</div>
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Conclusion / result</label>
                    <input type="text" name="conclusion" value="{{ old('conclusion', $report->conclusion ?? '') }}" placeholder="e.g. Satisfactory / Conditional / High risk" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.75rem;">
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Surveyed on</label>
                    <input type="date" name="surveyed_on" max="{{ date('Y-m-d') }}" value="{{ old('surveyed_on', optional($report->surveyed_on ?? null)->format('Y-m-d')) }}" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Issue date *</label>
                    <input type="date" name="issued_on" max="{{ date('Y-m-d') }}" value="{{ old('issued_on', optional($report->issued_on ?? null)->format('Y-m-d') ?: date('Y-m-d')) }}" required style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>
        </section>

        <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm); display: grid; gap: 0.75rem;">
            <h2 style="margin: 0; font-size: 1.05rem; color: var(--primary-navy);">Client</h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Client / commissioning party</label>
                    <input type="text" name="client_name" id="fieldClientName" value="{{ old('client_name', $report->client_name ?? ($context['client_name'] ?? '')) }}" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Contact person</label>
                    <input type="text" name="contact_person" id="fieldContactPerson" value="{{ old('contact_person', $report->contact_person ?? ($context['contact_person'] ?? '')) }}" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Contact phone</label>
                    <input type="text" name="contact_phone" id="fieldContactPhone" value="{{ old('contact_phone', $report->contact_phone ?? ($context['contact_phone'] ?? '')) }}" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Site / premises</label>
                    <input type="text" name="site_address" id="fieldSiteAddress" value="{{ old('site_address', $report->site_address ?? ($context['site_address'] ?? '')) }}" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>
            <div>
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Client address</label>
                <textarea name="client_address" id="fieldClientAddress" rows="2" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ old('client_address', $report->client_address ?? ($context['client_address'] ?? '')) }}</textarea>
            </div>
        </section>

        <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm); display: grid; gap: 0.75rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                <h2 style="margin: 0; font-size: 1.05rem; color: var(--primary-navy);">Subject attributes</h2>
                <button type="button" id="addAttr" class="eng-touch-btn" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-weight: 600; cursor: pointer;">+ Row</button>
            </div>
            <input type="text" name="payload[subject_heading]" value="{{ $p['subject_heading'] }}" placeholder="Section heading (e.g. Premises / scope)" style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            <div id="attrRows" style="display: grid; gap: 0.5rem;">
                @foreach($p['attributes'] as $i => $row)
                    <div class="attr-row" style="display: grid; grid-template-columns: 1fr 1.4fr auto; gap: 0.45rem;">
                        <input type="text" name="payload[attributes][{{ $i }}][label]" value="{{ $row['label'] }}" placeholder="Label" style="padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        <input type="text" name="payload[attributes][{{ $i }}][value]" value="{{ $row['value'] }}" placeholder="Value" style="padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        <button type="button" class="rm" style="background: none; border: none; color: #b91c1c; font-weight: 700; cursor: pointer;">×</button>
                    </div>
                @endforeach
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-top: 0.35rem;">
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Highlight label</label>
                    <input type="text" name="payload[highlight_label]" value="{{ $p['highlight_label'] }}" placeholder="e.g. Overall risk" style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Highlight value</label>
                    <input type="text" name="payload[highlight_value]" value="{{ $p['highlight_value'] }}" placeholder="e.g. Medium" style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>
        </section>

        <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm); display: grid; gap: 0.75rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                <h2 style="margin: 0; font-size: 1.05rem; color: var(--primary-navy);">Checklist</h2>
                <div style="display: flex; gap: 0.4rem; flex-wrap: wrap;">
                    @if(count($commonChecklistItems))
                        <button type="button" id="insertCommonChecklist" class="eng-touch-btn" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-weight: 600; cursor: pointer;">Insert common list</button>
                    @endif
                    <button type="button" id="addCheck" class="eng-touch-btn" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-weight: 600; cursor: pointer;">+ Row</button>
                </div>
            </div>
            <input type="text" name="payload[checklist_heading]" value="{{ $p['checklist_heading'] }}" placeholder="Checklist heading (leave blank to hide on PDF if empty)" style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            <div id="checkRows" style="display: grid; gap: 0.5rem;">
                @foreach($p['checklist'] as $i => $row)
                    <div class="check-row" data-row-id="{{ $row['id'] }}" style="display: grid; grid-template-columns: 1.4fr 0.7fr 1fr auto; gap: 0.45rem;">
                        <input type="hidden" name="payload[checklist][{{ $i }}][id]" value="{{ $row['id'] }}">
                        <input type="text" name="payload[checklist][{{ $i }}][item]" class="check-label" value="{{ $row['item'] }}" placeholder="Item" style="padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        <input type="text" name="payload[checklist][{{ $i }}][outcome]" value="{{ $row['outcome'] }}" placeholder="Outcome" style="padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        <input type="text" name="payload[checklist][{{ $i }}][comments]" value="{{ $row['comments'] }}" placeholder="Comments" style="padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        <button type="button" class="rm" style="background: none; border: none; color: #b91c1c; font-weight: 700; cursor: pointer;">×</button>
                    </div>
                @endforeach
            </div>
        </section>

        <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm); display: grid; gap: 0.75rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                <h2 style="margin: 0; font-size: 1.05rem; color: var(--primary-navy);">Measurements</h2>
                <button type="button" id="addMeas" class="eng-touch-btn" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-weight: 600; cursor: pointer;">+ Row</button>
            </div>
            <input type="text" name="payload[measurements_heading]" value="{{ $p['measurements_heading'] }}" placeholder="Measurements heading (optional — leave blank if not used)" style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            <div id="measRows" style="display: grid; gap: 0.5rem; overflow-x: auto;">
                @foreach($p['measurements'] as $i => $row)
                    <div class="meas-row" data-row-id="{{ $row['id'] }}" style="display: grid; grid-template-columns: 1.1fr 0.9fr 0.7fr 0.55fr 0.7fr 0.7fr auto; gap: 0.35rem; min-width: 640px;">
                        <input type="hidden" name="payload[measurements][{{ $i }}][id]" value="{{ $row['id'] }}">
                        <input type="text" name="payload[measurements][{{ $i }}][location]" class="meas-location" value="{{ $row['location'] }}" placeholder="Location" style="padding: 0.5rem 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        <input type="text" name="payload[measurements][{{ $i }}][parameter]" class="meas-parameter" value="{{ $row['parameter'] }}" placeholder="Parameter" style="padding: 0.5rem 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        <input type="text" name="payload[measurements][{{ $i }}][reading]" value="{{ $row['reading'] }}" placeholder="Reading" style="padding: 0.5rem 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        <input type="text" name="payload[measurements][{{ $i }}][unit]" value="{{ $row['unit'] }}" placeholder="Unit" style="padding: 0.5rem 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        <input type="text" name="payload[measurements][{{ $i }}][limit]" value="{{ $row['limit'] }}" placeholder="Limit" style="padding: 0.5rem 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        <input type="text" name="payload[measurements][{{ $i }}][result]" value="{{ $row['result'] }}" placeholder="Result" style="padding: 0.5rem 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        <button type="button" class="rm" style="background: none; border: none; color: #b91c1c; font-weight: 700; cursor: pointer;">×</button>
                    </div>
                @endforeach
            </div>
            <div style="font-size: 0.78rem; color: var(--text-muted);">Useful for noise, lighting, and ventilation. Clear all rows or leave blank if the report is narrative-only.</div>
        </section>

        <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm); display: grid; gap: 0.75rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                <h2 style="margin: 0; font-size: 1.05rem; color: var(--primary-navy);">Narrative sections</h2>
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
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Legal / disclaimer footer</label>
                <textarea name="payload[legal_footer]" rows="3" style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ $p['legal_footer'] }}</textarea>
            </div>
        </section>

        @include('pro.shared.field-photos', [
            'photoLinkOptions' => $photoLinkOptions,
            'existingPhotos' => $report->photos ?? collect(),
            'existingPhotoBase' => $isEdit ? '/pro/engineer/reports/'.$report->id.'/photos' : null,
        ])

        <div class="eng-field-savebar">
            <button type="submit">{{ $isEdit ? 'Save draft' : 'Save draft report' }}</button>
            <div class="eng-field-hint">Draft stays editable until you Stamp &amp; issue.</div>
        </div>
    </form>

    <script>
        (function () {
            var projectOptions = @json($projectOptions);
            var commonChecklistItems = @json($commonChecklistItems);
            var projectSelect = document.getElementById('engProjectSelect');
            var refField = document.getElementById('docRefField');
            var attrBox = document.getElementById('attrRows');
            var checkBox = document.getElementById('checkRows');
            var measBox = document.getElementById('measRows');
            var sectionBox = document.getElementById('sectionRows');
            var refDirty = {{ $isEdit ? 'true' : 'false' }};
            var attrIndex = attrBox.children.length;
            var checkIndex = checkBox.children.length;
            var measIndex = measBox.children.length;
            var sectionIndex = sectionBox.children.length;

            function newRowId(prefix) {
                return prefix + Math.random().toString(36).slice(2, 10);
            }

            function applyProject(id, forceRef) {
                var data = projectOptions[String(id)];
                if (!data) return;
                var map = {
                    fieldClientName: data.client_name,
                    fieldClientAddress: data.client_address,
                    fieldContactPerson: data.contact_person,
                    fieldContactPhone: data.contact_phone,
                    fieldSiteAddress: data.site_address
                };
                Object.keys(map).forEach(function (fid) {
                    var el = document.getElementById(fid);
                    if (el) el.value = map[fid] || '';
                });
                if (refField && (forceRef || !refDirty)) refField.value = data.suggested_ref || '';
            }

            if (refField) refField.addEventListener('input', function () { refDirty = true; });
            if (projectSelect) {
                projectSelect.addEventListener('change', function () {
                    applyProject(projectSelect.value, true);
                    refDirty = false;
                    var pa = document.getElementById('engPaSelect');
                    if (pa) pa.value = '';
                });
            }

            function syncPhotoLinks() {
                var options = [];
                Array.prototype.forEach.call(checkBox.querySelectorAll('.check-row'), function (row, i) {
                    var id = row.getAttribute('data-row-id') || '';
                    if (!id) return;
                    var item = (row.querySelector('.check-label') || {}).value || '';
                    options.push({ id: id, label: 'Checklist ' + (i + 1) + (item ? ' — ' + item : '') });
                });
                Array.prototype.forEach.call(measBox.querySelectorAll('.meas-row'), function (row, i) {
                    var id = row.getAttribute('data-row-id') || '';
                    if (!id) return;
                    var location = (row.querySelector('.meas-location') || {}).value || '';
                    var parameter = (row.querySelector('.meas-parameter') || {}).value || '';
                    var label = 'Measurement ' + (i + 1);
                    if (location) label += ' — ' + location;
                    if (parameter) label += ' — ' + parameter;
                    options.push({ id: id, label: label });
                });
                if (typeof window.practisRefreshPhotoLinkOptions === 'function') {
                    window.practisRefreshPhotoLinkOptions(options);
                }
            }

            checkBox.addEventListener('input', syncPhotoLinks);
            measBox.addEventListener('input', syncPhotoLinks);

            function bindRemove(container) {
                container.addEventListener('click', function (e) {
                    if (!e.target.classList.contains('rm')) return;
                    var row = e.target.closest('.attr-row, .check-row, .meas-row, .section-row');
                    if (!row) return;
                    var parent = row.parentNode;
                    if (parent.children.length <= 1) {
                        row.querySelectorAll('input:not([type="hidden"]), textarea').forEach(function (el) { el.value = ''; });
                        syncPhotoLinks();
                        return;
                    }
                    row.remove();
                    syncPhotoLinks();
                });
            }

            bindRemove(attrBox);
            bindRemove(checkBox);
            bindRemove(measBox);
            bindRemove(sectionBox);

            document.getElementById('addAttr').addEventListener('click', function () {
                var i = attrIndex++;
                var div = document.createElement('div');
                div.className = 'attr-row';
                div.style.cssText = 'display: grid; grid-template-columns: 1fr 1.4fr auto; gap: 0.45rem;';
                div.innerHTML = '<input type="text" name="payload[attributes][' + i + '][label]" placeholder="Label" style="padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">' +
                    '<input type="text" name="payload[attributes][' + i + '][value]" placeholder="Value" style="padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">' +
                    '<button type="button" class="rm" style="background: none; border: none; color: #b91c1c; font-weight: 700; cursor: pointer;">×</button>';
                attrBox.appendChild(div);
            });

            function appendCheck(label) {
                var i = checkIndex++;
                var id = newRowId('c');
                var div = document.createElement('div');
                div.className = 'check-row';
                div.setAttribute('data-row-id', id);
                div.style.cssText = 'display: grid; grid-template-columns: 1.4fr 0.7fr 1fr auto; gap: 0.45rem;';
                div.innerHTML = '<input type="hidden" name="payload[checklist][' + i + '][id]" value="' + id + '">' +
                    '<input type="text" name="payload[checklist][' + i + '][item]" class="check-label" placeholder="Item" style="padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">' +
                    '<input type="text" name="payload[checklist][' + i + '][outcome]" placeholder="Outcome" style="padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">' +
                    '<input type="text" name="payload[checklist][' + i + '][comments]" placeholder="Comments" style="padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">' +
                    '<button type="button" class="rm" style="background: none; border: none; color: #b91c1c; font-weight: 700; cursor: pointer;">×</button>';
                checkBox.appendChild(div);
                div.querySelector('.check-label').value = label || '';
            }

            document.getElementById('addCheck').addEventListener('click', function () {
                appendCheck('');
                syncPhotoLinks();
            });

            document.getElementById('addMeas').addEventListener('click', function () {
                var i = measIndex++;
                var id = newRowId('m');
                var div = document.createElement('div');
                div.className = 'meas-row';
                div.setAttribute('data-row-id', id);
                div.style.cssText = 'display: grid; grid-template-columns: 1.1fr 0.9fr 0.7fr 0.55fr 0.7fr 0.7fr auto; gap: 0.35rem; min-width: 640px;';
                div.innerHTML = '<input type="hidden" name="payload[measurements][' + i + '][id]" value="' + id + '">' +
                    '<input type="text" name="payload[measurements][' + i + '][location]" class="meas-location" placeholder="Location" style="padding: 0.5rem 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">' +
                    '<input type="text" name="payload[measurements][' + i + '][parameter]" class="meas-parameter" placeholder="Parameter" style="padding: 0.5rem 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">' +
                    '<input type="text" name="payload[measurements][' + i + '][reading]" placeholder="Reading" style="padding: 0.5rem 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">' +
                    '<input type="text" name="payload[measurements][' + i + '][unit]" placeholder="Unit" style="padding: 0.5rem 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">' +
                    '<input type="text" name="payload[measurements][' + i + '][limit]" placeholder="Limit" style="padding: 0.5rem 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">' +
                    '<input type="text" name="payload[measurements][' + i + '][result]" placeholder="Result" style="padding: 0.5rem 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">' +
                    '<button type="button" class="rm" style="background: none; border: none; color: #b91c1c; font-weight: 700; cursor: pointer;">×</button>';
                measBox.appendChild(div);
                syncPhotoLinks();
            });

            document.getElementById('addSection').addEventListener('click', function () {
                var i = sectionIndex++;
                var div = document.createElement('div');
                div.className = 'section-row';
                div.style.cssText = 'border: 1px solid #e2e8f0; border-radius: var(--radius-md); padding: 0.75rem; display: grid; gap: 0.45rem;';
                div.innerHTML = '<div style="display: flex; gap: 0.45rem;"><input type="text" name="payload[sections][' + i + '][heading]" placeholder="Heading" style="flex: 1; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);"><button type="button" class="rm" style="background: none; border: none; color: #b91c1c; font-weight: 700; cursor: pointer;">×</button></div>' +
                    '<textarea name="payload[sections][' + i + '][body]" rows="4" placeholder="Body text" style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);"></textarea>';
                sectionBox.appendChild(div);
            });

            var commonBtn = document.getElementById('insertCommonChecklist');
            if (commonBtn) {
                commonBtn.addEventListener('click', function () {
                    var onlyBlank = checkBox.children.length === 1 &&
                        !(checkBox.querySelector('.check-label') || {}).value;
                    if (onlyBlank) checkBox.innerHTML = '';
                    (commonChecklistItems || []).forEach(function (label) { appendCheck(label); });
                    if (checkBox.children.length === 0) appendCheck('');
                    syncPhotoLinks();
                });
            }
        })();
    </script>
@endsection
