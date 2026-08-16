@extends('layouts.app')

@section('page_title', $template ? 'Edit template' : 'New template')

@section('content')
    <div style="max-width: 760px; margin: 0 auto;">
        <a href="/pro/medical/templates" style="color: var(--text-muted); font-weight: 600; text-decoration: none; font-size: 0.85rem;">&larr; Templates</a>
        <h1 style="margin: 0.4rem 0 1rem; color: var(--primary-navy); font-size: 1.4rem;">{{ $template ? 'Edit template' : 'New note template' }}</h1>

        @if($errors->any())
            <div style="background: #fef2f2; color: #991b1b; padding: 0.85rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
                @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif

        <form action="{{ $template ? '/pro/medical/templates/'.$template->id : '/pro/medical/templates' }}" method="POST" id="template-builder-form">
            @csrf
            @if($template)
                @method('PUT')
            @endif

            <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.25rem; margin-bottom: 1rem; box-shadow: var(--shadow-sm);">
                <label style="display: block; font-weight: 700; margin-bottom: 0.4rem;">Template name *</label>
                <input type="text" name="name" value="{{ $name }}" required maxlength="120"
                       placeholder="e.g. Follow-up"
                       style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>

            <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.25rem; margin-bottom: 1rem; box-shadow: var(--shadow-sm);">
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap; margin-bottom: 0.85rem;">
                    <div style="font-weight: 700; color: var(--primary-navy);">
                        Fields
                        @include('partials.help-tip', ['text' => 'Drag to reorder. Field keys are generated automatically from labels.'])
                    </div>
                    <button type="button" id="add-field-btn" style="padding: 0.45rem 0.85rem; background: var(--primary-navy); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer; font-size: 0.85rem;">+ Add field</button>
                </div>
                <div id="fields-list" style="display: grid; gap: 0.65rem;"></div>
            </div>

            <button type="submit" style="width: 100%; padding: 0.85rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">
                {{ $template ? 'Save template' : 'Create template' }}
            </button>
        </form>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    var list = document.getElementById('fields-list');
    var addBtn = document.getElementById('add-field-btn');
    var initial = @json($fields);

    function rowHtml(field, index) {
        return '' +
            '<div class="tpl-field-row" draggable="true" style="display:grid; grid-template-columns: 1fr 90px auto; gap:0.5rem; align-items:end; padding:0.75rem; background:#f8fafc; border:1px solid var(--border-light); border-radius:var(--radius-md);">' +
            '<div><label style="display:block;font-size:0.7rem;font-weight:700;color:var(--text-muted);margin-bottom:0.25rem;">Label *</label>' +
            '<input type="text" name="fields[' + index + '][label]" value="' + escapeAttr(field.label || '') + '" required maxlength="80" placeholder="Field label" style="width:100%;padding:0.55rem;border:1px solid var(--border-light);border-radius:var(--radius-md);"></div>' +
            '<input type="hidden" name="fields[' + index + '][key]" value="' + escapeAttr(field.key || '') + '">' +
            '<div><label style="display:block;font-size:0.7rem;font-weight:700;color:var(--text-muted);margin-bottom:0.25rem;">Rows</label>' +
            '<input type="number" name="fields[' + index + '][rows]" value="' + (field.rows || 2) + '" min="1" max="12" style="width:100%;padding:0.55rem;border:1px solid var(--border-light);border-radius:var(--radius-md);"></div>' +
            '<button type="button" data-remove style="padding:0.55rem 0.7rem;border:1px solid #fecaca;background:#fef2f2;color:#991b1b;border-radius:var(--radius-md);font-weight:700;cursor:pointer;">✕</button>' +
            '</div>';
    }

    function escapeAttr(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;');
    }

    function readFields() {
        return Array.prototype.map.call(list.querySelectorAll('.tpl-field-row'), function (row) {
            return {
                label: row.querySelector('[name$="[label]"]').value,
                key: row.querySelector('[name$="[key]"]').value,
                rows: parseInt(row.querySelector('[name$="[rows]"]').value, 10) || 2
            };
        });
    }

    function render(fields) {
        list.innerHTML = fields.map(rowHtml).join('');
        bindRows();
    }

    function reindex() {
        render(readFields());
    }

    var dragEl = null;
    function bindRows() {
        list.querySelectorAll('[data-remove]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var fields = readFields();
                if (fields.length <= 1) return;
                btn.closest('.tpl-field-row').remove();
                reindex();
            });
        });
        list.querySelectorAll('.tpl-field-row').forEach(function (row) {
            row.addEventListener('dragstart', function () { dragEl = row; row.style.opacity = '0.5'; });
            row.addEventListener('dragend', function () { row.style.opacity = '1'; dragEl = null; reindex(); });
            row.addEventListener('dragover', function (e) {
                e.preventDefault();
                if (!dragEl || dragEl === row) return;
                var rect = row.getBoundingClientRect();
                var before = (e.clientY - rect.top) < rect.height / 2;
                list.insertBefore(dragEl, before ? row : row.nextSibling);
            });
        });
    }

    addBtn.addEventListener('click', function () {
        var fields = readFields();
        fields.push({ label: '', key: '', rows: 2 });
        render(fields);
        var inputs = list.querySelectorAll('[name$="[label]"]');
        if (inputs.length) inputs[inputs.length - 1].focus();
    });

    render(initial && initial.length ? initial : [{ label: '', key: '', rows: 2 }]);
})();
</script>
@endpush
