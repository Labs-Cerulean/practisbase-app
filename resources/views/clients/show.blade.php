@extends('layouts.app')

@section('page_title', 'Client Profile')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem;">
        <div>
            <a href="/clients" style="color: var(--text-muted); font-size: 0.85rem; font-weight: 600; text-decoration: none; margin-bottom: 0.5rem; display: inline-block;">&larr; Back to Directory</a>
            <h1 style="font-size: 1.75rem; color: var(--primary-navy); margin-bottom: 0.25rem;">{{ $client->name }}</h1>
            <span style="display: inline-block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--primary-cerulean); background: rgba(2, 132, 199, 0.1); padding: 0.25rem 0.75rem; border-radius: 20px;">
                {{ ucfirst($client->type) }}
            </span>
        </div>
        <a href="/clients/{{ $client->id }}/edit" style="display: inline-block; background: white; border: 1px solid var(--border-light); color: var(--text-main); text-decoration: none; padding: 0.5rem 1rem; border-radius: var(--radius-md); font-weight: 600; cursor: pointer; box-shadow: var(--shadow-sm);">
            Edit Details
        </a>
            Edit Details
        </button>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        
        <div style="background: white; padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-light); box-shadow: var(--shadow-sm);">
            <h3 style="color: var(--primary-navy); margin-top: 0; margin-bottom: 1.25rem; font-size: 1.1rem; border-bottom: 1px solid var(--border-light); padding-bottom: 0.5rem;">Contact Information</h3>
            
            <div style="margin-bottom: 1rem;">
                <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 0.25rem;">Phone Number</div>
                <div style="font-weight: 500; color: var(--text-main);">{{ $client->phone ?? 'Not provided' }}</div>
            </div>
            
            <div style="margin-bottom: 1rem;">
                <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 0.25rem;">Email Address</div>
                <div style="font-weight: 500; color: var(--text-main);">{{ $client->email ?? 'Not provided' }}</div>
            </div>

            <div style="margin-bottom: 0;">
                <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 0.25rem;">Billing Address</div>
                <div style="font-weight: 500; color: var(--text-main); line-height: 1.4;">{!! nl2br(e($client->billing_address ?? 'Not provided')) !!}</div>
            </div>
        </div>

        <div style="background: white; padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-light); box-shadow: var(--shadow-sm);">
            <h3 style="color: var(--primary-navy); margin-top: 0; margin-bottom: 1.25rem; font-size: 1.1rem; border-bottom: 1px solid var(--border-light); padding-bottom: 0.5rem;">Profile Specifics</h3>
            
            @if($client->profile_data)
                @foreach($client->profile_data as $key => $value)
                    @if($value)
                        <div style="margin-bottom: 1rem;">
                            <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 0.25rem;">
                                {{ str_replace('_', ' ', $key) }}
                            </div>
                            <div style="font-weight: 500; color: var(--text-main);">{{ $value }}</div>
                        </div>
                    @endif
                @endforeach
            @else
                <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0;">No additional data on file.</p>
            @endif
        </div>
    </div>
@endsection