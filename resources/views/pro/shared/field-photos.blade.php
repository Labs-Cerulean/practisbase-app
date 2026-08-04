{{--
  Camera-first photo capture for Arch/Eng field forms.
  Expects no Blade vars; wires itself via ids.
--}}
<section id="photos" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm); display: grid; gap: 0.75rem;">
    <h2 style="margin: 0; font-size: 1.05rem; color: var(--primary-navy);">Site photos</h2>
    <p style="margin: 0; font-size: 0.82rem; color: var(--text-muted); line-height: 1.45;">
        Prefer the camera on site. You can also pick from the library. Up to 12 images, 5 MB each. Existing photos stay when you add more.
    </p>

    <div class="eng-photo-actions">
        <button type="button" class="eng-photo-camera" id="engPhotoCameraBtn">Take photo</button>
        <button type="button" class="eng-photo-library" id="engPhotoLibraryBtn">From library</button>
    </div>

    <input type="file" id="engPhotoCamera" accept="image/*" capture="environment" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0;" tabindex="-1" aria-hidden="true">
    <input type="file" id="engPhotoLibrary" accept="image/jpeg,image/png,image/webp" multiple style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0;" tabindex="-1" aria-hidden="true">
    <input type="file" name="photos[]" id="engPhotoSubmit" accept="image/jpeg,image/png,image/webp" multiple style="display:none;" tabindex="-1" aria-hidden="true">

    <div id="engPhotoPreviews" class="eng-photo-previews" hidden></div>
    <div id="engPhotoCount" style="font-size: 0.78rem; color: var(--text-muted);"></div>
</section>

<script>
    (function () {
        var MAX = 12;
        var cameraInput = document.getElementById('engPhotoCamera');
        var libraryInput = document.getElementById('engPhotoLibrary');
        var submitInput = document.getElementById('engPhotoSubmit');
        var previews = document.getElementById('engPhotoPreviews');
        var countEl = document.getElementById('engPhotoCount');
        var cameraBtn = document.getElementById('engPhotoCameraBtn');
        var libraryBtn = document.getElementById('engPhotoLibraryBtn');
        if (!cameraInput || !libraryInput || !submitInput || !previews) return;

        var files = [];
        var urls = [];

        function syncSubmit() {
            var dt = new DataTransfer();
            files.forEach(function (f) { dt.items.add(f); });
            submitInput.files = dt.files;
        }

        function revokeUrls() {
            urls.forEach(function (u) { URL.revokeObjectURL(u); });
            urls = [];
        }

        function render() {
            revokeUrls();
            previews.innerHTML = '';
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
                    render();
                });
                wrap.appendChild(img);
                wrap.appendChild(rm);
                previews.appendChild(wrap);
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
            }
            render();
        }

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
