<?php

namespace App\Support;

use Illuminate\Http\Request;
use lbuchs\WebAuthn\Binary\ByteBuffer;
use lbuchs\WebAuthn\WebAuthn;
use lbuchs\WebAuthn\WebAuthnException;

class VaultWebAuthn
{
    public static function relyingPartyId(Request $request): string
    {
        return $request->getHost();
    }

    public static function make(Request $request): WebAuthn
    {
        return new WebAuthn(
            'PractisBase Medical Vault',
            self::relyingPartyId($request),
            ['none', 'packed', 'apple', 'android-key', 'tpm'],
            true
        );
    }

    public static function binaryFromBase64Url(string $value): string
    {
        return ByteBuffer::fromBase64Url($value)->getBinaryString();
    }

    public static function base64UrlFromBinary(string $binary): string
    {
        return rtrim(strtr(base64_encode($binary), '+/', '-_'), '=');
    }

    public static function decodeClientBinary(string $value): string
    {
        // Accept base64url or standard base64 from the browser helper.
        try {
            return self::binaryFromBase64Url($value);
        } catch (WebAuthnException|\Throwable) {
            $decoded = base64_decode(strtr($value, '-_', '+/'), true);
            if ($decoded === false) {
                throw new WebAuthnException('Invalid binary payload encoding.');
            }

            return $decoded;
        }
    }
}
