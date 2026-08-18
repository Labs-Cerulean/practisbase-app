{{-- Editable site pin. Expects hidden inputs #site_latitude #site_longitude and optional street/locality fields. --}}
@php
    $pinLat = old('latitude', $project?->latitude ?? null);
    $pinLng = old('longitude', $project?->longitude ?? null);
    $mapServerUrl = $mapServerUrl ?? \App\Support\Architect\MapServerLink::home();
@endphp
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
@include('pro.architect.partials.map-pin-assets')
<div style="margin-top: 0.85rem;">
    <div style="display: flex; justify-content: space-between; align-items: baseline; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 0.45rem;">
        <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Site pin</div>
        <div style="display: flex; gap: 0.65rem; flex-wrap: wrap;">
            <button type="button" id="arch-clear-pin" style="background: none; border: none; color: var(--text-muted); font-size: 0.75rem; font-weight: 600; cursor: pointer; padding: 0;">Clear pin</button>
            <a href="{{ $mapServerUrl }}" target="_blank" rel="noopener noreferrer" style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); text-decoration: none;">PA MapServer ↗</a>
        </div>
    </div>
    <p style="margin: 0 0 0.5rem; font-size: 0.8rem; color: var(--text-muted);">Click the map to drop a pin. Street and locality fill from the map point when empty.</p>
    <div id="arch-site-pin-map" style="height: 280px; width: 100%; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: #e8eef5;"></div>
    <input type="hidden" name="latitude" id="site_latitude" value="{{ $pinLat }}">
    <input type="hidden" name="longitude" id="site_longitude" value="{{ $pinLng }}">
    <div id="arch-geocode-hint" style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.4rem;"></div>
</div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
(function () {
    var el = document.getElementById('arch-site-pin-map');
    if (!el || typeof L === 'undefined' || !window.pbArchMapPin) return;
    var latInput = document.getElementById('site_latitude');
    var lngInput = document.getElementById('site_longitude');
    var streetInput = document.querySelector('input[name="site_street"]');
    var localityInput = document.querySelector('input[name="site_locality"]');
    var hint = document.getElementById('arch-geocode-hint');
    var startLat = parseFloat(latInput.value);
    var startLng = parseFloat(lngInput.value);
    var hasStart = !isNaN(startLat) && !isNaN(startLng);
    var map = L.map(el).setView(hasStart ? [startLat, startLng] : [35.94, 14.38], hasStart ? 16 : 10);
    var basemap = @json(\App\Support\Architect\MapBasemap::leafletConfig());
    L.tileLayer(basemap.url, {
        maxZoom: basemap.maxZoom,
        attribution: basemap.attribution,
        subdomains: 'abcd'
    }).addTo(map);
    var pinIcon = window.pbArchMapPin.icon({ color: '#3f6212' });
    var marker = null;
    function setPin(lat, lng, doGeocode) {
        latInput.value = Number(lat).toFixed(7);
        lngInput.value = Number(lng).toFixed(7);
        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng], { icon: pinIcon, draggable: true }).addTo(map);
            marker.on('dragend', function () {
                var p = marker.getLatLng();
                setPin(p.lat, p.lng, true);
            });
        }
        if (doGeocode) reverseGeocode(lat, lng);
    }
    function reverseGeocode(lat, lng) {
        if (!hint) return;
        hint.textContent = 'Looking up street…';
        fetch('/pro/architect/geocode/reverse?lat=' + encodeURIComponent(lat) + '&lng=' + encodeURIComponent(lng), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) { return r.json(); }).then(function (data) {
            if (!data || !data.ok) {
                hint.textContent = 'Could not fill street automatically — type it in.';
                return;
            }
            if (streetInput && !streetInput.value.trim() && data.street) streetInput.value = data.street;
            if (localityInput && !localityInput.value.trim() && data.locality) {
                localityInput.value = data.locality;
                localityInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
            hint.textContent = data.street || data.locality
                ? 'Filled from map point (edit if needed).'
                : 'Pin set — add street manually if needed.';
        }).catch(function () {
            hint.textContent = 'Could not fill street automatically — type it in.';
        });
    }
    if (hasStart) setPin(startLat, startLng, false);
    map.on('click', function (e) {
        setPin(e.latlng.lat, e.latlng.lng, true);
    });
    var clearBtn = document.getElementById('arch-clear-pin');
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            latInput.value = '';
            lngInput.value = '';
            if (marker) { map.removeLayer(marker); marker = null; }
            if (hint) hint.textContent = 'Pin cleared.';
        });
    }
    setTimeout(function () { map.invalidateSize(); }, 80);
})();
</script>
