@extends('layouts.app')

@section('page_title', $pa ? 'Edit case' : 'New case')

@section('content')
    <div style="margin-bottom: 1.25rem;">
        <a href="/pro/architect/projects/{{ $project->id }}" style="color: var(--text-muted); text-decoration: none; font-weight: 600;">← {{ $project->name }}</a>
        <h1 style="margin: 0.5rem 0 0; color: var(--primary-navy); font-size: 1.5rem;">{{ $pa ? 'Edit planning case' : 'New planning case' }}</h1>
        <p style="margin: 0.35rem 0 0; color: var(--text-muted); font-size: 0.88rem;">PA / PC / DN number is optional until Planning Authority issues it. Case numbers are stored zero-padded for eApps (e.g. 00525, not 0525).</p>
    </div>

    @if($errors->any())
        <div style="background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
            <ul style="margin: 0; padding-left: 1.1rem;">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    @php
        $oldType = old('case_type', $pa->case_type ?? ($pa ? $pa->resolvedCaseType() : 'PA'));
        $oldNumber = old('case_number', $pa->case_number ?? ($pa ? $pa->resolvedCaseNumber() : ''));
        $oldYear = old('case_year', $pa->case_year ?? ($pa ? $pa->resolvedCaseYear() : ''));
        $oldStatus = old('status', $pa->status ?? 'tracking');
        if ($oldStatus === 'active') {
            $oldStatus = 'tracking';
        }
        if ($oldStatus === 'approved') {
            $oldStatus = 'endorsed';
        }
    @endphp

    <form method="POST" action="{{ $pa ? '/pro/architect/pa/'.$pa->id : '/pro/architect/projects/'.$project->id.'/pa' }}" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.35rem; max-width: 720px; box-shadow: var(--shadow-sm);">
        @csrf
        @if($pa) @method('PUT') @endif
        <div style="display: grid; gap: 0.85rem;">
            <div style="display: grid; grid-template-columns: 1.2fr 1fr 0.7fr; gap: 0.85rem;">
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Case type *</label>
                    <select name="case_type" required style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        @foreach($caseTypes as $key => $label)
                            <option value="{{ $key }}" @selected($oldType === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Case number</label>
                    <input type="text" name="case_number" id="case_number" value="{{ $oldNumber }}" inputmode="numeric" placeholder="e.g. 525 → 00525" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    <div id="case_number_hint" style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.25rem;">Saved as five digits for eApps.</div>
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Year</label>
                    <input type="text" name="case_year" value="{{ $oldYear }}" inputmode="numeric" maxlength="4" placeholder="22" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>
            <div>
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Title / description</label>
                <input type="text" name="title" value="{{ old('title', $pa->title ?? '') }}" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem;">
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Status</label>
                    <select name="status" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}" @selected($oldStatus === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Works commencement</label>
                    <input type="date" name="works_commencement_date" max="{{ date('Y-m-d') }}" value="{{ old('works_commencement_date', optional($pa->works_commencement_date ?? null)->format('Y-m-d')) }}" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>
            <div>
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Notes</label>
                <textarea name="notes" rows="3" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ old('notes', $pa->notes ?? '') }}</textarea>
            </div>
            <button type="submit" style="background: #3f6212; color: white; border: none; padding: 0.75rem 1.1rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer; width: fit-content;">Save case</button>
        </div>
    </form>
    <script>
    (function () {
        var input = document.getElementById('case_number');
        var hint = document.getElementById('case_number_hint');
        if (!input || !hint) return;
        function preview() {
            var digits = (input.value || '').replace(/\D+/g, '');
            if (!digits) {
                hint.textContent = 'Saved as five digits for eApps.';
                return;
            }
            var padded = digits.length >= 5 ? digits : ('00000' + digits).slice(-5);
            hint.textContent = 'Will link as …/' + padded + '/… on eApps.';
        }
        input.addEventListener('input', preview);
        preview();
    })();
    </script>
@endsection
