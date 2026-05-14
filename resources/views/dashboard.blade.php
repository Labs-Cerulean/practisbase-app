@extends('layouts.app')

@section('page_title', 'Dashboard')

@section('content')
    <div style="margin-bottom: var(--space-lg);">
        <h1 style="font-size: 1.5rem; color: var(--primary-navy);">Welcome back, Dr. Borg</h1>
        <p style="color: var(--text-muted); margin-top: 0.25rem;">Here is what is happening in your practice today.</p>
    </div>
    
    <div style="padding: 2rem; border: 1px dashed var(--border-light); border-radius: var(--radius-md); text-align: center; color: var(--text-muted);">
        Dashboard widgets will go here
    </div>
@endsection