{{-- Car + WFH percentage helpers (on-demand questionnaires). --}}
<div id="car-use-helper-modal" class="pb-modal" hidden>
    <div class="pb-modal-backdrop" data-close-car-helper></div>
    <div class="pb-modal-panel" role="dialog" aria-modal="true" aria-labelledby="car-use-helper-title" style="max-width: 520px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 0.75rem;">
            <h2 id="car-use-helper-title" style="margin: 0; color: var(--primary-navy); font-size: 1.1rem;">Estimate car / fuel practice use</h2>
            <button type="button" data-close-car-helper aria-label="Close" style="background: none; border: none; color: var(--text-muted); font-size: 1.35rem; line-height: 1; cursor: pointer;">&times;</button>
        </div>
        <p style="margin: 0 0 1rem; font-size: 0.85rem; color: var(--text-muted); line-height: 1.45;">
            Rough guide only — you confirm the final percentage. Commuting that is purely private usually does not count as practice use.
        </p>
        <div style="display: grid; gap: 0.85rem; margin-bottom: 1rem;">
            <div>
                <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.35rem;">Days per week you use the car for practice</label>
                <input type="number" id="carDaysWeek" min="0" max="7" step="1" value="3" style="width: 100%; padding: 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div>
                <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.35rem;">Of those days, roughly how much of the driving is for practice? (%)</label>
                <input type="number" id="carDayShare" min="1" max="100" step="1" value="70" style="width: 100%; padding: 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
        </div>
        <div style="background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.75rem 1rem; margin-bottom: 1rem;">
            <div style="font-size: 0.8rem; color: var(--text-muted);">Suggested practice use</div>
            <div style="font-size: 1.35rem; font-weight: 700; color: var(--primary-navy);"><span id="carSuggestedPct">30</span>%</div>
        </div>
        <form action="/expenses/business-use" method="POST" id="carHelperForm">
            @csrf
            @method('PUT')
            <input type="hidden" name="car_business_use_percent" id="carHelperSaveValue" value="30">
            <input type="hidden" name="redirect_to" value="{{ $redirectTo ?? '/expenses' }}">
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <button type="button" id="carHelperApplyOnly" style="flex: 1; min-width: 8rem; padding: 0.7rem; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-weight: 700; cursor: pointer; color: var(--primary-navy);">Use on this form</button>
                <button type="submit" style="flex: 1; min-width: 8rem; padding: 0.7rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Save as my default</button>
            </div>
        </form>
    </div>
</div>

<div id="wfh-helper-modal" class="pb-modal" hidden>
    <div class="pb-modal-backdrop" data-close-wfh-helper></div>
    <div class="pb-modal-panel" role="dialog" aria-modal="true" aria-labelledby="wfh-helper-title" style="max-width: 520px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 0.75rem;">
            <h2 id="wfh-helper-title" style="margin: 0; color: var(--primary-navy); font-size: 1.1rem;">Estimate home-office share</h2>
            <button type="button" data-close-wfh-helper aria-label="Close" style="background: none; border: none; color: var(--text-muted); font-size: 1.35rem; line-height: 1; cursor: pointer;">&times;</button>
        </div>
        <p style="margin: 0 0 1rem; font-size: 0.85rem; color: var(--text-muted); line-height: 1.45;">
            Only a fair share of household bills for the space/time used for your practice is claimable — not the whole bill.
        </p>
        <div style="display: grid; gap: 0.85rem; margin-bottom: 1rem;">
            <div>
                <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.35rem;">Rooms in your home (approx.)</label>
                <input type="number" id="wfhRoomsHome" min="1" max="20" step="1" value="5" style="width: 100%; padding: 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div>
                <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.35rem;">Rooms used mainly for practice</label>
                <input type="number" id="wfhRoomsWork" min="0.25" max="10" step="0.25" value="1" style="width: 100%; padding: 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div>
                <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.35rem;">Hours per week you work from home (optional fine-tune)</label>
                <input type="number" id="wfhHours" min="0" max="80" step="1" value="20" style="width: 100%; padding: 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
        </div>
        <div style="background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.75rem 1rem; margin-bottom: 1rem;">
            <div style="font-size: 0.8rem; color: var(--text-muted);">Suggested home-office share</div>
            <div style="font-size: 1.35rem; font-weight: 700; color: var(--primary-navy);"><span id="wfhSuggestedPct">20</span>%</div>
        </div>
        <form action="/expenses/business-use" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="home_office_percent" id="wfhHelperSaveValue" value="20">
            <input type="hidden" name="redirect_to" value="{{ $redirectTo ?? '/expenses' }}">
            <button type="submit" style="width: 100%; padding: 0.75rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Save home-office %</button>
        </form>
    </div>
</div>

<script>
(function () {
    function bindModal(modalId, openSel, closeSel) {
        var modal = document.getElementById(modalId);
        if (!modal) return null;
        function open(e) { if (e) e.preventDefault(); modal.hidden = false; document.body.style.overflow = 'hidden'; }
        function close() { modal.hidden = true; document.body.style.overflow = ''; }
        document.querySelectorAll(openSel).forEach(function (el) { el.addEventListener('click', open); });
        modal.querySelectorAll(closeSel).forEach(function (el) { el.addEventListener('click', close); });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.hidden) close();
        });
        return { modal: modal, open: open, close: close };
    }

    bindModal('car-use-helper-modal', '[data-open-car-helper]', '[data-close-car-helper]');
    bindModal('wfh-helper-modal', '[data-open-wfh-helper]', '[data-close-wfh-helper]');

    function clamp(n, min, max) { return Math.max(min, Math.min(max, n)); }

    function refreshCar() {
        var days = Number(document.getElementById('carDaysWeek').value || 0);
        var share = Number(document.getElementById('carDayShare').value || 0);
        var pct = Math.round(clamp(days, 0, 7) / 7 * clamp(share, 0, 100));
        pct = clamp(pct, 1, 100);
        document.getElementById('carSuggestedPct').textContent = String(pct);
        document.getElementById('carHelperSaveValue').value = String(pct);
        return pct;
    }
    ['carDaysWeek', 'carDayShare'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('input', refreshCar);
    });
    refreshCar();

    var applyBtn = document.getElementById('carHelperApplyOnly');
    if (applyBtn) {
        applyBtn.addEventListener('click', function () {
            var pct = refreshCar();
            var input = document.getElementById('businessUsePercent');
            if (input) input.value = String(pct);
            document.dispatchEvent(new CustomEvent('business-use-updated', { detail: { car: pct } }));
            var modal = document.getElementById('car-use-helper-modal');
            if (modal) { modal.hidden = true; document.body.style.overflow = ''; }
        });
    }

    function refreshWfh() {
        var home = Number(document.getElementById('wfhRoomsHome').value || 1);
        var work = Number(document.getElementById('wfhRoomsWork').value || 0);
        var hours = Number(document.getElementById('wfhHours').value || 0);
        home = Math.max(1, home);
        work = clamp(work, 0, home);
        var roomPct = (work / home) * 100;
        var timePct = hours > 0 ? (hours / 40) * 100 : roomPct;
        var pct = Math.round(clamp((roomPct * 0.7) + (Math.min(timePct, 100) * 0.3), 1, 100));
        document.getElementById('wfhSuggestedPct').textContent = String(pct);
        document.getElementById('wfhHelperSaveValue').value = String(pct);
        return pct;
    }
    ['wfhRoomsHome', 'wfhRoomsWork', 'wfhHours'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('input', refreshWfh);
    });
    refreshWfh();
})();
</script>
