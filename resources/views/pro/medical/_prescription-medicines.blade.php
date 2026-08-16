{{-- Expects $medicines as list of rows (may be empty). Used on create/edit. --}}
@php
    $medicines = $medicines ?? [['name' => '', 'strength' => '', 'dose' => '', 'quantity' => '', 'instructions' => '']];
    if (old('medicines')) {
        $medicines = old('medicines');
    }
@endphp
@php
    $dispenseMode = old('dispense_mode', $dispenseMode ?? 'single');
    if (! in_array($dispenseMode, ['single', 'repeat'], true)) {
        $dispenseMode = 'single';
    }
@endphp
<div id="prescription-fields" style="{{ ($visible ?? false) ? '' : 'display:none;' }} margin-bottom: 1rem;">
    <div style="padding: 1rem; background: #f8fafc; border: 1px solid #cbd5e1; border-left: 4px solid #0f172a; border-radius: var(--radius-md);">
        <div style="margin-bottom: 1rem; padding-bottom: 0.85rem; border-bottom: 1px solid #e2e8f0;">
            <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #0f172a; margin-bottom: 0.45rem;">Dispense</div>
            <div style="display: flex; gap: 1.25rem; flex-wrap: wrap; font-size: 0.9rem;">
                <label style="display: inline-flex; align-items: center; gap: 0.4rem; cursor: pointer; font-weight: 600; color: var(--primary-navy);">
                    <input type="radio" name="dispense_mode" value="single" {{ $dispenseMode === 'single' ? 'checked' : '' }}>
                    Single
                </label>
                <label style="display: inline-flex; align-items: center; gap: 0.4rem; cursor: pointer; font-weight: 600; color: var(--primary-navy);">
                    <input type="radio" name="dispense_mode" value="repeat" {{ $dispenseMode === 'repeat' ? 'checked' : '' }}>
                    Repeat
                </label>
            </div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.35rem;">Printed on the pad footer like a paper prescription.</div>
        </div>
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 0.75rem;">
            <div>
                <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #0f172a;">Medicines</div>
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">
                    Suggestions from your previous prescriptions. Empty rows are ignored.
                </div>
            </div>
            <button type="button" id="add-medicine-row" style="background: #0f172a; color: white; border: none; padding: 0.45rem 0.85rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer; font-size: 0.8rem;">
                + Add medicine
            </button>
        </div>
        <div id="medicine-rows" style="display: grid; gap: 0.75rem;">
            @foreach($medicines as $i => $med)
                <div class="medicine-row" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.85rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.65rem;">
                        <strong style="font-size: 0.8rem; color: var(--primary-navy);">Medicine <span class="medicine-index">{{ $i + 1 }}</span></strong>
                        <button type="button" class="remove-medicine-row" style="background: none; border: none; color: #b91c1c; font-weight: 700; cursor: pointer; font-size: 0.8rem;">Remove</button>
                    </div>
                    <div style="display: grid; gap: 0.65rem;">
                        <div class="medicine-name-wrap" style="position: relative;">
                            <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Name *</label>
                            <input type="text" name="medicines[{{ $i }}][name]" value="{{ $med['name'] ?? '' }}" placeholder="e.g. Amoxicillin" autocomplete="off" class="medicine-name-input"
                                   style="width: 100%; padding: 0.6rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                            <div class="medicine-suggest" role="listbox" hidden
                                 style="position: absolute; left: 0; right: 0; top: 100%; z-index: 40; margin-top: 2px; background: #fff; border: 1px solid #cbd5e1; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); max-height: 220px; overflow-y: auto;"></div>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.65rem;">
                            <div>
                                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Strength</label>
                                <input type="text" name="medicines[{{ $i }}][strength]" value="{{ $med['strength'] ?? '' }}" placeholder="e.g. 500 mg" class="medicine-strength-input"
                                       style="width: 100%; padding: 0.6rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Dose / frequency</label>
                                <input type="text" name="medicines[{{ $i }}][dose]" value="{{ $med['dose'] ?? '' }}" placeholder="e.g. 1 cap TDS" class="medicine-dose-input"
                                       style="width: 100%; padding: 0.6rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Quantity</label>
                                <input type="text" name="medicines[{{ $i }}][quantity]" value="{{ $med['quantity'] ?? '' }}" placeholder="e.g. 21 caps" class="medicine-quantity-input"
                                       style="width: 100%; padding: 0.6rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                            </div>
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Directions / notes</label>
                            <textarea name="medicines[{{ $i }}][instructions]" rows="2" placeholder="e.g. Take with food · no alcohol" class="medicine-instructions-input"
                                      style="width: 100%; padding: 0.6rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ $med['instructions'] ?? '' }}</textarea>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<template id="medicine-row-template">
    <div class="medicine-row" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.85rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.65rem;">
            <strong style="font-size: 0.8rem; color: var(--primary-navy);">Medicine <span class="medicine-index">1</span></strong>
            <button type="button" class="remove-medicine-row" style="background: none; border: none; color: #b91c1c; font-weight: 700; cursor: pointer; font-size: 0.8rem;">Remove</button>
        </div>
        <div style="display: grid; gap: 0.65rem;">
            <div class="medicine-name-wrap" style="position: relative;">
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Name *</label>
                <input type="text" data-field="name" placeholder="e.g. Amoxicillin" autocomplete="off" class="medicine-name-input"
                       style="width: 100%; padding: 0.6rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                <div class="medicine-suggest" role="listbox" hidden
                     style="position: absolute; left: 0; right: 0; top: 100%; z-index: 40; margin-top: 2px; background: #fff; border: 1px solid #cbd5e1; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); max-height: 220px; overflow-y: auto;"></div>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.65rem;">
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Strength</label>
                    <input type="text" data-field="strength" placeholder="e.g. 500 mg" class="medicine-strength-input"
                           style="width: 100%; padding: 0.6rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Dose / frequency</label>
                    <input type="text" data-field="dose" placeholder="e.g. 1 cap TDS" class="medicine-dose-input"
                           style="width: 100%; padding: 0.6rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Quantity</label>
                    <input type="text" data-field="quantity" placeholder="e.g. 21 caps" class="medicine-quantity-input"
                           style="width: 100%; padding: 0.6rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>
            <div>
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Directions / notes</label>
                <textarea data-field="instructions" rows="2" placeholder="e.g. Take with food · no alcohol" class="medicine-instructions-input"
                          style="width: 100%; padding: 0.6rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);"></textarea>
            </div>
        </div>
    </div>
</template>

<script>
    (function () {
        var list = document.getElementById('medicine-rows');
        var addBtn = document.getElementById('add-medicine-row');
        var tpl = document.getElementById('medicine-row-template');
        if (!list || !addBtn || !tpl) return;

        var catalogUrl = '/pro/medical/prescription-catalog';

        function reindex() {
            var rows = list.querySelectorAll('.medicine-row');
            rows.forEach(function (row, i) {
                var idx = row.querySelector('.medicine-index');
                if (idx) idx.textContent = String(i + 1);
                ['name', 'strength', 'dose', 'quantity', 'instructions'].forEach(function (field) {
                    var el = row.querySelector('[name="medicines[' + i + '][' + field + ']"]') || row.querySelector('[data-field="' + field + '"]');
                    if (el) {
                        el.setAttribute('name', 'medicines[' + i + '][' + field + ']');
                        el.removeAttribute('data-field');
                    }
                });
                var remove = row.querySelector('.remove-medicine-row');
                if (remove) {
                    remove.style.visibility = rows.length > 1 ? 'visible' : 'hidden';
                }
            });
        }

        function hideSuggest(box) {
            if (!box) return;
            box.hidden = true;
            box.innerHTML = '';
        }

        function hideAllSuggest(except) {
            list.querySelectorAll('.medicine-suggest').forEach(function (box) {
                if (box !== except) hideSuggest(box);
            });
        }

        function applySuggestion(row, item) {
            var name = row.querySelector('.medicine-name-input');
            var strength = row.querySelector('.medicine-strength-input');
            var dose = row.querySelector('.medicine-dose-input');
            var quantity = row.querySelector('.medicine-quantity-input');
            var instructions = row.querySelector('.medicine-instructions-input');
            if (name) name.value = item.medicine_name || '';
            if (strength) strength.value = item.strength || '';
            if (dose) dose.value = item.dose || '';
            if (quantity) quantity.value = item.quantity || '';
            if (instructions) instructions.value = item.instructions || '';
            hideSuggest(row.querySelector('.medicine-suggest'));
            if (dose) dose.focus();
        }

        function renderSuggest(row, items) {
            var box = row.querySelector('.medicine-suggest');
            if (!box) return;
            hideAllSuggest(box);
            box.innerHTML = '';
            if (!items || !items.length) {
                hideSuggest(box);
                return;
            }
            items.forEach(function (item) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.setAttribute('role', 'option');
                btn.style.cssText = 'display:block;width:100%;text-align:left;padding:0.55rem 0.75rem;border:none;border-bottom:1px solid #e2e8f0;background:#fff;cursor:pointer;font-size:0.85rem;color:#0f172a;';
                var title = item.medicine_name || '';
                if (item.strength) title += ' · ' + item.strength;
                var detail = [];
                if (item.dose) detail.push(item.dose);
                if (item.quantity) detail.push('Qty ' + item.quantity);
                btn.innerHTML = '<div style="font-weight:700;">' + title.replace(/</g, '&lt;') + '</div>' +
                    (detail.length ? '<div style="font-size:0.75rem;color:#64748b;margin-top:0.15rem;">' + detail.join(' · ').replace(/</g, '&lt;') + '</div>' : '');
                btn.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    applySuggestion(row, item);
                });
                btn.addEventListener('mouseenter', function () {
                    btn.style.background = '#f1f5f9';
                });
                btn.addEventListener('mouseleave', function () {
                    btn.style.background = '#fff';
                });
                box.appendChild(btn);
            });
            box.hidden = false;
        }

        function bindAutocomplete(row) {
            var input = row.querySelector('.medicine-name-input');
            var box = row.querySelector('.medicine-suggest');
            if (!input || !box || input.dataset.autocompleteBound === '1') return;
            input.dataset.autocompleteBound = '1';

            var timer = null;
            var reqId = 0;

            input.addEventListener('input', function () {
                var q = (input.value || '').trim();
                clearTimeout(timer);
                if (q.length < 1) {
                    hideSuggest(box);
                    return;
                }
                timer = setTimeout(function () {
                    var myId = ++reqId;
                    fetch(catalogUrl + '?q=' + encodeURIComponent(q) + '&limit=8', {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin'
                    }).then(function (r) {
                        if (!r.ok) throw new Error('catalog');
                        return r.json();
                    }).then(function (data) {
                        if (myId !== reqId) return;
                        renderSuggest(row, (data && data.items) ? data.items : []);
                    }).catch(function () {
                        if (myId === reqId) hideSuggest(box);
                    });
                }, 180);
            });

            input.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') hideSuggest(box);
            });

            input.addEventListener('blur', function () {
                setTimeout(function () { hideSuggest(box); }, 150);
            });
        }

        function bindRemove(row) {
            var btn = row.querySelector('.remove-medicine-row');
            if (!btn) return;
            btn.addEventListener('click', function () {
                if (list.querySelectorAll('.medicine-row').length <= 1) return;
                row.remove();
                reindex();
            });
        }

        list.querySelectorAll('.medicine-row').forEach(function (row) {
            bindRemove(row);
            bindAutocomplete(row);
        });

        addBtn.addEventListener('click', function () {
            var node = tpl.content.cloneNode(true);
            var row = node.querySelector('.medicine-row');
            list.appendChild(node);
            bindRemove(row);
            bindAutocomplete(row);
            reindex();
            var name = row.querySelector('.medicine-name-input');
            if (name) name.focus();
        });

        document.addEventListener('click', function (e) {
            if (!e.target.closest || !e.target.closest('.medicine-name-wrap')) {
                hideAllSuggest();
            }
        });

        reindex();
        window.__rxMedicineReindex = reindex;
    })();
</script>
