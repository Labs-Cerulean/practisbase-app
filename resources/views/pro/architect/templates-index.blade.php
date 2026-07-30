@extends('layouts.app')

@section('page_title', 'BCA templates')

@section('content')
    <div style="margin-bottom: 1.25rem;">
        <h1 style="margin: 0 0 0.25rem; color: var(--primary-navy); font-size: 1.5rem;">BCA templates and architect declarations</h1>
        <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem; max-width: 44rem;">
            Official blanks are available for download. Fillable templates generate a PractisBase PDF prefilled from your client, project, PA and site team.
            Live BCA licence registers:
            <a href="{{ $registerUrls['contractor'] }}" target="_blank" rel="noopener">Contractors</a>,
            <a href="{{ $registerUrls['sto'] }}" target="_blank" rel="noopener">STOs</a>,
            <a href="{{ $registerUrls['mason'] }}" target="_blank" rel="noopener">Masons</a>.
        </p>
    </div>

    @if($errors->any())
        <div style="background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
            <ul style="margin: 0; padding-left: 1.1rem;">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; margin-bottom: 1.5rem; box-shadow: var(--shadow-sm);">
        <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.65rem;">Prefill context</div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.75rem;">
            <select id="ctxClient" style="padding: 0.6rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                <option value="">Client (optional)</option>
                @foreach($clients as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </select>
            <select id="ctxProject" style="padding: 0.6rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                <option value="">Project (optional)</option>
                @foreach($projects as $p)
                    <option value="{{ $p->id }}" data-client="{{ $p->architect_client_id }}">{{ $p->name }}</option>
                @endforeach
            </select>
            <select id="ctxPa" style="padding: 0.6rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                <option value="">PA (optional)</option>
                @foreach($pas as $pa)
                    <option value="{{ $pa->id }}" data-project="{{ $pa->architect_project_id }}">{{ $pa->pa_number }}</option>
                @endforeach
            </select>
        </div>
        <p style="margin: 0.65rem 0 0; font-size: 0.8rem; color: var(--text-muted);">Choose a PA when possible. Prefills pull perit details, site address, PA number and site team roles.</p>
    </div>

    @foreach($groups as $group => $templates)
        <h2 style="margin: 0 0 0.65rem; font-size: 1.1rem; color: var(--primary-navy);">{{ $group }}</h2>
        <div style="display: grid; gap: 0.75rem; margin-bottom: 1.5rem;">
            @foreach($templates as $tpl)
                <article style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.1rem 1.2rem; box-shadow: var(--shadow-sm);">
                    <div style="display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; align-items: flex-start;">
                        <div style="flex: 1; min-width: 220px;">
                            <div style="font-weight: 700; color: var(--primary-navy);">{{ $tpl['title'] }}</div>
                            <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem; line-height: 1.45;">{{ $tpl['description'] }}</div>
                        </div>
                        <div style="display: flex; gap: 0.45rem; flex-wrap: wrap;">
                            @if($tpl['blank_file'])
                                <a href="/pro/architect/templates/{{ $tpl['key'] }}/blank" style="background: white; border: 1px solid var(--border-light); color: var(--primary-navy); padding: 0.5rem 0.85rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none; font-size: 0.85rem;">Download blank</a>
                            @endif
                            @if($tpl['fillable'])
                                <button type="button" class="open-fill" data-key="{{ $tpl['key'] }}" data-title="{{ $tpl['title'] }}" style="background: #3f6212; color: white; border: none; padding: 0.5rem 0.85rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer; font-size: 0.85rem;">Fill and download PDF</button>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endforeach

    <div id="fillModal" hidden style="position: fixed; inset: 0; background: rgba(15,39,68,0.45); backdrop-filter: blur(4px); z-index: 80; display: flex; align-items: center; justify-content: center; padding: 1rem;">
        <form id="fillForm" method="POST" action="#" style="background: white; border-radius: 16px; width: min(560px, 100%); padding: 1.25rem; box-shadow: 0 20px 50px rgba(15,39,68,0.25);">
            @csrf
            <div style="font-weight: 700; color: var(--primary-navy); margin-bottom: 0.35rem;" id="fillTitle">Fill template</div>
            <p style="margin: 0 0 0.85rem; font-size: 0.82rem; color: var(--text-muted);">Uses the prefill context selected above. Add any extra wording the form needs.</p>
            <input type="hidden" name="architect_client_id" id="fillClient">
            <input type="hidden" name="architect_project_id" id="fillProject">
            <input type="hidden" name="architect_pa_application_id" id="fillPa">
            <div style="display: grid; gap: 0.65rem;">
                <textarea name="extra_text" rows="3" placeholder="Extra declaration text / reasons" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);"></textarea>
                <textarea name="works_description" rows="2" placeholder="Description of works (exemptions / method statements)" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);"></textarea>
                <input type="text" name="ds_number" placeholder="DS number (dangerous structures)" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.55rem;">
                    <input type="text" name="third_party_count" placeholder="Third party properties count" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    <input type="text" name="complex_count" placeholder="Affected complexes count" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.55rem;">
                    <input type="date" name="start_date" max="{{ date('Y-m-d') }}" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    <input type="date" name="end_date" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>
            <div style="display: flex; gap: 0.5rem; justify-content: flex-end; margin-top: 1rem;">
                <button type="button" id="fillCancel" style="background: white; border: 1px solid var(--border-light); padding: 0.55rem 0.9rem; border-radius: var(--radius-md); font-weight: 600; cursor: pointer;">Cancel</button>
                <button type="submit" style="background: #3f6212; color: white; border: none; padding: 0.55rem 0.9rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Generate PDF</button>
            </div>
        </form>
    </div>

    <script>
        (function () {
            var modal = document.getElementById('fillModal');
            var form = document.getElementById('fillForm');
            document.querySelectorAll('.open-fill').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    form.action = '/pro/architect/templates/' + btn.getAttribute('data-key') + '/generate';
                    document.getElementById('fillTitle').textContent = btn.getAttribute('data-title');
                    document.getElementById('fillClient').value = document.getElementById('ctxClient').value;
                    document.getElementById('fillProject').value = document.getElementById('ctxProject').value;
                    document.getElementById('fillPa').value = document.getElementById('ctxPa').value;
                    modal.hidden = false;
                    modal.style.display = 'flex';
                });
            });
            document.getElementById('fillCancel').addEventListener('click', function () {
                modal.hidden = true;
                modal.style.display = 'none';
            });
        })();
    </script>
@endsection
