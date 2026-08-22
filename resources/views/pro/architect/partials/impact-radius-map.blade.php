{{-- Impact radius map (satellite default). Expects: $project with pin, $mapServerUrl, optional $mapId/$height/$defaultRadiusM --}}
@php
    $mapId = $mapId ?? 'arch-impact-map';
    $height = $height ?? '360px';
    $defaultRadiusM = (int) ($defaultRadiusM ?? 20);
    $mapServerUrl = $mapServerUrl ?? \App\Support\Architect\MapServerLink::home();
    $pin = $project->mapPinPayload();
@endphp
@if($pin)
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
@include('pro.architect.partials.map-pin-assets')
<div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-sm);">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 0.75rem; flex-wrap: wrap; padding: 0.75rem 0.9rem; border-bottom: 1px solid var(--border-light);">
        <div>
            <div style="font-size: 0.9rem; font-weight: 700; color: var(--primary-navy);">Impact radius</div>
            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.2rem; line-height: 1.4; max-width: 36rem;">
                Working aid only — confirm on site / title. Not legal property boundaries. Click the map to suggest a neighbour address.
            </div>
        </div>
        <a href="{{ $mapServerUrl }}" target="_blank" rel="noopener noreferrer" style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); text-decoration: none; white-space: nowrap;">Open PA MapServer ↗</a>
    </div>

    <div style="display: flex; flex-wrap: wrap; gap: 0.65rem; align-items: center; padding: 0.65rem 0.9rem; border-bottom: 1px solid var(--border-light); background: #f8fafc;">
        <div style="display: flex; gap: 0.35rem; align-items: center;">
            <button type="button" id="{{ $mapId }}-basemap-sat" class="arch-impact-basemap is-active" data-mode="satellite" style="padding: 0.35rem 0.65rem; border-radius: var(--radius-md); font-size: 0.75rem; font-weight: 650; cursor: pointer; border: 1px solid #3f6212; background: #3f6212; color: white;">Satellite</button>
            <button type="button" id="{{ $mapId }}-basemap-str" class="arch-impact-basemap" data-mode="streets" style="padding: 0.35rem 0.65rem; border-radius: var(--radius-md); font-size: 0.75rem; font-weight: 650; cursor: pointer; border: 1px solid var(--border-light); background: white; color: var(--primary-navy);">Streets</button>
        </div>
        <label style="display: flex; align-items: center; gap: 0.45rem; font-size: 0.78rem; color: var(--primary-navy); font-weight: 650;">
            Radius
            <input type="range" id="{{ $mapId }}-radius" min="5" max="100" step="1" value="{{ $defaultRadiusM }}" style="width: 120px; accent-color: #3f6212;">
            <span id="{{ $mapId }}-radius-label" style="min-width: 2.5rem; font-variant-numeric: tabular-nums;">{{ $defaultRadiusM }} m</span>
        </label>
        <div style="display: flex; gap: 0.3rem; flex-wrap: wrap;">
            @foreach([10, 20, 30, 50] as $preset)
                <button type="button" class="arch-impact-preset" data-m="{{ $preset }}" style="padding: 0.28rem 0.5rem; border-radius: var(--radius-md); font-size: 0.72rem; font-weight: 650; cursor: pointer; border: 1px solid var(--border-light); background: white; color: var(--primary-navy);">{{ $preset }} m</button>
            @endforeach
        </div>
        <label style="display: flex; align-items: center; gap: 0.35rem; font-size: 0.75rem; color: var(--text-muted); font-weight: 600;">
            <input type="checkbox" id="{{ $mapId }}-bands" style="accent-color: #3f6212;">
            Show ½ / 1½ rings
        </label>
    </div>

    <div id="{{ $mapId }}" style="height: {{ $height }}; width: 100%; background: #1e293b; cursor: crosshair;"></div>

    <div style="padding: 0.75rem 0.9rem; border-top: 1px solid var(--border-light);">
        <div id="{{ $mapId }}-hint" style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.55rem;">Click a building or point inside the buffer to suggest an address for the neighbour register.</div>
        <div id="{{ $mapId }}-suggestions" style="display: grid; gap: 0.4rem;"></div>
    </div>
