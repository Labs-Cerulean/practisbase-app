{{-- Shared WebAuthn trusted-device helpers for medical vault. --}}
<script>
window.PractisVaultDevice = (function () {
    var DB_NAME = 'practisbase_vault_v1';
    var STORE = 'wrap_keys';

    function csrfMeta() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }

    function xsrfCookie() {
        var match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
        if (!match) return '';
        try {
            return decodeURIComponent(match[1]);
        } catch (e) {
            return match[1];
        }
    }

    function authHeaders() {
        var headers = {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfMeta()
        };
        var xsrf = xsrfCookie();
        if (xsrf) headers['X-XSRF-TOKEN'] = xsrf;
        return headers;
    }

    function bufferToBase64url(buffer) {
        var bytes = new Uint8Array(buffer);
        var str = '';
        for (var i = 0; i < bytes.length; i++) str += String.fromCharCode(bytes[i]);
        return btoa(str).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');
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
                if (!db.objectStoreNames.contains(STORE)) {
                    db.createObjectStore(STORE);
                }
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
        if (!supported() || !window.PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable) {
            return Promise.resolve(false);
        }
        return window.PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable().catch(function () { return false; });
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

    function authErrorMessage(status, data) {
        if (status === 401 || (data && data.message === 'Unauthenticated.')) {
            return 'Your PractisBase login session expired during the biometric prompt. Stay on this tab, refresh the page, unlock the vault if asked, then try Enable quick unlock once.';
        }
        if (status === 419) {
            return 'Security token expired. Refresh the page and try again.';
        }
        return (data && data.message) ? data.message : 'Request failed';
    }

    function requestJson(url, method, body) {
        var opts = {
            method: method || 'GET',
            headers: authHeaders(),
            credentials: 'include',
            redirect: 'manual'
        };
        if (body !== undefined) {
            opts.headers['Content-Type'] = 'application/json';
            opts.body = JSON.stringify(body || {});
        }
        return fetch(url, opts).then(function (res) {
            if (res.type === 'opaqueredirect' || res.status === 0) {
                var bounce = new Error(authErrorMessage(401, null));
                bounce.status = 401;
                throw bounce;
            }
            var ctype = res.headers.get('content-type') || '';
            if (ctype.indexOf('application/json') === -1) {
                if (res.status === 401 || res.status === 419 || res.status === 302) {
                    var errNav = new Error(authErrorMessage(res.status, null));
                    errNav.status = res.status;
                    throw errNav;
                }
                throw new Error('Unexpected response from server (' + res.status + '). Refresh and try again.');
            }
            return res.json().then(function (data) {
                if (!res.ok) {
                    var err = new Error(authErrorMessage(res.status, data));
                    err.status = res.status;
                    err.data = data;
                    throw err;
                }
                return data;
            });
        });
    }

    function postJson(url, body) {
        return requestJson(url, 'POST', body || {});
    }

    /** Keep the Laravel session warm after Android biometric UI steals focus. */
    function warmSession() {
        return requestJson('/pro/medical/vault/devices', 'GET').catch(function (e) {
            throw e;
        });
    }

    function listDevices() {
        return requestJson('/pro/medical/vault/devices', 'GET').then(function (data) {
            return (data && data.devices) ? data.devices : [];
        });
    }

    function revokeDevice(deviceId, credentialId) {
        return requestJson('/pro/medical/vault/devices/' + deviceId, 'DELETE', {}).then(function (result) {
            var id = credentialId || (result && result.credential_id);
            if (id) {
                return idbDelete(id).then(function () { return result; }).catch(function () { return result; });
            }
            return result;
        });
    }

    function registerDevice(deviceLabel) {
        return postJson('/pro/medical/vault/devices/register-options', {}).then(function (opts) {
            return navigator.credentials.create({ publicKey: preparePublicKey(opts.publicKey) }).then(function (cred) {
                if (!cred) throw new Error('Device registration was cancelled.');
                var response = cred.response;
                return warmSession().then(function () {
                    return postJson('/pro/medical/vault/devices/register', {
                        clientDataJSON: bufferToBase64url(response.clientDataJSON),
                        attestationObject: bufferToBase64url(response.attestationObject),
                        device_label: deviceLabel || null
                    });
                }).then(function (result) {
                    return idbPut(result.credential_id, result.wrap_key).then(function () { return result; });
                });
            });
        });
    }

    function unlockWithDevice() {
        return postJson('/pro/medical/vault/devices/unlock-options', {}).then(function (opts) {
            return navigator.credentials.get({ publicKey: preparePublicKey(opts.publicKey) }).then(function (cred) {
                if (!cred) throw new Error('Device unlock was cancelled.');
                var credentialId = bufferToBase64url(cred.rawId);
                return idbGet(credentialId).then(function (wrapKey) {
                    if (!wrapKey) {
                        throw new Error('This browser is missing the local unlock key. Unlock with your recovery code, then enable quick unlock again.');
                    }
                    var response = cred.response;
                    return warmSession().then(function () {
                        return postJson('/pro/medical/vault/devices/unlock', {
                            credential_id: credentialId,
                            clientDataJSON: bufferToBase64url(response.clientDataJSON),
                            authenticatorData: bufferToBase64url(response.authenticatorData),
                            signature: bufferToBase64url(response.signature),
                            wrap_key: wrapKey
                        });
                    });
                });
            });
        });
    }

    function hasLocalWrapKey() {
        return idbKeys().then(function (keys) { return keys.length > 0; }).catch(function () { return false; });
    }

    return {
        supported: supported,
        platformAvailable: platformAvailable,
        registerDevice: registerDevice,
        unlockWithDevice: unlockWithDevice,
        hasLocalWrapKey: hasLocalWrapKey,
        listDevices: listDevices,
        revokeDevice: revokeDevice,
        idbDelete: idbDelete,
        warmSession: warmSession
    };
})();
</script>
