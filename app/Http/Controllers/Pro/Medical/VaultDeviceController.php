<?php

namespace App\Http\Controllers\Pro\Medical;

use App\Http\Controllers\Controller;
use App\Models\MedicalVault;
use App\Models\MedicalVaultDevice;
use App\Models\User;
use App\Support\MedicalVaultCrypto;
use App\Support\VaultWebAuthn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use lbuchs\WebAuthn\WebAuthnException;

class VaultDeviceController extends Controller
{
    /** Short-lived tickets; biometric UI must finish within this window. */
    private const TICKET_TTL_MINUTES = 5;

    /** Registration options — vault must already be unlocked (session still valid). */
    public function registerOptions(Request $request)
    {
        $user = Auth::user();
        $vault = MedicalVault::activeForUser($user->id);
        $dek = MedicalVaultCrypto::keyFromSession(session('medical_vault_key'));

        if (! $vault || ! $dek) {
            return response()->json(['message' => 'Unlock the vault with your recovery code first.'], 403);
        }

        $webAuthn = VaultWebAuthn::make($request);
        $exclude = MedicalVaultDevice::where('user_id', $user->id)
            ->where('vault_id', $vault->id)
            ->pluck('credential_id')
            ->map(fn ($id) => VaultWebAuthn::decodeClientBinary($id))
            ->all();

        $createArgs = $webAuthn->getCreateArgs(
            (string) $user->id,
            $user->email ?: ('user-'.$user->id),
            $user->name ?: 'Practitioner',
            120,
            false,
            'required',
            false,
            $exclude
        );

        $challenge = $webAuthn->getChallenge()->getBinaryString();
        $ticket = Str::random(64);
        $fp = $this->ticketClientFingerprint($request);

        // Android biometric UI often drops the browser session cookie mid-ceremony.
        // Cache everything needed to finish registration without the session.
        // DEK is Laravel-encrypted; ticket secret + UA soft-check (no IP — mobile
        // Wi‑Fi/cellular/Private Relay IP changes were breaking fingerprint unlock).
        Cache::put($this->ticketKey('register', $ticket), array_merge($fp, [
            'user_id' => $user->id,
            'vault_id' => $vault->id,
            'challenge_b64' => base64_encode($challenge),
            'dek_enc' => Crypt::encryptString(base64_encode($dek)),
            'device_label_hint' => $this->guessDeviceLabel($request),
        ]), now()->addMinutes(self::TICKET_TTL_MINUTES));

        return response()->json([
            'publicKey' => $createArgs->publicKey,
            'rpId' => VaultWebAuthn::relyingPartyId($request),
            'registration_ticket' => $ticket,
            'expires_in' => self::TICKET_TTL_MINUTES * 60,
        ]);
    }

