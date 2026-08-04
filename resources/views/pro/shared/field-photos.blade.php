{{--
  Camera-first photo capture for Arch/Eng field forms.
  Optional Blade vars:
    $photoLinkOptions — list of ['id' => ..., 'label' => ...] for issue/row links
    $existingPhotos — collection of already-saved photos (edit mode)
    $existingPhotoBase — URL prefix to open an existing photo, e.g. /pro/.../photos/
--}}
@php
    $photoLinkOptions = $photoLinkOptions ?? [];
    $existingPhotos = $existingPhotos ?? collect();
    $existingPhotoBase = $existingPhotoBase ?? null;
@endphp
<section id="photos" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm); display: grid; gap: 0.75rem;">
    <h2 style="margin: 0; font-size: 1.05rem; color: var(--primary-navy);">Site photos</h2>
    <p style="margin: 0; font-size: 0.82rem; color: var(--text-muted); line-height: 1.45;">
        Prefer the camera on site. Link each photo to an issue/row id or leave a note. Up to 12 new images per save, 5 MB each. Existing photos stay when you add more.
    </p>

    @if($existingPhotos->isNotEmpty())
        <div style="display: grid; gap: 0.45rem;">
            <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted);">SAVED PHOTOS</div>
            <div style="display: grid; gap: 0.45rem;">
                @foreach($existingPhotos as $photo)
                    <div style="display: grid; grid-template-columns: auto 1fr; gap: 0.65rem; align-items: start; border: 1px solid #e2e8f0; border-radius: var(--radius-md); padding: 0.55rem 0.65rem;">
                        <div style="font-size: 0.8rem; font-weight: 700; color: #3f6212; min-width: 4.5rem;">
                            @if($existingPhotoBase)
                                <a href="{{ $existingPhotoBase }}/{{ $photo->id }}" target="_blank" style="color: inherit; text-decoration: none;">Photo {{ $loop->iteration }}</a>
                            @else
                                Photo {{ $loop->iteration }}
                            @endif
                        </div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">
                            @if(!empty($photo->linked_row_id))
                                <div>Linked to <code style="font-size: 0.75rem;">{{ $photo->linked_row_id }}</code></div>
                            @endif
                            @if($photo->caption)
                                <div>{{ $photo->caption }}</div>
                            @endif
                            @if(empty($photo->linked_row_id) && ! $photo->caption)
                                <div>—</div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="eng-photo-actions">
        <button type="button" class="eng-photo-camera" id="engPhotoCameraBtn">Take photo</button>
        <button type="button" class="eng-photo-library" id="engPhotoLibraryBtn">From library</button>
    </div>

    <input type="file" id="engPhotoCamera" accept="image/*" capture="environment" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0;" tabindex="-1" aria-hidden="true">
    <input type="file" id="engPhotoLibrary" accept="image/jpeg,image/png,image/webp" multiple style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0;" tabindex="-1" aria-hidden="true">
    <input type="file" name="photos[]" id="engPhotoSubmit" accept="image/jpeg,image/png,image/webp" multiple style="display:none;" tabindex="-1" aria-hidden="true">

    <div id="engPhotoPreviews" class="eng-photo-previews" hidden></div>
    <div id="engPhotoMeta" style="display: grid; gap: 0.45rem;"></div>
    <div id="engPhotoCount" style="font-size: 0.78rem; color: var(--text-muted);"></div>
</section>

