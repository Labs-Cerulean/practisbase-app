@extends('layouts.app')

@section('page_title', $stamp ? 'Edit stamp' : 'New stamp')

@section('content')
    @php
        $isEdit = (bool) $stamp;
        $action = $isEdit ? '/stamper/stamps/'.$stamp->id : '/stamper/stamps';
        $sigUri = $isEdit ? $stamp->signatureDataUri() : null;
        $returnTo = $returnTo ?? null;
        $isClinicalSetup = $isClinicalSetup ?? false;
    @endphp

    <div style="max-width: 980px; margin: 0 auto;">
        <div style="margin-bottom: 1.25rem;">
            <a href="{{ $returnTo ?: '/stamper/stamps' }}" style="color: var(--primary-cerulean); font-weight: 600; font-size: 0.85rem; text-decoration: none;">← {{ $returnTo ? 'Back' : 'My stamps' }}</a>
            <h1 style="color: var(--primary-navy); margin: 0.5rem 0 0.35rem; font-size: 1.5rem;">{{ $isEdit ? 'Edit stamp' : ($isClinicalSetup ? 'Create your clinical stamp' : 'Create your stamp') }}</h1>
            <p style="color: var(--text-muted); margin: 0; line-height: 1.45;">
                @if($isClinicalSetup)
                    One stamp for prescriptions, referrals, and certificates. Choose a preset, add your warrant details, then upload or draw a signature.
                @else
                    Choose a preset, enter your details, then upload a wet signature or draw one here.
                @endif
            </p>
        </div>

        @if(session('success'))
            <div style="background: #ecfdf5; color: #065f46; padding: 0.85rem 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div style="background: #fef2f2; color: #991b1b; padding: 0.85rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
                @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif

        <form action="{{ $action }}" method="POST" enctype="multipart/form-data" id="stamp-form">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif
            @if($returnTo)
                <input type="hidden" name="return" value="{{ $returnTo }}">
            @endif
            <input type="hidden" name="composed_data" id="composed-data" value="">

            <div style="display: grid; grid-template-columns: minmax(0, 1.1fr) minmax(0, 0.9fr); gap: 1.25rem; align-items: start;">
                <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.5rem; box-shadow: var(--shadow-sm);">
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Stamp label</label>
                        <input type="text" name="label" id="stamp-label" value="{{ old('label', $defaults['label']) }}" required maxlength="120" placeholder="e.g. Official warrant stamp" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.55rem;">Preset</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.55rem;" id="preset-grid">
                            @foreach($presets as $key => $label)
                                <label style="display: block; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.7rem 0.8rem; cursor: pointer; background: #fff;">
                                    <input type="radio" name="preset" value="{{ $key }}" {{ old('preset', $defaults['preset']) === $key ? 'checked' : '' }} style="margin-right: 0.4rem;">
                                    <span style="font-weight: 600; color: var(--primary-navy); font-size: 0.9rem;">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem; margin-bottom: 1rem;">
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Name</label>
                            <input type="text" name="first_name" id="stamp-first" value="{{ old('first_name', $defaults['first_name']) }}" required maxlength="120" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        </div>
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Surname</label>
                            <input type="text" name="last_name" id="stamp-last" value="{{ old('last_name', $defaults['last_name']) }}" required maxlength="120" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        </div>
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Postnominals</label>
                        <input type="text" name="postnominals" id="stamp-post" value="{{ old('postnominals', $defaults['postnominals']) }}" maxlength="120" placeholder="e.g. B.E.&amp;A. (Hons), A.&amp;C.E." style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Role / profession</label>
                        <input type="text" name="role_title" id="stamp-role" value="{{ old('role_title', $defaults['role_title']) }}" required maxlength="160" placeholder="e.g. Perit, Medical Practitioner, Engineer" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Warrant number</label>
                        @php
                            $warrantDefault = $defaults['warrant_number'] ?? '';
                            if ($warrantDefault === '' || $warrantDefault === null) {
                                $warrantDefault = (string) (auth()->user()->warrant_number ?? '');
                            }
                        @endphp
                        <input type="text" name="warrant_number" id="stamp-warrant" value="{{ old('warrant_number', $warrantDefault) }}" maxlength="80" placeholder="e.g. 3264" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.35rem;">Same as Settings → Practice</div>
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Wet signature upload</label>
                        <input type="file" name="signature" id="stamp-signature-file" accept=".jpg,.jpeg,.png,.webp,image/*" style="width: 100%; padding: 0.5rem 0;">
                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.35rem;">JPG, PNG or WebP up to 2 MB. White paper behind the ink is removed automatically. Or draw below.</div>
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; margin-bottom: 0.4rem;">
                            <label style="font-weight: 600; margin: 0;">Draw signature</label>
                            <button type="button" id="clear-draw" style="background: none; border: none; color: var(--primary-cerulean); font-weight: 600; font-size: 0.8rem; cursor: pointer; padding: 0;">Clear pad</button>
                        </div>
                        <canvas id="sig-pad" width="640" height="180" style="width: 100%; height: 180px; border: 1px dashed var(--border-light); border-radius: var(--radius-md); background: #f8fafc; touch-action: none; cursor: crosshair;"></canvas>
                        <input type="hidden" name="signature_data" id="signature-data" value="">
                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.35rem;">Drawn signatures export with a clear background so they do not cover PDF text.</div>
                    </div>

                    @if($sigUri)
                        <div style="margin-bottom: 1rem; padding: 0.85rem; background: #f8fafc; border-radius: var(--radius-md); border: 1px solid var(--border-light);">
                            <div style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.45rem;">Current signature</div>
                            <img src="{{ $sigUri }}" alt="Current signature" style="max-height: 72px; max-width: 100%;">
                            <label style="display: flex; align-items: center; gap: 0.45rem; margin-top: 0.65rem; font-size: 0.85rem; color: #991b1b;">
                                <input type="checkbox" name="remove_signature" value="1">
                                Remove saved signature
                            </label>
                        </div>
                    @endif

                    <label style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem; font-size: 0.9rem;">
                        <input type="checkbox" name="is_default" value="1" {{ old('is_default', $defaults['is_default']) ? 'checked' : '' }}>
                        Use as default stamp
                    </label>

                    <button type="submit" style="width: 100%; padding: 0.9rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">{{ $isEdit ? 'Save changes' : 'Save stamp' }}</button>
                </div>

                <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.5rem; box-shadow: var(--shadow-sm); position: sticky; top: 1rem;">
                    <div style="font-weight: 700; color: var(--primary-navy); margin-bottom: 0.75rem;">Live preview</div>
                    <canvas id="stamp-preview" width="420" height="220" style="width: 100%; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: #fff;"></canvas>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0.75rem 0 0; line-height: 1.4;">Checkerboard means a clear stamp background. Text on the PDF stays visible underneath.</p>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    var preview = document.getElementById('stamp-preview');
    var pad = document.getElementById('sig-pad');
    var previewCtx = preview.getContext('2d');
    var padCtx = pad.getContext('2d');
    var signatureData = document.getElementById('signature-data');
    var fileInput = document.getElementById('stamp-signature-file');
    var uploadedImg = null;
    var drawing = false;
    var drawn = false;
    var existingSig = @json($sigUri);

    function fitPad() {
        var rect = pad.getBoundingClientRect();
        var ratio = window.devicePixelRatio || 1;
        pad.width = Math.floor(rect.width * ratio);
        pad.height = Math.floor(180 * ratio);
        padCtx.setTransform(ratio, 0, 0, ratio, 0, 0);
        padCtx.lineWidth = 2.2;
        padCtx.lineCap = 'round';
        padCtx.strokeStyle = '#0b1f33';
        padCtx.clearRect(0, 0, rect.width, 180);
    }

    function pointerPos(e) {
        var rect = pad.getBoundingClientRect();
        var src = e.touches && e.touches[0] ? e.touches[0] : e;
        return { x: src.clientX - rect.left, y: src.clientY - rect.top };
    }

    function startDraw(e) {
        drawing = true;
        var p = pointerPos(e);
        padCtx.beginPath();
        padCtx.moveTo(p.x, p.y);
        e.preventDefault();
    }
    function moveDraw(e) {
        if (!drawing) return;
        var p = pointerPos(e);
        padCtx.lineTo(p.x, p.y);
        padCtx.stroke();
        drawn = true;
        e.preventDefault();
        renderPreview();
    }
    function endDraw() {
        if (!drawing) return;
        drawing = false;
        if (drawn) {
            signatureData.value = pad.toDataURL('image/png');
            uploadedImg = null;
            if (fileInput) fileInput.value = '';
        }
    }

    pad.addEventListener('mousedown', startDraw);
    pad.addEventListener('mousemove', moveDraw);
    window.addEventListener('mouseup', endDraw);
    pad.addEventListener('touchstart', startDraw, { passive: false });
    pad.addEventListener('touchmove', moveDraw, { passive: false });
    pad.addEventListener('touchend', endDraw);

    document.getElementById('clear-draw').addEventListener('click', function () {
        fitPad();
        drawn = false;
        signatureData.value = '';
        renderPreview();
    });

    if (fileInput) {
        fileInput.addEventListener('change', function () {
            var file = fileInput.files && fileInput.files[0];
            if (!file) return;
            var reader = new FileReader();
            reader.onload = function () {
                var img = new Image();
                img.onload = function () {
                    uploadedImg = knockOutPaper(img);
                    drawn = false;
                    signatureData.value = '';
                    fitPad();
                    renderPreview();
                };
                img.src = reader.result;
            };
            reader.readAsDataURL(file);
        });
    }

    function field(id) {
        var el = document.getElementById(id);
        return el ? el.value.trim() : '';
    }
    function selectedPreset() {
        var el = document.querySelector('input[name="preset"]:checked');
        return el ? el.value : 'classic_border';
    }
    function fullName() {
        return (field('stamp-first') + ' ' + field('stamp-last')).trim();
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

    function sigSource() {
        if (uploadedImg) return uploadedImg;
        if (drawn && signatureData.value) {
            var img = new Image();
            img.src = signatureData.value;
            return img;
        }
        if (existingSig && !document.querySelector('input[name="remove_signature"]:checked')) {
            var existing = new Image();
            existing.src = existingSig;
            return existing;
        }
        return null;
    }

    function drawStamp(ctx, w, h, opts) {
        ctx.clearRect(0, 0, w, h);

        var size = 12;
        for (var y = 0; y < h; y += size) {
            for (var x = 0; x < w; x += size) {
                ctx.fillStyle = ((x / size + y / size) % 2 === 0) ? '#f1f5f9' : '#ffffff';
                ctx.fillRect(x, y, size, size);
            }
        }

        var name = opts.name || 'Your name';
        var post = opts.post || '';
        var role = opts.role || 'Role';
        var warrant = opts.warrant || '';
        var warrantLine = warrant ? ('Warrant ' + warrant) : '';
        var preset = opts.preset;
        var sig = opts.sig ? knockOutPaper(opts.sig) : null;

        ctx.textAlign = 'center';
        ctx.fillStyle = '#0b1f33';

        if (preset === 'circular_seal') {
            var cx = w / 2, cy = h / 2, r = Math.min(w, h) * 0.42;
            ctx.strokeStyle = '#0b1f33';
            ctx.lineWidth = 3;
            ctx.beginPath();
            ctx.arc(cx, cy, r, 0, Math.PI * 2);
            ctx.stroke();
            ctx.lineWidth = 1.5;
            ctx.beginPath();
            ctx.arc(cx, cy, r - 8, 0, Math.PI * 2);
            ctx.stroke();
            ctx.font = 'bold 14px Georgia, serif';
            ctx.fillText(name, cx, cy - 22);
            ctx.font = '11px Georgia, serif';
            if (post) ctx.fillText(post, cx, cy - 4);
            ctx.fillText(role, cx, cy + 14);
            if (warrantLine) ctx.fillText(warrantLine, cx, cy + 30);
            if (sig && sig.complete !== false) {
                try { ctx.drawImage(sig, cx - 50, cy + 38, 100, 32); } catch (e) {}
            }
            return;
        }

        if (preset === 'minimal_line') {
            ctx.textAlign = 'left';
            ctx.font = 'bold 18px Georgia, serif';
            ctx.fillText(name + (post ? ', ' + post : ''), 24, 42);
            ctx.strokeStyle = '#0b1f33';
            ctx.lineWidth = 1.5;
            ctx.beginPath();
            ctx.moveTo(24, 56);
            ctx.lineTo(w - 24, 56);
            ctx.stroke();
            ctx.font = '13px Inter, sans-serif';
            ctx.fillStyle = '#334155';
            ctx.fillText(role + (warrantLine ? ' · ' + warrantLine : ''), 24, 78);
            if (sig && sig.complete !== false) {
                try { ctx.drawImage(sig, 24, 92, 180, 55); } catch (e) {}
            }
            return;
        }

        if (preset === 'warrant_block') {
            ctx.strokeStyle = '#0b1f33';
            ctx.lineWidth = 2;
            ctx.strokeRect(16, 16, w - 32, h - 32);
            ctx.fillStyle = '#0b1f33';
            ctx.fillRect(16, 16, w - 32, 34);
            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 13px Inter, sans-serif';
            ctx.fillText(role.toUpperCase(), w / 2, 38);
            ctx.fillStyle = '#0b1f33';
            ctx.font = 'bold 18px Georgia, serif';
            ctx.fillText(name, w / 2, 72);
            ctx.font = '12px Georgia, serif';
            if (post) ctx.fillText(post, w / 2, 92);
            if (warrantLine) ctx.fillText(warrantLine, w / 2, 110);
            if (sig && sig.complete !== false) {
                try { ctx.drawImage(sig, w / 2 - 70, 122, 140, 48); } catch (e) {}
            }
            return;
        }

        ctx.strokeStyle = '#0b1f33';
        ctx.lineWidth = 2.5;
        ctx.strokeRect(18, 18, w - 36, h - 36);
        ctx.lineWidth = 1;
        ctx.strokeRect(24, 24, w - 48, h - 48);
        ctx.font = 'bold 17px Georgia, serif';
        ctx.fillText(name, w / 2, 62);
        ctx.font = '12px Georgia, serif';
        if (post) ctx.fillText(post, w / 2, 82);
        ctx.font = '12px Inter, sans-serif';
        ctx.fillStyle = '#334155';
        ctx.fillText(role, w / 2, 100);
        if (warrantLine) ctx.fillText(warrantLine, w / 2, 116);
        if (sig && sig.complete !== false) {
            try { ctx.drawImage(sig, w / 2 - 70, 128, 140, 48); } catch (e) {}
        }
    }

    function renderPreview() {
        var sig = sigSource();
        var paint = function () {
            drawStamp(previewCtx, preview.width, preview.height, {
                name: fullName(),
                post: field('stamp-post'),
                role: field('stamp-role'),
                warrant: field('stamp-warrant'),
                preset: selectedPreset(),
                sig: sig
            });
        };
        if (sig && !sig.complete) {
            sig.onload = paint;
        }
        paint();
    }

    ['stamp-first', 'stamp-last', 'stamp-post', 'stamp-role', 'stamp-warrant'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('input', renderPreview);
    });
    document.querySelectorAll('input[name="preset"]').forEach(function (el) {
        el.addEventListener('change', renderPreview);
    });
    var removeBox = document.querySelector('input[name="remove_signature"]');
    if (removeBox) removeBox.addEventListener('change', renderPreview);

    function exportComposedDataUri() {
        var c = document.createElement('canvas');
        c.width = 840;
        c.height = 440;
        var g = c.getContext('2d');
        var name = fullName() || 'Your name';
        var post = field('stamp-post');
        var role = field('stamp-role') || 'Role';
        var warrant = field('stamp-warrant');
        var warrantLine = warrant ? ('Warrant ' + warrant) : '';
        var preset = selectedPreset();
        var sigImg = sigSource();
        var sig = sigImg ? knockOutPaper(sigImg) : null;

        g.clearRect(0, 0, c.width, c.height);
        g.textAlign = 'center';
        g.fillStyle = '#0b1f33';

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
            if (sig) try { g.drawImage(sig, cx - 100, cy + 62, 200, 64); } catch (e) {}
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
            if (sig) try { g.drawImage(sig, 48, 200, 320, 110); } catch (e) {}
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
            if (sig) try { g.drawImage(sig, c.width / 2 - 140, 250, 280, 100); } catch (e) {}
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
            if (sig) try { g.drawImage(sig, c.width / 2 - 140, 255, 280, 100); } catch (e) {}
        }

        return c.toDataURL('image/png');
    }

    document.getElementById('stamp-form').addEventListener('submit', function () {
        if (drawn) {
            signatureData.value = pad.toDataURL('image/png');
        }
        var composed = document.getElementById('composed-data');
        if (composed) {
            composed.value = exportComposedDataUri();
        }
    });

    fitPad();
    window.addEventListener('resize', function () {
        var keep = drawn ? pad.toDataURL('image/png') : null;
        fitPad();
        if (keep) {
            var img = new Image();
            img.onload = function () {
                padCtx.drawImage(img, 0, 0, pad.getBoundingClientRect().width, 180);
                signatureData.value = keep;
                drawn = true;
                renderPreview();
            };
            img.src = keep;
        } else {
            renderPreview();
        }
    });
    renderPreview();
})();
</script>
<style>
@media (max-width: 860px) {
    #stamp-form > div { grid-template-columns: 1fr !important; }
    #preset-grid { grid-template-columns: 1fr !important; }
}
</style>
@endpush
