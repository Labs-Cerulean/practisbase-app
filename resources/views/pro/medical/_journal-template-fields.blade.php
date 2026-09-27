@php
    $templateCatalogue = $templateCatalogue ?? [];
    $selectedKey = $noteTemplate ?? ($templateCatalogue[0]['key'] ?? 'general');
    $fieldValues = $fieldValues ?? [];
@endphp
<div id="journal-template-fields" style="{{ ($visible ?? true) ? '' : 'display:none;' }} margin-bottom: 1rem; padding: 1rem; background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
    <div style="display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; align-items: flex-end; margin-bottom: 0.85rem;">
        <div>
            <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.35rem;">Consult template</div>
            <select name="note_template" id="note_template" style="min-width: 240px; padding: 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                @foreach($templateCatalogue as $row)
                    <option value="{{ $row['key'] }}" {{ $selectedKey === $row['key'] ? 'selected' : '' }}>
                        {{ $row['name'] }}{{ !empty($row['builtin']) ? '' : '' }}
                    </option>
                @endforeach
            </select>
        </div>
        <div style="font-size: 0.75rem; color: var(--text-muted); max-width: 18rem; line-height: 1.4;">
            <a href="/pro/medical/templates" style="color: var(--primary-cerulean); font-weight: 700; text-decoration: none; border-bottom: 1px dotted var(--primary-navy);">Note templates</a>
        </div>
    </div>

    <div id="journal-structured-fields" style="display: grid; gap: 0.75rem;"
         data-seed-defaults="{{ !empty($seedDefaults) ? '1' : '0' }}"
         data-initial-values='@json($fieldValues)'
         data-catalogue='@json($templateCatalogue)'></div>
</div>
<script>
    window.PractisNoteRich = {
        escapeHtml: function (value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        },
        storageToHtml: function (value) {
            return String(value || '').split('\n').map(function (line) {
                var html = window.PractisNoteRich.escapeHtml(line).replace(/\*\*([^*\n]+)\*\*/g, '<strong>$1</strong>');
                return '<div>' + (html === '' ? '<br>' : html) + '</div>';
            }).join('');
        },
        seedHtml: function (defaults) {
            return (defaults || []).map(function (line) {
                var label = String(line || '').trim();
                if (!label) return '';
                if (!/:$/.test(label)) label += ':';
                return '<div><strong>' + window.PractisNoteRich.escapeHtml(label) + '</strong> </div>';
            }).join('');
        },
        htmlToStorage: function (root) {
            var parts = [];
            function walk(node) {
                if (!node) return;
                if (node.nodeType === 3) {
                    parts.push(node.textContent || '');
                    return;
                }
                if (node.nodeType !== 1) return;
                var name = node.nodeName;
                if (name === 'BR') {
                    parts.push('\n');
                    return;
                }
                if (name === 'STRONG' || name === 'B') {
                    var text = node.textContent || '';
                    if (text.trim() !== '') parts.push('**' + text + '**');
                    return;
                }
                var block = name === 'DIV' || name === 'P';
                if (block && parts.length && parts[parts.length - 1] !== '\n') parts.push('\n');
                for (var i = 0; i < node.childNodes.length; i++) walk(node.childNodes[i]);
            }
            for (var i = 0; i < root.childNodes.length; i++) walk(root.childNodes[i]);
            return parts.join('').replace(/^\n+/, '').replace(/\n+$/, '').replace(/\u00a0/g, ' ');
        },
        textControl: function (name, keyAttr, style, val, defaults, seedDefaults) {
            var list = Array.isArray(defaults) ? defaults.filter(function (line) { return String(line || '').trim() !== ''; }) : [];
            var hasMarkup = /\*\*[^*\n]+\*\*/.test(val || '');
            if (!list.length && !hasMarkup) {
                return '<textarea name="' + name + '" ' + keyAttr + ' rows="3" style="' + style + '">' + this.escapeHtml(val) + '</textarea>';
            }
            var html = '';
            if (val && String(val).trim() !== '') html = this.storageToHtml(val);
            else if (seedDefaults && list.length) html = this.seedHtml(list);
            var stored = val && String(val).trim() !== '' ? val : (seedDefaults ? this.htmlFromSeedStorage(list) : '');
            return '<div contenteditable="true" data-rich-field="1" ' + keyAttr + ' style="' + style + 'min-height:4.8rem;background:white;white-space:pre-wrap;line-height:1.5;">' + html + '</div>' +
                '<input type="hidden" name="' + name + '" data-rich-store="1" value="' + this.escapeHtml(stored) + '">';
        },
        htmlFromSeedStorage: function (defaults) {
            return (defaults || []).map(function (line) {
                var label = String(line || '').trim();
                if (!label) return '';
                if (!/:$/.test(label)) label += ':';
                return '**' + label + '** ';
            }).filter(Boolean).join('\n');
        },
        syncHost: function (host) {
            if (!host) return;
            host.querySelectorAll('[data-rich-field]').forEach(function (el) {
                var hidden = el.parentElement ? el.parentElement.querySelector('[data-rich-store]') : null;
                if (!hidden) return;
                hidden.value = window.PractisNoteRich.htmlToStorage(el);
            });
        },
        bind: function (host) {
            if (!host) return;
            if (host.getAttribute('data-rich-bound') !== '1') {
                host.setAttribute('data-rich-bound', '1');
                var form = host.closest('form');
                if (form && form.getAttribute('data-rich-submit') !== '1') {
                    form.setAttribute('data-rich-submit', '1');
                    form.addEventListener('submit', function () {
                        window.PractisNoteRich.syncHost(host);
                    });
                }
            }
            host.querySelectorAll('[data-rich-field]').forEach(function (el) {
                if (el.getAttribute('data-rich-listening') === '1') return;
                el.setAttribute('data-rich-listening', '1');
                el.addEventListener('input', function () {
                    window.PractisNoteRich.syncHost(host);
                });
            });
            this.syncHost(host);
        }
    };
</script>
