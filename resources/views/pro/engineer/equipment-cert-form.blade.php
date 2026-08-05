@extends('layouts.app')

@section('page_title', $certificate ? 'Edit certificate' : 'Equipment certificate')

@section('content')
    @include('pro.shared.field-styles')

    <div style="margin-bottom: 1rem;">
        <a href="/pro/engineer/equipment/{{ $equipment->id }}" style="color: var(--primary-cerulean); font-weight: 600; text-decoration: none; font-size: 0.9rem;">← {{ $equipment->name }}</a>
    </div>

    <h1 style="margin: 0 0 0.25rem; color: var(--primary-navy); font-size: 1.45rem;">{{ $certificate ? 'Edit equipment certificate' : 'Issue equipment certificate' }}</h1>
    <p style="margin: 0 0 1.25rem; color: var(--text-muted); font-size: 0.9rem;">
        {{ $equipment->asset_code }} · {{ $equipment->client->name ?? '' }}
        @if($equipment->serial_number) · S/N {{ $equipment->serial_number }} @endif
    </p>

    @if($errors->any())
        <div style="background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem; border: 1px solid #fecaca;">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ $certificate ? '/pro/engineer/certificates/'.$certificate->id : '/pro/engineer/equipment/'.$equipment->id.'/certificates' }}" style="max-width: 860px;">
        @csrf
        @if($certificate) @method('PUT') @endif

        <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); padding: 1.2rem; margin-bottom: 1rem;">
            <div style="margin-bottom: 0.85rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.35rem; color: var(--primary-navy); font-size: 0.9rem;">Title</label>
                <input type="text" name="title" required value="{{ old('title', $certificate->title ?? $defaultTitle) }}" style="width: 100%; padding: 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font: inherit;">
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.75rem;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.35rem; color: var(--primary-navy); font-size: 0.8rem;">Inspected</label>
                    <input type="date" name="inspected_on" max="{{ date('Y-m-d') }}" value="{{ old('inspected_on', optional($certificate->inspected_on ?? null)->format('Y-m-d') ?: date('Y-m-d')) }}" style="width: 100%; padding: 0.6rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.35rem; color: var(--primary-navy); font-size: 0.8rem;">Issued</label>
                    <input type="date" name="issued_on" required max="{{ date('Y-m-d') }}" value="{{ old('issued_on', optional($certificate->issued_on ?? null)->format('Y-m-d') ?: date('Y-m-d')) }}" style="width: 100%; padding: 0.6rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.35rem; color: var(--primary-navy); font-size: 0.8rem;">Expires</label>
                    <input type="date" name="expires_on" value="{{ old('expires_on', optional($certificate->expires_on ?? null)->format('Y-m-d') ?: now()->addYear()->format('Y-m-d')) }}" style="width: 100%; padding: 0.6rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.35rem; color: var(--primary-navy); font-size: 0.8rem;">Next inspection</label>
                    <input type="date" name="next_inspection_on" value="{{ old('next_inspection_on', optional($certificate->next_inspection_on ?? null)->format('Y-m-d') ?: now()->addYear()->format('Y-m-d')) }}" style="width: 100%; padding: 0.6rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>
            <div style="margin-top: 0.85rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.35rem; color: var(--primary-navy); font-size: 0.8rem;">Outcome</label>
                <input type="text" name="outcome" value="{{ old('outcome', $certificate->outcome ?? '') }}" placeholder="e.g. Fit for continued use" style="width: 100%; padding: 0.6rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div style="margin-top: 0.85rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.35rem; color: var(--primary-navy); font-size: 0.8rem;">Certificate number (optional)</label>
                <input type="text" name="certificate_number" value="{{ old('certificate_number', $certificate->certificate_number ?? '') }}" style="width: 100%; padding: 0.6rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
        </section>

        <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); padding: 1.2rem; margin-bottom: 1rem;">
            <h2 style="margin: 0 0 0.75rem; font-size: 1rem; color: var(--primary-navy);">Holder / site</h2>
            <div style="display: grid; gap: 0.75rem;">
                <input type="text" name="holder_name" value="{{ old('holder_name', $certificate->holder_name ?? $equipment->client->name) }}" placeholder="Holder name" style="width: 100%; padding: 0.6rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                <textarea name="holder_address" rows="2" placeholder="Holder address" style="width: 100%; padding: 0.6rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); resize: vertical;">{{ old('holder_address', $certificate->holder_address ?? $equipment->client->billing_address) }}</textarea>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                    <input type="text" name="contact_person" value="{{ old('contact_person', $certificate->contact_person ?? ($equipment->client->profile_data['contact_person'] ?? '')) }}" placeholder="Contact person" style="width: 100%; padding: 0.6rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    <input type="text" name="contact_phone" value="{{ old('contact_phone', $certificate->contact_phone ?? $equipment->client->phone) }}" placeholder="Contact phone" style="width: 100%; padding: 0.6rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <input type="text" name="site_address" value="{{ old('site_address', $certificate->site_address ?? $equipment->site_location) }}" placeholder="Inspection site / current location" style="width: 100%; padding: 0.6rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
        </section>

        <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); padding: 1.2rem; margin-bottom: 1rem;">
            <h2 style="margin: 0 0 0.75rem; font-size: 1rem; color: var(--primary-navy);">Equipment attributes</h2>
            <input type="hidden" name="payload[subject_heading]" value="{{ old('payload.subject_heading', $payload['subject_heading'] ?? 'Subject / equipment') }}">
            <div id="attrRows" style="display: grid; gap: 0.55rem;">
                @foreach(old('payload.attributes', $payload['attributes'] ?? []) as $i => $row)
                    <div style="display: grid; grid-template-columns: 0.9fr 1.1fr; gap: 0.5rem;">
                        <input type="text" name="payload[attributes][{{ $i }}][label]" value="{{ $row['label'] ?? '' }}" placeholder="Label" style="padding: 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        <input type="text" name="payload[attributes][{{ $i }}][value]" value="{{ $row['value'] ?? '' }}" placeholder="Value" style="padding: 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    </div>
                @endforeach
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-top: 0.85rem;">
                <input type="text" name="payload[highlight_label]" value="{{ old('payload.highlight_label', $payload['highlight_label'] ?? 'SWL') }}" placeholder="Highlight label" style="padding: 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                <input type="text" name="payload[highlight_value]" value="{{ old('payload.highlight_value', $payload['highlight_value'] ?? ($equipment->capacity_rating ?? '')) }}" placeholder="Highlight value" style="padding: 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
        </section>

        <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); padding: 1.2rem; margin-bottom: 1rem;">
            <h2 style="margin: 0 0 0.75rem; font-size: 1rem; color: var(--primary-navy);">Checklist &amp; findings</h2>
            <input type="hidden" name="payload[checklist_heading]" value="{{ old('payload.checklist_heading', $payload['checklist_heading'] ?? 'Inspection checklist') }}">
            @foreach(old('payload.checklist', $payload['checklist'] ?? [['id' => '', 'item' => '', 'outcome' => '', 'comments' => '']]) as $i => $row)
                <div style="border: 1px solid #e2e8f0; border-radius: var(--radius-md); padding: 0.75rem; margin-bottom: 0.55rem;">
                    <input type="hidden" name="payload[checklist][{{ $i }}][id]" value="{{ $row['id'] ?? '' }}">
                    <input type="text" name="payload[checklist][{{ $i }}][item]" value="{{ $row['item'] ?? '' }}" placeholder="Checklist item" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); margin-bottom: 0.4rem;">
                    <div style="display: grid; grid-template-columns: 0.8fr 1.2fr; gap: 0.45rem;">
                        <input type="text" name="payload[checklist][{{ $i }}][outcome]" value="{{ $row['outcome'] ?? '' }}" placeholder="Outcome" style="padding: 0.5rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        <input type="text" name="payload[checklist][{{ $i }}][comments]" value="{{ $row['comments'] ?? '' }}" placeholder="Comments" style="padding: 0.5rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    </div>
                </div>
            @endforeach
            @if(count($commonChecklistItems))
                <p style="margin: 0.5rem 0 0; font-size: 0.78rem; color: var(--text-muted);">Hints: {{ implode(' · ', $commonChecklistItems) }}</p>
            @endif

            @foreach(old('payload.sections', $payload['sections'] ?? [['heading' => 'Findings / remarks', 'body' => ''], ['heading' => 'Conditions of validity', 'body' => '']]) as $i => $section)
                <div style="margin-top: 0.85rem;">
                    <input type="text" name="payload[sections][{{ $i }}][heading]" value="{{ $section['heading'] ?? '' }}" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); margin-bottom: 0.4rem; font-weight: 600;">
                    <textarea name="payload[sections][{{ $i }}][body]" rows="3" style="width: 100%; padding: 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); resize: vertical;">{{ $section['body'] ?? '' }}</textarea>
                </div>
            @endforeach
            <div style="margin-top: 0.85rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.35rem; color: var(--primary-navy); font-size: 0.8rem;">Legal footer</label>
                <textarea name="payload[legal_footer]" rows="2" style="width: 100%; padding: 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); resize: vertical;">{{ old('payload.legal_footer', $payload['legal_footer'] ?? '') }}</textarea>
            </div>
        </section>

        <button type="submit" style="background: var(--primary-cerulean); color: white; border: none; padding: 0.7rem 1.25rem; border-radius: var(--radius-md); font-weight: 600; cursor: pointer;">
            {{ $certificate ? 'Save draft' : 'Save draft certificate' }}
        </button>
    </form>
@endsection
