{{-- Read-only portfolio map. Expects: $mapId, $pins (array), optional $height, $mapServerUrl --}}
@php
    $mapId = $mapId ?? 'arch-map';
    $pins = $pins ?? [];
    $height = $height ?? '320px';
    $mapServerUrl = $mapServerUrl ?? \App\Support\Architect\MapServerLink::home();
@endphp
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
@include('pro.architect.partials.map-pin-assets')
<div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-sm);">
    <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; flex-wrap: wrap; padding: 0.65rem 0.85rem; border-bottom: 1px solid var(--border-light);">
        <div>
            <div style="font-size: 0.78rem; font-weight: 700; color: var(--primary-navy);">
                {{ count($pins) }} site{{ count($pins) === 1 ? '' : 's' }} on map
            </div>
            <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.15rem;">Scroll to zoom · drag to pan · pinch on touch</div>
        </div>
        <a href="{{ $mapServerUrl }}" target="_blank" rel="noopener noreferrer" style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); text-decoration: none;">Open PA MapServer ↗</a>
    </div>
    <div id="{{ $mapId }}" style="height: {{ $height }}; width: 100%; background: #e8eef5; cursor: grab;"></div>
    @if(count($pins) === 0)
        <div style="padding: 0.65rem 0.85rem; font-size: 0.8rem; color: var(--text-muted); border-top: 1px solid var(--border-light);">
            Pin a site when editing a project — drop a marker on Malta or Gozo.
        </div>
    @endif
</div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
(function () {
    var el = document.getElementById(@json($mapId));
    if (!el || typeof L === 'undefined' || !window.pbArchMapPin) return;
    var pins = @json($pins);
    var map = L.map(el, {
        scrollWheelZoom: true,
        dragging: true,
        touchZoom: true,
        doubleClickZoom: true,
        boxZoom: true,
        keyboard: true
    }).setView([35.94, 14.38], 10);
    var basemap = @json(\App\Support\Architect\MapBasemap::leafletConfig());
    L.tileLayer(basemap.url, {
        maxZoom: basemap.maxZoom,
        attribution: basemap.attribution,
        subdomains: 'abcd'
    }).addTo(map);
    var icon = window.pbArchMapPin.icon({ color: '#3f6212' });
    var bounds = [];
    pins.forEach(function (pin) {
        var m = L.marker([pin.lat, pin.lng], { icon: icon }).addTo(map);
        m.bindPopup(window.pbArchMapPin.popupHtml(pin));
        bounds.push([pin.lat, pin.lng]);
    });
    if (bounds.length === 1) {
        map.setView(bounds[0], 15);
    } else if (bounds.length > 1) {
        map.fitBounds(bounds, { padding: [28, 28], maxZoom: 14 });
    }
    map.scrollWheelZoom.enable();
    el.addEventListener('mouseenter', function () { map.scrollWheelZoom.enable(); });
    setTimeout(function () { map.invalidateSize(); }, 80);
})();
</script>
