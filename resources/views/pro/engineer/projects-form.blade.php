@extends('layouts.app')

@section('page_title', $project ? 'Edit project' : 'New project')

@section('content')
    @php
        $isEdit = $project !== null;
        $action = $isEdit ? '/pro/engineer/projects/'.$project->id : '/pro/engineer/projects';
        $cancelHref = $isEdit ? '/pro/engineer/projects/'.$project->id : '/pro/engineer/projects';
    @endphp
    <div style="max-width: 640px; margin: 0 auto; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; margin-bottom: 1.25rem;">
            <h2 style="margin: 0; color: var(--primary-navy);">{{ $isEdit ? 'Edit project' : 'New engineering project' }}</h2>
            <a href="{{ $cancelHref }}" style="color: var(--text-muted); font-weight: 600; text-decoration: none;">Cancel</a>
        </div>
        @if($errors->any())
            <div style="background: #fef2f2; color: #991b1b; padding: 0.85rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
                @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif
        <form action="{{ $action }}" method="POST">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Project name</label>
                <input type="text" name="name" value="{{ old('name', $project->name ?? '') }}" required maxlength="255" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Reference</label>
                <input type="text" name="reference_code" value="{{ old('reference_code', $project->reference_code ?? '') }}" maxlength="100" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Discipline</label>
                    <select name="discipline" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                        @foreach($disciplines as $key => $label)
                            <option value="{{ $key }}" {{ old('discipline', $project->discipline ?? 'general') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Phase</label>
                    <select name="phase" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                        @foreach($phases as $key => $label)
                            <option value="{{ $key }}" {{ old('phase', $project->phase ?? 'design') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Status</label>
                <select name="status" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}" {{ old('status', $project->status ?? 'active') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <p style="margin: 0.4rem 0 0; font-size: 0.8rem; color: var(--text-muted);">Archived projects leave the main register but stay available under Archived.</p>
            </div>
            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Notes</label>
                <textarea name="notes" rows="4" maxlength="5000" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ old('notes', $project->notes ?? '') }}</textarea>
            </div>
            <button type="submit" style="width: 100%; padding: 0.85rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">{{ $isEdit ? 'Save changes' : 'Create project' }}</button>
        </form>
    </div>
@endsection
