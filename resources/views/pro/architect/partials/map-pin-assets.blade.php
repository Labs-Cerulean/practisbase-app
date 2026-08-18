{{-- Shared Architect map pin: include once per page before marker creation. --}}
<style>
    .pb-site-pin {
        background: transparent !important;
        border: none !important;
    }
    .pb-site-pin__wrap {
        width: 28px;
        height: 36px;
        display: block;
        filter: drop-shadow(0 2px 4px rgba(15, 23, 42, 0.35));
    }
    .pb-site-pin__wrap svg {
        display: block;
        width: 28px;
        height: 36px;
    }
    .leaflet-popup-content-wrapper {
        border-radius: 10px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.14);
        border: 1px solid #e2e8f0;
    }
    .leaflet-popup-content {
        margin: 0.7rem 0.85rem;
        font-size: 0.82rem;
        line-height: 1.4;
        color: #334155;
        min-width: 140px;
    }
    .pb-pin-popup__title {
        font-weight: 700;
        color: #0f172a;
        font-size: 0.9rem;
        margin-bottom: 0.2rem;
    }
    .pb-pin-popup__meta {
        color: #64748b;
        font-size: 0.78rem;
    }
    .pb-pin-popup__link {
        display: inline-block;
        margin-top: 0.45rem;
        font-weight: 650;
        color: #3f6212;
        text-decoration: none;
    }
</style>
<script>
window.pbArchMapPin = window.pbArchMapPin || {
    icon: function (opts) {
        opts = opts || {};
        var color = opts.color || '#3f6212';
        var html = ''
            + '<span class="pb-site-pin__wrap" aria-hidden="true">'
            + '<svg viewBox="0 0 28 36" xmlns="http://www.w3.org/2000/svg">'
            + '<path d="M14 1.5C7.1 1.5 1.5 7.1 1.5 14c0 9.4 12.5 20.2 12.5 20.2S26.5 23.4 26.5 14C26.5 7.1 20.9 1.5 14 1.5z" fill="' + color + '"/>'
            + '<circle cx="14" cy="14" r="5" fill="#fff"/>'
            + '</svg></span>';
        return L.divIcon({
            className: 'pb-site-pin',
            html: html,
            iconSize: [28, 36],
            iconAnchor: [14, 34],
            popupAnchor: [0, -30]
        });
    },
    popupHtml: function (pin) {
        var title = pin.name || 'Project';
        var meta = [];
        if (pin.client) meta.push(pin.client);
        if (pin.locality) meta.push(pin.locality);
        var html = '<div class="pb-pin-popup__title"></div>';
        var wrap = document.createElement('div');
        wrap.innerHTML = html;
        wrap.querySelector('.pb-pin-popup__title').textContent = title;
        if (meta.length) {
            var m = document.createElement('div');
            m.className = 'pb-pin-popup__meta';
            m.textContent = meta.join(' · ');
            wrap.appendChild(m);
        }
        if (pin.href) {
            var a = document.createElement('a');
            a.className = 'pb-pin-popup__link';
            a.href = pin.href;
            a.textContent = 'Open project';
            wrap.appendChild(a);
        }
        return wrap;
    }
};
</script>
