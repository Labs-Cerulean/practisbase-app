{{-- Impact offset map (satellite default). Buffer from drawn site polygon when present; else site pin. --}}
@php
    $mapId = $mapId ?? 'arch-impact-map';
    $height = $height ?? '380px';
    $defaultRadiusM = (int) ($defaultRadiusM ?? 20);
    $mapServerUrl = $mapServerUrl ?? \App\Support\Architect\MapServerLink::home();
    $pin = $project->mapPinPayload();
    $boundary = $project->siteBoundaryPolygon();
    $saveBoundaryUrl = '/pro/architect/projects/'.$project->id.'/site-boundary';
@endphp
@if($pin)
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
@include('pro.architect.partials.map-pin-assets')
<div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-sm);">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 0.75rem; flex-wrap: wrap; padding: 0.75rem 0.9rem; border-bottom: 1px solid var(--border-light);">
        <div>
            <div style="font-size: 0.9rem; font-weight: 700; color: var(--primary-navy);">Impact offset</div>
            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.2rem; line-height: 1.4; max-width: 38rem;">
                Draw the <strong>site outline</strong> — impact is an offset from that boundary, not the pin.
                Working aid only — confirm on site / title. Not legal property boundaries.
            </div>
        </div>
        <a href="{{ $mapServerUrl }}" target="_blank" rel="noopener noreferrer" style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); text-decoration: none; white-space: nowrap;">Open PA MapServer ↗</a>
    </div>

    <div style="display: flex; flex-wrap: wrap; gap: 0.55rem; align-items: center; padding: 0.65rem 0.9rem; border-bottom: 1px solid var(--border-light); background: #f8fafc;">
        <div style="display: flex; gap: 0.35rem; align-items: center;">
            <button type="button" id="{{ $mapId }}-basemap-sat" style="padding: 0.35rem 0.65rem; border-radius: var(--radius-md); font-size: 0.75rem; font-weight: 650; cursor: pointer; border: 1px solid #3f6212; background: #3f6212; color: white;">Satellite</button>
            <button type="button" id="{{ $mapId }}-basemap-str" style="padding: 0.35rem 0.65rem; border-radius: var(--radius-md); font-size: 0.75rem; font-weight: 650; cursor: pointer; border: 1px solid var(--border-light); background: white; color: var(--primary-navy);">Streets</button>
        </div>
        <div style="width: 1px; height: 1.4rem; background: #e2e8f0;"></div>
        <button type="button" id="{{ $mapId }}-draw" style="padding: 0.35rem 0.65rem; border-radius: var(--radius-md); font-size: 0.75rem; font-weight: 650; cursor: pointer; border: 1px solid var(--border-light); background: white; color: var(--primary-navy);">Draw site</button>
        <button type="button" id="{{ $mapId }}-finish" style="display: none; padding: 0.35rem 0.65rem; border-radius: var(--radius-md); font-size: 0.75rem; font-weight: 650; cursor: pointer; border: 1px solid #3f6212; background: #3f6212; color: white;">Finish outline</button>
        <button type="button" id="{{ $mapId }}-undo" style="display: none; padding: 0.35rem 0.65rem; border-radius: var(--radius-md); font-size: 0.75rem; font-weight: 650; cursor: pointer; border: 1px solid var(--border-light); background: white; color: var(--primary-navy);">Undo corner</button>
        <button type="button" id="{{ $mapId }}-clear" style="padding: 0.35rem 0.65rem; border-radius: var(--radius-md); font-size: 0.75rem; font-weight: 650; cursor: pointer; border: 1px solid var(--border-light); background: white; color: #b91c1c;">Clear outline</button>
        <div style="width: 1px; height: 1.4rem; background: #e2e8f0;"></div>
        <label style="display: flex; align-items: center; gap: 0.45rem; font-size: 0.78rem; color: var(--primary-navy); font-weight: 650;">
            Offset
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
            Show ½ / 1½ offsets
        </label>
    </div>

    <div id="{{ $mapId }}" style="height: {{ $height }}; width: 100%; background: #1e293b; cursor: crosshair;"></div>

    <div style="padding: 0.75rem 0.9rem; border-top: 1px solid var(--border-light);">
        <div id="{{ $mapId }}-hint" style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.55rem;">
            @if($boundary)
                Site outline loaded. Offset follows the boundary. Click outside the site (in the amber band) to suggest a neighbour address.
            @else
                No site outline yet — impact uses the pin as a fallback. Click <strong>Draw site</strong>, tap corners on the satellite view, then <strong>Finish outline</strong>.
            @endif
        </div>
        <div id="{{ $mapId }}-suggestions" style="display: grid; gap: 0.4rem;"></div>
    </div>
