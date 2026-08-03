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
            Create a client first, then add a project.
            <a href="/pro/architect/clients/create" style="font-weight: 700; color: #92400e;">Add client</a>
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
                    <select name="architect_client_id" required style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        @foreach($clients as $c)
                            <option value="{{ $c->id }}" @selected(old('architect_client_id', $preselectClientId) == $c->id)>{{ $c->name }}</option>
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
                        <input type="text" name="reference_code" value="{{ old('reference_code', $project->reference_code ?? '') }}" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Phase</label>
                        <select name="phase" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                            @foreach($phases as $key => $label)
                                <option value="{{ $key }}" @selected(old('phase', $project->phase ?? 'concept') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Status</label>
                        <select name="status" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                            @foreach($statuses as $key => $label)
                                <option value="{{ $key }}" @selected(old('status', $project->status ?? 'active') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Commencement</label>
                        <input type="date" name="commencement_date" max="{{ date('Y-m-d') }}" value="{{ old('commencement_date', optional($project->commencement_date ?? null)->format('Y-m-d')) }}" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    </div>
                </div>
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
                            <input type="text" name="site_locality" value="{{ old('site_locality', $project->site_locality ?? '') }}" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        </div>
                    </div>
                    <div style="margin-top: 0.85rem;">
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Full site address (optional override)</label>
                        <textarea name="site_address" rows="2" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ old('site_address', $project->site_address ?? '') }}</textarea>
                    </div>
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Notes</label>
                    <textarea name="notes" rows="3" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ old('notes', $project->notes ?? '') }}</textarea>
                </div>
                <button type="submit" style="background: #3f6212; color: white; border: none; padding: 0.75rem 1.1rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer; width: fit-content;">Save project</button>
            </div>
        </form>
    @endif
@endsection
