{{-- Global Download app modal + install script (logged-in sole-trader shell). --}}
<div id="pb-install-app-modal" class="pb-modal" hidden>
    <div class="pb-modal-backdrop" data-close-install-app></div>
    <div class="pb-modal-panel" role="dialog" aria-modal="true" aria-labelledby="pb-install-app-title" style="max-width: 440px;">
        <h2 id="pb-install-app-title" style="margin: 0 0 0.5rem; color: var(--primary-navy); font-size: 1.15rem;">Download PractisBase</h2>
        <p style="margin: 0 0 1rem; color: var(--text-muted); font-size: 0.9rem; line-height: 1.45;">
            Add PractisBase to your phone or desktop for one-tap return — no app store.
        </p>

        <button type="button" id="pb-install-prompt-btn" hidden style="width: 100%; margin-bottom: 1rem; background: var(--primary-cerulean); color: white; border: none; padding: 0.75rem 1rem; border-radius: var(--radius-md); font-weight: 700; font-size: 0.95rem; cursor: pointer;">
            Install PractisBase
        </button>

        <div id="pb-install-already" hidden style="margin-bottom: 1rem; padding: 0.75rem 0.9rem; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: var(--radius-md); color: #065f46; font-size: 0.85rem; line-height: 1.45;">
            PractisBase is already installed on this device. Open it from your home screen or app list.
        </div>

        <div style="display: grid; gap: 0.75rem; font-size: 0.85rem; line-height: 1.5; color: var(--text-main);">
            <div style="padding: 0.75rem 0.9rem; background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                <strong style="color: var(--primary-navy);">iPhone / iPad</strong>
                <div style="color: var(--text-muted); margin-top: 0.25rem;">Safari → Share → Add to Home Screen</div>
            </div>
            <div style="padding: 0.75rem 0.9rem; background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                <strong style="color: var(--primary-navy);">Android</strong>
                <div style="color: var(--text-muted); margin-top: 0.25rem;">Chrome menu → Install app / Add to Home screen</div>
            </div>
            <div style="padding: 0.75rem 0.9rem; background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                <strong style="color: var(--primary-navy);">Desktop</strong>
                <div style="color: var(--text-muted); margin-top: 0.25rem;">Chrome or Edge → install icon in the address bar, or use the button above when shown</div>
            </div>
        </div>

        <div id="pb-install-status" style="display: none; margin-top: 0.85rem; font-size: 0.85rem; color: var(--text-muted);"></div>

        <div style="display: flex; justify-content: flex-end; margin-top: 1.15rem;">
            <button type="button" data-close-install-app style="padding: 0.65rem 1rem; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-weight: 600; cursor: pointer;">Close</button>
        </div>
    </div>
</div>

<script>
(function () {
    var modal = document.getElementById('pb-install-app-modal');
    if (!modal) return;

    var promptBtn = document.getElementById('pb-install-prompt-btn');
    var already = document.getElementById('pb-install-already');
    var status = document.getElementById('pb-install-status');
    var deferredPrompt = null;

    function isStandalone() {
        return window.matchMedia('(display-mode: standalone)').matches
            || window.navigator.standalone === true;
    }

    function openModal() {
        modal.hidden = false;
        document.body.classList.add('nav-lock');
        if (isStandalone() && already) already.hidden = false;
    }

    function closeModal() {
        modal.hidden = true;
        document.body.classList.remove('nav-lock');
    }

    document.querySelectorAll('[data-open-install-app]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            openModal();
        });
    });

    modal.querySelectorAll('[data-close-install-app]').forEach(function (el) {
        el.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.hidden) closeModal();
    });

    if (isStandalone()) {
        document.querySelectorAll('[data-open-install-app]').forEach(function (el) {
            el.style.display = 'none';
        });
        if (already) already.hidden = false;
        return;
    }

    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        deferredPrompt = e;
        if (promptBtn) promptBtn.hidden = false;
    });

    if (promptBtn) {
        promptBtn.addEventListener('click', function () {
            if (!deferredPrompt) return;
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then(function (choice) {
                if (status) {
                    status.style.display = 'block';
                    status.textContent = choice.outcome === 'accepted'
                        ? 'Installed. Open PractisBase from your home screen or app list next time.'
                        : 'You can still use the steps above anytime.';
                }
                deferredPrompt = null;
                promptBtn.hidden = true;
            });
        });
    }
})();
</script>