</div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="https://cdn.jsdelivr.net/npm/@turf/turf@6.5.0/turf.min.js"></script>
<script>
(function () {
    var mapId = @json($mapId);
    var el = document.getElementById(mapId);
    if (!el || typeof L === 'undefined' || !window.pbArchMapPin || typeof turf === 'undefined') return;

    var pin = @json($pin);
    var savedBoundary = @json($boundary);
    var saveUrl = @json($saveBoundaryUrl);
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    var streets = @json(\App\Support\Architect\MapBasemap::streetsConfig());
    var satellite = @json(\App\Support\Architect\MapBasemap::satelliteConfig());

    var radiusInput = document.getElementById(mapId + '-radius');
    var radiusLabel = document.getElementById(mapId + '-radius-label');
    var bandsToggle = document.getElementById(mapId + '-bands');
    var hint = document.getElementById(mapId + '-hint');
    var suggestionsEl = document.getElementById(mapId + '-suggestions');
    var satBtn = document.getElementById(mapId + '-basemap-sat');
    var strBtn = document.getElementById(mapId + '-basemap-str');
    var drawBtn = document.getElementById(mapId + '-draw');
    var finishBtn = document.getElementById(mapId + '-finish');
    var undoBtn = document.getElementById(mapId + '-undo');
    var clearBtn = document.getElementById(mapId + '-clear');

    var map = L.map(el, {
        scrollWheelZoom: true,
        dragging: true,
        touchZoom: true,
        doubleClickZoom: false
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

    L.marker([pin.lat, pin.lng], { icon: window.pbArchMapPin.icon({ color: '#3f6212' }) })
        .addTo(map)
        .bindPopup('<strong>' + (pin.name || 'Site') + '</strong><br>Project pin (fallback centre)');

    var siteLayer = null;
    var bufferLayer = null;
    var innerBand = null;
    var outerBand = null;
    var siteGeoJson = savedBoundary || null;
    var drawing = false;
    var draftLatLngs = [];
    var draftMarkers = [];
    var draftLine = null;
    var clickIcon = window.pbArchMapPin.icon({ color: '#b45309' });
    var clickMarker = null;
    var suggestions = [];
    var fitOnce = true;

    function setHint(text, ok) {
        if (!hint) return;
        hint.textContent = text;
        hint.style.color = ok ? '#3f6212' : 'var(--text-muted)';
    }

    function clearImpactLayers() {
        if (siteLayer) { map.removeLayer(siteLayer); siteLayer = null; }
        if (bufferLayer) { map.removeLayer(bufferLayer); bufferLayer = null; }
        if (innerBand) { map.removeLayer(innerBand); innerBand = null; }
        if (outerBand) { map.removeLayer(outerBand); outerBand = null; }
    }

    function clearDraft() {
        draftLatLngs = [];
        draftMarkers.forEach(function (m) { map.removeLayer(m); });
        draftMarkers = [];
        if (draftLine) { map.removeLayer(draftLine); draftLine = null; }
    }

    function setDrawMode(on) {
        drawing = on;
        if (drawBtn) drawBtn.style.display = on ? 'none' : '';
        if (finishBtn) finishBtn.style.display = on ? '' : 'none';
        if (undoBtn) undoBtn.style.display = on ? '' : 'none';
        el.style.cursor = on ? 'crosshair' : 'grab';
        if (on) {
            clearDraft();
            setHint('Drawing site outline — click corners in order, then Finish outline (min. 3 corners).');
        }
    }

    function latLngsToGeoJsonPolygon(latLngs) {
        var ring = latLngs.map(function (ll) { return [ll.lng, ll.lat]; });
        var first = ring[0];
        var last = ring[ring.length - 1];
        if (first[0] !== last[0] || first[1] !== last[1]) ring.push([first[0], first[1]]);
        return { type: 'Polygon', coordinates: [ring] };
    }

    function redrawImpact() {
        var r = parseInt(radiusInput.value, 10) || 20;
        if (radiusLabel) radiusLabel.textContent = r + ' m';
        clearImpactLayers();

        if (siteGeoJson) {
            siteLayer = L.geoJSON(siteGeoJson, {
                style: {
                    color: '#3f6212',
                    weight: 2.5,
                    fillColor: '#84cc16',
                    fillOpacity: 0.22
                }
            }).addTo(map);

            try {
                var buffered = turf.buffer(siteGeoJson, r, { units: 'meters' });
                bufferLayer = L.geoJSON(buffered, {
                    style: {
                        color: '#f59e0b',
                        weight: 2,
                        fillColor: '#fbbf24',
                        fillOpacity: 0.16
                    }
                }).addTo(map);

                if (bandsToggle && bandsToggle.checked) {
                    var half = turf.buffer(siteGeoJson, Math.max(5, r * 0.5), { units: 'meters' });
                    var oneHalf = turf.buffer(siteGeoJson, r * 1.5, { units: 'meters' });
                    innerBand = L.geoJSON(half, {
                        style: { color: '#fb923c', weight: 1, dashArray: '4 4', fill: false }
                    }).addTo(map);
                    outerBand = L.geoJSON(oneHalf, {
                        style: { color: '#fdba74', weight: 1, dashArray: '4 4', fill: false }
                    }).addTo(map);
                }
            } catch (err) {
                setHint('Could not build offset from outline — check the polygon and try again.');
            }

            if (fitOnce) {
                try {
                    var b = (bufferLayer || siteLayer).getBounds();
                    map.fitBounds(b.pad(0.25), { maxZoom: 19 });
                } catch (e) {}
                fitOnce = false;
            }
            return;
        }

        bufferLayer = L.circle([pin.lat, pin.lng], {
            radius: r,
            color: '#f59e0b',
            weight: 2,
            fillColor: '#fbbf24',
            fillOpacity: 0.18
        }).addTo(map);
        if (bandsToggle && bandsToggle.checked) {
            innerBand = L.circle([pin.lat, pin.lng], {
                radius: Math.max(5, Math.round(r * 0.5)),
                color: '#fb923c', weight: 1, dashArray: '4 4', fill: false
            }).addTo(map);
            outerBand = L.circle([pin.lat, pin.lng], {
                radius: Math.round(r * 1.5),
                color: '#fdba74', weight: 1, dashArray: '4 4', fill: false
            }).addTo(map);
        }
        if (fitOnce) {
            try { map.fitBounds(bufferLayer.getBounds().pad(0.35), { maxZoom: 19 }); } catch (e) {}
            fitOnce = false;
        }
    }

    function measureClick(lat, lng) {
        var r = parseInt(radiusInput.value, 10) || 20;
        var pt = turf.point([lng, lat]);

        if (siteGeoJson) {
            try {
                var onSite = turf.booleanPointInPolygon(pt, siteGeoJson);
                var buffered = turf.buffer(siteGeoJson, r, { units: 'meters' });
                var inImpact = turf.booleanPointInPolygon(pt, buffered);
                var line = turf.polygonToLine(siteGeoJson);
                var dist = turf.pointToLineDistance(pt, line, { units: 'meters' });
                if (onSite) dist = 0;
                return {
                    distanceM: dist,
                    outside: ! inImpact,
                    onSite: onSite,
                    from: 'boundary'
                };
            } catch (e) {}
        }

        var d = turf.distance(turf.point([pin.lng, pin.lat]), pt, { units: 'meters' });
        return { distanceM: d, outside: d > r, onSite: false, from: 'pin' };
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
        setHint('Address filled in Add neighbour — edit if needed, then save.', true);
    }

    function renderSuggestions() {
        if (!suggestionsEl) return;
        suggestionsEl.innerHTML = '';
        suggestions.slice().reverse().forEach(function (item) {
            var row = document.createElement('div');
            row.style.cssText = 'display:flex;justify-content:space-between;gap:0.5rem;flex-wrap:wrap;align-items:center;border:1px solid var(--border-light);border-radius:var(--radius-md);padding:0.55rem 0.7rem;background:#fffbeb;';
            var left = document.createElement('div');
            left.style.cssText = 'min-width:0;flex:1;';
            var title = document.createElement('div');
            title.style.cssText = 'font-weight:650;color:var(--primary-navy);font-size:0.85rem;';
            title.textContent = item.address || item.display || 'Map point';
            var meta = document.createElement('div');
            meta.style.cssText = 'font-size:0.72rem;color:var(--text-muted);';
            var fromLabel = item.from === 'boundary' ? 'from site boundary' : 'from pin';
            meta.textContent = Math.round(item.distanceM) + ' m ' + fromLabel +
                (item.onSite ? ' · on site' : '') +
                (item.outside ? ' · outside offset — confirm on site' : '');
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
        });
    }

    function reverseGeocode(lat, lng, measure) {
        setHint('Looking up address…');
        fetch('/pro/architect/geocode/reverse?lat=' + encodeURIComponent(lat) + '&lng=' + encodeURIComponent(lng), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) { return r.json(); }).then(function (data) {
            if (!data || !data.ok) {
                setHint('Could not resolve address — type it manually in the register.');
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
                distanceM: measure.distanceM,
                outside: measure.outside,
                onSite: measure.onSite,
                from: measure.from
            };
            suggestions.push(item);
            if (suggestions.length > 6) suggestions.shift();
            renderSuggestions();
            fillNeighbourForm(item);
        }).catch(function () {
            setHint('Could not resolve address — type it manually in the register.');
        });
    }

    function saveBoundary(geojson) {
        return fetch(saveUrl, {
            method: 'PUT',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ geojson: geojson })
        }).then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); });
    }

    function clearBoundaryRemote() {
        return fetch(saveUrl, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); });
    }

    if (drawBtn) {
        drawBtn.addEventListener('click', function () {
            setDrawMode(true);
        });
    }
    if (undoBtn) {
        undoBtn.addEventListener('click', function () {
            if (!draftLatLngs.length) return;
            draftLatLngs.pop();
            var m = draftMarkers.pop();
            if (m) map.removeLayer(m);
            if (draftLine) map.removeLayer(draftLine);
            draftLine = draftLatLngs.length ? L.polyline(draftLatLngs, { color: '#3f6212', weight: 2, dashArray: '6 4' }).addTo(map) : null;
        });
    }
    if (finishBtn) {
        finishBtn.addEventListener('click', function () {
            if (draftLatLngs.length < 3) {
                setHint('Need at least 3 corners to close the site outline.');
                return;
            }
            var geo = latLngsToGeoJsonPolygon(draftLatLngs);
            setHint('Saving site outline…');
            saveBoundary(geo).then(function (res) {
                if (!res.ok || !res.data || !res.data.ok) {
                    setHint((res.data && res.data.message) || 'Could not save site outline.');
                    return;
                }
                siteGeoJson = res.data.geojson;
                clearDraft();
                setDrawMode(false);
                fitOnce = true;
                redrawImpact();
                setHint(res.data.message || 'Site outline saved. Offset now follows the boundary.', true);
            }).catch(function () {
                setHint('Could not save site outline — check your connection.');
            });
        });
    }
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            if (!siteGeoJson && !drawing) {
                setHint('No site outline to clear.');
                return;
            }
            if (!confirm('Clear the drawn site outline? Impact will fall back to the pin.')) return;
            clearDraft();
            setDrawMode(false);
            clearBoundaryRemote().then(function (res) {
                siteGeoJson = null;
                fitOnce = true;
                redrawImpact();
                setHint((res.data && res.data.message) || 'Site outline cleared.', true);
            }).catch(function () {
                siteGeoJson = null;
                redrawImpact();
                setHint('Outline cleared locally — save may have failed.');
            });
        });
    }

    radiusInput.addEventListener('input', function () { fitOnce = false; redrawImpact(); });
    if (bandsToggle) bandsToggle.addEventListener('change', function () { fitOnce = false; redrawImpact(); });
    document.querySelectorAll('.arch-impact-preset').forEach(function (btn) {
        btn.addEventListener('click', function () {
            radiusInput.value = btn.getAttribute('data-m');
            fitOnce = false;
            redrawImpact();
        });
    });

    map.on('click', function (e) {
        var lat = e.latlng.lat;
        var lng = e.latlng.lng;

        if (drawing) {
            draftLatLngs.push(e.latlng);
            var corner = L.circleMarker(e.latlng, {
                radius: 5,
                color: '#3f6212',
                fillColor: '#84cc16',
                fillOpacity: 1,
                weight: 2
            }).addTo(map);
            draftMarkers.push(corner);
            if (draftLine) map.removeLayer(draftLine);
            draftLine = L.polyline(draftLatLngs, { color: '#3f6212', weight: 2, dashArray: '6 4' }).addTo(map);
            setHint(draftLatLngs.length + ' corner' + (draftLatLngs.length === 1 ? '' : 's') + ' — add more, then Finish outline.');
            return;
        }

        var measure = measureClick(lat, lng);
        if (measure.onSite) {
            setHint('That click is on the site itself — pick a neighbouring property in the amber offset.');
            return;
        }
        if (clickMarker) map.removeLayer(clickMarker);
        clickMarker = L.marker([lat, lng], { icon: clickIcon }).addTo(map);
        reverseGeocode(lat, lng, measure);
    });

    redrawImpact();
    setTimeout(function () { map.invalidateSize(); }, 80);
})();
</script>
@endif
