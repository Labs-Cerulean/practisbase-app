/**
 * Browser vault helpers matching App\Support\MedicalVaultCrypto.
 * DEK stays in sessionStorage for client-side import encryption only.
 */
(function (global) {
    'use strict';

    var STORAGE_KEY = 'pb_medical_dek_b64';

    function normalizeRecoveryCode(code) {
        return String(code || '').toUpperCase().replace(/[\s\-]+/g, '');
    }

    function bytesToBase64(bytes) {
        var binary = '';
        var len = bytes.length;
        for (var i = 0; i < len; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return btoa(binary);
    }

    function base64ToBytes(b64) {
        var binary = atob(b64);
        var bytes = new Uint8Array(binary.length);
        for (var i = 0; i < binary.length; i++) {
            bytes[i] = binary.charCodeAt(i);
        }
        return bytes;
    }

    function utf8Encode(str) {
        return new TextEncoder().encode(str);
    }

    async function sha256Raw(message) {
        var digest = await crypto.subtle.digest('SHA-256', utf8Encode(message));
        return new Uint8Array(digest);
    }

    async function deriveKey(recoveryCode) {
        var normalized = normalizeRecoveryCode(recoveryCode);
        return sha256Raw('practisbase-medical-vault-v1|' + normalized);
    }

    function requireNacl() {
        if (!global.nacl || !global.nacl.secretbox) {
            throw new Error('tweetnacl is required for vault encryption.');
        }
        return global.nacl;
    }

    function encryptPayload(object, keyBytes) {
        var nacl = requireNacl();
        var json = JSON.stringify(object);
        var message = utf8Encode(json);
        var nonce = nacl.randomBytes(nacl.secretbox.nonceLength);
        var cipher = nacl.secretbox(message, nonce, keyBytes);
        if (!cipher) {
            throw new Error('Encryption failed.');
        }
        return {
            ciphertext: bytesToBase64(cipher),
            nonce: bytesToBase64(nonce)
        };
    }

    function storeDek(keyBytes) {
        sessionStorage.setItem(STORAGE_KEY, bytesToBase64(keyBytes));
    }

    function clearDek() {
        try {
            sessionStorage.removeItem(STORAGE_KEY);
        } catch (e) {
            /* ignore */
        }
    }

    function loadDek() {
        var b64 = sessionStorage.getItem(STORAGE_KEY);
        if (!b64) {
            return null;
        }
        try {
            var bytes = base64ToBytes(b64);
            return bytes.length === 32 ? bytes : null;
        } catch (e) {
            return null;
        }
    }

    async function stashDekFromRecoveryCode(recoveryCode) {
        var key = await deriveKey(recoveryCode);
        storeDek(key);
        return key;
    }

    function bindUnlockForm(form) {
        if (!form) {
            return;
        }
        form.addEventListener('submit', function (event) {
            var input = form.querySelector('[name="recovery_code"]');
            if (!input || !input.value) {
                return;
            }
            event.preventDefault();
            stashDekFromRecoveryCode(input.value)
                .then(function () {
                    form.submit();
                })
                .catch(function () {
                    clearDek();
                    form.submit();
                });
        });
    }

    function bindLockForms() {
        document.querySelectorAll('form[action="/pro/medical/vault/lock"]').forEach(function (form) {
            form.addEventListener('submit', function () {
                clearDek();
            });
        });
    }

    global.PractisBaseVaultCrypto = {
        normalizeRecoveryCode: normalizeRecoveryCode,
        deriveKey: deriveKey,
        encryptPayload: encryptPayload,
        storeDek: storeDek,
        clearDek: clearDek,
        loadDek: loadDek,
        stashDekFromRecoveryCode: stashDekFromRecoveryCode,
        bindUnlockForm: bindUnlockForm,
        bindLockForms: bindLockForms,
        bytesToBase64: bytesToBase64,
        base64ToBytes: base64ToBytes
    };

    document.addEventListener('DOMContentLoaded', function () {
        bindLockForms();
    });
})(window);
