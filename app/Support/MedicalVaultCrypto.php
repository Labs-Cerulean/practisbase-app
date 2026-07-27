<?php

namespace App\Support;

use RuntimeException;

/**
 * Practitioner-held recovery code cryptography.
 * Server stores only a verifier hash; encryption key lives in session after unlock.
 */
class MedicalVaultCrypto
{
    public static function generateRecoveryCode(): string
    {
        // 32 chars, grouped for readability — high entropy, shown once.
        $raw = strtoupper(bin2hex(random_bytes(16)));

        return implode('-', str_split($raw, 4));
    }

    public static function verifier(string $recoveryCode): string
    {
        return hash('sha256', self::normalize($recoveryCode));
    }

    public static function matches(string $recoveryCode, string $verifier): bool
    {
        return hash_equals($verifier, self::verifier($recoveryCode));
    }

    public static function deriveKey(string $recoveryCode): string
    {
        return hash('sha256', 'practisbase-medical-vault-v1|' . self::normalize($recoveryCode), true);
    }

    public static function encrypt(array $payload, string $binaryKey): array
    {
        $json = json_encode($payload, JSON_THROW_ON_ERROR);
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = sodium_crypto_secretbox($json, $nonce, $binaryKey);

        return [
            'ciphertext' => base64_encode($cipher),
            'nonce' => base64_encode($nonce),
        ];
    }

    public static function decrypt(string $ciphertextB64, string $nonceB64, string $binaryKey): array
    {
        $plain = self::decryptRaw($ciphertextB64, $nonceB64, $binaryKey);
        $decoded = json_decode($plain, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($decoded)) {
            throw new RuntimeException('Decrypted payload was not an object.');
        }

        return $decoded;
    }

    /**
     * Encrypt arbitrary binary (e.g. journal attachment file bytes).
     * Returns raw ciphertext for private blob storage + base64 nonce for the DB.
     */
    public static function encryptBytes(string $plainBytes, string $binaryKey): array
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = sodium_crypto_secretbox($plainBytes, $nonce, $binaryKey);

        return [
            'ciphertext' => $cipher,
            'nonce' => base64_encode($nonce),
        ];
    }

    public static function decryptBytes(string $cipherBytes, string $nonceB64, string $binaryKey): string
    {
        $nonce = base64_decode($nonceB64, true);

        if ($nonce === false) {
            throw new RuntimeException('Invalid attachment nonce encoding.');
        }

        $plain = sodium_crypto_secretbox_open($cipherBytes, $nonce, $binaryKey);

        if ($plain === false) {
            throw new RuntimeException('Unable to decrypt vault attachment.');
        }

        return $plain;
    }

    public static function keyFromSession(?string $base64Key): ?string
    {
        if (! filled($base64Key)) {
            return null;
        }

        $key = base64_decode($base64Key, true);

        return $key !== false && strlen($key) === 32 ? $key : null;
    }

    /**
     * Wrap the vault DEK for a trusted device. Wrap key stays on the device (IndexedDB).
     */
    public static function wrapDek(string $dekBinary, string $wrapKeyBinary): array
    {
        if (strlen($dekBinary) !== 32 || strlen($wrapKeyBinary) !== 32) {
            throw new RuntimeException('DEK and wrap key must be 32 bytes.');
        }

        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = sodium_crypto_secretbox($dekBinary, $nonce, $wrapKeyBinary);

        return [
            'wrapped_dek' => base64_encode($cipher),
            'wrap_nonce' => base64_encode($nonce),
        ];
    }

    public static function unwrapDek(string $wrappedDekB64, string $wrapNonceB64, string $wrapKeyBinary): string
    {
        if (strlen($wrapKeyBinary) !== 32) {
            throw new RuntimeException('Wrap key must be 32 bytes.');
        }

        $cipher = base64_decode($wrappedDekB64, true);
        $nonce = base64_decode($wrapNonceB64, true);

        if ($cipher === false || $nonce === false) {
            throw new RuntimeException('Invalid wrapped DEK encoding.');
        }

        $dek = sodium_crypto_secretbox_open($cipher, $nonce, $wrapKeyBinary);

        if ($dek === false || strlen($dek) !== 32) {
            throw new RuntimeException('Unable to unwrap vault key for this device.');
        }

        return $dek;
    }

    private static function decryptRaw(string $ciphertextB64, string $nonceB64, string $binaryKey): string
    {
        $cipher = base64_decode($ciphertextB64, true);
        $nonce = base64_decode($nonceB64, true);

        if ($cipher === false || $nonce === false) {
            throw new RuntimeException('Invalid ciphertext encoding.');
        }

        $plain = sodium_crypto_secretbox_open($cipher, $nonce, $binaryKey);

        if ($plain === false) {
            throw new RuntimeException('Unable to decrypt vault payload.');
        }

        return $plain;
    }

    private static function normalize(string $recoveryCode): string
    {
        return strtoupper(preg_replace('/[\s\-]+/', '', $recoveryCode) ?? '');
    }
}
