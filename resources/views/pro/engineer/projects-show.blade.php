@extends('layouts.app')

@section('page_title', $project->name)

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem;">
        <div>
            <a href="{{ $project->client ? '/pro/engineer/clients/'.$project->client->id : '/pro/engineer/projects' }}" style="color: var(--text-muted); text-decoration: none; font-weight: 600;">← {{ $project->client->name ?? 'Projects' }}</a>
            <h1 style="margin: 0.4rem 0 0.25rem; color: var(--primary-navy); font-size: 1.5rem;">{{ $project->name }}</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">
                {{ $disciplines[$project->discipline] ?? $project->discipline }}
                · {{ $phases[$project->phase] ?? $project->phase }}
                · {{ $statuses[$project->status] ?? $project->status }}
                @if($project->siteAddressLine()) · {{ $project->siteAddressLine() }} @endif
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="/pro/engineer/projects/{{ $project->id }}/edit" style="background: white; border: 1px solid var(--border-light); color: var(--primary-navy); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">Edit</a>
            <a href="/pro/engineer/projects/{{ $project->id }}/pa/create" style="background: var(--primary-cerulean); color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">+ PA</a>
            <a href="/pro/certificates/create" style="background: white; border: 1px solid var(--border-light); color: var(--primary-navy); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">+ Certificate</a>
        </div>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">{{ session('success') }}</div>
    @endif

    @if($project->isArchived())
        <div style="background: #fffbeb; color: #92400e; padding: 0.85rem 1rem; border-radius: var(--radius-md); margin-bottom: 1rem; font-size: 0.9rem;">
            This project is archived. Change status under Edit to bring it back to the main register.
        </div>
    @endif

    <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 1.25rem;" class="eng-split">
        <div style="display: grid; gap: 1.25rem;">
            <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm);">
                <div style="display: flex; justify-content: space-between; align-items: baseline; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 0.75rem;">
                    <h2 style="margin: 0; font-size: 1.05rem; color: var(--primary-navy);">PA applications</h2>
                    <a href="/pro/engineer/projects/{{ $project->id }}/pa/create" style="font-size: 0.82rem; font-weight: 600; color: var(--primary-cerulean); text-decoration: none;">+ Add PA</a>
                </div>
                @if($project->paApplications->isEmpty())
                    <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem; line-height: 1.45;">
                        No PA yet — that is fine. Start the project now and add the PA number when Planning Authority issues it.
                    </p>
                @else
                    <div style="display: grid; gap: 0.5rem;">
                        @foreach($project->paApplications as $pa)
                            <a href="/pro/engineer/pa/{{ $pa->id }}" style="display: block; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.75rem 0.9rem; text-decoration: none;">
                                <div style="font-weight: 700; color: var(--primary-navy);">{{ $pa->displayLabel() }}</div>
                                <div style="font-size: 0.8rem; color: var(--text-muted);">{{ $statuses[$pa->status] ?? $pa->status }}</div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>

            <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm);">
                <h2 style="margin: 0 0 0.35rem; font-size: 1.05rem; color: var(--primary-navy);">Overview</h2>
                @if($project->notes)
                    <p style="margin: 0; color: var(--primary-navy); font-size: 0.95rem; white-space: pre-wrap; line-height: 1.5;">{{ $project->notes }}</p>
                @else
                    <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">No notes yet.</p>
                @endif
                <dl style="margin: 1rem 0 0; display: grid; grid-template-columns: auto 1fr; gap: 0.35rem 1rem; font-size: 0.88rem;">
                    <dt style="color: var(--text-muted);">Reference</dt>
                    <dd style="margin: 0; color: var(--primary-navy);">{{ $project->reference_code ?: '—' }}</dd>
                    <dt style="color: var(--text-muted);">Updated</dt>
                    <dd style="margin: 0; color: var(--primary-navy);">{{ optional($project->updated_at)->format('d M Y H:i') ?? '—' }}</dd>
                </dl>
            </section>

            <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm);">
                <h2 style="margin: 0 0 0.35rem; font-size: 1.05rem; color: var(--primary-navy);">Drawings & documents</h2>
                <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem; line-height: 1.45;">
                    Versioned uploads land here next (roadmap E2).
                </p>
            </section>

            <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm);">
                <h2 style="margin: 0 0 0.35rem; font-size: 1.05rem; color: var(--primary-navy);">Field certificates</h2>
                <p style="margin: 0 0 0.75rem; color: var(--text-muted); font-size: 0.9rem; line-height: 1.45;">
                    Project-linked certificates land here next (roadmap E3). The shared register still works today.
                </p>
                <a href="/pro/certificates" style="font-size: 0.85rem; font-weight: 600; color: var(--primary-cerulean); text-decoration: none;">Open certificate register →</a>
            </section>

            <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm);">
                <h2 style="margin: 0 0 0.35rem; font-size: 1.05rem; color: var(--primary-navy);">Specialised reports</h2>
                <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem; line-height: 1.45;">
                    Fire, noise, ventilation, and lighting reports attach here next (roadmap E4).
                </p>
            </section>
        </div>

        <aside style="display: grid; gap: 1.25rem; align-content: start;">
            <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm);">
                <h2 style="margin: 0 0 0.5rem; font-size: 1.05rem; color: var(--primary-navy);">Quick links</h2>
                <div style="display: grid; gap: 0.5rem;">
                    <a href="/pro/engineer/projects/{{ $project->id }}/pa/create" style="padding: 0.65rem 0.85rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.88rem;">+ PA (number optional)</a>
                    <a href="/pro/certificates/create" style="padding: 0.65rem 0.85rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.88rem;">+ New certificate</a>
                    <a href="/pro/engineer/projects/create{{ $project->client ? '?client_id='.$project->client->id : '' }}" style="padding: 0.65rem 0.85rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.88rem;">+ Another project</a>
                </div>
            </section>
        </aside>
    </div>

    <style>
        @media (max-width: 860px) {
            .eng-split { grid-template-columns: 1fr !important; }
        }
    </style>
@endsection
