@extends('layouts.app')

@section('page_title', $statement->title)

@section('content')
    @include('pro.engineer._field-styles')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem;">
        <div>
            <a href="/pro/architect/method-statements" style="color: var(--text-muted); text-decoration: none; font-weight: 600;">← Method statements</a>
            <h1 style="margin: 0.4rem 0 0.25rem; color: var(--primary-navy); font-size: 1.5rem;">{{ $statement->title }}</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">
                {{ $statement->typeLabel() }}
                @if($statement->statement_number) · {{ $statement->statement_number }} @endif
                · {{ $statement->isStamped() ? 'Issued '.$statement->issue_code : 'Draft' }}
                @if($statement->project) · {{ $statement->project->name }} @endif
            </p>
        </div>
        <div class="eng-desktop-actions" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            @if($statement->isEditable())
                <a href="/pro/architect/method-statements/{{ $statement->id }}/edit" style="background: white; border: 1px solid var(--border-light); color: var(--primary-navy); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">Edit</a>
                <form method="POST" action="/pro/architect/method-statements/{{ $statement->id }}/stamp" onsubmit="return confirm('Stamp & issue this method statement? It will lock and cannot be edited.');">
                    @csrf
                    <button type="submit" style="background: #3f6212; color: white; border: none; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Stamp & issue</button>
                </form>
            @else
                <a href="/pro/architect/method-statements/{{ $statement->id }}/pdf" style="background: #3f6212; color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">Download PDF</a>
            @endif
        </div>
    </div>

    <div class="eng-sticky-actions eng-mobile-actions">
        @if($statement->isEditable())
            <a href="/pro/architect/method-statements/{{ $statement->id }}/edit" style="background: white; border: 1px solid var(--border-light); color: var(--primary-navy); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">Edit</a>
            <form method="POST" action="/pro/architect/method-statements/{{ $statement->id }}/stamp" onsubmit="return confirm('Stamp & issue this method statement? It will lock and cannot be edited.');" style="flex: 1 1 auto; display: flex;">
                @csrf
                <button type="submit" style="flex: 1; background: #3f6212; color: white; border: none; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Stamp & issue</button>
            </form>
        @else
            <a href="/pro/architect/method-statements/{{ $statement->id }}/pdf" style="flex: 1; background: #3f6212; color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">Download PDF</a>
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
                <div><div style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700;">ISSUED</div>{{ $statement->issued_on->format('d M Y') }}</div>
                <div><div style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700;">COMMENCEMENT</div>{{ $statement->commencement_note ?: '—' }}</div>
                @if($statement->paApplication)
                    <div><div style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700;">PA</div>{{ $statement->paApplication->displayLabel() }}</div>
                @endif
            </div>
            <div style="margin-top: 1rem; padding-top: 0.85rem; border-top: 1px solid #e2e8f0; font-size: 0.9rem; color: var(--primary-navy); display: grid; gap: 0.35rem;">
                @if($statement->client_name)<div><strong>{{ $statement->client_name }}</strong></div>@endif
                @if($statement->client_address)<div style="color: var(--text-muted); font-size: 0.85rem;">{{ $statement->client_address }}</div>@endif
                @if($statement->project_description)<div style="margin-top: 0.35rem; white-space: pre-wrap;">{{ $statement->project_description }}</div>@endif
                @if($statement->site_address)<div>Site: {{ $statement->site_address }}</div>@endif
            </div>
        </section>

        @foreach($payload['sections'] as $section)
            <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm);">
                @if($section['heading'])
                    <h2 style="margin: 0 0 0.55rem; font-size: 1.05rem; color: var(--primary-navy);">{{ $section['heading'] }}</h2>
                @endif
                <div style="white-space: pre-wrap; font-size: 0.92rem; line-height: 1.55; color: var(--primary-navy);">{{ $section['body'] ?: '—' }}</div>
            </section>
        @endforeach

        @if($payload['appendix_ref'])
            <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm);">
                <h2 style="margin: 0 0 0.55rem; font-size: 1.05rem; color: var(--primary-navy);">Annexes</h2>
                <div style="font-size: 0.92rem; color: var(--primary-navy);">{{ $payload['appendix_ref'] }}</div>
            </section>
        @endif

        @if($statement->photos->isNotEmpty())
            <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm);">
                <h2 style="margin: 0 0 0.75rem; font-size: 1.05rem; color: var(--primary-navy);">Photos / annex images</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 0.65rem;">
                    @foreach($statement->photos as $photo)
                        <a href="/pro/architect/method-statements/{{ $statement->id }}/photos/{{ $photo->id }}" target="_blank" style="display: block; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.5rem; text-decoration: none; font-size: 0.78rem; color: #3f6212; font-weight: 600;">
                            Photo {{ $loop->iteration }}@if($photo->caption) — {{ $photo->caption }}@endif
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