<script>
    (function () {
        var MAX = 12;
        var cameraInput = document.getElementById('engPhotoCamera');
        var libraryInput = document.getElementById('engPhotoLibrary');
        var submitInput = document.getElementById('engPhotoSubmit');
        var previews = document.getElementById('engPhotoPreviews');
        var metaBox = document.getElementById('engPhotoMeta');
        var countEl = document.getElementById('engPhotoCount');
        var cameraBtn = document.getElementById('engPhotoCameraBtn');
        var libraryBtn = document.getElementById('engPhotoLibraryBtn');
        if (!cameraInput || !libraryInput || !submitInput || !previews || !metaBox) return;

        var linkOptions = @json($photoLinkOptions);
        var files = [];
        var urls = [];
        var captions = [];
        var linkedIds = [];

        function syncSubmit() {
            var dt = new DataTransfer();
            files.forEach(function (f) { dt.items.add(f); });
            submitInput.files = dt.files;
        }

        function revokeUrls() {
            urls.forEach(function (u) { URL.revokeObjectURL(u); });
            urls = [];
        }

        function optionHtml(selected) {
            var html = '<option value="">No issue link</option>';
            (linkOptions || []).forEach(function (opt) {
                html += '<option value="' + String(opt.id).replace(/"/g, '&quot;') + '"' +
                    (selected === opt.id ? ' selected' : '') + '>' +
                    String(opt.label).replace(/</g, '&lt;') + '</option>';
            });
            return html;
        }

        function render() {
            revokeUrls();
            previews.innerHTML = '';
            metaBox.innerHTML = '';
            if (files.length === 0) {
                previews.hidden = true;
                countEl.textContent = '';
                syncSubmit();
                return;
            }
            previews.hidden = false;
            countEl.textContent = files.length + ' photo' + (files.length === 1 ? '' : 's') + ' ready to upload (max ' + MAX + ').';
            files.forEach(function (file, index) {
                var url = URL.createObjectURL(file);
                urls.push(url);
                var wrap = document.createElement('div');
                wrap.className = 'eng-photo-thumb';
                var img = document.createElement('img');
                img.src = url;
                img.alt = file.name || ('Photo ' + (index + 1));
                var rm = document.createElement('button');
                rm.type = 'button';
                rm.className = 'eng-photo-remove';
                rm.setAttribute('aria-label', 'Remove photo');
                rm.textContent = '×';
                rm.addEventListener('click', function () {
                    files.splice(index, 1);
                    captions.splice(index, 1);
                    linkedIds.splice(index, 1);
                    render();
                });
                wrap.appendChild(img);
                wrap.appendChild(rm);
                previews.appendChild(wrap);

                var meta = document.createElement('div');
                meta.style.cssText = 'display:grid;grid-template-columns:auto 1fr 1.2fr;gap:0.4rem;align-items:center;border:1px solid #e2e8f0;border-radius:var(--radius-md);padding:0.45rem 0.55rem;';
                meta.innerHTML =
                    '<div style="font-size:0.78rem;font-weight:700;color:var(--primary-navy);">New ' + (index + 1) + '</div>' +
                    '<select name="photo_linked_row_ids[]" style="width:100%;padding:0.4rem 0.45rem;border:1px solid var(--border-light);border-radius:var(--radius-md);font-size:0.8rem;">' +
                        optionHtml(linkedIds[index] || '') +
                    '</select>' +
                    '<input type="text" name="photo_captions[]" value="' + String(captions[index] || '').replace(/"/g, '&quot;') + '" placeholder="Note / comment" style="width:100%;padding:0.4rem 0.45rem;border:1px solid var(--border-light);border-radius:var(--radius-md);font-size:0.8rem;">';
                var sel = meta.querySelector('select');
                var note = meta.querySelector('input');
                sel.addEventListener('change', function () { linkedIds[index] = sel.value; });
                note.addEventListener('input', function () { captions[index] = note.value; });
                metaBox.appendChild(meta);
            });
            syncSubmit();
        }

        function addFiles(list) {
            if (!list || !list.length) return;
            for (var i = 0; i < list.length; i++) {
                if (files.length >= MAX) break;
                var f = list[i];
                if (!f || !f.type || f.type.indexOf('image/') !== 0) continue;
                files.push(f);
                captions.push('');
                linkedIds.push('');
            }
            render();
        }

        window.practisRefreshPhotoLinkOptions = function (options) {
            linkOptions = options || [];
            render();
        };

        cameraBtn.addEventListener('click', function () { cameraInput.click(); });
        libraryBtn.addEventListener('click', function () { libraryInput.click(); });
        cameraInput.addEventListener('change', function () {
            addFiles(cameraInput.files);
            cameraInput.value = '';
        });
        libraryInput.addEventListener('change', function () {
            addFiles(libraryInput.files);
            libraryInput.value = '';
        });

        if (window.location.hash === '#photos') {
            var photos = document.getElementById('photos');
            if (photos) {
                setTimeout(function () {
                    photos.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 80);
            }
        }
    })();
</script>
