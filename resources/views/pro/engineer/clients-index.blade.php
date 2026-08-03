@extends('layouts.app')

@section('page_title', 'Engineer clients')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem;">
        <div>
            <h1 style="margin: 0 0 0.25rem; color: var(--primary-navy); font-size: 1.5rem;">Clients</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">Client → Project → PA (optional). Start early; add the PA number when it is issued.</p>
        </div>
        <a href="/pro/engineer/clients/create" style="background: var(--primary-cerulean); color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">+ Client</a>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">{{ session('success') }}</div>
    @endif

    @if($orphanProjects > 0)
        <div style="background: #fffbeb; border: 1px solid #fde68a; color: #92400e; padding: 0.9rem 1.1rem; border-radius: var(--radius-md); margin-bottom: 1rem; font-size: 0.9rem;">
            {{ $orphanProjects }} older project{{ $orphanProjects === 1 ? '' : 's' }} are not linked to a client yet. Open Projects, edit each one, and assign a client.
        </div>
    @endif

    <form method="GET" action="/pro/engineer/clients" style="margin-bottom: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <input type="search" name="q" value="{{ $q }}" placeholder="Search clients…"
               style="flex: 1; min-width: 200px; padding: 0.65rem 0.85rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
        <button type="submit" style="background: var(--primary-navy); color: white; border: none; padding: 0.65rem 1rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Search</button>
    </form>

    @if($clients->isEmpty())
        <div style="padding: 3rem; border: 2px dashed var(--border-light); border-radius: var(--radius-md); text-align: center; background: white;">
            <p style="color: var(--text-muted); margin: 0 0 1rem;">No engineering clients yet.</p>
            <a href="/pro/engineer/clients/create" style="color: var(--primary-cerulean); font-weight: 700; text-decoration: none;">Add your first client</a>
        </div>
    @else
        <div style="display: grid; gap: 0.75rem;">
            @foreach($clients as $client)
                <a href="/pro/engineer/clients/{{ $client->id }}" style="display: block; background: white; border: 1px solid var(--border-light); border-left: 4px solid var(--primary-cerulean); border-radius: var(--radius-md); padding: 1rem 1.25rem; text-decoration: none; box-shadow: var(--shadow-sm);">
                    <div style="display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                        <div>
                            <div style="font-weight: 700; color: var(--primary-navy);">{{ $client->name }}</div>
                            <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.2rem;">
                                {{ $client->locality ?: 'No locality' }}
                                @if($client->phone) · {{ $client->phone }} @endif
                                @if($client->email) · {{ $client->email }} @endif
                            </div>
                        </div>
                        <div style="font-size: 0.85rem; font-weight: 700; color: var(--primary-cerulean);">{{ $client->projects_count }} project{{ $client->projects_count === 1 ? '' : 's' }}</div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
@endsection