</div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
(function () {
    var mapId = @json($mapId);
    var el = document.getElementById(mapId);
    if (!el || typeof L === 'undefined' || !window.pbArchMapPin) return;

    var pin = @json($pin);
    var streets = @json(\App\Support\Architect\MapBasemap::streetsConfig());
    var satellite = @json(\App\Support\Architect\MapBasemap::satelliteConfig());
    var radiusInput = document.getElementById(mapId + '-radius');
    var radiusLabel = document.getElementById(mapId + '-radius-label');
    var bandsToggle = document.getElementById(mapId + '-bands');
    var hint = document.getElementById(mapId + '-hint');
    var suggestionsEl = document.getElementById(mapId + '-suggestions');
    var satBtn = document.getElementById(mapId + '-basemap-sat');
    var strBtn = document.getElementById(mapId + '-basemap-str');

    var map = L.map(el, {
        scrollWheelZoom: true,
        dragging: true,
        touchZoom: true,
        doubleClickZoom: true
    }).setView([pin.lat, pin.lng], 18);

    function makeLayer(cfg) {
        var opts = { maxZoom: cfg.maxZoom, attribution: cfg.attribution };
        if (cfg.subdomains) opts.subdomains = cfg.subdomains;
        return L.tileLayer(cfg.url, opts);
    }

    var satLayer = makeLayer(satellite);
    var streetLayer = makeLayer(streets);
    var activeBase = satLayer;
    satLayer.addTo(map);

    function setBasemap(mode) {
        map.removeLayer(activeBase);
        activeBase = mode === 'streets' ? streetLayer : satLayer;
        activeBase.addTo(map);
        var active = mode === 'streets' ? strBtn : satBtn;
        var inactive = mode === 'streets' ? satBtn : strBtn;
        if (active) {
            active.style.background = '#3f6212';
            active.style.borderColor = '#3f6212';
            active.style.color = 'white';
        }
        if (inactive) {
            inactive.style.background = 'white';
            inactive.style.borderColor = 'var(--border-light)';
            inactive.style.color = 'var(--primary-navy)';
        }
    }
    if (satBtn) satBtn.addEventListener('click', function () { setBasemap('satellite'); });
    if (strBtn) strBtn.addEventListener('click', function () { setBasemap('streets'); });

    var siteIcon = window.pbArchMapPin.icon({ color: '#3f6212' });
    L.marker([pin.lat, pin.lng], { icon: siteIcon }).addTo(map)
        .bindPopup('<strong>' + (pin.name || 'Site') + '</strong><br>Project pin');

    var buffer = L.circle([pin.lat, pin.lng], {
        radius: parseInt(radiusInput.value, 10) || 20,
        color: '#f59e0b',
        weight: 2,
        fillColor: '#fbbf24',
        fillOpacity: 0.18
    }).addTo(map);

    var innerBand = null;
    var outerBand = null;

    function clearBands() {
        if (innerBand) { map.removeLayer(innerBand); innerBand = null; }
        if (outerBand) { map.removeLayer(outerBand); outerBand = null; }
    }

    function redrawBuffer() {
        var r = parseInt(radiusInput.value, 10) || 20;
        if (radiusLabel) radiusLabel.textContent = r + ' m';
        buffer.setRadius(r);
        clearBands();
        if (bandsToggle && bandsToggle.checked) {
            innerBand = L.circle([pin.lat, pin.lng], {
                radius: Math.max(5, Math.round(r * 0.5)),
                color: '#fb923c',
                weight: 1,
                dashArray: '4 4',
                fill: false
            }).addTo(map);
            outerBand = L.circle([pin.lat, pin.lng], {
                radius: Math.round(r * 1.5),
                color: '#fdba74',
                weight: 1,
                dashArray: '4 4',
                fill: false
            }).addTo(map);
        }
        try {
            map.fitBounds(buffer.getBounds().pad(0.35), { maxZoom: 19 });
        } catch (e) {}
    }

    radiusInput.addEventListener('input', redrawBuffer);
    if (bandsToggle) bandsToggle.addEventListener('change', redrawBuffer);
    document.querySelectorAll('.arch-impact-preset').forEach(function (btn) {
        btn.addEventListener('click', function () {
            radiusInput.value = btn.getAttribute('data-m');
            redrawBuffer();
        });
    });

    var clickIcon = window.pbArchMapPin.icon({ color: '#b45309' });
    var clickMarker = null;
    var suggestions = [];

    function haversineM(lat1, lng1, lat2, lng2) {
        var R = 6371000;
        var toRad = Math.PI / 180;
        var dLat = (lat2 - lat1) * toRad;
        var dLng = (lng2 - lng1) * toRad;
        var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(lat1 * toRad) * Math.cos(lat2 * toRad) *
            Math.sin(dLng / 2) * Math.sin(dLng / 2);
        return 2 * R * Math.asin(Math.sqrt(a));
    }

    function fillNeighbourForm(item) {
        var form = document.getElementById('neighbour-add-form');
        var address = document.getElementById('neighbour-add-address');
        var street = document.getElementById('neighbour-add-street');
        var locality = document.getElementById('neighbour-add-locality');
        var lat = document.getElementById('neighbour-add-latitude');
        var lng = document.getElementById('neighbour-add-longitude');
        if (address) address.value = item.address || item.display || '';
        if (street) street.value = item.street || '';
        if (locality) locality.value = item.locality || '';
        if (lat) lat.value = item.lat != null ? Number(item.lat).toFixed(7) : '';
        if (lng) lng.value = item.lng != null ? Number(item.lng).toFixed(7) : '';
        if (form) {
            form.scrollIntoView({ behavior: 'smooth', block: 'center' });
            if (address) address.focus();
        }
        if (hint) {
            hint.textContent = 'Address filled in Add neighbour — edit if needed, then save.';
            hint.style.color = '#3f6212';
        }
    }

    function renderSuggestions() {
        if (!suggestionsEl) return;
        suggestionsEl.innerHTML = '';
        if (!suggestions.length) return;
        suggestions.slice().reverse().forEach(function (item, idx) {
            var row = document.createElement('div');
            row.style.cssText = 'display:flex;justify-content:space-between;gap:0.5rem;flex-wrap:wrap;align-items:center;border:1px solid var(--border-light);border-radius:var(--radius-md);padding:0.55rem 0.7rem;background:#fffbeb;';
            var left = document.createElement('div');
            left.style.cssText = 'min-width:0;flex:1;';
            var title = document.createElement('div');
            title.style.cssText = 'font-weight:650;color:var(--primary-navy);font-size:0.85rem;';
            title.textContent = item.address || item.display || 'Map point';
            var meta = document.createElement('div');
            meta.style.cssText = 'font-size:0.72rem;color:var(--text-muted);';
            meta.textContent = Math.round(item.distanceM) + ' m from pin' +
                (item.outside ? ' · outside buffer — confirm on site' : '');
            left.appendChild(title);
            left.appendChild(meta);
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = 'Use in form';
            btn.style.cssText = 'background:#3f6212;color:white;border:none;border-radius:var(--radius-md);padding:0.4rem 0.75rem;font-weight:650;font-size:0.78rem;cursor:pointer;';
            btn.addEventListener('click', function () { fillNeighbourForm(item); });
            row.appendChild(left);
            row.appendChild(btn);
            suggestionsEl.appendChild(row);
            if (idx === 0) {
                // newest already at top via reverse
            }
        });
    }

    function reverseGeocode(lat, lng, distanceM, outside) {
        if (hint) {
            hint.textContent = 'Looking up address…';
            hint.style.color = 'var(--text-muted)';
        }
        fetch('/pro/architect/geocode/reverse?lat=' + encodeURIComponent(lat) + '&lng=' + encodeURIComponent(lng), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) { return r.json(); }).then(function (data) {
            if (!data || !data.ok) {
                if (hint) hint.textContent = 'Could not resolve address — type it manually in the register.';
                return;
            }
            var address = data.display_name || [data.street, data.locality].filter(Boolean).join(', ');
            var item = {
                lat: lat,
                lng: lng,
                street: data.street || '',
                locality: data.locality || '',
                display: data.display_name || '',
                address: address,
                distanceM: distanceM,
                outside: outside
            };
            suggestions.push(item);
            if (suggestions.length > 6) suggestions.shift();
            renderSuggestions();
            fillNeighbourForm(item);
        }).catch(function () {
            if (hint) hint.textContent = 'Could not resolve address — type it manually in the register.';
        });
    }

    map.on('click', function (e) {
        var lat = e.latlng.lat;
        var lng = e.latlng.lng;
        var r = parseInt(radiusInput.value, 10) || 20;
        var d = haversineM(pin.lat, pin.lng, lat, lng);
        var outside = d > r;
        if (clickMarker) map.removeLayer(clickMarker);
        clickMarker = L.marker([lat, lng], { icon: clickIcon }).addTo(map);
        reverseGeocode(lat, lng, d, outside);
    });

    redrawBuffer();
    setTimeout(function () { map.invalidateSize(); }, 80);
})();
</script>
@endif
