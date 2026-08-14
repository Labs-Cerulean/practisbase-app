<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Support\CompanyBooks;
use App\Support\FiscalReportEngine;
use App\Support\FiscalYearGuard;
use App\Support\RegimeHistory;
use App\Support\TenantStorage;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user();

        if ($user->canAccessCompanyBooks()) {
            $profile = CompanyBooks::ensureProfile($user);

            return view('profile.settings-company', [
                'user' => $user,
                'profile' => $profile,
                'periodLabel' => CompanyBooks::periodLabel($profile),
            ]);
        }

        $allowedTiers = \App\Support\TierPolicy::allowedTiersForProfession($user->profession);
        $planConsequences = [];
        foreach ($allowedTiers as $tierOption) {
            $planConsequences[$tierOption] = \App\Support\TierPolicy::changeConsequences($user->tier, $tierOption);
        }

        $showMedicalVaultDevices = $user->canAccessProPackage('med');
        $medicalVaultDevices = [];
        if ($showMedicalVaultDevices) {
            $vault = \App\Models\MedicalVault::activeForUser($user->id);
            if ($vault) {
                $medicalVaultDevices = \App\Models\MedicalVaultDevice::where('user_id', $user->id)
                    ->where('vault_id', $vault->id)
                    ->orderByDesc('last_used_at')
                    ->orderByDesc('id')
                    ->get()
                    ->map(fn (\App\Models\MedicalVaultDevice $d) => [
                        'id' => $d->id,
                        'credential_id' => $d->credential_id,
                        'device_label' => $d->device_label,
                        'last_used_at' => optional($d->last_used_at)->toIso8601String(),
                        'created_at' => optional($d->created_at)->toIso8601String(),
                    ])
                    ->values()
                    ->all();
            }
        }

        $hasClosedFiscalYears = FiscalReportEngine::hasClosedYears($user->id);
        $dobLocked = $hasClosedFiscalYears && filled($user->date_of_birth);
        RegimeHistory::ensureBaseline($user);

        return view('profile.settings', [
            'user' => $user,
            'allowedTiers' => $allowedTiers,
            'planConsequences' => $planConsequences,
            'currentTier' => \App\Support\TierPolicy::normalize($user->tier),
            'showMedicalVaultDevices' => $showMedicalVaultDevices,
            'medicalVaultUnlocked' => $showMedicalVaultDevices
                && \App\Support\MedicalVaultCrypto::keyFromSession(session('medical_vault_key')) !== null,
            'medicalVaultDevices' => $medicalVaultDevices,
            'hasClosedFiscalYears' => $hasClosedFiscalYears,
            'dobLocked' => $dobLocked,
            'regimeSegments' => RegimeHistory::listForUser($user),
        ]);
    }

    public function updatePlan(Request $request)
    {
        $user = Auth::user();

        if ($user->canAccessCompanyBooks()) {
            return redirect('/settings')->with('error', 'Plan changes are not used for the Cerulean Labs company desk.');
        }

        if ($user->beta_invite_code_id) {
            return redirect('/settings')->with('error', 'Your plan is locked to your beta invite. Contact Cerulean Labs if you need a change.');
        }

        $currentTier = \App\Support\TierPolicy::normalize($user->tier);

        $request->validate([
            'tier' => \App\Support\TierPolicy::validationRule(),
            'confirm_downgrade' => 'nullable|accepted',
            'confirm_downgrade_typed' => 'nullable|string|max:32',
        ]);

        $newTier = \App\Support\TierPolicy::normalize($request->tier);
        \App\Support\TierPolicy::assertTierAllowedForProfession($user, $newTier);

        if ($newTier === $currentTier) {
            return back()->with('success', 'You are already on '.\App\Support\TierPolicy::label($currentTier).'.');
        }

        if (\App\Support\TierPolicy::isDowngrade($currentTier, $newTier)) {
            $request->validate([
                'confirm_downgrade' => 'accepted',
                'confirm_downgrade_typed' => ['required', 'string', function ($attribute, $value, $fail) {
                    if (strtoupper(trim((string) $value)) !== 'DOWNGRADE') {
                        $fail('Type DOWNGRADE to confirm you understand the loss of access.');
                    }
                }],
            ], [
                'confirm_downgrade.accepted' => 'Confirm that you understand what this downgrade means before continuing.',
            ]);
        }

        $user->update([
            'tier' => $newTier,
        ]);

        if (\App\Support\TierPolicy::isDowngrade($currentTier, $newTier)) {
            return back()->with(
                'success',
                'Plan downgraded from '.\App\Support\TierPolicy::label($currentTier).' to '.\App\Support\TierPolicy::label($newTier).'. Restricted tools are hidden; existing data was not deleted.'
            );
        }

        return back()->with(
            'success',
            'Plan updated to '.\App\Support\TierPolicy::label($newTier).' (closed beta — Stripe not connected yet).'
        );
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        if ($user->canAccessCompanyBooks()) {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            ]);

            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);

            return redirect('/settings')->with('success', 'Login details updated.');
        }

        $isMedical = $user->profession === 'Medical Professional';
        $hasClosedFiscalYears = FiscalReportEngine::hasClosedYears($user->id);
        $dobLocked = $hasClosedFiscalYears && filled($user->date_of_birth);

        if (!$request->has('pm_cheque') && !$request->has('pm_bank') && !$request->has('pm_bov') && !$request->has('pm_revolut')) {
            return back()->withErrors(['payment_error' => 'You must enable at least one payment method for your invoices.'])->withInput();
        }

        $adultCutoff = now()->subYears(18)->startOfDay();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'postnominals' => 'nullable|string|max:255',
            'warrant_type' => 'nullable|string|max:255',
            'warrant_number' => 'nullable|string|max:255',
            'clinic_phone' => 'nullable|string|max:64',
            'clinic_address' => 'nullable|string|max:500',
            'clinical_note_template' => 'nullable|in:' . implode(',', array_keys(\App\Support\ClinicalNoteTemplates::options())),
            'employment_type' => 'required|in:full_time,part_time',
            'date_of_birth' => array_values(array_filter([
                $dobLocked ? 'nullable' : 'required_if:employment_type,full_time',
                'nullable',
                'date',
                'before_or_equal:today',
                $dobLocked ? null : function (string $attribute, mixed $value, \Closure $fail) use ($adultCutoff) {
                    if ($value && \Illuminate\Support\Carbon::parse($value)->gt($adultCutoff)) {
                        $fail('You must be 18 years of age or older to use PractisBase.');
                    }
                },
            ])),
            'vat_status' => 'required|in:article_10,article_11,exempt',
            'vat_number' => 'nullable|string|max:50',
            'tax_computation' => 'required|in:single,married,parent',
            'primary_salary' => 'required|numeric|min:0',
            'estimated_expenses' => 'required|numeric|min:0',
            'regime_effective_from' => 'nullable|date|before_or_equal:today',
            'pm_cheque_name' => 'required_if:pm_cheque,1|nullable|string',
            'pm_cheque_address' => 'required_if:pm_cheque,1|nullable|string',
            'bank_names' => 'required_if:pm_bank,1|array',
            'ibans' => 'required_if:pm_bank,1|array',
            'pm_bov_number' => 'required_if:pm_bov,1|nullable|string',
            'pm_revolut_number' => 'required_if:pm_revolut,1|nullable|string',
        ]);

        $paymentMethods = [
            'cheque' => $request->has('pm_cheque') ? ['name' => $request->pm_cheque_name, 'address' => $request->pm_cheque_address] : null,
            'bov_mobile' => $request->has('pm_bov') ? $request->pm_bov_number : null,
            'revolut' => $request->has('pm_revolut') ? $request->pm_revolut_number : null,
            'banks' => []
        ];

        if ($request->has('pm_bank') && $request->bank_names) {
            for ($i = 0; $i < count($request->bank_names); $i++) {
                if (!empty($request->bank_names[$i]) && !empty($request->ibans[$i])) {
                    $paymentMethods['banks'][] = [
                        'bank' => $request->bank_names[$i],
                        'iban' => $request->ibans[$i]
                    ];
                }
            }
        }

        $vatStatus = $request->vat_status;
        // Medical default remains exempt, but Art 10/11 is allowed for non-therapeutic billing.
        if ($isMedical && ! in_array($vatStatus, ['article_10', 'article_11', 'exempt'], true)) {
            $vatStatus = 'exempt';
        }

        $dateOfBirth = $user->date_of_birth;
        if ($request->employment_type === 'full_time') {
            if ($dobLocked) {
                $dateOfBirth = $user->date_of_birth;
            } else {
                $dateOfBirth = $request->date_of_birth;
            }
        } else {
            // Part-time: keep DOB if locked (SSC history); otherwise clear.
            $dateOfBirth = $dobLocked ? $user->date_of_birth : null;
        }

        $byYear = $user->estimated_expenses_by_year ?? [];
        $byYear[(string) date('Y')] = (float) $request->estimated_expenses;

        $oldRegime = RegimeHistory::tipFromUser($user);
        $newRegime = [
            'vat_status' => $vatStatus,
            'employment_type' => $request->employment_type,
            'max_ssc_paid' => $request->has('max_ssc_paid'),
            'primary_salary' => (float) $request->primary_salary,
            'tax_computation' => $request->tax_computation,
        ];
        $regimeChanged = RegimeHistory::regimeChanged($oldRegime, $newRegime);
        $regimeEffectiveFrom = null;

        if ($regimeChanged) {
            $request->validate([
                'regime_effective_from' => 'required|date|before_or_equal:today',
            ], [
                'regime_effective_from.required' => 'Choose the date this tax setup applies from (invoices and expenses before that date keep the previous setup).',
            ]);
            $regimeEffectiveFrom = $request->input('regime_effective_from');
            $effectiveYear = FiscalYearGuard::yearFromDate($regimeEffectiveFrom);
            if (FiscalYearGuard::isClosed($user->id, $effectiveYear)) {
                return back()->withErrors([
                    'regime_effective_from' => "Cannot apply a tax setup change from {$regimeEffectiveFrom} — fiscal year {$effectiveYear} is closed.",
                ])->withInput();
            }
            // Capture current tip as baseline before overwriting users.*.
            RegimeHistory::ensureBaseline($user);
            RegimeHistory::applyChange($user, $newRegime, $regimeEffectiveFrom);
        }

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'postnominals' => filled($request->postnominals) ? trim($request->postnominals) : null,
            'warrant_type' => filled($request->warrant_type) ? trim($request->warrant_type) : null,
            'warrant_number' => filled($request->warrant_number) ? trim($request->warrant_number) : null,
            'clinic_phone' => filled($request->clinic_phone) ? trim($request->clinic_phone) : null,
            'clinic_address' => filled($request->clinic_address) ? trim($request->clinic_address) : null,
            'clinical_note_template' => $isMedical
                ? \App\Support\ClinicalNoteTemplates::normalize($request->input('clinical_note_template'))
                : ($user->clinical_note_template ?? 'general'),
            'employment_type' => $request->employment_type,
            'date_of_birth' => $dateOfBirth,
            'vat_status' => $vatStatus,
            'vat_number' => in_array($vatStatus, ['article_10', 'article_11'], true)
                ? ($request->vat_number ?: null)
                : null,
            'payment_methods' => $paymentMethods,
            'tax_computation' => $request->tax_computation,
            'primary_salary' => $request->primary_salary,
            'max_ssc_paid' => $request->has('max_ssc_paid'),
            'estimated_expenses' => $request->estimated_expenses,
            'estimated_expenses_by_year' => $byYear,
        ]);

        $msg = 'Profile updated successfully.';
        if ($regimeChanged && $regimeEffectiveFrom) {
            $msg .= ' Tax setup applies from '.$regimeEffectiveFrom.' — earlier open-year documents keep the previous regime.';
        }
        if ($hasClosedFiscalYears) {
            $msg .= ' Closed fiscal years stay frozen — this change only affects open years.';
        }
        if ($dobLocked) {
            $msg .= ' Date of birth was not changed (locked after a year-end close).';
        }

        return back()->with('success', $msg);
    }

    public function updateBranding(Request $request)
    {
        $user = Auth::user();
        $canLogo = $user->canAccessStandardTools();
        $canStamp = $user->canAccessProPackage('med');

        if (! $canLogo && ! $canStamp) {
            return redirect('/settings')->withErrors([
                'branding' => 'Document branding is available on Standard and Pro plans.',
            ]);
        }

        $request->validate([
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'remove_logo' => 'nullable|boolean',
            'clinical_stamp' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'remove_clinical_stamp' => 'nullable|boolean',
        ]);

        $messages = [];

        if ($canLogo && $request->boolean('remove_logo') && $user->logo_path) {
            TenantStorage::disk()->delete($user->logo_path);
            $user->update(['logo_path' => null]);
            $messages[] = 'Logo removed.';
        }

        if ($canLogo && $request->hasFile('logo')) {
            if ($user->logo_path) {
                TenantStorage::disk()->delete($user->logo_path);
            }

            $path = $request->file('logo')->store(
                TenantStorage::brandingPath($user->id),
                TenantStorage::diskName()
            );

            $user->update(['logo_path' => $path]);
            $messages[] = 'Logo uploaded.';
        }

        if ($canStamp && $request->boolean('remove_clinical_stamp') && $user->clinical_stamp_path) {
            TenantStorage::disk()->delete($user->clinical_stamp_path);
            $user->update(['clinical_stamp_path' => null]);
            $messages[] = 'Clinical stamp removed.';
        }

        if ($canStamp && $request->hasFile('clinical_stamp')) {
            if ($user->clinical_stamp_path) {
                TenantStorage::disk()->delete($user->clinical_stamp_path);
            }

            $path = $request->file('clinical_stamp')->store(
                TenantStorage::brandingPath($user->id),
                TenantStorage::diskName()
            );

            $user->update(['clinical_stamp_path' => $path]);
            $messages[] = 'Clinical stamp uploaded. It will appear on issued prescriptions, referrals, and certificates.';
        }

        if ($messages === []) {
            return back()->withErrors(['logo' => 'Choose an image to upload, or check a remove option.']);
        }

        return back()->with('success', implode(' ', $messages));
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => [
                'required',
                'confirmed',
                Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()
            ],
        ]);

        Auth::user()->update([
            'password' => Hash::make($request->password)
        ]);

        return back()->with('success', 'Password changed successfully.');
    }
}
