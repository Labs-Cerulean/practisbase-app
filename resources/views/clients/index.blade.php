@extends('layouts.app')

@section('page_title', 'Client Directory')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.5rem; color: var(--primary-navy); margin-bottom: 0.25rem;">Clients</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem;">Manage your database.</p>
        </div>
        <a href="/clients/create" style="background: var(--primary-cerulean); color: white; padding: 0.6rem 1.25rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.9rem;">
            + Add Client
        </a>
    </div>

    @if($clients->isEmpty())
        <div style="padding: 3rem; border: 2px dashed var(--border-light); border-radius: var(--radius-md); text-align: center;">
            <p style="color: var(--text-muted); margin-bottom: 1rem;">You don't have any clients yet.</p>
            <a href="/clients/create" style="color: var(--primary-cerulean); font-weight: 600;">Add your first client &rarr;</a>
        </div>
    @else
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
            @foreach($clients as $client)
                <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 1.5rem; box-shadow: var(--shadow-sm); display: flex; flex-direction: column;">
                    
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                        <div>
                            <h3 style="margin: 0; color: var(--primary-navy); font-size: 1.1rem;">{{ $client->name }}</h3>
                            <span style="display: inline-block; margin-top: 0.25rem; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); background: #f1f5f9; padding: 0.2rem 0.5rem; border-radius: 4px;">
                                {{ ucfirst($client->type) }}
                            </span>
                        </div>
                    </div>

                    <div style="flex-grow: 1; font-size: 0.9rem; color: var(--text-main); margin-bottom: 1.5rem;">
                        @if($client->phone)
                            <div style="margin-bottom: 0.25rem;">📞 {{ $client->phone }}</div>
                        @endif
                        @if($client->email)
                            <div>✉️ {{ $client->email }}</div>
                        @endif
                    </div>

                    <div style="display: flex; gap: 0.5rem; border-top: 1px solid var(--border-light); padding-top: 1rem; margin-top: auto;">
                        @if($client->phone)
                            <a href="tel:{{ $client->phone }}" style="flex: 1; text-align: center; padding: 0.5rem; background: rgba(16, 185, 129, 0.1); color: #059669; border-radius: 6px; font-weight: 600; font-size: 0.85rem;">Call</a>
                        @endif
                        <a href="/clients/{{ $client->id }}" style="flex: 1; text-align: center; padding: 0.5rem; background: rgba(2, 132, 199, 0.1); color: var(--primary-cerulean); border-radius: 6px; font-weight: 600; font-size: 0.85rem;">View Profile</a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection