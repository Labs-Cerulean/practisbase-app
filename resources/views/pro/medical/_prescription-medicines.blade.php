{{-- Expects $medicines as list of rows (may be empty). Used on create/edit. --}}
@php
    $medicines = $medicines ?? [['name' => '', 'strength' => '', 'dose' => '', 'quantity' => '', 'instructions' => '']];
    if (old('medicines')) {
        $medicines = old('medicines');
    }
@endphp
<div id="prescription-fields" style="{{ ($visible ?? false) ? '' : 'display:none;' }} margin-bottom: 1rem;">
    <div style="padding: 1rem; background: #f8fafc; border: 1px solid #cbd5e1; border-left: 4px solid #0f172a; border-radius: var(--radius-md);">
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 0.75rem;">
            <div>
                <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #0f172a;">Medicines</div>
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">Add every item on this prescription. Empty rows are ignored.</div>
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
                        <div>
                            <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Name *</label>
                            <input type="text" name="medicines[{{ $i }}][name]" value="{{ $med['name'] ?? '' }}" placeholder="e.g. Amoxicillin"
                                   style="width: 100%; padding: 0.6rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.65rem;">
                            <div>
                                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Strength</label>
                                <input type="text" name="medicines[{{ $i }}][strength]" value="{{ $med['strength'] ?? '' }}" placeholder="e.g. 500 mg"
                                       style="width: 100%; padding: 0.6rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Dose / frequency</label>
                                <input type="text" name="medicines[{{ $i }}][dose]" value="{{ $med['dose'] ?? '' }}" placeholder="e.g. 1 cap TDS"
                                       style="width: 100%; padding: 0.6rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Quantity</label>
                                <input type="text" name="medicines[{{ $i }}][quantity]" value="{{ $med['quantity'] ?? '' }}" placeholder="e.g. 21 caps"
                                       style="width: 100%; padding: 0.6rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                            </div>
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Directions / notes</label>
                            <textarea name="medicines[{{ $i }}][instructions]" rows="2" placeholder="e.g. Take with food · no alcohol"
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
            <div>
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Name *</label>
                <input type="text" data-field="name" placeholder="e.g. Amoxicillin"
                       style="width: 100%; padding: 0.6rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.65rem;">
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Strength</label>
                    <input type="text" data-field="strength" placeholder="e.g. 500 mg"
                           style="width: 100%; padding: 0.6rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Dose / frequency</label>
                    <input type="text" data-field="dose" placeholder="e.g. 1 cap TDS"
                           style="width: 100%; padding: 0.6rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Quantity</label>
                    <input type="text" data-field="quantity" placeholder="e.g. 21 caps"
                           style="width: 100%; padding: 0.6rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>
            <div>
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Directions / notes</label>
                <textarea data-field="instructions" rows="2" placeholder="e.g. Take with food · no alcohol"
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

        function bindRemove(row) {
            var btn = row.querySelector('.remove-medicine-row');
            if (!btn) return;
            btn.addEventListener('click', function () {
                if (list.querySelectorAll('.medicine-row').length <= 1) return;
                row.remove();
                reindex();
            });
        }

        list.querySelectorAll('.medicine-row').forEach(bindRemove);

        addBtn.addEventListener('click', function () {
            var node = tpl.content.cloneNode(true);
            var row = node.querySelector('.medicine-row');
            list.appendChild(node);
            bindRemove(row);
            reindex();
            var name = row.querySelector('[data-field="name"], input');
            if (name) name.focus();
        });

        reindex();
        window.__rxMedicineReindex = reindex;
    })();
</script>
