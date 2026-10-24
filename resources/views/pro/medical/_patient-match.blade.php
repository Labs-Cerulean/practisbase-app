@php
    $serverMatches = session('possible_patient_matches', []);
    if (! is_array($serverMatches)) {
        $serverMatches = [];
    }
@endphp
<div data-patient-match data-ignore="{{ (int) ($ignorePatientId ?? 0) }}" data-match-sig="{{ collect($serverMatches)->pluck('id')->implode(',') }}" style="margin-bottom: 1rem;">
    <div data-match-box style="{{ $serverMatches === [] ? 'display: none;' : '' }} background: #fffbeb; border: 1px solid #fcd34d; color: #92400e; padding: 0.85rem 1rem; border-radius: var(--radius-md);">
        <div style="font-weight: 700; margin-bottom: 0.35rem;">This may already be in the vault.</div>
        <div data-match-list style="display: grid; gap: 0.35rem; margin-bottom: 0.65rem;">
            @foreach($serverMatches as $match)
                <a href="/pro/medical/patients/{{ (int) ($match['id'] ?? 0) }}" target="_blank" rel="noopener" style="color: #92400e; font-weight: 700;">
                    {{ $match['name'] ?? 'Patient' }}
                    @if(! empty($match['id_card']))
                        · ID {{ $match['id_card'] }}
                    @endif
                    @if(! empty($match['date_of_birth']))
                        · DOB {{ \Illuminate\Support\Carbon::parse($match['date_of_birth'])->format('d M Y') }}
                    @endif
                    @if(! empty($match['archived']))
                        · archived
                    @endif
                    · {{ ($match['reason'] ?? '') === 'id' ? 'Same ID card' : 'Same or very similar name' }}
                </a>
            @endforeach
        </div>
        <label style="display: flex; gap: 0.5rem; align-items: flex-start; font-weight: 600;">
            <input type="checkbox" name="confirm_possible_duplicate" value="1" data-match-confirm style="margin-top: 0.2rem;" {{ $serverMatches !== [] ? 'required' : '' }}>
            <span>I checked. This is a different person.</span>
        </label>
    </div>
</div>
<script>
    (function () {
        var root = document.querySelector('[data-patient-match]');
        if (!root || root.getAttribute('data-match-bound') === '1') return;
        root.setAttribute('data-match-bound', '1');
        var form = root.closest('form');
        var box = root.querySelector('[data-match-box]');
        var list = root.querySelector('[data-match-list]');
        var confirmBox = root.querySelector('[data-match-confirm]');
        if (!form || !box || !list || !confirmBox) return;

        var nameInput = form.querySelector('[name="display_name"]');
        var idInput = form.querySelector('[name="id_card"]');
        var timer = null;

        function enough(name, idCard) {
            var idNorm = String(idCard || '').replace(/[^A-Za-z0-9]/g, '');
            return String(name || '').trim().length >= 3 || idNorm.length >= 5;
        }

        function hide() {
            box.style.display = 'none';
            confirmBox.required = false;
            confirmBox.checked = false;
            root.dataset.matchSig = '';
        }

        function render(matches) {
            if (!matches.length) {
                hide();
                return;
            }
            var signature = matches.map(function (row) { return String(row.id); }).join(',');
            if (signature !== (root.dataset.matchSig || '')) {
                confirmBox.checked = false;
                root.dataset.matchSig = signature;
            }
            list.textContent = '';
            matches.forEach(function (row) {
                var link = document.createElement('a');
                link.href = row.url;
                link.target = '_blank';
                link.rel = 'noopener';
                link.style.color = '#92400e';
                link.style.fontWeight = '700';
                var text = row.name || 'Patient';
                if (row.id_card) text += ' · ID ' + row.id_card;
                if (row.date_of_birth) text += ' · DOB ' + row.date_of_birth;
                if (row.archived) text += ' · archived';
                if (row.reason) text += ' · ' + row.reason;
                link.textContent = text;
                list.appendChild(link);
            });
            box.style.display = 'block';
            confirmBox.required = true;
        }

        function lookup() {
            var name = nameInput ? nameInput.value : '';
            var idCard = idInput ? idInput.value : '';
            if (!enough(name, idCard)) {
                hide();
                return;
            }
            var params = new URLSearchParams();
            params.set('display_name', name);
            params.set('id_card', idCard);
            params.set('ignore', root.getAttribute('data-ignore') || '0');
            fetch('/pro/medical/patients/matches?' + params.toString(), { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                .then(function (res) { return res.ok ? res.json() : { matches: [] }; })
                .then(function (data) { render((data && data.matches) || []); })
                .catch(function () {});
        }

        function schedule() {
            clearTimeout(timer);
            timer = setTimeout(lookup, 350);
        }

        if (nameInput) nameInput.addEventListener('input', schedule);
        if (idInput) idInput.addEventListener('input', schedule);
        form.addEventListener('focusout', schedule);

        form.addEventListener('submit', function (event) {
            if (box.style.display !== 'none' && !confirmBox.checked) {
                event.preventDefault();
                event.stopImmediatePropagation();
                box.scrollIntoView({ block: 'center' });
            }
        });
    })();
</script>
