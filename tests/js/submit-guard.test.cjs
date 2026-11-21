const fs = require('fs');
const path = require('path');
const vm = require('vm');

function assert(condition, message) {
    if (!condition) {
        throw new Error(message);
    }
}

function Element(tag) {
    this.tagName = String(tag).toUpperCase();
    this.nodeName = this.tagName;
    this.attributes = {};
    this.children = [];
    this.parentNode = null;
    this.style = {};
    this.disabled = false;
    this.value = '';
    this.type = '';
    this.id = '';
    this.form = null;
    this._text = '';
    this._html = '';
    this._listeners = { true: {}, false: {} };
    elements.push(this);
}

Object.defineProperty(Element.prototype, 'textContent', {
    get() { return this._text; },
    set(value) {
        this._text = String(value);
        this._html = String(value);
    }
});

Object.defineProperty(Element.prototype, 'innerHTML', {
    get() { return this._html; },
    set(value) {
        this._html = String(value);
        this._text = String(value).replace(/<[^>]+>/g, '');
    }
});

Element.prototype.setAttribute = function (name, value) {
    this.attributes[name] = String(value);
    if (name === 'id') this.id = String(value);
    if (name === 'type') this.type = String(value);
};

Element.prototype.getAttribute = function (name) {
    return Object.prototype.hasOwnProperty.call(this.attributes, name) ? this.attributes[name] : null;
};

Element.prototype.removeAttribute = function (name) {
    delete this.attributes[name];
};

Element.prototype.addEventListener = function (type, fn, capture) {
    const cap = capture === true;
    const bag = this._listeners[cap];
    (bag[type] || (bag[type] = [])).push(fn);
};

Element.prototype.appendChild = function (child) {
    child.parentNode = this;
    this.children.push(child);
    return child;
};

Element.prototype.removeChild = function (child) {
    this.children = this.children.filter((item) => item !== child);
    child.parentNode = null;
};

function matches(el, selector) {
    const parts = selector.split(',').map((part) => part.trim());
    return parts.some((part) => {
        const attr = part.match(/^([a-z]*)\[([^\]]+)\]$/i);
        if (attr) {
            const tag = attr[1].toUpperCase();
            if (tag && el.tagName !== tag) return false;
            return Object.prototype.hasOwnProperty.call(el.attributes, attr[2]);
        }
        return el.tagName === part.toUpperCase();
    });
}

function walk(el, visit) {
    el.children.forEach((child) => {
        visit(child);
        walk(child, visit);
    });
}

Element.prototype.querySelectorAll = function (selector) {
    const found = [];
    walk(this, (child) => {
        if (matches(child, selector)) found.push(child);
    });
    return found;
};

Element.prototype.querySelector = function (selector) {
    return this.querySelectorAll(selector)[0] || null;
};

const elements = [];

function HTMLFormElement() {}
HTMLFormElement.prototype.submit = function () {
    this._nativeSubmits = (this._nativeSubmits || 0) + 1;
};

function Form() {
    Element.call(this, 'form');
}
Form.prototype = Object.create(HTMLFormElement.prototype);
Object.getOwnPropertyNames(Element.prototype).forEach((key) => {
    if (key === 'constructor') return;
    Object.defineProperty(Form.prototype, key, Object.getOwnPropertyDescriptor(Element.prototype, key));
});

const jar = {};
const document = {
    _listeners: { true: {}, false: {} },
    addEventListener: Element.prototype.addEventListener,
    createElement(tag) {
        return new Element(tag);
    },
    getElementById(id) {
        return elements.find((el) => el.id === id) || null;
    },
    querySelectorAll(selector) {
        return elements.filter((el) => matches(el, selector));
    }
};

Object.defineProperty(document, 'cookie', {
    get() {
        return Object.keys(jar).filter((name) => jar[name] !== '').map((name) => name + '=' + jar[name]).join('; ');
    },
    set(raw) {
        const pair = String(raw).split(';')[0];
        const eq = pair.indexOf('=');
        const name = pair.slice(0, eq).trim();
        const value = pair.slice(eq + 1);
        jar[name] = /max-age=0/i.test(raw) ? '' : value;
    }
});

const window = {
    _listeners: { true: {}, false: {} },
    addEventListener: Element.prototype.addEventListener,
    setTimeout,
    setInterval,
    clearInterval,
    clearTimeout,
    document
};

function runList(target, type, event, capture) {
    const list = ((target._listeners[capture] && target._listeners[capture][type]) || []).slice();
    for (let i = 0; i < list.length; i++) {
        list[i].call(target, event);
        if (event.stopImm) return;
    }
}

function makeEvent(type, target) {
    return {
        type,
        target,
        defaultPrevented: false,
        stopImm: false,
        preventDefault() { this.defaultPrevented = true; },
        stopPropagation() { this.stopped = true; },
        stopImmediatePropagation() { this.stopped = true; this.stopImm = true; }
    };
}

function dispatchSubmit(form, submitter) {
    const event = makeEvent('submit', form);
    event.submitter = submitter || null;
    runList(window, 'submit', event, true);
    if (event.stopped) return event;
    if (typeof form.onsubmit === 'function') {
        const result = form.onsubmit(event);
        if (result === false) event.preventDefault();
    }
    runList(form, 'submit', event, false);
    if (event.stopped) return event;
    runList(window, 'submit', event, false);
    return event;
}

