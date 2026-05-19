@extends('layouts.app')

@section('page_title', 'Dashboard')

@section('content')
    <div style="margin-bottom: var(--space-lg);">
        <h1 style="font-size: 1.75rem; color: var(--primary-navy);">Welcome back, {{ explode(' ', auth()->user()->name)[0] }}!</h1>
        <p style="color: var(--text-muted); margin-top: 0.25rem; font-size: 1.05rem;">
            You are operating on the <strong>{{ ucfirst(auth()->user()->tier) }}</strong> tier as <strong>{{ preg_match('/^[aeiou]/i', auth()->user()->profession) ? 'an' : 'a' }} {{ auth()->user()->profession }}</strong>.
        </p>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div style="background: white; padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-light); box-shadow: var(--shadow-sm);">
            <div style="color: var(--text-muted); font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">Active Clients</div>
            <div style="font-size: 2rem; font-weight: 700; color: var(--primary-navy); margin-top: 0.5rem;">0</div>
        </div>
        <div style="background: white; padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-light); box-shadow: var(--shadow-sm);">
            <div style="color: var(--text-muted); font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">Pending Invoices</div>
            <div style="font-size: 2rem; font-weight: 700; color: var(--primary-navy); margin-top: 0.5rem;">€0.00</div>
        </div>
    </div>

    <div style="padding: 3rem; border: 2px dashed var(--border-light); background: rgba(255,255,255,0.5); border-radius: var(--radius-md); text-align: center;">
        <h3 style="color: var(--primary-navy); margin-bottom: 0.5rem;">Your dashboard is empty</h3>
        <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Start by adding your first client to the database.</p>
        
        <a href="/clients/create" style="display: inline-block; background: var(--primary-cerulean); color: white; text-decoration: none; padding: 0.75rem 1.5rem; border-radius: var(--radius-md); font-weight: 600; cursor: pointer;">
            + Add New Client
        </a>
    </div>
@endsection