    /**
     * Finish registration using a short-lived ticket (works even if session cookies were dropped).
     * Restores auth + vault session key on success.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'registration_ticket' => 'required|string|min:32|max:128',
            'clientDataJSON' => 'required|string',
            'attestationObject' => 'required|string',
            'device_label' => 'nullable|string|max:255',
        ]);

        $payload = Cache::get($this->ticketKey('register', $validated['registration_ticket']));
        if (! is_array($payload)) {
            return response()->json(['message' => 'Registration ticket expired. Unlock the vault and try Enable quick unlock again.'], 422);
        }

        if ($mismatch = $this->assertTicketClient($request, $payload, 'Registration')) {
            return $mismatch;
        }

        if (Auth::check() && (int) Auth::id() !== (int) $payload['user_id']) {
            return response()->json(['message' => 'Registration ticket does not match this account.'], 403);
        }

        $user = User::find($payload['user_id']);
        $vault = MedicalVault::where('id', $payload['vault_id'])
            ->where('user_id', $payload['user_id'])
            ->where('status', 'active')
            ->first();

        if (! $user || ! $vault) {
            return response()->json(['message' => 'Vault not found for this registration.'], 404);
        }

        $dek = null;
        try {
            try {
                $dek = base64_decode(Crypt::decryptString($payload['dek_enc']), true);
                $challenge = base64_decode($payload['challenge_b64'], true);
            } catch (\Throwable) {
                return response()->json(['message' => 'Registration ticket is invalid.'], 422);
            }

            if ($dek === false || strlen($dek) !== 32 || $challenge === false || $challenge === '') {
                return response()->json(['message' => 'Registration ticket is invalid.'], 422);
            }

            try {
                $webAuthn = VaultWebAuthn::make($request);
                $data = $webAuthn->processCreate(
                    VaultWebAuthn::decodeClientBinary($validated['clientDataJSON']),
                    VaultWebAuthn::decodeClientBinary($validated['attestationObject']),
                    $challenge,
                    true,
                    true,
                    false,
                    false
                );
            } catch (WebAuthnException $e) {
                return response()->json(['message' => 'Device registration failed: '.$e->getMessage()], 422);
            }

            // Consume ticket only after the authenticator ceremony verified.
            Cache::forget($this->ticketKey('register', $validated['registration_ticket']));

            $credentialIdB64 = VaultWebAuthn::base64UrlFromBinary($data->credentialId);
            $existing = MedicalVaultDevice::where('credential_id', $credentialIdB64)->first();
            if ($existing) {
                $this->restoreSession($request, $user, $dek);

                return response()->json(['message' => 'This authenticator is already trusted.'], 422);
            }

            $wrapKey = random_bytes(32);
            $wrapped = MedicalVaultCrypto::wrapDek($dek, $wrapKey);

            $device = MedicalVaultDevice::create([
                'user_id' => $user->id,
                'vault_id' => $vault->id,
                'credential_id' => $credentialIdB64,
                'public_key' => $data->credentialPublicKey,
                'attestation_format' => $data->attestationFormat ?? null,
                'wrap_nonce' => $wrapped['wrap_nonce'],
                'wrapped_dek' => $wrapped['wrapped_dek'],
                'device_label' => $validated['device_label'] ?: ($payload['device_label_hint'] ?? $this->guessDeviceLabel($request)),
                'signature_counter' => (int) ($data->signatureCounter ?? 0),
                'last_used_at' => now(),
            ]);

            $this->restoreSession($request, $user, $dek);

            $wrapKeyB64 = base64_encode($wrapKey);
            $this->forgetSensitiveString($wrapKey);

            return response()->json([
                'ok' => true,
                'device_id' => $device->id,
                'credential_id' => $credentialIdB64,
                'wrap_key' => $wrapKeyB64,
                'device_label' => $device->device_label,
                'message' => 'This device can now unlock your vault with biometrics or your device unlock (Touch ID, Face ID, Windows Hello, etc.).',
            ]);
        } finally {
            $this->forgetSensitiveString($dek);
            if (isset($wrapKey)) {
                $this->forgetSensitiveString($wrapKey);
            }
        }
    }

    /** Assertion options for device unlock (vault may be locked; login session should still be valid). */
    public function unlockOptions(Request $request)
    {
        $user = Auth::user();
        $vault = MedicalVault::activeForUser($user->id);

        if (! $vault) {
            return response()->json(['message' => 'No active vault.'], 404);
        }

        $devices = MedicalVaultDevice::where('user_id', $user->id)
            ->where('vault_id', $vault->id)
            ->get();

        if ($devices->isEmpty()) {
            return response()->json(['message' => 'No trusted devices yet.', 'devices' => []], 404);
        }

        $credentialIds = $devices->map(fn (MedicalVaultDevice $d) => VaultWebAuthn::decodeClientBinary($d->credential_id))->all();

        $webAuthn = VaultWebAuthn::make($request);
        $getArgs = $webAuthn->getGetArgs(
            $credentialIds,
            120,
            false,
            false,
            false,
            false,
            true,
            'required'
        );

        $challenge = $webAuthn->getChallenge()->getBinaryString();
        $ticket = Str::random(64);
        $fp = $this->ticketClientFingerprint($request);

        Cache::put($this->ticketKey('unlock', $ticket), array_merge($fp, [
            'user_id' => $user->id,
            'vault_id' => $vault->id,
            'challenge_b64' => base64_encode($challenge),
        ]), now()->addMinutes(self::TICKET_TTL_MINUTES));

        return response()->json([
            'publicKey' => $getArgs->publicKey,
            'credential_ids' => $devices->pluck('credential_id')->values(),
            'unlock_ticket' => $ticket,
            'expires_in' => self::TICKET_TTL_MINUTES * 60,
        ]);
    }

