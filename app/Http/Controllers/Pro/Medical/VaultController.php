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

        if ($existing && session()->has('medical_vault_key')) {
            return redirect('/pro/medical/patients');
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
        ]);

        $recoveryCode = MedicalVaultCrypto::generateRecoveryCode();

        DB::transaction(function () use ($user, $request, $recoveryCode) {
            MedicalVault::create([
                'user_id' => $user->id,
                'recovery_verifier' => MedicalVaultCrypto::verifier($recoveryCode),
                'acknowledged_at' => now(),
                'acknowledged_ip' => $request->ip(),
                'status' => 'active',
            ]);
        });

        $key = MedicalVaultCrypto::deriveKey($recoveryCode);
        $request->session()->put('medical_vault_key', base64_encode($key));

        return view('pro.medical.vault-reveal', [
            'recoveryCode' => $recoveryCode,
            'user' => $user,
        ]);
    }

    public function unlockForm()
    {
        $user = Auth::user();
        $vault = MedicalVault::activeForUser($user->id);

        if (! $vault) {
            return redirect('/pro/medical/vault/setup');
        }

        if (session()->has('medical_vault_key')) {
            return redirect('/pro/medical/patients');
        }

        return view('pro.medical.vault-unlock', [
            'vault' => $vault,
            'backupOverdue' => $vault->isBackupOverdue(),
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
        ]);

        if (! MedicalVaultCrypto::matches($request->recovery_code, $vault->recovery_verifier)) {
            return back()->withErrors([
                'recovery_code' => 'That recovery code does not match this vault.',
            ]);
        }

        $key = MedicalVaultCrypto::deriveKey($request->recovery_code);
        $request->session()->put('medical_vault_key', base64_encode($key));

        return redirect('/pro/medical/patients')->with('success', 'Medical vault unlocked for this session.');
    }

    public function lock(Request $request)
    {
        $request->session()->forget('medical_vault_key');

        return redirect('/pro/medical/vault/unlock')->with('success', 'Medical vault locked.');
    }
}
