<script>
    (function () {
        function cssEscape(value) {
            if (window.CSS && CSS.escape) return CSS.escape(value);
            return String(value).replace(/"/g, '\\"');
        }

        function meaningful(data) {
            return Object.keys(data).some(function (key) {
                if (key === '_token' || key === '_method' || key === 'entry_type' || key === 'entry_date' || key === 'title' || key === 'note_template' || key === 'attachment') {
                    return false;
                }
                var value = String(data[key] || '').trim();
                return value !== '' && value !== '1.';
            });
        }

        function collect(form) {
            var data = {};
            if (window.PractisNoteRich) {
                form.querySelectorAll('#journal-structured-fields').forEach(function (host) {
                    window.PractisNoteRich.syncHost(host);
                });
            }
            form.querySelectorAll('input, textarea, select').forEach(function (el) {
                if (!el.name || el.type === 'file' || el.type === 'password' || el.type === 'submit' || el.type === 'button') return;
                if ((el.type === 'checkbox' || el.type === 'radio') && !el.checked) return;
                data[el.name] = el.value;
            });
            return data;
        }

        function applyField(form, name, value) {
            var nodes = form.querySelectorAll('[name="' + cssEscape(name) + '"]');
            nodes.forEach(function (el) {
                if (el.type === 'radio' || el.type === 'checkbox') {
                    el.checked = el.value === value;
                    return;
                }
                el.value = value;
            });
            var match = /^fields\[(.+)\]$/.exec(name);
            if (!match || !window.PractisNoteRich) return;
            form.querySelectorAll('[data-rich-field]').forEach(function (rich) {
                if (rich.getAttribute('data-field-key') !== match[1]) return;
                rich.innerHTML = window.PractisNoteRich.storageToHtml(value);
            });
        }

        document.querySelectorAll('form[data-note-draft]').forEach(function (form) {
            if (form.getAttribute('data-draft-bound') === '1') return;
            form.setAttribute('data-draft-bound', '1');
            var key = 'pb-note:' + form.getAttribute('data-note-draft');
            var banner = form.querySelector('[data-note-draft-banner]');
            var timer = null;

            function save() {
                var data = collect(form);
                try {
                    if (!meaningful(data)) {
                        sessionStorage.removeItem(key);
                        return;
                    }
                    sessionStorage.setItem(key, JSON.stringify(data));
                } catch (e) {}
            }

            function restore() {
                if (form.getAttribute('data-note-draft-hold') === '1') return;
                var raw = null;
                try { raw = sessionStorage.getItem(key); } catch (e) { return; }
                if (!raw) return;
                var data = null;
                try { data = JSON.parse(raw); } catch (e) { return; }
                if (!data || !meaningful(data)) return;

                var template = form.querySelector('[name="note_template"]');
                if (template && data.note_template && template.value !== data.note_template) {
                    template.value = data.note_template;
                    template.dispatchEvent(new Event('change', { bubbles: true }));
                }
                Object.keys(data).forEach(function (name) {
                    if (name === 'note_template' || name === '_token' || name === '_method') return;
                    applyField(form, name, data[name]);
                });
                if (window.PractisNoteRich) {
                    form.querySelectorAll('#journal-structured-fields').forEach(function (host) {
                        window.PractisNoteRich.syncHost(host);
                    });
                }
                if (banner) banner.hidden = false;
                document.dispatchEvent(new CustomEvent('note-draft-restored'));
            }

            form.addEventListener('input', function () {
                clearTimeout(timer);
                timer = setTimeout(save, 120);
            });
            form.addEventListener('change', save);
            form.addEventListener('focusout', save);
            restore();
        });
    })();
</script>