    /** Verify assertion + local wrap key via ticket; restore login + vault session. */
    public function unlock(Request $request)
    {
        $validated = $request->validate([
            'unlock_ticket' => 'required|string|min:32|max:128',
            'credential_id' => 'required|string',
            'clientDataJSON' => 'required|string',
            'authenticatorData' => 'required|string',
            'signature' => 'required|string',
            'wrap_key' => 'required|string',
        ]);

        $payload = Cache::get($this->ticketKey('unlock', $validated['unlock_ticket']));
        if (! is_array($payload)) {
            return response()->json(['message' => 'Unlock ticket expired. Tap Unlock again, or use your recovery code.'], 422);
        }

        if ($mismatch = $this->assertTicketClient($request, $payload, 'Unlock')) {
            return $mismatch;
        }

        if (Auth::check() && (int) Auth::id() !== (int) $payload['user_id']) {
            return response()->json(['message' => 'Unlock ticket does not match this account.'], 403);
        }

        $user = User::find($payload['user_id']);
        $vault = MedicalVault::where('id', $payload['vault_id'])
            ->where('user_id', $payload['user_id'])
            ->where('status', 'active')
            ->first();

        if (! $user || ! $vault) {
            return response()->json(['message' => 'Vault not found.'], 404);
        }

        $challenge = base64_decode($payload['challenge_b64'], true);
        if ($challenge === false || $challenge === '') {
            return response()->json(['message' => 'Unlock ticket is invalid.'], 422);
        }

        $device = MedicalVaultDevice::where('user_id', $user->id)
            ->where('vault_id', $vault->id)
            ->where('credential_id', $validated['credential_id'])
            ->first();

        if (! $device) {
            return response()->json(['message' => 'Trusted device not found. Unlock with your recovery code and register this device again.'], 404);
        }

        try {
            $webAuthn = VaultWebAuthn::make($request);
            // Do not enforce signature counters — iCloud/Google synced platform
            // credentials often reset or desync counters and reject valid unlocks.
            $webAuthn->processGet(
                VaultWebAuthn::decodeClientBinary($validated['clientDataJSON']),
                VaultWebAuthn::decodeClientBinary($validated['authenticatorData']),
                VaultWebAuthn::decodeClientBinary($validated['signature']),
                $device->public_key,
                $challenge,
                null,
                true,
                true
            );
            $counter = $webAuthn->getSignatureCounter();
        } catch (WebAuthnException $e) {
            return response()->json(['message' => 'Device unlock failed: '.$e->getMessage()], 422);
        }

        $wrapKey = base64_decode($validated['wrap_key'], true);
        if ($wrapKey === false || strlen($wrapKey) !== 32) {
            return response()->json(['message' => 'Missing device unlock key on this browser. Unlock with your recovery code and re-enable quick unlock.'], 422);
        }

        $dek = null;
        try {
            try {
                $dek = MedicalVaultCrypto::unwrapDek($device->wrapped_dek, $device->wrap_nonce, $wrapKey);
            } catch (\Throwable) {
                return response()->json(['message' => 'Could not unwrap the vault key for this device. Unlock with your recovery code and re-enable quick unlock.'], 422);
            }

            Cache::forget($this->ticketKey('unlock', $validated['unlock_ticket']));

            if (is_int($counter) && $counter > 0) {
                $device->signature_counter = $counter;
            }
            $device->last_used_at = now();
            $device->save();

            $this->restoreSession($request, $user, $dek);

            return response()->json([
                'ok' => true,
                'redirect' => '/pro/medical/patients',
                'message' => 'Medical vault unlocked on this device.',
            ]);
        } finally {
            $this->forgetSensitiveString($dek);
            $this->forgetSensitiveString($wrapKey);
        }
    }

