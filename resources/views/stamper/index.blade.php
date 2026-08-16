@extends('layouts.app')

@section('page_title', 'Document Stamper')

@section('content')
    <style>
        .stamper-page { max-width: 1100px; margin: 0 auto; }
        .stamper-hero { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem; }
        .stamper-title { color: var(--primary-navy); margin: 0 0 0.35rem; font-size: 1.5rem; }
        .stamper-sub { color: var(--text-muted); margin: 0; line-height: 1.45; }
        .stamper-hero-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .stamper-card { background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.25rem 1.5rem; box-shadow: var(--shadow-sm); margin-bottom: 1rem; }
        .stamper-empty { text-align: center; padding: 2rem; }
        .stamper-empty p { color: var(--text-muted); margin: 0 0 1rem; }
        .stamper-controls { display: flex; flex-direction: column; gap: 0.9rem; }
        .stamper-field label { display: block; font-weight: 600; margin-bottom: 0.4rem; }
        .stamper-field select,
        .stamper-field input[type="file"] { width: 100%; padding: 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white; box-sizing: border-box; }
        .stamper-field input[type="file"] { padding: 0.45rem 0; border: none; }
        .stamper-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; }
        .stamper-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.7rem 0.95rem;
            border-radius: var(--radius-md);
            font-family: inherit;
            font-weight: 700;
            font-size: 0.85rem;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
            line-height: 1.2;
            appearance: none;
            -webkit-appearance: none;
        }
        .stamper-btn:disabled { opacity: 0.45; cursor: not-allowed; }
        .stamper-btn-sm { padding: 0.4rem 0.75rem; font-weight: 600; }
        .stamper-btn-primary { background: var(--primary-cerulean); color: #fff; border-color: var(--primary-cerulean); }
        .stamper-btn-ghost { background: #fff; color: var(--primary-navy); border-color: var(--border-light); }
        .stamper-btn-navy { background: #0b1f33; color: #fff; border-color: #0b1f33; }
        .stamper-btn-danger { background: #991b1b; color: #fff; border-color: #991b1b; }
        .stamper-btn-danger-outline { background: #fef2f2; color: #991b1b; border-color: #fecaca; }
        .stamper-help { margin-top: 0.85rem; font-size: 0.85rem; color: var(--text-muted); line-height: 1.45; }
        .stamper-workspace { margin-top: 0.25rem; }
        .stamper-toolbar { display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 0.75rem; }
        .stamper-pager { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
        .stamper-pager span { font-size: 0.9rem; color: var(--primary-navy); font-weight: 600; }
        .stamper-status { font-size: 0.7rem !important; font-weight: 700 !important; letter-spacing: 0.03em; text-transform: uppercase; background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; padding: 0.15rem 0.45rem; border-radius: 999px; }
        .stamper-stage { position: relative; overflow: auto; max-height: 75vh; background: #e2e8f0; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1rem; text-align: center; -webkit-overflow-scrolling: touch; }
        .stamper-frame { position: relative; display: inline-block; box-shadow: var(--shadow-sm); background: white; max-width: 100%; }
        .stamper-frame canvas { display: block; max-width: 100%; height: auto; }
        .stamper-overlay-wrap {
            position: absolute;
            left: 40px;
            top: 40px;
            width: 180px;
            z-index: 2;
            touch-action: none;
            user-select: none;
        }
        .stamper-overlay-wrap[hidden] { display: none !important; }
        #stamp-overlay {
            display: block;
            width: 100%;
            height: auto;
            cursor: grab;
            background: transparent;
            pointer-events: auto;
        }
        .stamper-resize-handle {
            position: absolute;
            right: -6px;
            bottom: -6px;
            width: 16px;
            height: 16px;
            padding: 0;
            border: 2px solid #fff;
            border-radius: 3px;
            background: var(--primary-cerulean);
            cursor: nwse-resize;
            box-shadow: 0 0 0 1px rgba(11, 31, 51, 0.25);
        }
        @media (min-width: 900px) {
            .stamper-controls { display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr); gap: 0.85rem 1rem; align-items: end; }
            .stamper-actions { grid-column: 1 / -1; }
        }
        @media (max-width: 640px) {
            .stamper-card { padding: 1rem; }
            .stamper-actions .stamper-btn { flex: 1 1 calc(50% - 0.5rem); min-width: 0; }
            .stamper-stage { padding: 0.65rem; max-height: 65vh; }
        }
    </style>

    <div class="stamper-page">
        <div class="stamper-hero">
            <div>
                <h1 class="stamper-title">Document Stamper</h1>
                <p class="stamper-sub">Upload a PDF, place your stamp where you want it, on one page or all pages. Your file stays in the browser.</p>
            </div>
            <div class="stamper-hero-actions">
                <a href="/stamper/stamps" class="stamper-btn stamper-btn-ghost">My stamps</a>
                <a href="/stamper/stamps/create" class="stamper-btn stamper-btn-primary">+ New stamp</a>
            </div>
        </div>

        @if($stamps->isEmpty())
            <div class="stamper-card stamper-empty">
                <p>Create a stamp first (name, role, signature), then come back to place it on PDFs.</p>
                <a href="/stamper/stamps/create" class="stamper-btn stamper-btn-primary">Create your stamp</a>
            </div>
        @else
            <div class="stamper-card">
                <div class="stamper-controls">
                    <div class="stamper-field">
                        <label for="stamp-select">Stamp</label>
                        <select id="stamp-select">
                            @foreach($stamps as $stamp)
                                <option value="{{ $stamp->id }}" @selected($stamp->is_default)>{{ $stamp->label }} — {{ $stamp->displayName() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="stamper-field">
                        <label for="pdf-file">PDF file</label>
                        <input type="file" id="pdf-file" accept="application/pdf,.pdf">
                    </div>
                    <div class="stamper-actions">
                        <button type="button" id="btn-apply-page" class="stamper-btn stamper-btn-primary" disabled>Stamp this page</button>
                        <button type="button" id="btn-apply-all" class="stamper-btn stamper-btn-ghost" disabled>Stamp all pages</button>
                        <button type="button" id="btn-remove-page" class="stamper-btn stamper-btn-danger" disabled>Remove</button>
                        <button type="button" id="btn-remove-all" class="stamper-btn stamper-btn-danger-outline" disabled>Remove all</button>
                    </div>
                </div>
                <div id="stamper-help" class="stamper-help">
                    Choose a stamp and upload a PDF. Drag to place, drag the corner to resize, then stamp. Download when ready.
                </div>
            </div>

            <div id="stamper-workspace" class="stamper-workspace" hidden>
                <div class="stamper-toolbar">
                    <div class="stamper-pager">
                        <button type="button" id="btn-prev" class="stamper-btn stamper-btn-ghost stamper-btn-sm">Prev</button>
                        <span id="page-label">Page 1</span>
                        <button type="button" id="btn-next" class="stamper-btn stamper-btn-ghost stamper-btn-sm">Next</button>
                        <span id="page-stamp-status" class="stamper-status" hidden>Stamped</span>
                    </div>
                    <button type="button" id="btn-download" class="stamper-btn stamper-btn-navy" disabled>Download PDF</button>
                </div>

                <div id="pdf-stage" class="stamper-stage">
                    <div id="pdf-frame" class="stamper-frame">
                        <canvas id="pdf-canvas"></canvas>
                        <div id="stamp-overlay-wrap" class="stamper-overlay-wrap" hidden>
                            <img id="stamp-overlay" alt="Stamp" draggable="false">
                            <button type="button" id="stamp-resize-handle" class="stamper-resize-handle" aria-label="Resize stamp"></button>
                        </div>
                    </div>
                </div>
                <button type="button" id="btn-place-stamp" class="stamper-btn stamper-btn-ghost stamper-btn-sm" style="margin-top: 0.65rem;" hidden>Place stamp</button>
            </div>
        @endif
    </div>
@endsection

@if($stamps->isNotEmpty())
@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>
<script>
(function () {
    var stamps = @json($stampPayload);
    var stampById = {};
    stamps.forEach(function (s) { stampById[String(s.id)] = s; });

    var pdfjsLib = window['pdfjs-dist/build/pdf'] || window.pdfjsLib;
    if (pdfjsLib) {
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    }

    var select = document.getElementById('stamp-select');
    var fileInput = document.getElementById('pdf-file');
    var help = document.getElementById('stamper-help');
    var workspace = document.getElementById('stamper-workspace');
    var canvas = document.getElementById('pdf-canvas');
    var ctx = canvas.getContext('2d');
    var overlayWrap = document.getElementById('stamp-overlay-wrap');
    var overlay = document.getElementById('stamp-overlay');
    var resizeHandle = document.getElementById('stamp-resize-handle');
    var btnPlace = document.getElementById('btn-place-stamp');
    var pageLabel = document.getElementById('page-label');
    var pageStatus = document.getElementById('page-stamp-status');
    var btnPrev = document.getElementById('btn-prev');
    var btnNext = document.getElementById('btn-next');
    var btnApplyPage = document.getElementById('btn-apply-page');
    var btnApplyAll = document.getElementById('btn-apply-all');
    var btnRemovePage = document.getElementById('btn-remove-page');
    var btnRemoveAll = document.getElementById('btn-remove-all');
    var btnDownload = document.getElementById('btn-download');

    var pdfDoc = null;
    var sourcePdfBytes = null;
    var pageNum = 1;
    var pageCount = 0;
    var renderScale = 1.15;
    var placements = {};
    var fileName = 'stamped.pdf';
    var dragging = false;
    var resizing = false;
    var dragOffset = { x: 0, y: 0 };
    var resizeStart = { x: 0, y: 0, w: 0 };
    var stampPngCache = {};
    var draftVisible = false;

    function currentStamp() {
        return stampById[String(select.value)] || stamps[0];
    }

    function setHelp(msg) {
        help.textContent = msg;
    }

    function placementCount() {
        return Object.keys(placements).length;
    }

    function updateActionState() {
        var ready = !!sourcePdfBytes;
        var hasPage = !!placements[pageNum];
        btnApplyPage.disabled = !ready || !draftVisible;
        btnApplyAll.disabled = !ready || !draftVisible;
        btnDownload.disabled = !ready || placementCount() === 0;
        btnRemovePage.disabled = !ready || !hasPage;
        btnRemoveAll.disabled = !ready || placementCount() === 0;
        pageStatus.hidden = !hasPage;
        if (btnPlace) btnPlace.hidden = !ready || draftVisible || hasPage;
        if (hasPage || draftVisible) {
            overlayWrap.hidden = false;
            overlayWrap.style.outline = hasPage ? '2px dashed rgba(11, 31, 51, 0.35)' : '2px dashed rgba(2, 132, 199, 0.45)';
            overlayWrap.style.outlineOffset = '2px';
        } else {
            overlayWrap.hidden = true;
            overlayWrap.style.outline = 'none';
        }
    }

    function defaultOverlaySize() {
        return Math.min(180, Math.max(120, (document.getElementById('pdf-frame').clientWidth || 320) * 0.42));
    }

    function showDraftOverlay(atCenter) {
        var frame = document.getElementById('pdf-frame');
        if (!frame) return;
        draftVisible = true;
        overlayWrap.hidden = false;
        var w = defaultOverlaySize();
        overlayWrap.style.width = Math.round(w) + 'px';
        if (atCenter) {
            overlayWrap.style.left = Math.max(0, Math.round((frame.clientWidth - w) / 2)) + 'px';
            overlayWrap.style.top = Math.max(0, Math.round(frame.clientHeight * 0.35)) + 'px';
        } else if (!overlayWrap.style.left) {
            overlayWrap.style.left = '40px';
            overlayWrap.style.top = '40px';
        }
        updateActionState();
    }

    function hideDraftOverlay() {
        draftVisible = false;
        if (!placements[pageNum]) {
            overlayWrap.hidden = true;
        }
        updateActionState();
    }

    function knockOutPaper(img) {
        try {
            var c = document.createElement('canvas');
            c.width = img.naturalWidth || img.width;
            c.height = img.naturalHeight || img.height;
            if (!c.width || !c.height) return img;
            var g = c.getContext('2d', { willReadFrequently: true });
            g.drawImage(img, 0, 0);
            var data = g.getImageData(0, 0, c.width, c.height);
            var px = data.data;
            var hard = 235;
            var soft = 200;
            for (var i = 0; i < px.length; i += 4) {
                var r = px[i], gv = px[i + 1], b = px[i + 2], a = px[i + 3];
                if (a === 0) continue;
                var minc = Math.min(r, gv, b);
                var maxc = Math.max(r, gv, b);
                var spread = maxc - minc;
                if (minc >= hard && spread < 30) {
                    px[i + 3] = 0;
                    continue;
                }
                if (minc >= soft && spread < 40) {
                    var fade = 1 - ((minc - soft) / Math.max(1, hard - soft));
                    px[i + 3] = Math.round(a * Math.max(0, Math.min(1, fade)));
                }
            }
            g.putImageData(data, 0, 0);
            return c;
        } catch (e) {
            return img;
        }
    }

    function drawStampCanvas(stamp) {
        var c = document.createElement('canvas');
        c.width = 840;
        c.height = 440;
        var g = c.getContext('2d');
        var name = (stamp.first_name + ' ' + stamp.last_name).trim();
        var post = stamp.postnominals || '';
        var role = stamp.role_title || '';
        var warrant = stamp.warrant_number || '';
        var warrantLine = warrant ? ('Warrant ' + warrant) : '';
        var preset = stamp.preset || 'classic_border';

        function paint(sigImg) {
            g.clearRect(0, 0, c.width, c.height);
            g.textAlign = 'center';
            g.fillStyle = '#0b1f33';
            var sig = sigImg ? knockOutPaper(sigImg) : null;

            if (preset === 'circular_seal') {
                var cx = c.width / 2, cy = c.height / 2, r = 180;
                g.strokeStyle = '#0b1f33';
                g.lineWidth = 6;
                g.beginPath(); g.arc(cx, cy, r, 0, Math.PI * 2); g.stroke();
                g.lineWidth = 3;
                g.beginPath(); g.arc(cx, cy, r - 16, 0, Math.PI * 2); g.stroke();
                g.font = 'bold 32px Georgia, serif';
                g.fillText(name, cx, cy - 44);
                g.font = '20px Georgia, serif';
                if (post) g.fillText(post, cx, cy - 12);
                g.fillText(role, cx, cy + 20);
                if (warrantLine) {
                    g.font = '18px Georgia, serif';
                    g.fillText(warrantLine, cx, cy + 48);
                }
                if (sig) g.drawImage(sig, cx - 100, cy + 62, 200, 64);
            } else if (preset === 'minimal_line') {
                g.textAlign = 'left';
                g.font = 'bold 40px Georgia, serif';
                g.fillText(name + (post ? ', ' + post : ''), 48, 90);
                g.strokeStyle = '#0b1f33';
                g.lineWidth = 3;
                g.beginPath(); g.moveTo(48, 118); g.lineTo(c.width - 48, 118); g.stroke();
                g.font = '26px Inter, sans-serif';
                g.fillStyle = '#334155';
                g.fillText(role + (warrantLine ? ' · ' + warrantLine : ''), 48, 165);
                if (sig) g.drawImage(sig, 48, 200, 320, 110);
            } else if (preset === 'warrant_block') {
                g.strokeStyle = '#0b1f33';
                g.lineWidth = 4;
                g.strokeRect(32, 32, c.width - 64, c.height - 64);
                g.fillStyle = '#0b1f33';
                g.fillRect(32, 32, c.width - 64, 70);
                g.fillStyle = '#ffffff';
                g.font = 'bold 26px Inter, sans-serif';
                g.fillText(role.toUpperCase(), c.width / 2, 78);
                g.fillStyle = '#0b1f33';
                g.font = 'bold 40px Georgia, serif';
                g.fillText(name, c.width / 2, 150);
                g.font = '24px Georgia, serif';
                if (post) g.fillText(post, c.width / 2, 188);
                if (warrantLine) g.fillText(warrantLine, c.width / 2, 222);
                if (sig) g.drawImage(sig, c.width / 2 - 140, 250, 280, 100);
            } else {
                g.strokeStyle = '#0b1f33';
                g.lineWidth = 5;
                g.strokeRect(36, 36, c.width - 72, c.height - 72);
                g.lineWidth = 2;
                g.strokeRect(48, 48, c.width - 96, c.height - 96);
                g.font = 'bold 38px Georgia, serif';
                g.fillText(name, c.width / 2, 125);
                g.font = '24px Georgia, serif';
                if (post) g.fillText(post, c.width / 2, 162);
                g.font = '22px Inter, sans-serif';
                g.fillStyle = '#334155';
                g.fillText(role, c.width / 2, 198);
                if (warrantLine) g.fillText(warrantLine, c.width / 2, 230);
                if (sig) g.drawImage(sig, c.width / 2 - 140, 255, 280, 100);
            }
        }

        return new Promise(function (resolve) {
            if (!stamp.signature_data_uri) {
                paint(null);
                resolve(c.toDataURL('image/png'));
                return;
            }
            var img = new Image();
            img.onload = function () {
                paint(img);
                resolve(c.toDataURL('image/png'));
            };
            img.onerror = function () {
                paint(null);
                resolve(c.toDataURL('image/png'));
            };
            img.src = stamp.signature_data_uri;
        });
    }

    async function refreshOverlayImage() {
        var stamp = currentStamp();
        var key = String(stamp.id) + ':clear-sig';
        if (!stampPngCache[key]) {
            stampPngCache[key] = await drawStampCanvas(stamp);
        }
        overlay.src = stampPngCache[key];
    }

    function capturePlacement() {
        var frame = document.getElementById('pdf-frame');
        if (!frame || !frame.clientWidth || !frame.clientHeight || overlayWrap.hidden) return null;
        var left = parseFloat(overlayWrap.style.left) || 0;
        var top = parseFloat(overlayWrap.style.top) || 0;
        var width = overlayWrap.offsetWidth || overlayWrap.getBoundingClientRect().width;
        var height = overlayWrap.offsetHeight || overlayWrap.getBoundingClientRect().height;
        return {
            nx: left / frame.clientWidth,
            ny: top / frame.clientHeight,
            nw: width / frame.clientWidth,
            nh: height / frame.clientHeight,
            stampId: currentStamp().id
        };
    }

    function restorePlacementForPage() {
        var p = placements[pageNum];
        var frame = document.getElementById('pdf-frame');
        if (!p || !frame) {
            draftVisible = false;
            overlayWrap.hidden = true;
            updateActionState();
            return;
        }
        draftVisible = true;
        overlayWrap.hidden = false;
        overlayWrap.style.left = Math.round(p.nx * frame.clientWidth) + 'px';
        overlayWrap.style.top = Math.round(p.ny * frame.clientHeight) + 'px';
        overlayWrap.style.width = Math.round(p.nw * frame.clientWidth) + 'px';
        if (p.stampId && stampById[String(p.stampId)] && String(select.value) !== String(p.stampId)) {
            select.value = String(p.stampId);
            refreshOverlayImage().then(function () {
                overlayWrap.style.left = Math.round(p.nx * frame.clientWidth) + 'px';
                overlayWrap.style.top = Math.round(p.ny * frame.clientHeight) + 'px';
                overlayWrap.style.width = Math.round(p.nw * frame.clientWidth) + 'px';
            });
        }
        updateActionState();
    }

    async function renderPage(num) {
        var page = await pdfDoc.getPage(num);
        var viewport = page.getViewport({ scale: renderScale });
        canvas.width = viewport.width;
        canvas.height = viewport.height;
        await page.render({ canvasContext: ctx, viewport: viewport }).promise;
        pageLabel.textContent = 'Page ' + num + ' of ' + pageCount;
        restorePlacementForPage();
    }

    async function loadPdf(file) {
        sourcePdfBytes = new Uint8Array(await file.arrayBuffer());
        fileName = (file.name || 'document.pdf').replace(/\.pdf$/i, '') + '-stamped.pdf';
        pdfDoc = await pdfjsLib.getDocument({ data: sourcePdfBytes.slice(0) }).promise;
        pageCount = pdfDoc.numPages;
        pageNum = 1;
        placements = {};
        draftVisible = true;
        workspace.hidden = false;
        await refreshOverlayImage();
        await renderPage(pageNum);
        showDraftOverlay(true);
        updateActionState();
        setHelp('Drag the stamp, resize from the corner, then Stamp this page or all pages. Download when ready.');
    }

    fileInput.addEventListener('change', async function () {
        var file = fileInput.files && fileInput.files[0];
        if (!file) return;
        if (file.type && file.type !== 'application/pdf') {
            setHelp('Please choose a PDF file.');
            return;
        }
        try {
            setHelp('Loading PDF…');
            await loadPdf(file);
        } catch (err) {
            console.error(err);
            setHelp('Could not read that PDF. Try another file.');
            workspace.hidden = true;
            sourcePdfBytes = null;
            updateActionState();
        }
    });

    select.addEventListener('change', async function () {
        await refreshOverlayImage();
        if (pdfDoc) restorePlacementForPage();
    });

    if (btnPlace) {
        btnPlace.addEventListener('click', function () {
            showDraftOverlay(true);
            setHelp('Place the stamp, then Stamp this page.');
        });
    }

    btnPrev.addEventListener('click', async function () {
        if (!pdfDoc || pageNum <= 1) return;
        pageNum -= 1;
        await renderPage(pageNum);
    });
    btnNext.addEventListener('click', async function () {
        if (!pdfDoc || pageNum >= pageCount) return;
        pageNum += 1;
        await renderPage(pageNum);
    });

    function startDrag(e) {
        if (e.target === resizeHandle) return;
        dragging = true;
        overlay.style.cursor = 'grabbing';
        var point = e.touches && e.touches[0] ? e.touches[0] : e;
        var rect = overlayWrap.getBoundingClientRect();
        dragOffset.x = point.clientX - rect.left;
        dragOffset.y = point.clientY - rect.top;
        e.preventDefault();
    }
    function moveDrag(e) {
        if (!dragging && !resizing) return;
        var point = e.touches && e.touches[0] ? e.touches[0] : e;
        var frameEl = document.getElementById('pdf-frame');
        var frame = frameEl.getBoundingClientRect();
        if (resizing) {
            var nextW = Math.max(80, Math.min(frame.width * 0.85, resizeStart.w + (point.clientX - resizeStart.x)));
            overlayWrap.style.width = Math.round(nextW) + 'px';
            e.preventDefault();
            return;
        }
        var left = point.clientX - frame.left - dragOffset.x;
        var top = point.clientY - frame.top - dragOffset.y;
        var maxL = frame.width - overlayWrap.offsetWidth;
        var maxT = frame.height - overlayWrap.offsetHeight;
        overlayWrap.style.left = Math.max(0, Math.min(maxL, left)) + 'px';
        overlayWrap.style.top = Math.max(0, Math.min(maxT, top)) + 'px';
        e.preventDefault();
    }
    function endDrag() {
        dragging = false;
        resizing = false;
        overlay.style.cursor = 'grab';
    }

    function startResize(e) {
        resizing = true;
        var point = e.touches && e.touches[0] ? e.touches[0] : e;
        resizeStart.x = point.clientX;
        resizeStart.y = point.clientY;
        resizeStart.w = overlayWrap.offsetWidth;
        e.preventDefault();
        e.stopPropagation();
    }

    overlay.addEventListener('mousedown', startDrag);
    resizeHandle.addEventListener('mousedown', startResize);
    window.addEventListener('mousemove', moveDrag);
    window.addEventListener('mouseup', endDrag);
    overlay.addEventListener('touchstart', startDrag, { passive: false });
    resizeHandle.addEventListener('touchstart', startResize, { passive: false });
    window.addEventListener('touchmove', moveDrag, { passive: false });
    window.addEventListener('touchend', endDrag);

    async function stampImageBytes(stamp) {
        var key = String(stamp.id) + ':clear-sig';
        if (!stampPngCache[key]) {
            stampPngCache[key] = await drawStampCanvas(stamp);
        }
        var dataUri = stampPngCache[key];
        var raw = atob(dataUri.split(',')[1]);
        var bytes = new Uint8Array(raw.length);
        for (var i = 0; i < raw.length; i++) bytes[i] = raw.charCodeAt(i);
        return bytes;
    }

    function pdfLibPlacement(page, placement) {
        var size = page.getSize();
        var width = placement.nw * size.width;
        var height = placement.nh * size.height;
        var x = placement.nx * size.width;
        var y = size.height - (placement.ny * size.height) - height;
        return { x: x, y: y, width: width, height: height };
    }

    async function buildStampedPdf() {
        if (!sourcePdfBytes || placementCount() === 0) {
            return sourcePdfBytes ? sourcePdfBytes.slice(0) : null;
        }

        var pdf = await PDFLib.PDFDocument.load(sourcePdfBytes.slice(0));
        var pages = pdf.getPages();
        var embeddedByStamp = {};

        for (var pageKey of Object.keys(placements)) {
            var placement = placements[pageKey];
            var stamp = stampById[String(placement.stampId)] || currentStamp();
            var stampKey = String(stamp.id);
            if (!embeddedByStamp[stampKey]) {
                embeddedByStamp[stampKey] = await pdf.embedPng(await stampImageBytes(stamp));
            }
            var pageIndex = parseInt(pageKey, 10) - 1;
            if (pageIndex < 0 || pageIndex >= pages.length) continue;
            var page = pages[pageIndex];
            page.drawImage(embeddedByStamp[stampKey], pdfLibPlacement(page, placement));
        }

        return await pdf.save();
    }

    btnApplyPage.addEventListener('click', function () {
        var p = capturePlacement();
        if (!p) return;
        placements[pageNum] = p;
        updateActionState();
        setHelp('Stamp saved on page ' + pageNum + '. Move it and stamp again to update, or Remove to delete it. Download when ready.');
    });

    btnApplyAll.addEventListener('click', function () {
        var p = capturePlacement();
        if (!p) return;
        for (var i = 1; i <= pageCount; i++) {
            placements[i] = {
                nx: p.nx,
                ny: p.ny,
                nw: p.nw,
                nh: p.nh,
                stampId: p.stampId
            };
        }
        updateActionState();
        setHelp('Stamp saved on all ' + pageCount + ' pages. Use Remove or Remove all to correct a mistake before download.');
    });

    btnRemovePage.addEventListener('click', function () {
        delete placements[pageNum];
        hideDraftOverlay();
        updateActionState();
        setHelp('Removed stamp from page ' + pageNum + '.');
    });

    btnRemoveAll.addEventListener('click', function () {
        placements = {};
        hideDraftOverlay();
        updateActionState();
        setHelp('All stamps removed.');
    });

    btnDownload.addEventListener('click', async function () {
        try {
            if (placementCount() === 0) {
                setHelp('Stamp at least one page before downloading.');
                return;
            }
            if (!placements[pageNum]) {
                // keep only committed placements; do not auto-commit current drag
            }
            setHelp('Building PDF…');
            var bytes = await buildStampedPdf();
            var blob = new Blob([bytes], { type: 'application/pdf' });
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = fileName;
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);
            setHelp('Downloaded ' + fileName + '. Stamps stay editable here until you upload a new PDF.');
        } catch (err) {
            console.error(err);
            setHelp('Download failed. Try again.');
        }
    });

    window.addEventListener('resize', function () {
        if (!pdfDoc) return;
        restorePlacementForPage();
    });

    refreshOverlayImage();
    updateActionState();
})();
</script>
@endpush
@endif
