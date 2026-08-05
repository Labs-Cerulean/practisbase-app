@extends('layouts.app')

@section('page_title', 'Clients')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.5rem; color: var(--primary-navy); margin-bottom: 0.25rem;">Clients</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem;">
                One client list for invoicing and practice projects.
                @if(auth()->user()->canAccessProPackage('arch') || auth()->user()->canAccessProPackage('eng'))
                    New clients are linked automatically so you can pick them when creating a project.
                @else
                    Manage contacts and client finances.
                @endif
            </p>
        </div>
        <a href="/clients/create" style="background: var(--primary-cerulean); color: white; padding: 0.6rem 1.25rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.9rem; text-decoration: none;">
            + Add Client
        </a>
    </div>

    <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.85rem 1rem; margin-bottom: 1rem; box-shadow: var(--shadow-sm); font-size: 0.85rem; color: var(--text-main); font-weight: 600;">
        {{ auth()->user()->clientUsageLabel() }}
        @unless(auth()->user()->hasUnlimitedClients())
            <span style="font-weight: 500; color: var(--text-muted);"> — archiving a client does not free a Free-plan slot.</span>
        @endunless
    </div>

    @if($errors->any())
        <div style="background: #fef2f2; border: 1px solid #f87171; color: #b91c1c; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; font-size: 0.9rem;">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @if(session('success'))
        <div style="background: #d1fae5; border: 1px solid #10b981; color: #047857; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; font-weight: 500;">
            {{ session('success') }}
        </div>
    @endif

    <div style="display: flex; gap: 0.75rem; margin-bottom: 1rem; flex-wrap: wrap;">
        <a href="/clients" style="padding: 0.45rem 0.9rem; border-radius: 6px; font-size: 0.85rem; font-weight: 600; text-decoration: none; {{ !$showArchived ? 'background: var(--primary-navy); color: white;' : 'background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;' }}">
            Active
        </a>
        <a href="/clients?archived=1" style="padding: 0.45rem 0.9rem; border-radius: 6px; font-size: 0.85rem; font-weight: 600; text-decoration: none; {{ $showArchived ? 'background: var(--primary-navy); color: white;' : 'background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;' }}">
            Archived{{ $archivedCount > 0 ? ' ('.$archivedCount.')' : '' }}
        </a>
    </div>

    <form method="GET" action="/clients" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 1rem; margin-bottom: 2rem; display: flex; gap: 1rem; flex-wrap: wrap; align-items: center; box-shadow: var(--shadow-sm);">
        @if($showArchived)
            <input type="hidden" name="archived" value="1">
        @endif
        <input type="text" name="search" placeholder="Search name or email..." value="{{ request('search') }}" style="flex: 1; min-width: 150px; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.85rem; color: var(--primary-navy); outline: none;">

        <select name="type" style="flex: 1; min-width: 150px; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.85rem; color: var(--primary-navy); outline: none;">
            <option value="">All Types</option>
            <option value="individual" {{ request('type') == 'individual' ? 'selected' : '' }}>Individual</option>
            <option value="company" {{ request('type') == 'company' ? 'selected' : '' }}>Company</option>
        </select>

        <select name="sort" style="flex: 1; min-width: 150px; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.85rem; color: var(--primary-navy); outline: none;">
            <option value="recent" {{ request('sort') == 'recent' ? 'selected' : '' }}>Recently Added</option>
            <option value="name_asc" {{ request('sort') == 'name_asc' ? 'selected' : '' }}>Name (A-Z)</option>
            <option value="name_desc" {{ request('sort') == 'name_desc' ? 'selected' : '' }}>Name (Z-A)</option>
            <option value="highest_due" {{ request('sort') == 'highest_due' ? 'selected' : '' }}>Highest Dues</option>
        </select>

        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" style="padding: 0.5rem 1rem; background: var(--primary-navy); color: white; border: none; border-radius: 6px; font-weight: 600; font-size: 0.85rem; cursor: pointer;">Filter</button>
            @if(request()->anyFilled(['search', 'type', 'sort']) || $showArchived)
                <a href="/clients" style="padding: 0.5rem 1rem; background: #f1f5f9; color: #475569; text-decoration: none; border: 1px solid #cbd5e1; border-radius: 6px; font-weight: 600; font-size: 0.85rem;">Clear</a>
            @endif
        </div>
    </form>

    @if($clients->isEmpty())
        <div style="padding: 3rem; border: 2px dashed var(--border-light); border-radius: var(--radius-md); text-align: center;">
            <p style="color: var(--text-muted); margin-bottom: 1rem;">
                {{ $showArchived ? 'No archived clients.' : 'No clients found.' }}
            </p>
            @unless($showArchived)
                <a href="/clients/create" style="color: var(--primary-cerulean); font-weight: 600;">Add a new client &rarr;</a>
            @endunless
        </div>
    @else
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem;">
            @foreach($clients as $client)
                <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 1.5rem; box-shadow: var(--shadow-sm); display: flex; flex-direction: column; position: relative; {{ $showArchived ? 'opacity: 0.85;' : '' }}">

                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                        <div>
                            <h3 style="margin: 0; color: var(--primary-navy); font-size: 1.1rem;">{{ $client->name }}</h3>
                            <span style="display: inline-block; margin-top: 0.25rem; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); background: #f1f5f9; padding: 0.2rem 0.5rem; border-radius: 4px;">
                                {{ ucfirst($client->type) }}{{ $showArchived ? ' · Archived' : '' }}
                            </span>
                        </div>
                    </div>

                    <div style="font-size: 0.9rem; color: var(--text-main); margin-bottom: 1.25rem;">
                        @if($client->phone)
                            <div style="margin-bottom: 0.25rem;">📞 {{ $client->phone }}</div>
                        @endif
                        @if($client->email)
                            <div>✉️ {{ $client->email }}</div>
                        @endif
                    </div>

                    <div style="margin-top: auto; padding-top: 1.25rem; border-top: 1px dashed var(--border-light); display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                        <div>
                            <div style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Unbilled (RFP)</div>
                            <div style="font-size: 0.95rem; font-weight: 600; color: #4338ca;">€{{ number_format($client->unbilled_pipeline, 2) }}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Official Invoiced</div>
                            <div style="font-size: 0.95rem; font-weight: 600; color: #0369a1;">€{{ number_format($client->net_invoiced, 2) }}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Total Paid</div>
                            <div style="font-size: 0.95rem; font-weight: 600; color: #10b981;">€{{ number_format($client->total_paid, 2) }}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Total Dues</div>
                            <div style="font-size: 0.95rem; font-weight: 700; color: {{ $client->total_dues > 0 ? '#dc2626' : 'var(--primary-navy)' }};">€{{ number_format($client->total_dues, 2) }}</div>
                        </div>
                    </div>

                    <div style="display: flex; gap: 0.5rem;">
                        @if(!$showArchived && $client->phone)
                            <a href="tel:{{ $client->phone }}" style="flex: 1; text-align: center; padding: 0.5rem; background: rgba(16, 185, 129, 0.1); color: #059669; border-radius: 6px; font-weight: 600; font-size: 0.85rem; text-decoration: none;">Call</a>
                        @endif
                        <a href="/clients/{{ $client->id }}?tab=statement" style="flex: 1; text-align: center; padding: 0.5rem; background: rgba(2, 132, 199, 0.1); color: var(--primary-cerulean); border-radius: 6px; font-weight: 600; font-size: 0.85rem; text-decoration: none;">Statement</a>
                        <a href="/clients/{{ $client->id }}?tab=history" style="flex: 1; text-align: center; padding: 0.5rem; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; border-radius: 6px; font-weight: 600; font-size: 0.85rem; text-decoration: none;">History</a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