    public function destroy(Request $request, MedicalVaultDevice $device)
    {
        $user = Auth::user();
        $vault = MedicalVault::activeForUser($user->id);

        if (! $vault || $device->user_id !== $user->id || (int) $device->vault_id !== (int) $vault->id) {
            abort(403);
        }

        $credentialId = $device->credential_id;
        $device->delete();

        return response()->json([
            'ok' => true,
            'credential_id' => $credentialId,
            'message' => 'Trusted device removed.',
        ]);
    }

    public function index()
    {
        $user = Auth::user();
        $vault = MedicalVault::activeForUser($user->id);
        if (! $vault) {
            return response()->json(['devices' => []]);
        }

        $devices = MedicalVaultDevice::where('user_id', $user->id)
            ->where('vault_id', $vault->id)
            ->orderByDesc('last_used_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (MedicalVaultDevice $d) => [
                'id' => $d->id,
                'credential_id' => $d->credential_id,
                'device_label' => $d->device_label,
                'last_used_at' => optional($d->last_used_at)->toIso8601String(),
                'created_at' => optional($d->created_at)->toIso8601String(),
            ]);

        return response()->json(['devices' => $devices]);
    }

    private function restoreSession(Request $request, User $user, string $dek): void
    {
        Auth::login($user, true);
        $request->session()->regenerate();
        $request->session()->put('medical_vault_key', base64_encode($dek));
    }

    private function ticketKey(string $kind, string $ticket): string
    {
        return "webauthn_{$kind}_ticket:{$ticket}";
    }

    /** @return array{ua_hash: string} */
    private function ticketClientFingerprint(Request $request): array
    {
        return [
            'ua_hash' => hash('sha256', strtolower(trim((string) $request->userAgent()))),
        ];
    }

    private function assertTicketClient(Request $request, array $payload, string $kind): ?JsonResponse
    {
        // Soft UA check only. Exact IP binding was the main mobile failure mode
        // (Wi‑Fi ↔ cellular, Private Relay, carrier CGNAT). The ticket secret is
        // enough binding for a 5-minute ceremony.
        $fp = $this->ticketClientFingerprint($request);
        $storedUa = (string) ($payload['ua_hash'] ?? '');
        if ($storedUa === '') {
            return null;
        }

        if (hash_equals($storedUa, $fp['ua_hash'])) {
            return null;
        }

        return response()->json([
            'message' => $kind.' ticket is not valid for this browser. Refresh the page and try again.',
        ], 422);
    }

    private function forgetSensitiveString(mixed &$value): void
    {
        if (! is_string($value) || $value === '') {
            $value = null;

            return;
        }

        if (function_exists('sodium_memzero')) {
            try {
                sodium_memzero($value);
            } catch (\Throwable) {
                // PHP may refuse memzero on interned/immutable strings; fall through.
            }
        }

        $value = null;
    }

    private function guessDeviceLabel(Request $request): string
    {
        $ua = strtolower((string) $request->userAgent());
        if (str_contains($ua, 'iphone') || str_contains($ua, 'ipad')) {
            return 'iPhone / iPad';
        }
        if (str_contains($ua, 'android')) {
            return 'Android phone / tablet';
        }
        if (str_contains($ua, 'mac os') || str_contains($ua, 'macintosh')) {
            return 'Mac';
        }
        if (str_contains($ua, 'windows')) {
            return 'Windows PC';
        }
        if (str_contains($ua, 'linux')) {
            return 'Linux PC';
        }

        return 'This browser';
    }
}
