@extends('layouts.app')

@section('page_title', $certificate->title)

@section('content')
    @include('pro.engineer._field-styles')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem;">
        <div>
            <a href="/pro/engineer/certificates" style="color: var(--text-muted); text-decoration: none; font-weight: 600;">← Certificates</a>
            <h1 style="margin: 0.4rem 0 0.25rem; color: var(--primary-navy); font-size: 1.5rem;">{{ $certificate->title }}</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">
                @if($certificate->certificate_number) {{ $certificate->certificate_number }} · @endif
                {{ $certificate->isStamped() ? 'Issued '.$certificate->issue_code : 'Draft' }}
                @if($certificate->project) · {{ $certificate->project->name }} @endif
            </p>
        </div>
        <div class="eng-desktop-actions" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            @if($certificate->isEditable())
                <a href="/pro/engineer/certificates/{{ $certificate->id }}/edit" style="background: white; border: 1px solid var(--border-light); color: var(--primary-navy); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">Edit</a>
                <form method="POST" action="/pro/engineer/certificates/{{ $certificate->id }}/stamp" onsubmit="return confirm('Stamp & issue this certificate? It will lock and cannot be edited.');">
                    @csrf
                    <button type="submit" style="background: var(--primary-cerulean); color: white; border: none; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Stamp & issue</button>
                </form>
            @else
                <a href="/pro/engineer/certificates/{{ $certificate->id }}/pdf" style="background: var(--primary-cerulean); color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">Download PDF</a>
            @endif
        </div>
    </div>

    <div class="eng-sticky-actions eng-mobile-actions">
        @if($certificate->isEditable())
            <a href="/pro/engineer/certificates/{{ $certificate->id }}/edit" style="background: white; border: 1px solid var(--border-light); color: var(--primary-navy); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">Edit</a>
            <form method="POST" action="/pro/engineer/certificates/{{ $certificate->id }}/stamp" onsubmit="return confirm('Stamp & issue this certificate? It will lock and cannot be edited.');" style="flex: 1 1 auto; display: flex;">
                @csrf
                <button type="submit" style="flex: 1; background: var(--primary-cerulean); color: white; border: none; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Stamp & issue</button>
            </form>
        @else
            <a href="/pro/engineer/certificates/{{ $certificate->id }}/pdf" style="flex: 1; background: var(--primary-cerulean); color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">Download PDF</a>
        @endif
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div style="background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <div style="display: grid; gap: 1rem; max-width: 860px;">
        <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm);">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.75rem; font-size: 0.88rem;">
                <div><div style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700;">INSPECTED</div>{{ optional($certificate->inspected_on)->format('d M Y') ?: '—' }}</div>
                <div><div style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700;">ISSUED</div>{{ $certificate->issued_on->format('d M Y') }}</div>
                <div><div style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700;">EXPIRES</div>{{ optional($certificate->expires_on)->format('d M Y') ?: '—' }}</div>
                <div><div style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700;">NEXT INSPECTION</div>{{ optional($certificate->next_inspection_on)->format('d M Y') ?: '—' }}</div>
                <div><div style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700;">OUTCOME</div>{{ $certificate->outcome ?: '—' }}</div>
            </div>
            @if($certificate->holder_name || $certificate->site_address)
                <div style="margin-top: 1rem; padding-top: 0.85rem; border-top: 1px solid #e2e8f0; font-size: 0.9rem; color: var(--primary-navy);">
                    @if($certificate->holder_name)<div><strong>{{ $certificate->holder_name }}</strong></div>@endif
                    @if($certificate->contact_person || $certificate->contact_phone)
                        <div style="color: var(--text-muted); font-size: 0.85rem;">{{ $certificate->contact_person }} {{ $certificate->contact_phone }}</div>
                    @endif
                    @if($certificate->site_address)<div style="margin-top: 0.35rem;">Site: {{ $certificate->site_address }}</div>@endif
                </div>
            @endif
        </section>

        @if(count($payload['attributes']))
            <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm);">
                <h2 style="margin: 0 0 0.75rem; font-size: 1.05rem; color: var(--primary-navy);">{{ $payload['subject_heading'] ?: 'Subject' }}</h2>
                <dl style="margin: 0; display: grid; grid-template-columns: minmax(120px, 0.8fr) 1.2fr; gap: 0.4rem 1rem; font-size: 0.9rem;">
                    @foreach($payload['attributes'] as $row)
                        <dt style="color: var(--text-muted);">{{ $row['label'] }}</dt>
                        <dd style="margin: 0; color: var(--primary-navy);">{{ $row['value'] ?: '—' }}</dd>
                    @endforeach
                </dl>
                @if($payload['highlight_label'] || $payload['highlight_value'])
                    <div style="margin-top: 1rem; background: #0f172a; color: white; padding: 0.85rem 1rem; border-radius: var(--radius-md);">
                        <div style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.04em; opacity: 0.8;">{{ $payload['highlight_label'] }}</div>
                        <div style="font-size: 1.25rem; font-weight: 700; margin-top: 0.2rem;">{{ $payload['highlight_value'] }}</div>
                    </div>
                @endif
            </section>
        @endif

        @if(count($payload['checklist']))
            <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm);">
                <h2 style="margin: 0 0 0.75rem; font-size: 1.05rem; color: var(--primary-navy);">{{ $payload['checklist_heading'] ?: 'Checklist' }}</h2>
                <div style="display: grid; gap: 0.45rem;">
                    @foreach($payload['checklist'] as $row)
                        <div style="display: grid; grid-template-columns: 1.4fr 0.6fr 1fr; gap: 0.5rem; font-size: 0.88rem; padding: 0.45rem 0; border-bottom: 1px dashed var(--border-light);">
                            <div>{{ $row['item'] }}</div>
                            <div style="font-weight: 700;">{{ $row['outcome'] }}</div>
                            <div style="color: var(--text-muted);">{{ $row['comments'] }}</div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @foreach($payload['sections'] as $section)
            <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm);">
                @if($section['heading'])
                    <h2 style="margin: 0 0 0.55rem; font-size: 1.05rem; color: var(--primary-navy);">{{ $section['heading'] }}</h2>
                @endif
                <div style="white-space: pre-wrap; font-size: 0.92rem; line-height: 1.55; color: var(--primary-navy);">{{ $section['body'] }}</div>
            </section>
        @endforeach

        @if($certificate->photos->isNotEmpty())
            <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm);">
                <h2 style="margin: 0 0 0.75rem; font-size: 1.05rem; color: var(--primary-navy);">Photos</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 0.65rem;">
                    @foreach($certificate->photos as $photo)
                        <a href="/pro/engineer/certificates/{{ $certificate->id }}/photos/{{ $photo->id }}" target="_blank" style="display: block; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.5rem; text-decoration: none; font-size: 0.78rem; color: var(--primary-cerulean); font-weight: 600;">
                            Photo {{ $loop->iteration }}@if($photo->caption) — {{ $photo->caption }}@endif
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
