@extends('layouts.app')

@section('page_title', 'Document Stamper')

@section('content')
    <div style="max-width: 1100px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem;">
            <div>
                <h1 style="color: var(--primary-navy); margin: 0 0 0.35rem; font-size: 1.5rem;">Document Stamper</h1>
                <p style="color: var(--text-muted); margin: 0; line-height: 1.45;">Upload a PDF, place your stamp where you want it, on one page or all pages. Your file stays in the browser.</p>
            </div>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <a href="/stamper/stamps" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">My stamps</a>
                <a href="/stamper/stamps/create" style="background: var(--primary-cerulean); color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">+ New stamp</a>
            </div>
        </div>

        @if($stamps->isEmpty())
            <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm); text-align: center;">
                <p style="color: var(--text-muted); margin: 0 0 1rem;">Create a stamp first (name, role, signature), then come back to place it on PDFs.</p>
                <a href="/stamper/stamps/create" style="display: inline-block; background: var(--primary-cerulean); color: white; padding: 0.7rem 1.25rem; border-radius: var(--radius-md); font-weight: 700; text-decoration: none;">Create your stamp</a>
            </div>
        @else
            <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.25rem 1.5rem; box-shadow: var(--shadow-sm); margin-bottom: 1rem;">
                <div style="display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr) auto; gap: 0.85rem; align-items: end;">
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Stamp</label>
                        <select id="stamp-select" style="width: 100%; padding: 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                            @foreach($stamps as $stamp)
                                <option value="{{ $stamp->id }}" @selected($stamp->is_default)>{{ $stamp->label }} — {{ $stamp->displayName() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">PDF file</label>
                        <input type="file" id="pdf-file" accept="application/pdf,.pdf" style="width: 100%; padding: 0.45rem 0;">
                    </div>
                    <div style="display: flex; gap: 0.45rem; flex-wrap: wrap;">
                        <button type="button" id="btn-apply-page" disabled style="padding: 0.7rem 0.95rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Stamp this page</button>
                        <button type="button" id="btn-apply-all" disabled style="padding: 0.7rem 0.95rem; background: white; color: var(--primary-navy); border: 1px solid var(--border-light); border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Stamp all pages</button>
                        <button type="button" id="btn-download" disabled style="padding: 0.7rem 0.95rem; background: #0b1f33; color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Download PDF</button>
                    </div>
                </div>
                <div id="stamper-help" style="margin-top: 0.85rem; font-size: 0.85rem; color: var(--text-muted); line-height: 1.45;">
                    Choose a stamp and upload a PDF. Drag the stamp on the page preview, then stamp this page or all pages.
                </div>
            </div>

            <div id="stamper-workspace" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 0.75rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <button type="button" id="btn-prev" style="padding: 0.4rem 0.75rem; border: 1px solid var(--border-light); background: white; border-radius: var(--radius-md); cursor: pointer; font-weight: 600;">Prev</button>
                        <span id="page-label" style="font-size: 0.9rem; color: var(--primary-navy); font-weight: 600;">Page 1</span>
                        <button type="button" id="btn-next" style="padding: 0.4rem 0.75rem; border: 1px solid var(--border-light); background: white; border-radius: var(--radius-md); cursor: pointer; font-weight: 600;">Next</button>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.65rem; flex-wrap: wrap;">
                        <label style="font-size: 0.85rem; color: var(--text-muted); display: flex; align-items: center; gap: 0.4rem;">
                            Size
                            <input type="range" id="stamp-scale" min="40" max="160" value="100" style="width: 120px;">
                        </label>
                        <button type="button" id="btn-clear-page" style="padding: 0.4rem 0.75rem; border: 1px solid #fecaca; background: #fef2f2; color: #991b1b; border-radius: var(--radius-md); cursor: pointer; font-weight: 600; font-size: 0.8rem;">Clear this page</button>
                    </div>
                </div>

                <div id="pdf-stage" style="position: relative; overflow: auto; max-height: 75vh; background: #e2e8f0; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1rem; text-align: center;">
                    <div id="pdf-frame" style="position: relative; display: inline-block; box-shadow: var(--shadow-sm); background: white;">
                        <canvas id="pdf-canvas"></canvas>
                        <img id="stamp-overlay" alt="Stamp" draggable="false" style="position: absolute; left: 40px; top: 40px; width: 180px; cursor: grab; user-select: none; touch-action: none; z-index: 2;">
                    </div>
                </div>
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
    var overlay = document.getElementById('stamp-overlay');
    var scaleInput = document.getElementById('stamp-scale');
    var pageLabel = document.getElementById('page-label');
    var btnPrev = document.getElementById('btn-prev');
    var btnNext = document.getElementById('btn-next');
    var btnApplyPage = document.getElementById('btn-apply-page');
    var btnApplyAll = document.getElementById('btn-apply-all');
    var btnDownload = document.getElementById('btn-download');
    var btnClear = document.getElementById('btn-clear-page');

    var pdfDoc = null;
    var pdfBytes = null;
    var pageNum = 1;
    var pageCount = 0;
    var renderScale = 1.35;
    var pageViewport = null;
    var placements = {};
    var fileName = 'stamped.pdf';
    var dragging = false;
    var dragOffset = { x: 0, y: 0 };
    var stampPngCache = {};

    function currentStamp() {
        return stampById[String(select.value)] || stamps[0];
    }

    function setHelp(msg) {
        help.textContent = msg;
    }

    function enableActions(on) {
        btnApplyPage.disabled = !on;
        btnApplyAll.disabled = !on;
        btnDownload.disabled = !on;
    }

    function drawStampCanvas(stamp) {
        var c = document.createElement('canvas');
        c.width = 840;
        c.height = 440;
        var g = c.getContext('2d');
        var name = (stamp.first_name + ' ' + stamp.last_name).trim();
        var post = stamp.postnominals || '';
        var role = stamp.role_title || '';
        var preset = stamp.preset || 'classic_border';
        var sig = null;

        function paint(sigImg) {
            g.clearRect(0, 0, c.width, c.height);
            g.fillStyle = 'rgba(255,255,255,0.92)';
            g.fillRect(0, 0, c.width, c.height);
            g.textAlign = 'center';
            g.fillStyle = '#0b1f33';

            if (preset === 'circular_seal') {
                var cx = c.width / 2, cy = c.height / 2, r = 180;
                g.strokeStyle = '#0b1f33';
                g.lineWidth = 6;
                g.beginPath(); g.arc(cx, cy, r, 0, Math.PI * 2); g.stroke();
                g.lineWidth = 3;
                g.beginPath(); g.arc(cx, cy, r - 16, 0, Math.PI * 2); g.stroke();
                g.font = 'bold 34px Georgia, serif';
                g.fillText(name, cx, cy - 16);
                g.font = '22px Georgia, serif';
                if (post) g.fillText(post, cx, cy + 20);
                g.font = '20px Georgia, serif';
                g.fillText(role, cx, cy + 52);
                if (sigImg) g.drawImage(sigImg, cx - 110, cy + 70, 220, 70);
            } else if (preset === 'minimal_line') {
                g.textAlign = 'left';
                g.font = 'bold 40px Georgia, serif';
                g.fillText(name + (post ? ', ' + post : ''), 48, 100);
                g.strokeStyle = '#0b1f33';
                g.lineWidth = 3;
                g.beginPath(); g.moveTo(48, 128); g.lineTo(c.width - 48, 128); g.stroke();
                g.font = '26px Inter, sans-serif';
                g.fillStyle = '#475569';
                g.fillText(role, 48, 175);
                if (sigImg) g.drawImage(sigImg, 48, 210, 320, 110);
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
                g.fillText(name, c.width / 2, 160);
                g.font = '24px Georgia, serif';
                if (post) g.fillText(post, c.width / 2, 200);
                if (sigImg) g.drawImage(sigImg, c.width / 2 - 140, 240, 280, 100);
            } else {
                g.strokeStyle = '#0b1f33';
                g.lineWidth = 5;
                g.strokeRect(36, 36, c.width - 72, c.height - 72);
                g.lineWidth = 2;
                g.strokeRect(48, 48, c.width - 96, c.height - 96);
                g.font = 'bold 40px Georgia, serif';
                g.fillText(name, c.width / 2, 140);
                g.font = '24px Georgia, serif';
                if (post) g.fillText(post, c.width / 2, 180);
                g.font = '22px Inter, sans-serif';
                g.fillStyle = '#475569';
                g.fillText(role, c.width / 2, 220);
                if (sigImg) g.drawImage(sigImg, c.width / 2 - 140, 250, 280, 100);
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
        var key = String(stamp.id);
        if (!stampPngCache[key]) {
            stampPngCache[key] = await drawStampCanvas(stamp);
        }
        overlay.src = stampPngCache[key];
        applyOverlaySize();
    }

    function applyOverlaySize() {
        var base = 180;
        var pct = parseInt(scaleInput.value, 10) || 100;
        overlay.style.width = Math.round(base * pct / 100) + 'px';
        overlay.style.height = 'auto';
    }

    function savePlacementForPage() {
        var frame = document.getElementById('pdf-frame');
        if (!frame || !frame.clientWidth || !frame.clientHeight) return;
        var left = parseFloat(overlay.style.left) || 0;
        var top = parseFloat(overlay.style.top) || 0;
        var width = overlay.offsetWidth || overlay.getBoundingClientRect().width;
        var height = overlay.offsetHeight || overlay.getBoundingClientRect().height;
        placements[pageNum] = {
            nx: left / frame.clientWidth,
            ny: top / frame.clientHeight,
            nw: width / frame.clientWidth,
            nh: height / frame.clientHeight,
            stampId: currentStamp().id
        };
    }

    function restorePlacementForPage() {
        applyOverlaySize();
        var p = placements[pageNum];
        var frame = document.getElementById('pdf-frame');
        if (!p || !frame) {
            overlay.style.left = '40px';
            overlay.style.top = '40px';
            return;
        }
        overlay.style.left = Math.round(p.nx * frame.clientWidth) + 'px';
        overlay.style.top = Math.round(p.ny * frame.clientHeight) + 'px';
        overlay.style.width = Math.round(p.nw * frame.clientWidth) + 'px';
    }

    async function renderPage(num) {
        var page = await pdfDoc.getPage(num);
        pageViewport = page.getViewport({ scale: renderScale });
        canvas.width = pageViewport.width;
        canvas.height = pageViewport.height;
        await page.render({ canvasContext: ctx, viewport: pageViewport }).promise;
        pageLabel.textContent = 'Page ' + num + ' of ' + pageCount;
        restorePlacementForPage();
    }

    async function loadPdf(file) {
        pdfBytes = new Uint8Array(await file.arrayBuffer());
        fileName = (file.name || 'document.pdf').replace(/\.pdf$/i, '') + '-stamped.pdf';
        pdfDoc = await pdfjsLib.getDocument({ data: pdfBytes.slice(0) }).promise;
        pageCount = pdfDoc.numPages;
        pageNum = 1;
        placements = {};
        workspace.style.display = 'block';
        enableActions(true);
        await refreshOverlayImage();
        await renderPage(pageNum);
        setHelp('Drag the stamp. Use Stamp this page or Stamp all pages, then Download PDF. The PDF never leaves your device.');
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
            enableActions(false);
            workspace.style.display = 'none';
        }
    });

    select.addEventListener('change', async function () {
        delete stampPngCache[String(currentStamp().id)];
        await refreshOverlayImage();
        if (pdfDoc) restorePlacementForPage();
    });

    scaleInput.addEventListener('input', function () {
        applyOverlaySize();
    });

    btnPrev.addEventListener('click', async function () {
        if (!pdfDoc || pageNum <= 1) return;
        savePlacementForPage();
        pageNum -= 1;
        await renderPage(pageNum);
    });
    btnNext.addEventListener('click', async function () {
        if (!pdfDoc || pageNum >= pageCount) return;
        savePlacementForPage();
        pageNum += 1;
        await renderPage(pageNum);
    });

    btnClear.addEventListener('click', function () {
        delete placements[pageNum];
        overlay.style.left = '40px';
        overlay.style.top = '40px';
        setHelp('Cleared stamp on page ' + pageNum + '.');
    });

    function startDrag(e) {
        dragging = true;
        overlay.style.cursor = 'grabbing';
        var point = e.touches && e.touches[0] ? e.touches[0] : e;
        var rect = overlay.getBoundingClientRect();
        dragOffset.x = point.clientX - rect.left;
        dragOffset.y = point.clientY - rect.top;
        e.preventDefault();
    }
    function moveDrag(e) {
        if (!dragging) return;
        var point = e.touches && e.touches[0] ? e.touches[0] : e;
        var frame = document.getElementById('pdf-frame').getBoundingClientRect();
        var left = point.clientX - frame.left - dragOffset.x;
        var top = point.clientY - frame.top - dragOffset.y;
        var maxL = frame.width - overlay.offsetWidth;
        var maxT = frame.height - overlay.offsetHeight;
        overlay.style.left = Math.max(0, Math.min(maxL, left)) + 'px';
        overlay.style.top = Math.max(0, Math.min(maxT, top)) + 'px';
        e.preventDefault();
    }
    function endDrag() {
        dragging = false;
        overlay.style.cursor = 'grab';
    }

    overlay.addEventListener('mousedown', startDrag);
    window.addEventListener('mousemove', moveDrag);
    window.addEventListener('mouseup', endDrag);
    overlay.addEventListener('touchstart', startDrag, { passive: false });
    window.addEventListener('touchmove', moveDrag, { passive: false });
    window.addEventListener('touchend', endDrag);

    async function stampImageBytes(stamp) {
        var key = String(stamp.id);
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

    async function buildStampedPdf(mode) {
        if (!pdfBytes) return null;
        savePlacementForPage();

        var stamp = currentStamp();
        var pngBytes = await stampImageBytes(stamp);
        var pdf = await PDFLib.PDFDocument.load(pdfBytes.slice(0));
        var image = await pdf.embedPng(pngBytes);
        var pages = pdf.getPages();

        var sourcePlacement = placements[pageNum];
        if (!sourcePlacement) {
            savePlacementForPage();
            sourcePlacement = placements[pageNum];
        }

        if (mode === 'page') {
            var page = pages[pageNum - 1];
            page.drawImage(image, pdfLibPlacement(page, sourcePlacement));
        } else {
            pages.forEach(function (page, idx) {
                var p = placements[idx + 1] || sourcePlacement;
                page.drawImage(image, pdfLibPlacement(page, p));
            });
        }

        return await pdf.save();
    }

    async function reloadFromBytes(bytes, clearPlacements) {
        pdfBytes = bytes;
        pdfDoc = await pdfjsLib.getDocument({ data: pdfBytes.slice(0) }).promise;
        pageCount = pdfDoc.numPages;
        if (clearPlacements) {
            placements = {};
        } else {
            delete placements[pageNum];
        }
        await renderPage(pageNum);
    }

    btnApplyPage.addEventListener('click', async function () {
        try {
            var bytes = await buildStampedPdf('page');
            await reloadFromBytes(bytes, false);
            setHelp('Stamp applied to page ' + pageNum + '. Download when ready, or stamp more pages.');
        } catch (err) {
            console.error(err);
            setHelp('Could not stamp this page.');
        }
    });

    btnApplyAll.addEventListener('click', async function () {
        try {
            var bytes = await buildStampedPdf('all');
            await reloadFromBytes(bytes, true);
            setHelp('Stamp applied to all ' + pageCount + ' pages. Download your PDF when ready.');
        } catch (err) {
            console.error(err);
            setHelp('Could not stamp all pages.');
        }
    });

    btnDownload.addEventListener('click', async function () {
        try {
            savePlacementForPage();
            var bytes = placements[pageNum]
                ? await buildStampedPdf('page')
                : pdfBytes.slice(0);
            if (placements[pageNum]) {
                await reloadFromBytes(bytes, false);
            }
            var blob = new Blob([bytes], { type: 'application/pdf' });
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = fileName;
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);
            setHelp('Downloaded ' + fileName + '.');
        } catch (err) {
            console.error(err);
            setHelp('Download failed. Try applying the stamp again.');
        }
    });

    refreshOverlayImage();
})();
</script>
<style>
@media (max-width: 900px) {
    #stamper-workspace + div,
    .app-main-body > div > div[style*="grid-template-columns: minmax(0, 1.2fr)"] {
        grid-template-columns: 1fr !important;
    }
}
</style>
@endpush
@endif
