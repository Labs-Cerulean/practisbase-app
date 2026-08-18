<?php

namespace App\Http\Controllers\Pro\Medical;

use App\Http\Controllers\Controller;
use App\Models\MedicalVault;
use App\Support\MedicalVaultCrypto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VaultController extends Controller
{
    public function setupForm()
    {
        $user = Auth::user();
        $existing = MedicalVault::activeForUser($user->id);

        if ($existing && $existing->hasConfirmedCodeSaved() && session()->has('medical_vault_key')) {
            return redirect('/pro/medical/patients');
        }

        if ($existing && ! $existing->hasConfirmedCodeSaved()) {
            return redirect('/pro/medical/vault/reveal');
        }

        if ($existing) {
            return redirect('/pro/medical/vault/unlock');
        }

        return view('pro.medical.vault-setup');
    }

    public function setup(Request $request)
    {
        $user = Auth::user();

        if (MedicalVault::activeForUser($user->id)) {
            return redirect('/pro/medical/vault/unlock');
        }

        $request->validate([
            'acknowledge' => 'accepted',
            'confirm_saved' => 'accepted',
            'read_duration_seconds' => 'nullable|integer|min:0|max:86400',
        ]);

        $recoveryCode = MedicalVaultCrypto::generateRecoveryCode();

        DB::transaction(function () use ($user, $request, $recoveryCode) {
            MedicalVault::create([
                'user_id' => $user->id,
                'recovery_verifier' => MedicalVaultCrypto::verifier($recoveryCode),
                'acknowledged_at' => now(),
                'acknowledged_ip' => $request->ip(),
                'acknowledge_read_duration_seconds' => (int) $request->input('read_duration_seconds', 0),
                'status' => 'active',
            ]);
        });

        $key = MedicalVaultCrypto::deriveKey($recoveryCode);
        $request->session()->put('medical_vault_key', base64_encode($key));
        $request->session()->put('medical_vault_pending_reveal', $recoveryCode);

        return redirect('/pro/medical/vault/reveal');
    }

    public function revealForm(Request $request)
    {
        $user = Auth::user();
        $vault = MedicalVault::activeForUser($user->id);

        if (! $vault) {
            return redirect('/pro/medical/vault/setup');
        }

        if ($vault->hasConfirmedCodeSaved()) {
            return redirect(session()->has('medical_vault_key') ? '/pro/medical/patients' : '/pro/medical/vault/unlock');
        }

        if (! session()->has('medical_vault_key')) {
            return redirect('/pro/medical/vault/unlock')->withErrors([
                'recovery_code' => 'Confirm you saved the recovery code after unlocking. If you never saved it, Cerulean Labs cannot recover it.',
            ]);
        }

        $recoveryCode = $request->session()->get('medical_vault_pending_reveal');

        return view('pro.medical.vault-reveal', [
            'recoveryCode' => is_string($recoveryCode) && $recoveryCode !== '' ? $recoveryCode : null,
            'user' => $user,
            'vault' => $vault,
        ]);
    }

    public function confirmCodeSaved(Request $request)
    {
        $user = Auth::user();
        $vault = MedicalVault::activeForUser($user->id);

        if (! $vault) {
            return redirect('/pro/medical/vault/setup');
        }

        if ($vault->hasConfirmedCodeSaved()) {
            return redirect('/pro/medical/patients');
        }

        if (! session()->has('medical_vault_key')) {
            return redirect('/pro/medical/vault/unlock');
        }

        $request->validate([
            'confirm_code_saved' => 'accepted',
            'read_duration_seconds' => 'nullable|integer|min:0|max:86400',
        ], [
            'confirm_code_saved.accepted' => 'Confirm that you have saved the recovery code offline before continuing.',
        ]);

        $vault->update([
            'code_saved_at' => now(),
            'code_saved_ip' => $request->ip(),
            'code_saved_read_duration_seconds' => (int) $request->input('read_duration_seconds', 0),
        ]);

        $request->session()->forget('medical_vault_pending_reveal');

        return redirect('/pro/medical/patients?offer_trust=1')
            ->with('success', 'Recovery code save confirmed. Keep weekly backups — Labs cannot reset a lost code.');
    }

    public function unlockForm(Request $request)
    {
        $user = Auth::user();
        $vault = MedicalVault::activeForUser($user->id);

        if (! $vault) {
            return redirect('/pro/medical/vault/setup');
        }

        if (session()->has('medical_vault_key')) {
            if (! $vault->hasConfirmedCodeSaved()) {
                return redirect('/pro/medical/vault/reveal');
            }

            return redirect($this->safeReturnPath($request->query('return'), '/pro/medical/patients'));
        }

        return view('pro.medical.vault-unlock', [
            'vault' => $vault,
            'backupOverdue' => $vault->isBackupOverdue(),
            'returnPath' => $this->safeReturnPath($request->query('return'), null),
        ]);
    }

    public function unlock(Request $request)
    {
        $user = Auth::user();
        $vault = MedicalVault::activeForUser($user->id);

        if (! $vault) {
            return redirect('/pro/medical/vault/setup');
        }

        $request->validate([
            'recovery_code' => 'required|string|max:100',
            'return' => 'nullable|string|max:255',
        ]);

        if (! MedicalVaultCrypto::matches($request->recovery_code, $vault->recovery_verifier)) {
            return back()->withErrors([
                'recovery_code' => 'That recovery code does not match this vault.',
            ]);
        }

        $key = MedicalVaultCrypto::deriveKey($request->recovery_code);
        $request->session()->put('medical_vault_key', base64_encode($key));

        if (! $vault->hasConfirmedCodeSaved()) {
            $request->session()->put('medical_vault_pending_reveal', $request->recovery_code);

            return redirect('/pro/medical/vault/reveal');
        }

        $dest = $this->safeReturnPath($request->input('return'), '/pro/medical/patients');

        return redirect($dest)
            ->with('success', 'Medical vault unlocked for this session.')
            ->with('offer_device_trust', true);
    }

    /**
     * Allow only same-site relative paths (optionally with a hash).
     */
    private function safeReturnPath(mixed $path, ?string $fallback): ?string
    {
        if (! is_string($path)) {
            return $fallback;
        }

        $path = trim($path);
        if ($path === '' || ! str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return $fallback;
        }

        if (preg_match('/[\x00-\x1f\x7f]/', $path)) {
            return $fallback;
        }

        return $path;
    }

    /**
     * Return the unlocked session DEK to the same browser for client-side
     * import encryption. Plaintext clinical content still never leaves the device.
     */
    public function clientDek(Request $request)
    {
        $b64 = $request->session()->get('medical_vault_key');
        $key = MedicalVaultCrypto::keyFromSession(is_string($b64) ? $b64 : null);

        if (! $key || ! is_string($b64)) {
            return response()->json(['message' => 'Vault is locked.'], 403);
        }

        return response()->json([
            'dek_b64' => $b64,
        ]);
    }

    public function lock(Request $request)
    {
        $request->session()->forget('medical_vault_key');
        $request->session()->forget('medical_vault_pending_reveal');

        return redirect('/pro/medical/vault/unlock')->with('success', 'Medical vault locked.');
    }
}
