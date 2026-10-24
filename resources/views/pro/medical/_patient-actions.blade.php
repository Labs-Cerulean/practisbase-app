@php
    $openActions = array_values(array_filter($actions ?? [], fn ($row) => empty($row['done'])));
    $doneActions = array_values(array_filter($actions ?? [], fn ($row) => ! empty($row['done'])));
@endphp
<div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.1rem 1.2rem; margin-bottom: 1.25rem; box-shadow: var(--shadow-sm);">
    <div style="font-weight: 700; color: var(--primary-navy); margin-bottom: 0.75rem;">Actions</div>
    @if(empty($clinicDeskReady))
        <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">Action labels are not on this database yet.</p>
    @else
        @unless($patient->archived_at)
            <form action="/pro/medical/patients/{{ $patient->id }}/actions" method="POST" style="display: grid; gap: 0.55rem; margin-bottom: 0.85rem;">
                @csrf
                <div>
                    <label for="action-label" style="display: block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.3rem;">Label</label>
                    <input id="action-label" name="label_name" list="clinical-label-names" value="{{ old('label_name') }}" required maxlength="80" placeholder="Check lab results" style="width: 100%; padding: 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    <datalist id="clinical-label-names">
                        @foreach($labels ?? [] as $label)
                            <option value="{{ $label->name }}"></option>
                        @endforeach
                    </datalist>
                </div>
                <div>
                    <label for="action-note" style="display: block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.3rem;">Note</label>
                    <textarea id="action-note" name="note" rows="2" maxlength="2000" style="width: 100%; padding: 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ old('note') }}</textarea>
                </div>
                <button type="submit" style="justify-self: start; background: var(--primary-navy); color: white; border: none; padding: 0.55rem 0.9rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Add action</button>
            </form>
        @endunless

        @if($openActions === [])
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">No open actions.</p>
        @else
            <div style="display: grid; gap: 0.55rem;">
                @foreach($openActions as $action)
                    <div style="display: flex; justify-content: space-between; gap: 0.75rem; align-items: flex-start; flex-wrap: wrap; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.7rem 0.8rem;">
                        <div>
                            <div style="font-weight: 700; color: var(--primary-navy);">{{ $action['label'] }}</div>
                            @if($action['note'] !== '')
                                <div style="white-space: pre-wrap; font-size: 0.9rem; margin-top: 0.2rem;">{{ $action['note'] }}</div>
                            @endif
                        </div>
                        @unless($patient->archived_at)
                            <div style="display: flex; gap: 0.4rem;">
                                <form action="/pro/medical/patients/{{ $patient->id }}/actions/{{ $action['id'] }}/done" method="POST" style="margin: 0;">
                                    @csrf
                                    <button type="submit" style="background: white; border: 1px solid var(--border-light); color: var(--primary-navy); padding: 0.4rem 0.7rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Done</button>
                                </form>
                                <form action="/pro/medical/patients/{{ $patient->id }}/actions/{{ $action['id'] }}" method="POST" style="margin: 0;" onsubmit="return confirm('Remove this action?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="background: white; border: 1px solid #fecaca; color: #b91c1c; padding: 0.4rem 0.7rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Remove</button>
                                </form>
                            </div>
                        @endunless
                    </div>
                @endforeach
            </div>
        @endif

        @if($doneActions !== [])
            <details style="margin-top: 0.75rem;">
                <summary style="cursor: pointer; font-weight: 700; color: var(--text-muted); font-size: 0.85rem;">Done ({{ count($doneActions) }})</summary>
                <div style="display: grid; gap: 0.45rem; margin-top: 0.55rem;">
                    @foreach($doneActions as $action)
                        <div style="display: flex; justify-content: space-between; gap: 0.75rem; align-items: center;">
                            <span style="color: var(--text-muted);">{{ $action['label'] }}</span>
                            @unless($patient->archived_at)
                                <form action="/pro/medical/patients/{{ $patient->id }}/actions/{{ $action['id'] }}/reopen" method="POST" style="margin: 0;">
                                    @csrf
                                    <button type="submit" style="background: none; border: none; color: var(--primary-cerulean); font-weight: 700; cursor: pointer; padding: 0;">Reopen</button>
                                </form>
                            @endunless
                        </div>
                    @endforeach
                </div>
            </details>
        @endif
    @endif
</div>