function ownerForm(control) {
    if (control.form) return control.form;
    const id = control.getAttribute('form');
    return id ? document.getElementById(id) : null;
}

function click(button) {
    const event = makeEvent('click', button);
    runList(document, 'click', event, true);
    if (event.defaultPrevented || event.stopped) return event;
    const form = ownerForm(button);
    if (!form) return event;
    return dispatchSubmit(form, button);
}

global.window = window;
global.document = document;
global.HTMLFormElement = HTMLFormElement;

vm.runInThisContext(fs.readFileSync(path.join(__dirname, '../../public/js/submit-guard.js'), 'utf8'));

function button(label, form) {
    const el = new Element('button');
    el.setAttribute('type', 'submit');
    el.textContent = label;
    el.form = form;
    form.appendChild(el);
    return el;
}

async function main() {
    const pay = new Form();
    const save = button('Save payment', pay);
    save.value = '1';
    save.setAttribute('name', 'save');
    let handlerRuns = 0;
    pay.addEventListener('submit', () => { handlerRuns += 1; });

    click(save);
    assert(handlerRuns === 1, 'the first submit reaches the form');
    assert(save.textContent === 'Working…', 'the button shows that it is working');
    assert(save.getAttribute('aria-busy') === 'true', 'the button is marked busy');
    assert(save.disabled === false, 'the submitter stays enabled until the request is built');
    assert(save.value === '1', 'the submit button value is unchanged');
    assert(pay.getAttribute('data-pb-submitting') === '1', 'the form is marked as submitting');

    click(save);
    assert(handlerRuns === 1, 'a second click does not submit again');
    assert(save.textContent === 'Working…', 'the button still shows that it is working');

    await new Promise((resolve) => setTimeout(resolve, 0));
    assert(save.disabled === true, 'the button ignores further clicks after the request starts');

    const modal = new Form();
    const email = button('Email', modal);
    modal.addEventListener('submit', (event) => { event.preventDefault(); });
    click(email);
    assert(email.textContent === 'Email', 'a cancelled confirm does not change the button');
    assert(modal.getAttribute('data-pb-submitting') === null, 'a cancelled confirm does not lock the form');

    modal.submit();
    assert(modal._nativeSubmits === 1, 'the confirmed request is sent once');
    assert(email.textContent === 'Working…', 'the confirmed request shows that it is working');
    modal.submit();
    assert(modal._nativeSubmits === 1, 'a second programmatic submit is ignored');

    const confirmed = new Form();
    confirmed.onsubmit = () => false;
    const remove = button('Remove', confirmed);
    click(remove);
    assert(remove.textContent === 'Remove', 'cancelling the browser confirm leaves the label');
    assert(confirmed.getAttribute('data-pb-submitting') === null, 'cancelling the browser confirm does not lock');

    const held = new Form();
    const locked = button('Cannot save', held);
    locked.disabled = true;
    held.submit();
    const freshShow = makeEvent('pageshow', window);
    freshShow.persisted = false;
    runList(window, 'pageshow', freshShow, false);
    assert(locked.textContent === 'Working…', 'the first load does not clear a submit that is still running');
    const backShow = makeEvent('pageshow', window);
    backShow.persisted = true;
    runList(window, 'pageshow', backShow, false);
    assert(locked.textContent === 'Cannot save', 'leaving the page restores the label');
    assert(locked.disabled === true, 'a button that was already disabled stays disabled');
    assert(held.getAttribute('data-pb-submitting') === null, 'back to the page clears the lock');

    const filters = new Form();
    filters.submit();
    const note = filters.querySelector('span[data-pb-working]');
    assert(note && note.textContent === 'Working…', 'a filter with no button still shows that it is working');

    const download = new Form();
    const zip = button('Download ZIP', download);
    click(zip);
    const token = download.getAttribute('data-pb-token');
    assert(/^[a-z0-9]{16,40}$/.test(token), 'the download wait token is set');
    jar.pb_download = 'not-this-request';
    await new Promise((resolve) => setTimeout(resolve, 250));
    assert(zip.textContent === 'Working…', 'a different download does not release this form');
    jar.pb_download = token;
    await new Promise((resolve) => setTimeout(resolve, 250));
    assert(zip.textContent === 'Download ZIP', 'the matching download cookie releases the button');
    assert(download.getAttribute('data-pb-submitting') === null, 'the form accepts another download');
    assert(zip.disabled === false, 'the download button can be used again');

    const outside = new Form();
    outside.setAttribute('id', 'outside');
    const linked = new Element('button');
    linked.setAttribute('type', 'submit');
    linked.setAttribute('form', 'outside');
    linked.textContent = 'Post outside';
    linked.form = null;
    click(linked);
    assert(linked.textContent === 'Working…', 'a submit button outside the form is guarded');

    elements.forEach((el) => {
        if (el.nodeName === 'FORM' && el.getAttribute('data-pb-submitting') === '1') {
            window.pbSubmitGuard.release(el);
        }
    });

    console.log('submit guard ok');
}

main().catch((error) => {
    console.error(error);
    process.exit(1);
});
