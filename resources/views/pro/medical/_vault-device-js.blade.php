{{-- Shared WebAuthn trusted-device helpers for medical vault. --}}
<script>
window.PractisVaultDevice = (function () {
    var DB_NAME = 'practisbase_vault_v1';
    var STORE = 'wrap_keys';
    var prefetchedUnlock = null;
    var prefetchTimer = null;
    var prefetchVisibilityBound = false;

    function csrfMeta() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }

    function xsrfCookie() {
        var match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
        if (!match) return '';
        try { return decodeURIComponent(match[1]); } catch (e) { return match[1]; }
    }

    function authHeaders(includeCsrf) {
        var headers = {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        };
        if (includeCsrf !== false) {
            headers['X-CSRF-TOKEN'] = csrfMeta();
            var xsrf = xsrfCookie();
            if (xsrf) headers['X-XSRF-TOKEN'] = xsrf;
        }
        return headers;
    }

    function bufferToBase64url(buffer) {
        var bytes = new Uint8Array(buffer);
        var str = '';
        for (var i = 0; i < bytes.length; i++) str += String.fromCharCode(bytes[i]);
        return btoa(str).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');
    }

    /** Normalize credential ids to unpadded base64url for IndexedDB / allowCredentials matching. */
    function normalizeCredId(value) {
        if (value && typeof value !== 'string') {
            try { return bufferToBase64url(value); } catch (e) { return ''; }
        }
        return String(value || '')
            .replace(/\+/g, '-')
            .replace(/\//g, '_')
            .replace(/=+$/g, '');
    }

    function base64urlToBuffer(value) {
        var pad = '='.repeat((4 - (value.length % 4)) % 4);
        var b64 = (value + pad).replace(/-/g, '+').replace(/_/g, '/');
        var str = atob(b64);
        var bytes = new Uint8Array(str.length);
        for (var i = 0; i < str.length; i++) bytes[i] = str.charCodeAt(i);
        return bytes.buffer;
    }

    function openDb() {
        return new Promise(function (resolve, reject) {
            var req = indexedDB.open(DB_NAME, 1);
            req.onupgradeneeded = function () {
                var db = req.result;
                if (!db.objectStoreNames.contains(STORE)) db.createObjectStore(STORE);
            };
            req.onsuccess = function () { resolve(req.result); };
            req.onerror = function () { reject(req.error); };
        });
    }

    function idbPut(credentialId, wrapKeyB64) {
        return openDb().then(function (db) {
            return new Promise(function (resolve, reject) {
                var tx = db.transaction(STORE, 'readwrite');
                tx.objectStore(STORE).put(wrapKeyB64, credentialId);
                tx.oncomplete = function () { resolve(); };
                tx.onerror = function () { reject(tx.error); };
            });
        });
    }

    function idbGet(credentialId) {
        return openDb().then(function (db) {
            return new Promise(function (resolve, reject) {
                var tx = db.transaction(STORE, 'readonly');
                var req = tx.objectStore(STORE).get(credentialId);
                req.onsuccess = function () { resolve(req.result || null); };
                req.onerror = function () { reject(req.error); };
            });
        });
    }

    function idbDelete(credentialId) {
        return openDb().then(function (db) {
            return new Promise(function (resolve, reject) {
                var tx = db.transaction(STORE, 'readwrite');
                tx.objectStore(STORE).delete(credentialId);
                tx.oncomplete = function () { resolve(); };
                tx.onerror = function () { reject(tx.error); };
            });
        });
    }

    function idbKeys() {
        return openDb().then(function (db) {
            return new Promise(function (resolve, reject) {
                var tx = db.transaction(STORE, 'readonly');
                var req = tx.objectStore(STORE).getAllKeys();
                req.onsuccess = function () { resolve(req.result || []); };
                req.onerror = function () { reject(req.error); };
            });
        });
    }

    function supported() {
        return !!(window.PublicKeyCredential && navigator.credentials && navigator.credentials.create);
    }

    function platformAvailable() {
        if (!supported()) return Promise.resolve(false);
        if (!window.PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable) {
            // Older WebKit: still attempt if WebAuthn exists.
            return Promise.resolve(true);
        }
        return window.PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable().catch(function () {
            return true;
        });
    }

    function preparePublicKey(publicKey) {
        var pk = Object.assign({}, publicKey);
        pk.challenge = base64urlToBuffer(typeof pk.challenge === 'string' ? pk.challenge : bufferToBase64url(pk.challenge));
        if (pk.user && pk.user.id) {
            pk.user = Object.assign({}, pk.user, {
                id: base64urlToBuffer(typeof pk.user.id === 'string' ? pk.user.id : bufferToBase64url(pk.user.id))
            });
        }
        if (pk.excludeCredentials) {
            pk.excludeCredentials = pk.excludeCredentials.map(function (c) {
                return Object.assign({}, c, {
                    id: base64urlToBuffer(typeof c.id === 'string' ? c.id : bufferToBase64url(c.id))
                });
            });
        }
        if (pk.allowCredentials) {
            pk.allowCredentials = pk.allowCredentials.map(function (c) {
                return Object.assign({}, c, {
                    id: base64urlToBuffer(typeof c.id === 'string' ? c.id : bufferToBase64url(c.id))
                });
            });
        }
        return pk;
    }

    function humanizeWebAuthnError(e) {
        var name = (e && e.name) ? e.name : '';
        var msg = (e && e.message) ? e.message : '';
        if (name === 'NotAllowedError') {
            return 'Fingerprint / Face ID was cancelled or timed out. Tap Unlock and try again.';
        }
        if (name === 'InvalidStateError') {
            return 'This authenticator is already registered on the server. Unlock with your recovery code, revoke the old device in Settings, then enable quick unlock again.';
        }
        if (name === 'SecurityError') {
            return 'Browser blocked the unlock ceremony. Use Safari/Chrome on the same PractisBase address you registered.';
        }
        if (name === 'NotSupportedError') {
            return 'This browser does not support device unlock. Use your recovery code.';
        }
        if (name === 'AbortError') {
            return 'Unlock was interrupted. Tap Unlock and try again.';
        }
        return msg || 'Device unlock failed.';
    }

    function requestJson(url, method, body, opts) {
        opts = opts || {};
        var headers = authHeaders(opts.csrf !== false);
        if (body !== undefined) headers['Content-Type'] = 'application/json';
        return fetch(url, {
            method: method || 'GET',
            headers: headers,
            credentials: 'include',
            body: body !== undefined ? JSON.stringify(body || {}) : undefined
        }).then(function (res) {
            return res.json().then(function (data) {
                if (!res.ok) {
                    var err = new Error((data && data.message) ? data.message : 'Request failed');
                    err.status = res.status;
                    err.data = data;
                    throw err;
                }
                return data;
            }).catch(function (e) {
                if (e.status) throw e;
                throw new Error('Unexpected server response (' + res.status + '). Refresh and try again.');
            });
        });
    }

    function postJson(url, body, opts) {
        return requestJson(url, 'POST', body || {}, opts);
    }

    function listDevices() {
        return requestJson('/pro/medical/vault/devices', 'GET').then(function (data) {
            return (data && data.devices) ? data.devices : [];
        });
    }

    function revokeDevice(deviceId, credentialId) {
        return requestJson('/pro/medical/vault/devices/' + deviceId, 'DELETE').then(function (result) {
            var id = credentialId || (result && result.credential_id);
            if (id) {
                return idbDelete(id).then(function () { return result; }).catch(function () { return result; });
            }
            return result;
        });
    }

    function registerDevice(deviceLabel) {
        return postJson('/pro/medical/vault/devices/register-options', {}).then(function (opts) {
            var ticket = opts.registration_ticket;
            if (!ticket) throw new Error('Missing registration ticket from server. Refresh and try again.');
            return navigator.credentials.create({ publicKey: preparePublicKey(opts.publicKey) }).then(function (cred) {
                if (!cred) throw new Error('Device registration was cancelled.');
                var response = cred.response;
                return postJson('/pro/medical/vault/devices/register', {
                    registration_ticket: ticket,
                    clientDataJSON: bufferToBase64url(response.clientDataJSON),
                    attestationObject: bufferToBase64url(response.attestationObject),
                    device_label: deviceLabel || null
                }, { csrf: false }).then(function (result) {
                    return idbPut(result.credential_id, result.wrap_key).then(function () {
                        return result;
                    }).catch(function () {
                        // Server registered but local key failed — revoke so doctors can re-enable cleanly.
                        if (result.device_id) {
                            return revokeDevice(result.device_id, result.credential_id).then(function () {
                                throw new Error('Could not save the unlock key in this browser. Try again, or switch browsers/storage settings.');
                            }).catch(function (e) {
                                if (e.message && e.message.indexOf('Could not save') === 0) throw e;
                                throw new Error('Quick unlock registered on the server but this browser could not store the key. Revoke the device in Settings, then enable again.');
                            });
                        }
                        throw new Error('Could not save the unlock key in this browser. Try again.');
                    });
                });
            }).catch(function (e) {
                throw new Error(humanizeWebAuthnError(e));
            });
        });
    }

    function fetchUnlockOptions() {
        return Promise.all([
            postJson('/pro/medical/vault/devices/unlock-options', {}),
            idbKeys().catch(function () { return []; })
        ]).then(function (pair) {
            var opts = pair[0];
            var localKeys = pair[1] || [];
            if (!opts.unlock_ticket) throw new Error('Missing unlock ticket from server. Refresh and try again.');

            var localKeyIds = {};
            localKeys.forEach(function (k) { localKeyIds[normalizeCredId(k)] = true; });

            // Prefer credentials this browser can actually unwrap (IndexedDB wrap key).
            if (opts.publicKey && Array.isArray(opts.publicKey.allowCredentials)) {
                var filtered = opts.publicKey.allowCredentials.filter(function (c) {
                    return !!localKeyIds[normalizeCredId(c.id)];
                });
                if (filtered.length) {
                    opts = Object.assign({}, opts, {
                        publicKey: Object.assign({}, opts.publicKey, { allowCredentials: filtered })
                    });
                }
            }

            var expiresIn = (opts.expires_in || 300) * 1000;
            prefetchedUnlock = {
                opts: opts,
                readyAt: Date.now(),
                // Refresh before ticket mid-life so Safari click stays gesture-fresh.
                expiresAt: Date.now() + Math.max(60000, expiresIn - 90000)
            };
            return prefetchedUnlock;
        });
    }

    function prefetchUnlockOptions() {
        return fetchUnlockOptions().catch(function () {
            prefetchedUnlock = null;
            return null;
        });
    }

    /**
     * Keep a fresh unlock challenge ready while the unlock page is open.
     * Safari/iOS often rejects credentials.get() if a network fetch runs first
     * inside the same tap handler (user-activation is consumed by the await).
     */
    function startUnlockPrefetchKeepAlive() {
        prefetchUnlockOptions();
        if (prefetchTimer) clearInterval(prefetchTimer);
        prefetchTimer = setInterval(function () {
            if (document.visibilityState === 'hidden') return;
            prefetchUnlockOptions();
        }, 120000);
        if (!prefetchVisibilityBound) {
            prefetchVisibilityBound = true;
            document.addEventListener('visibilitychange', function () {
                if (document.visibilityState === 'visible') {
                    prefetchUnlockOptions();
                }
            });
        }
    }

    function finishUnlock(opts, cred) {
        var credentialId = bufferToBase64url(cred.rawId);
        return idbGet(credentialId).then(function (wrapKey) {
            if (!wrapKey) {
                throw new Error('This browser is missing the local unlock key. Unlock with your recovery code, then enable quick unlock again on this phone.');
            }
            var response = cred.response;
            return postJson('/pro/medical/vault/devices/unlock', {
                unlock_ticket: opts.unlock_ticket,
                credential_id: credentialId,
                clientDataJSON: bufferToBase64url(response.clientDataJSON),
                authenticatorData: bufferToBase64url(response.authenticatorData),
                signature: bufferToBase64url(response.signature),
                wrap_key: wrapKey
            }, { csrf: false });
        });
    }

    function runAssertion(opts, usedPrefetch) {
        // Call credentials.get as soon as possible after the tap — no IndexedDB/network first.
        return navigator.credentials.get({ publicKey: preparePublicKey(opts.publicKey) }).then(function (cred) {
            if (!cred) throw new Error('Device unlock was cancelled.');
            return finishUnlock(opts, cred);
        }).catch(function (e) {
            prefetchUnlockOptions();
            if (e && e.status) {
                var msg = (e.message || '').toLowerCase();
                if (msg.indexOf('ticket expired') !== -1 || msg.indexOf('challenge') !== -1) {
                    throw new Error('Unlock timed out. Tap Unlock with Face ID / fingerprint again (keep the page open so biometrics can start immediately).');
                }
                throw e;
            }
            if (!usedPrefetch && e && e.name === 'NotAllowedError') {
                throw new Error('Fingerprint prompt was blocked. Wait a second for the page to finish preparing, then tap Unlock again.');
            }
            throw new Error(humanizeWebAuthnError(e));
        });
    }

    function unlockWithDevice() {
        var cached = prefetchedUnlock;
        var fresh = cached && Date.now() <= cached.expiresAt;
        if (fresh) {
            prefetchedUnlock = null;
            return runAssertion(cached.opts, true);
        }

        // No warm challenge: fetch then get (may fail on Safari; keep-alive should avoid this).
        return fetchUnlockOptions().then(function (ready) {
            if (!ready || !ready.opts) throw new Error('Could not start device unlock. Refresh and try again.');
            prefetchedUnlock = null;
            return runAssertion(ready.opts, false);
        });
    }

    /**
     * True only when this browser holds a wrap key for a credential still trusted on the server.
     */
    function hasLocalWrapKey() {
        return listDevices().then(function (devices) {
            var serverIds = {};
            (devices || []).forEach(function (d) {
                if (d && d.credential_id) serverIds[normalizeCredId(d.credential_id)] = true;
            });
            if (!Object.keys(serverIds).length) return false;
            return idbKeys().then(function (keys) {
                for (var i = 0; i < keys.length; i++) {
                    if (serverIds[normalizeCredId(keys[i])]) return true;
                }
                return false;
            });
        }).catch(function () {
            // Fallback: any local key (offline / devices list failed).
            return idbKeys().then(function (keys) { return keys.length > 0; }).catch(function () { return false; });
        });
    }

    return {
        supported: supported,
        platformAvailable: platformAvailable,
        registerDevice: registerDevice,
        unlockWithDevice: unlockWithDevice,
        prefetchUnlockOptions: prefetchUnlockOptions,
        startUnlockPrefetchKeepAlive: startUnlockPrefetchKeepAlive,
        hasLocalWrapKey: hasLocalWrapKey,
        listDevices: listDevices,
        revokeDevice: revokeDevice,
        idbDelete: idbDelete
    };
})();
</script>
