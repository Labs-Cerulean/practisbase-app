(function () {
    if (window.pbSubmitGuard) return;

    var originals = new WeakMap();
    var watches = new WeakMap();
    var WAIT_COOKIE = 'pb_download_wait';
    var DONE_COOKIE = 'pb_download';

    function isForm(node) {
        return !!(node && node.nodeName === 'FORM');
    }

    function isSubmitControl(el) {
        if (!el || !el.tagName) return false;
        if (el.tagName === 'INPUT') return String(el.type || '').toLowerCase() === 'submit';
        if (el.tagName === 'BUTTON') {
            return (el.getAttribute('type') || 'submit').toLowerCase() === 'submit';
        }
        return false;
    }

    function controlsFor(form) {
        var found = [];
        var nodes = form.querySelectorAll ? form.querySelectorAll('button, input') : [];
        var i;
        for (i = 0; i < nodes.length; i++) {
            if (isSubmitControl(nodes[i])) found.push(nodes[i]);
        }
        if (form.id && document.querySelectorAll) {
            var outside = document.querySelectorAll('button[form], input[form]');
            for (i = 0; i < outside.length; i++) {
                var el = outside[i];
                if (el.getAttribute('form') === form.id && isSubmitControl(el) && found.indexOf(el) === -1) {
                    found.push(el);
                }
            }
        }
        return found;
    }

    function remember(el) {
        var saved = originals.get(el);
        if (saved) return saved;
        saved = {
            html: el.tagName === 'BUTTON' ? el.innerHTML : null,
            value: el.tagName === 'INPUT' ? el.value : null,
            disabled: !!el.disabled,
            cursor: el.style ? (el.style.cursor || '') : '',
            opacity: el.style ? (el.style.opacity || '') : '',
            frozen: false
        };
        originals.set(el, saved);
        return saved;
    }

    function snapshot(form) {
        var controls = controlsFor(form);
        for (var i = 0; i < controls.length; i++) remember(controls[i]);
    }

    function freeze(form) {
        var controls = controlsFor(form);
        for (var i = 0; i < controls.length; i++) remember(controls[i]).frozen = true;
    }

    function forgetUnfrozen(form) {
        var controls = controlsFor(form);
        for (var i = 0; i < controls.length; i++) {
            var saved = originals.get(controls[i]);
            if (saved && !saved.frozen) originals.delete(controls[i]);
        }
    }

    function paint(form) {
        var controls = controlsFor(form);
        var i;
        for (i = 0; i < controls.length; i++) {
            var el = controls[i];
            remember(el).frozen = true;
            if (el.tagName === 'BUTTON') el.textContent = 'Working…';
            el.setAttribute('aria-busy', 'true');
            el.setAttribute('aria-disabled', 'true');
            el.setAttribute('data-pb-working', '1');
            if (el.style) {
                el.style.cursor = 'wait';
                el.style.opacity = '0.72';
            }
        }
        form.setAttribute('aria-busy', 'true');
        if (form.style) form.style.cursor = 'wait';
        if (!controls.length && form.querySelector && !form.querySelector('[data-pb-working]')) {
            var note = document.createElement('span');
            note.setAttribute('data-pb-working', '1');
            note.textContent = 'Working…';
            note.style.cssText = 'margin-left:0.5rem;font-size:0.8rem;font-weight:650;color:var(--primary-navy);';
            form.appendChild(note);
        }
    }

    function lockControls(form) {
        if (!form.getAttribute || form.getAttribute('data-pb-submitting') !== '1') return;
        var controls = controlsFor(form);
        for (var i = 0; i < controls.length; i++) {
            var el = controls[i];
            if (el.getAttribute('data-pb-locked') === '1') continue;
            if (el.tagName === 'INPUT') el.value = 'Working…';
            el.disabled = true;
            el.setAttribute('data-pb-locked', '1');
        }
    }

    function writeCookie(name, value, maxAge) {
        document.cookie = name + '=' + value + '; Path=/; Max-Age=' + maxAge + '; SameSite=Lax';
    }

    function readCookie(name) {
        var parts = String(document.cookie || '').split(';');
        var prefix = name + '=';
        for (var i = 0; i < parts.length; i++) {
            var part = parts[i].replace(/^\s+/, '');
            if (part.indexOf(prefix) === 0) return decodeURIComponent(part.slice(prefix.length));
        }
        return '';
    }

    function newToken() {
        var alphabet = 'abcdefghijklmnopqrstuvwxyz0123456789';
        var out = '';
        for (var i = 0; i < 24; i++) out += alphabet.charAt(Math.floor(Math.random() * alphabet.length));
        return out;
    }

    function armWait(form) {
        var token = newToken();
        form.setAttribute('data-pb-token', token);
        writeCookie(WAIT_COOKIE, token, 180);
        writeCookie(DONE_COOKIE, '', 0);
        return token;
    }

    function stopWatch(form) {
        var timer = watches.get(form);
        if (!timer) return;
        window.clearInterval(timer);
        watches.delete(form);
    }

    function release(form) {
        if (!isForm(form)) return;
        stopWatch(form);
        form.removeAttribute('data-pb-submitting');
        form.removeAttribute('aria-busy');
        form.removeAttribute('data-pb-token');
        if (form.style) form.style.cursor = '';
        var controls = controlsFor(form);
        for (var i = 0; i < controls.length; i++) {
            var el = controls[i];
            var saved = originals.get(el);
            if (saved) {
                if (el.tagName === 'INPUT' && saved.value != null) el.value = saved.value;
                if (el.tagName === 'BUTTON' && saved.html != null) el.innerHTML = saved.html;
                if (el.style) {
                    el.style.cursor = saved.cursor;
                    el.style.opacity = saved.opacity;
                }
                el.disabled = !!saved.disabled;
                originals.delete(el);
            } else if (el.getAttribute('data-pb-locked') === '1') {
                el.disabled = false;
            }
            el.removeAttribute('aria-busy');
            el.removeAttribute('aria-disabled');
            el.removeAttribute('data-pb-working');
            el.removeAttribute('data-pb-locked');
        }
        if (form.querySelectorAll) {
            var notes = form.querySelectorAll('span[data-pb-working]');
            for (var n = 0; n < notes.length; n++) {
                if (notes[n].parentNode) notes[n].parentNode.removeChild(notes[n]);
            }
        }
    }

    function watchDownload(form) {
        stopWatch(form);
        var token = form.getAttribute('data-pb-token');
        var ticks = 0;
        var timer = window.setInterval(function () {
            ticks++;
            if (!form.getAttribute || form.getAttribute('data-pb-submitting') !== '1') {
                stopWatch(form);
                return;
            }
            if (token && readCookie(DONE_COOKIE) === token) {
                writeCookie(DONE_COOKIE, '', 0);
                writeCookie(WAIT_COOKIE, '', 0);
                release(form);
                return;
            }
            if (ticks > 6000) stopWatch(form);
        }, 100);
        watches.set(form, timer);
    }

    function commit(form) {
        if (form.getAttribute('data-pb-submitting') === '1') return;
        freeze(form);
        form.setAttribute('data-pb-submitting', '1');
        armWait(form);
        paint(form);
        window.setTimeout(function () { lockControls(form); }, 0);
        watchDownload(form);
    }

    window.addEventListener('submit', function (event) {
        var form = event.target;
        if (!isForm(form)) return;
        if (form.getAttribute('data-pb-submitting') === '1') {
            event.preventDefault();
            event.stopImmediatePropagation();
            return;
        }
        snapshot(form);
    }, true);

    window.addEventListener('submit', function (event) {
        var form = event.target;
        if (!isForm(form)) return;
        if (event.defaultPrevented) {
            forgetUnfrozen(form);
            return;
        }
        if (form.getAttribute('data-pb-submitting') === '1') {
            event.preventDefault();
            return;
        }
        commit(form);
    }, false);

    document.addEventListener('click', function (event) {
        var el = event.target;
        var control = null;
        while (el && el !== document) {
            if (isSubmitControl(el)) {
                control = el;
                break;
            }
            el = el.parentNode;
        }
        if (!control) return;
        var form = control.form || null;
        if (!form && control.getAttribute && control.getAttribute('form') && document.getElementById) {
            form = document.getElementById(control.getAttribute('form'));
        }
        if (!isForm(form) || form.getAttribute('data-pb-submitting') !== '1') return;
        event.preventDefault();
        event.stopPropagation();
    }, true);

    var nativeSubmit = HTMLFormElement.prototype.submit;
    HTMLFormElement.prototype.submit = function () {
        if (this.getAttribute('data-pb-submitting') === '1') return;
        freeze(this);
        this.setAttribute('data-pb-submitting', '1');
        armWait(this);
        nativeSubmit.call(this);
        paint(this);
        var form = this;
        window.setTimeout(function () { lockControls(form); }, 0);
        watchDownload(form);
    };

    window.addEventListener('pageshow', function (event) {
        if (!event || !event.persisted || !document.querySelectorAll) return;
        var forms = document.querySelectorAll('form[data-pb-submitting]');
        for (var i = 0; i < forms.length; i++) release(forms[i]);
    });

    window.pbSubmitGuard = { release: release };
})();
