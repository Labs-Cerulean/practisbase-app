@extends('layouts.app')

@section('page_title', $certificate ? 'Edit certificate' : 'New certificate')

@section('content')
    @php
        $isEdit = $certificate !== null;
        $action = $isEdit ? '/pro/engineer/certificates/'.$certificate->id : '/pro/engineer/certificates';
    @endphp
    <div style="margin-bottom: 1.25rem;">
        <a href="{{ $isEdit ? '/pro/engineer/certificates/'.$certificate->id : '/pro/engineer/certificates' }}" style="color: var(--text-muted); text-decoration: none; font-weight: 600;">← Back</a>
        <h1 style="margin: 0.5rem 0 0; color: var(--primary-navy); font-size: 1.5rem;">{{ $isEdit ? 'Edit certificate' : 'Certificate builder' }}</h1>
        <p style="margin: 0.35rem 0 0; color: var(--text-muted); font-size: 0.88rem;">Same form for any inspection certificate — add attributes, checklist rows, and narrative sections as needed.</p>
    </div>

    @unless($isEdit)
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1rem;">
            @foreach($starters as $key => $starter)
                <a href="/pro/engineer/certificates/create?starter={{ $key }}{{ ($prefill['project_id'] ?? null) ? '&project_id='.$prefill['project_id'] : '' }}{{ ($prefill['pa_id'] ?? null) ? '&pa_id='.$prefill['pa_id'] : '' }}"
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

    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" style="display: grid; gap: 1.1rem; max-width: 920px;">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm); display: grid; gap: 0.75rem;">
            <h2 style="margin: 0; font-size: 1.05rem; color: var(--primary-navy);">Header</h2>
            <div>
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Certificate title *</label>
                <input type="text" name="title" value="{{ old('title', $certificate->title ?? $defaultTitle) }}" required style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 0.75rem;">
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Your certificate no.</label>
                    <input type="text" name="certificate_number" value="{{ old('certificate_number', $certificate->certificate_number ?? '') }}" placeholder="e.g. PLC/PRA/…" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Outcome / result</label>
                    <input type="text" name="outcome" value="{{ old('outcome', $certificate->outcome ?? '') }}" placeholder="e.g. Safe for use / Pass / Good" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.75rem;">
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Inspected on</label>
                    <input type="date" name="inspected_on" max="{{ date('Y-m-d') }}" value="{{ old('inspected_on', optional($certificate->inspected_on ?? null)->format('Y-m-d')) }}" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Issue date *</label>
                    <input type="date" name="issued_on" max="{{ date('Y-m-d') }}" value="{{ old('issued_on', optional($certificate->issued_on ?? null)->format('Y-m-d') ?: date('Y-m-d')) }}" required style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Expires on</label>
                    <input type="date" name="expires_on" value="{{ old('expires_on', optional($certificate->expires_on ?? null)->format('Y-m-d')) }}" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Next inspection</label>
                    <input type="date" name="next_inspection_on" value="{{ old('next_inspection_on', optional($certificate->next_inspection_on ?? null)->format('Y-m-d')) }}" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Link project</label>
                    <select name="engineer_project_id" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        <option value="">None</option>
                        @foreach($projects as $p)
                            <option value="{{ $p->id }}" @selected(old('engineer_project_id', $prefill['project_id'] ?? '') == $p->id)>{{ $p->name }}@if($p->client) ({{ $p->client->name }})@endif</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Link PA (optional)</label>
                    <select name="engineer_pa_application_id" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        <option value="">None</option>
                        @foreach($pas as $pa)
                            <option value="{{ $pa->id }}" @selected(old('engineer_pa_application_id', $prefill['pa_id'] ?? '') == $pa->id)>{{ $pa->displayLabel() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </section>

        <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm); display: grid; gap: 0.75rem;">
            <h2 style="margin: 0; font-size: 1.05rem; color: var(--primary-navy);">Client / holder</h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Responsible person / entity</label>
                    <input type="text" name="holder_name" value="{{ old('holder_name', $certificate->holder_name ?? '') }}" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Contact person</label>
                    <input type="text" name="contact_person" value="{{ old('contact_person', $certificate->contact_person ?? '') }}" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Contact phone</label>
                    <input type="text" name="contact_phone" value="{{ old('contact_phone', $certificate->contact_phone ?? '') }}" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Site / work location</label>
                    <input type="text" name="site_address" value="{{ old('site_address', $certificate->site_address ?? '') }}" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>
            <div>
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Holder address</label>
                <textarea name="holder_address" rows="2" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ old('holder_address', $certificate->holder_address ?? '') }}</textarea>
            </div>
        </section>

        @php
            $oldPayload = old('payload');
            $p = is_array($oldPayload) ? \App\Support\EngineerCertificateBlueprint::normalize($oldPayload) : $payload;
        @endphp

        <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm); display: grid; gap: 0.75rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                <h2 style="margin: 0; font-size: 1.05rem; color: var(--primary-navy);">Subject attributes</h2>
                <button type="button" id="addAttr" style="background: white; border: 1px solid var(--border-light); padding: 0.4rem 0.7rem; border-radius: var(--radius-md); font-weight: 600; cursor: pointer; font-size: 0.82rem;">+ Row</button>
            </div>
            <input type="text" name="payload[subject_heading]" value="{{ $p['subject_heading'] }}" placeholder="Section heading (e.g. Equipment details)" style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
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
                    <input type="text" name="payload[highlight_label]" value="{{ $p['highlight_label'] }}" placeholder="e.g. Maximum certified SWL" style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Highlight value</label>
                    <input type="text" name="payload[highlight_value]" value="{{ $p['highlight_value'] }}" placeholder="e.g. 2000 KGS" style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>
        </section>

        <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm); display: grid; gap: 0.75rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                <h2 style="margin: 0; font-size: 1.05rem; color: var(--primary-navy);">Checklist</h2>
                <button type="button" id="addCheck" style="background: white; border: 1px solid var(--border-light); padding: 0.4rem 0.7rem; border-radius: var(--radius-md); font-weight: 600; cursor: pointer; font-size: 0.82rem;">+ Row</button>
            </div>
            <input type="text" name="payload[checklist_heading]" value="{{ $p['checklist_heading'] }}" placeholder="Checklist heading (leave blank to hide)" style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            <div id="checkRows" style="display: grid; gap: 0.5rem;">
                @foreach($p['checklist'] as $i => $row)
                    <div class="check-row" style="display: grid; grid-template-columns: 1.4fr 0.7fr 1fr auto; gap: 0.45rem;">
                        <input type="text" name="payload[checklist][{{ $i }}][item]" value="{{ $row['item'] }}" placeholder="Item" style="padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        <input type="text" name="payload[checklist][{{ $i }}][outcome]" value="{{ $row['outcome'] }}" placeholder="Outcome" style="padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        <input type="text" name="payload[checklist][{{ $i }}][comments]" value="{{ $row['comments'] }}" placeholder="Comments" style="padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        <button type="button" class="rm" style="background: none; border: none; color: #b91c1c; font-weight: 700; cursor: pointer;">×</button>
                    </div>
                @endforeach
            </div>
        </section>

        <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm); display: grid; gap: 0.75rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                <h2 style="margin: 0; font-size: 1.05rem; color: var(--primary-navy);">Narrative sections</h2>
                <button type="button" id="addSection" style="background: white; border: 1px solid var(--border-light); padding: 0.4rem 0.7rem; border-radius: var(--radius-md); font-weight: 600; cursor: pointer; font-size: 0.82rem;">+ Section</button>
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

        <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm); display: grid; gap: 0.75rem;">
            <h2 style="margin: 0; font-size: 1.05rem; color: var(--primary-navy);">Photos</h2>
            <input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple capture="environment">
            <div style="font-size: 0.78rem; color: var(--text-muted);">Optional. Up to 12 images, 5 MB each. Existing photos are kept when you upload more.</div>
        </section>

        <button type="submit" style="background: var(--primary-cerulean); color: white; border: none; padding: 0.85rem 1.2rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer; width: fit-content;">{{ $isEdit ? 'Save draft' : 'Save draft certificate' }}</button>
    </form>

    <script>
        (function () {
            function reindex(container, rowSel, fields) {
                var rows = container.querySelectorAll(rowSel);
                rows.forEach(function (row, i) {
                    fields.forEach(function (name) {
                        var el = row.querySelector('[data-field="' + name + '"]');
                        if (el) el.name = el.getAttribute('data-name-prefix').replace('__i__', i);
                    });
                });
            }

            function bindRemove(container) {
                container.addEventListener('click', function (e) {
                    if (!e.target.classList.contains('rm')) return;
                    var row = e.target.closest('.attr-row, .check-row, .section-row');
                    if (!row) return;
                    var parent = row.parentNode;
                    if (parent.children.length <= 1) {
                        row.querySelectorAll('input, textarea').forEach(function (el) { el.value = ''; });
                        return;
                    }
                    row.remove();
                });
            }

            var attrBox = document.getElementById('attrRows');
            var checkBox = document.getElementById('checkRows');
            var sectionBox = document.getElementById('sectionRows');
            bindRemove(attrBox);
            bindRemove(checkBox);
            bindRemove(sectionBox);

            document.getElementById('addAttr').addEventListener('click', function () {
                var i = attrBox.children.length;
                var div = document.createElement('div');
                div.className = 'attr-row';
                div.style.cssText = 'display: grid; grid-template-columns: 1fr 1.4fr auto; gap: 0.45rem;';
                div.innerHTML = '<input type="text" name="payload[attributes][' + i + '][label]" placeholder="Label" style="padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">' +
                    '<input type="text" name="payload[attributes][' + i + '][value]" placeholder="Value" style="padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">' +
                    '<button type="button" class="rm" style="background: none; border: none; color: #b91c1c; font-weight: 700; cursor: pointer;">×</button>';
                attrBox.appendChild(div);
            });

            document.getElementById('addCheck').addEventListener('click', function () {
                var i = checkBox.children.length;
                var div = document.createElement('div');
                div.className = 'check-row';
                div.style.cssText = 'display: grid; grid-template-columns: 1.4fr 0.7fr 1fr auto; gap: 0.45rem;';
                div.innerHTML = '<input type="text" name="payload[checklist][' + i + '][item]" placeholder="Item" style="padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">' +
                    '<input type="text" name="payload[checklist][' + i + '][outcome]" placeholder="Outcome" style="padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">' +
                    '<input type="text" name="payload[checklist][' + i + '][comments]" placeholder="Comments" style="padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">' +
                    '<button type="button" class="rm" style="background: none; border: none; color: #b91c1c; font-weight: 700; cursor: pointer;">×</button>';
                checkBox.appendChild(div);
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
