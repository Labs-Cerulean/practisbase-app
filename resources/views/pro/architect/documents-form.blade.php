@extends('layouts.app')

@section('page_title', 'Upload document')

@section('content')
    <div style="margin-bottom: 1.25rem;">
        <a href="/pro/architect/documents" style="color: var(--text-muted); text-decoration: none; font-weight: 600;">← Documents</a>
        <h1 style="margin: 0.5rem 0 0; color: var(--primary-navy); font-size: 1.5rem;">Upload document</h1>
        <p style="margin: 0.35rem 0 0; color: var(--text-muted); font-size: 0.88rem;">Files live under a client, project, or PA case — with revision history.</p>
    </div>

    @if($errors->any())
        <div style="background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
            <ul style="margin: 0; padding-left: 1.1rem;">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="/pro/architect/documents" enctype="multipart/form-data" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.35rem; max-width: 760px; box-shadow: var(--shadow-sm);">
        @csrf
        <div style="display: grid; gap: 0.85rem;">
            <div>
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Title *</label>
                <input type="text" name="title" value="{{ old('title') }}" required style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.85rem;">
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Type *</label>
                    <select name="doc_type" required style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        @foreach($docTypes as $key => $label)
                            <option value="{{ $key }}" @selected(old('doc_type', 'plans') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Status</label>
                    <select name="status" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}" @selected(old('status', 'draft') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Doc code</label>
                    <input type="text" name="doc_code" value="{{ old('doc_code') }}" placeholder="e.g. DRW-001" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>

            <div>
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Store at *</label>
                <select name="scope_level" id="scopeLevel" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    <option value="client" @selected(($prefill['client_id'] ?? null) && !($prefill['project_id'] ?? null) && !($prefill['pa_id'] ?? null))>Client level</option>
                    <option value="project" @selected(($prefill['project_id'] ?? null) && !($prefill['pa_id'] ?? null))>Project level</option>
                    <option value="pa" @selected($prefill['pa_id'] ?? null)>PA case</option>
                </select>
            </div>
            <div id="scopeClient">
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Client</label>
                <select name="client_id" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    <option value="">Select…</option>
                    @foreach($clients as $c)
                        <option value="{{ $c->id }}" @selected(old('client_id', $prefill['client_id'] ?? '') == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div id="scopeProject">
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Project</label>
                <select name="architect_project_id" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    <option value="">Select…</option>
                    @foreach($projects as $p)
                        <option value="{{ $p->id }}" @selected(old('architect_project_id', $prefill['project_id'] ?? '') == $p->id)>{{ $p->name }} @if($p->client) ({{ $p->client->name }}) @endif</option>
                    @endforeach
                </select>
            </div>
            <div id="scopePa">
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">PA / PC / DN case</label>
                <select name="architect_pa_application_id" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    <option value="">Select…</option>
                    @foreach($pas as $pa)
                        <option value="{{ $pa->id }}" @selected(old('architect_pa_application_id', $prefill['pa_id'] ?? '') == $pa->id)>{{ $pa->canonicalNumber() ?: ($pa->pa_number ?: 'Case #'.$pa->id) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">File *</label>
                <input type="file" name="file" required style="width: 100%; padding: 0.45rem 0; ">
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">Up to 20 MB. PDFs and images open in-app; other types download.</div>
            </div>
            <div>
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Revision note</label>
                <input type="text" name="change_note" value="{{ old('change_note', 'Initial upload') }}" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div>
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Notes</label>
                <textarea name="notes" rows="2" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ old('notes') }}</textarea>
            </div>
            <button type="submit" style="background: #3f6212; color: white; border: none; padding: 0.75rem 1.1rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer; width: fit-content;">Upload as Rev 1</button>
        </div>
    </form>

    <script>
        (function () {
            var level = document.getElementById('scopeLevel');
            var c = document.getElementById('scopeClient');
            var p = document.getElementById('scopeProject');
            var a = document.getElementById('scopePa');
            function sync() {
                var v = level.value;
                c.style.display = v === 'client' ? 'block' : 'none';
                p.style.display = v === 'project' ? 'block' : 'none';
                a.style.display = v === 'pa' ? 'block' : 'none';
            }
            level.addEventListener('change', sync);
            sync();
        })();
    </script>
@endsection
