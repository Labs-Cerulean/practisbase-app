@extends('layouts.app')

@section('page_title', 'Dashboard')

@section('content')
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        
        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.5rem; box-shadow: var(--shadow-sm);">
            <div style="color: var(--text-muted); font-size: 0.85rem; font-weight: 700; text-transform: uppercase; margin-bottom: 1rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem;">Directory</div>
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 0.5rem;">
                <div style="font-size: 0.85rem; color: var(--text-main); font-weight: 600;">Active Clients</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary-navy);">{{ $clientCount }}</div>
            </div>
            <a href="/clients" style="font-size: 0.8rem; color: var(--primary-cerulean); text-decoration: none; font-weight: 600;">View directory &rarr;</a>
        </div>

        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.5rem; box-shadow: var(--shadow-sm);">
            <div style="color: var(--text-muted); font-size: 0.85rem; font-weight: 700; text-transform: uppercase; margin-bottom: 1rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem;">Pipeline & Revenue</div>
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1rem;">
                <div style="font-size: 0.85rem; color: var(--text-main); font-weight: 600;">Total Projected</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary-navy);">€{{ number_format($totalPipeline, 2) }}</div>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; padding-top: 0.5rem; border-top: 1px dashed #e2e8f0;">
                <div style="font-size: 0.8rem; color: var(--text-muted);">Official Invoiced</div>
                <div style="font-size: 0.95rem; font-weight: 600; color: #0369a1;">€{{ number_format($netInvoiced, 2) }}</div>
            </div>
        </div>

        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.5rem; box-shadow: var(--shadow-sm);">
            <div style="color: var(--text-muted); font-size: 0.85rem; font-weight: 700; text-transform: uppercase; margin-bottom: 1rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem;">Outstanding Dues</div>
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1rem;">
                <div style="font-size: 0.85rem; color: var(--text-main); font-weight: 600;">Total Global Dues</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: #dc2626;">€{{ number_format($totalDues, 2) }}</div>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; padding-top: 0.5rem; border-top: 1px dashed #e2e8f0;">
                <div style="font-size: 0.8rem; color: var(--text-muted);">Total Collected</div>
                <div style="font-size: 0.95rem; font-weight: 600; color: #10b981;">€{{ number_format($totalCollected, 2) }}</div>
            </div>
        </div>
    </div>

    @if($clientCount === 0)
        <div style="padding: 3rem; border: 2px dashed var(--border-light); background: rgba(255,255,255,0.5); border-radius: var(--radius-md); text-align: center;">
            <h3 style="color: var(--primary-navy); margin-bottom: 0.5rem;">Your dashboard is empty</h3>
            <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Start by adding your first client to the database.</p>
            
            <a href="/clients/create" style="display: inline-block; background: var(--primary-cerulean); color: white; text-decoration: none; padding: 0.75rem 1.5rem; border-radius: var(--radius-md); font-weight: 600; cursor: pointer;">
                + Add New Client
            </a>
        </div>
    @else
        <div style="padding: 2rem; background: white; border-radius: var(--radius-md); border: 1px solid var(--border-light); box-shadow: var(--shadow-sm);">
            <h3 style="color: var(--primary-navy); margin-top: 0; margin-bottom: 1rem;">Recent Activity</h3>
            <p style="color: var(--text-muted); margin: 0; font-size: 0.95rem;">
                You have {{ $clientCount }} active {{ $clientCount === 1 ? 'client' : 'clients' }} in your directory. Head over to the <a href="/clients" style="color: var(--primary-cerulean); font-weight: 600;">Clients Directory</a> to manage them or to issue an invoice.
            </p>
        </div>
    @endif
@endsection