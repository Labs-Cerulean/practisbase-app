@extends('layouts.app')

@section('page_title', $report ? 'Edit condition report' : 'New condition report')

@section('content')
    @include('pro.shared.field-styles')
    @php
        $isEdit = $report !== null;
        $action = $isEdit ? '/pro/architect/condition-reports/'.$report->id : '/pro/architect/condition-reports';
    @endphp
    <div style="margin-bottom: 1.25rem;">
        <a href="{{ $isEdit ? '/pro/architect/condition-reports/'.$report->id : '/pro/architect/condition-reports' }}" style="color: var(--text-muted); text-decoration: none; font-weight: 600;">← Back</a>
        <h1 style="margin: 0.5rem 0 0; color: var(--primary-navy); font-size: 1.5rem;">{{ $isEdit ? 'Edit condition report' : 'Condition report builder' }}</h1>
        <p style="margin: 0.35rem 0 0; color: var(--text-muted); font-size: 0.88rem;">Seventh Schedule neighbour reports — narrative sections, defect rows, sketch reference, and site photos. Built for phone on site.</p>
    </div>

    @unless($isEdit)
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1rem;">
            @foreach($starters as $key => $starter)
                <a href="/pro/architect/condition-reports/create?starter={{ $key }}{{ ($prefill['project_id'] ?? null) ? '&project_id='.$prefill['project_id'] : '' }}{{ ($prefill['pa_id'] ?? null) ? '&pa_id='.$prefill['pa_id'] : '' }}"
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
        <input type="hidden" name="report_type" value="{{ old('report_type', $starterKey ?? $report->report_type ?? 'seventh_schedule') }}">

        <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm); display: grid; gap: 0.75rem;">
            <h2 style="margin: 0; font-size: 1.05rem; color: var(--primary-navy);">Header</h2>
            <div>
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Report title *</label>
                <input type="text" name="title" value="{{ old('title', $report->title ?? $defaultTitle) }}" required style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 0.75rem;">
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Your report no.</label>
                    <input type="text" name="report_number" value="{{ old('report_number', $report->report_number ?? '') }}" placeholder="e.g. MA.MSD.CR-01" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Inspected on</label>
                    <input type="date" name="inspected_on" max="{{ date('Y-m-d') }}" value="{{ old('inspected_on', optional($report->inspected_on ?? null)->format('Y-m-d')) }}" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Issue date *</label>
                    <input type="date" name="issued_on" max="{{ date('Y-m-d') }}" value="{{ old('issued_on', optional($report->issued_on ?? null)->format('Y-m-d') ?: date('Y-m-d')) }}" required style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Link project</label>
                    <select name="architect_project_id" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        <option value="">None</option>
                        @foreach($projects as $p)
                            <option value="{{ $p->id }}" @selected(old('architect_project_id', $prefill['project_id'] ?? '') == $p->id)>{{ $p->name }}@if($p->client) ({{ $p->client->name }})@endif</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Link PA (optional)</label>
                    <select name="architect_pa_application_id" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        <option value="">None</option>
                        @foreach($pas as $pa)
                            <option value="{{ $pa->id }}" @selected(old('architect_pa_application_id', $prefill['pa_id'] ?? '') == $pa->id)>{{ $pa->displayLabel() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </section>

        <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm); display: grid; gap: 0.75rem;">
            <h2 style="margin: 0; font-size: 1.05rem; color: var(--primary-navy);">Parties & addresses</h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Client / applicant</label>
                    <input type="text" name="client_name" value="{{ old('client_name', $report->client_name ?? '') }}" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Client address</label>
                    <input type="text" name="client_address" value="{{ old('client_address', $report->client_address ?? '') }}" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>
            <div>
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Project description</label>
                <textarea name="project_description" rows="2" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ old('project_description', $report->project_description ?? '') }}</textarea>
            </div>
            <div>
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Development / site address</label>
                <input type="text" name="development_address" value="{{ old('development_address', $report->development_address ?? '') }}" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div>
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Inspected property address (neighbour)</label>
                <input type="text" name="inspected_address" value="{{ old('inspected_address', $report->inspected_address ?? '') }}" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
        </section>

        @php
            $oldPayload = old('payload');
            $p = is_array($oldPayload) ? \App\Support\ArchitectConditionReportBlueprint::normalize($oldPayload) : $payload;
            if (count($p['defects']) === 0) {
                $p['defects'] = [['location' => '', 'defect' => '', 'photo_ref' => '', 'notes' => '']];
            }
            if (count($p['sections']) === 0) {
                $p['sections'] = [['heading' => '', 'body' => '']];
            }
        @endphp

        <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm); display: grid; gap: 0.75rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                <h2 style="margin: 0; font-size: 1.05rem; color: var(--primary-navy);">Schedule sections</h2>
                <button type="button" id="addSection" class="eng-touch-btn" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-weight: 600; cursor: pointer;">+ Section</button>
            </div>
            <div id="sectionRows" style="display: grid; gap: 0.75rem;">
                @foreach($p['sections'] as $i => $row)
                    <div class="section-row" style="border: 1px solid #e2e8f0; border-radius: var(--radius-md); padding: 0.75rem; display: grid; gap: 0.45rem;">
                        <div style="display: flex; gap: 0.45rem;">
                            <input type="text" name="payload[sections][{{ $i }}][heading]" value="{{ $row['heading'] }}" placeholder="Heading" style="flex: 1; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                            <button type="button" class="rm" style="background: none; border: none; color: #b91c1c; font-weight: 700; cursor: pointer;">×</button>
                        </div>
                        <textarea name="payload[sections][{{ $i }}][body]" rows="3" placeholder="Body text" style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ $row['body'] }}</textarea>
                    </div>
                @endforeach
            </div>
            <div>
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">6. Sketch plan reference</label>
                <input type="text" name="payload[sketch_ref]" value="{{ $p['sketch_ref'] }}" placeholder="e.g. Refer to Appendix A / sketch attached" style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
        </section>

        <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm); display: grid; gap: 0.75rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                <h2 style="margin: 0; font-size: 1.05rem; color: var(--primary-navy);">Observed defects</h2>
                <button type="button" id="addDefect" class="eng-touch-btn" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-weight: 600; cursor: pointer;">+ Row</button>
            </div>
            <input type="text" name="payload[defects_heading]" value="{{ $p['defects_heading'] }}" placeholder="Defects section heading" style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            <div id="defectRows" style="display: grid; gap: 0.5rem; overflow-x: auto;">
                @foreach($p['defects'] as $i => $row)
                    <div class="defect-row" style="display: grid; grid-template-columns: 1fr 1.2fr 0.7fr 1fr auto; gap: 0.35rem; min-width: 560px;">
                        <input type="text" name="payload[defects][{{ $i }}][location]" value="{{ $row['location'] }}" placeholder="Location / room" style="padding: 0.5rem 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        <input type="text" name="payload[defects][{{ $i }}][defect]" value="{{ $row['defect'] }}" placeholder="Defect" style="padding: 0.5rem 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        <input type="text" name="payload[defects][{{ $i }}][photo_ref]" value="{{ $row['photo_ref'] }}" placeholder="Photo ref" style="padding: 0.5rem 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        <input type="text" name="payload[defects][{{ $i }}][notes]" value="{{ $row['notes'] }}" placeholder="Notes" style="padding: 0.5rem 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        <button type="button" class="rm" style="background: none; border: none; color: #b91c1c; font-weight: 700; cursor: pointer;">×</button>
                    </div>
                @endforeach
            </div>
        </section>

        <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm); display: grid; gap: 0.75rem;">
            <h2 style="margin: 0; font-size: 1.05rem; color: var(--primary-navy);">Legal / disclaimer footer</h2>
            <textarea name="payload[legal_footer]" rows="3" style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ $p['legal_footer'] }}</textarea>
        </section>

        @include('pro.shared.field-photos')

        <div class="eng-field-savebar">
            <button type="submit" style="background: #3f6212;">{{ $isEdit ? 'Save draft' : 'Save draft report' }}</button>
            <div class="eng-field-hint">Draft stays editable until you Stamp &amp; issue. Upload sketch photos with site photos if needed.</div>
        </div>
    </form>

    <script>
        (function () {
            function bindRemove(container) {
                container.addEventListener('click', function (e) {
                    if (!e.target.classList.contains('rm')) return;
                    var row = e.target.closest('.section-row, .defect-row');
                    if (!row) return;
                    var parent = row.parentNode;
                    if (parent.children.length <= 1) {
                        row.querySelectorAll('input, textarea').forEach(function (el) { el.value = ''; });
                        return;
                    }
                    row.remove();
                });
            }

            var sectionBox = document.getElementById('sectionRows');
            var defectBox = document.getElementById('defectRows');
            bindRemove(sectionBox);
            bindRemove(defectBox);

            document.getElementById('addSection').addEventListener('click', function () {
                var i = sectionBox.children.length;
                var div = document.createElement('div');
                div.className = 'section-row';
                div.style.cssText = 'border: 1px solid #e2e8f0; border-radius: var(--radius-md); padding: 0.75rem; display: grid; gap: 0.45rem;';
                div.innerHTML = '<div style="display: flex; gap: 0.45rem;"><input type="text" name="payload[sections][' + i + '][heading]" placeholder="Heading" style="flex: 1; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);"><button type="button" class="rm" style="background: none; border: none; color: #b91c1c; font-weight: 700; cursor: pointer;">×</button></div>' +
                    '<textarea name="payload[sections][' + i + '][body]" rows="3" placeholder="Body text" style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);"></textarea>';
                sectionBox.appendChild(div);
            });

            document.getElementById('addDefect').addEventListener('click', function () {
                var i = defectBox.children.length;
                var div = document.createElement('div');
                div.className = 'defect-row';
                div.style.cssText = 'display: grid; grid-template-columns: 1fr 1.2fr 0.7fr 1fr auto; gap: 0.35rem; min-width: 560px;';
                div.innerHTML = '<input type="text" name="payload[defects][' + i + '][location]" placeholder="Location / room" style="padding: 0.5rem 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">' +
                    '<input type="text" name="payload[defects][' + i + '][defect]" placeholder="Defect" style="padding: 0.5rem 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">' +
                    '<input type="text" name="payload[defects][' + i + '][photo_ref]" placeholder="Photo ref" style="padding: 0.5rem 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">' +
                    '<input type="text" name="payload[defects][' + i + '][notes]" placeholder="Notes" style="padding: 0.5rem 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">' +
                    '<button type="button" class="rm" style="background: none; border: none; color: #b91c1c; font-weight: 700; cursor: pointer;">×</button>';
                defectBox.appendChild(div);
            });
        })();
    </script>
@endsection
