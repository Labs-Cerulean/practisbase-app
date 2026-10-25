<?php

namespace App\Http\Controllers;

use App\Models\DocumentStamp;
use App\Models\MedicalVault;
use App\Models\MedicalVaultDevice;
use App\Support\ClinicalNoteTemplates;
use App\Support\CompanyBooks;
use App\Support\EmailVerification;
use App\Support\FiscalReportEngine;
use App\Support\FiscalYearGuard;
use App\Support\MedicalVaultCrypto;
use App\Support\RegimeHistory;
use App\Support\StripeBilling;
use App\Support\TenantStorage;
use App\Support\TierPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

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

        $allowedTiers = TierPolicy::allowedTiersForProfession($user->profession);
        $planConsequences = [];
        foreach ($allowedTiers as $tierOption) {
            $planConsequences[$tierOption] = TierPolicy::changeConsequences($user->tier, $tierOption);
        }

        $showMedicalVaultDevices = $user->canAccessProPackage('med');
        $medicalVaultDevices = [];
        if ($showMedicalVaultDevices) {
            $vault = MedicalVault::activeForUser($user->id);
            if ($vault) {
                $medicalVaultDevices = MedicalVaultDevice::where('user_id', $user->id)
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
            'currentTier' => TierPolicy::normalize($user->tier),
            'showMedicalVaultDevices' => $showMedicalVaultDevices,
            'medicalVaultUnlocked' => $showMedicalVaultDevices
                && MedicalVaultCrypto::keyFromSession(session('medical_vault_key')) !== null,
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
            return redirect('/settings')->with('error', 'Plan locked to your access code. Contact Cerulean Labs to change.');
        }

        $currentTier = TierPolicy::normalize($user->tier);

        $request->validate([
            'tier' => TierPolicy::validationRule(),
        ]);

        $newTier = TierPolicy::normalize($request->tier);
        TierPolicy::assertTierAllowedForProfession($user, $newTier);

        if ($newTier === $currentTier) {
            return back()->with('success', 'You are already on '.TierPolicy::label($currentTier).'.');
        }

        if (TierPolicy::isDowngrade($currentTier, $newTier)) {
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

        if ($newTier === TierPolicy::TIER_FREE) {
            if (! StripeBilling::releasePaidSubscription($user)) {
                return back()->withErrors([
                    'tier' => 'The card subscription could not be cancelled, so the plan was left unchanged.',
                ]);
            }

            $user->update([
                'tier' => $newTier,
                'trial_ends_at' => null,
            ]);

            return back()->with(
                'success',
                'Plan downgraded from '.TierPolicy::label($currentTier).' to Free. Restricted tools are hidden; existing data was not deleted.'
            );
        }

        return redirect()->away(StripeBilling::checkoutUrl($user, $newTier, 'settings'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        if ($user->canAccessCompanyBooks()) {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            ]);

            $user->name = $validated['name'];
            $reverify = EmailVerification::applyEmailChange($user, $validated['email']);
            $user->save();

            if ($reverify) {
                $this->sendVerification($user);

                return redirect('/email/verify')->with('status', 'Confirm the new email address to continue.');
            }

            return redirect('/settings')->with('success', 'Login details updated.');
        }

        $section = $request->input('settings_section', 'practice');
        if (! in_array($section, ['practice', 'tax', 'payments'], true)) {
            $section = 'practice';
        }

        return match ($section) {
            'tax' => $this->updateTaxSection($request, $user),
            'payments' => $this->updatePaymentsSection($request, $user),
            default => $this->updatePracticeSection($request, $user),
        };
    }

    private function settingsRedirect(string $section, string $message)
    {
        $hash = match ($section) {
            'tax' => 'tax',
            'payments' => 'payments',
            default => 'practice',
        };

        return redirect('/settings#'.$hash)->with('success', $message);
    }

    private function settingsErrorRedirect(string $section, array $errors)
    {
        $hash = match ($section) {
            'tax' => 'tax',
            'payments' => 'payments',
            default => 'practice',
        };

        return redirect('/settings#'.$hash)->withErrors($errors)->withInput();
    }

    private function updatePracticeSection(Request $request, $user)
    {
        $isMedical = $user->profession === 'Medical Professional';

        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
                'postnominals' => 'nullable|string|max:255',
                'warrant_type' => 'nullable|string|max:255',
                'warrant_number' => 'nullable|string|max:255',
                'clinic_phone' => 'nullable|string|max:64',
                'clinic_address' => 'nullable|string|max:500',
                'clinical_note_template' => [
                    'nullable',
                    'string',
                    'max:64',
                    function (string $attribute, mixed $value, \Closure $fail) use ($user) {
                        if ($value === null || $value === '') {
                            return;
                        }
                        $allowed = array_keys(ClinicalNoteTemplates::optionsForUser($user));
                        if (! in_array((string) $value, $allowed, true)) {
                            $fail('Choose a valid journal template.');
                        }
                    },
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e->redirectTo(url('/settings#practice'));
        }

        $warrantNumber = filled($request->warrant_number) ? trim($request->warrant_number) : null;

        $user->name = $request->name;
        $reverify = EmailVerification::applyEmailChange($user, $request->email);
        $user->postnominals = filled($request->postnominals) ? trim($request->postnominals) : null;
        $user->warrant_type = filled($request->warrant_type) ? trim($request->warrant_type) : null;
        $user->warrant_number = $warrantNumber;
        $user->clinic_phone = filled($request->clinic_phone) ? trim($request->clinic_phone) : null;
        $user->clinic_address = filled($request->clinic_address) ? trim($request->clinic_address) : null;
        $user->clinical_note_template = $isMedical
            ? ClinicalNoteTemplates::normalizeForUser($user, $request->input('clinical_note_template'))
            : ($user->clinical_note_template ?? 'general');
        $user->save();

        if ($warrantNumber !== null) {
            DocumentStamp::query()
                ->where('user_id', $user->id)
                ->where('is_default', true)
                ->update(['warrant_number' => $warrantNumber]);
        }

        if ($reverify) {
            $this->sendVerification($user);

            return redirect('/email/verify')->with('status', 'Confirm the new email address to continue.');
        }

        return $this->settingsRedirect('practice', 'Practice profile saved.');
    }

    private function sendVerification($user): void
    {
        try {
            EmailVerification::send($user);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function updateTaxSection(Request $request, $user)
    {
        $isMedical = $user->profession === 'Medical Professional';
        $hasClosedFiscalYears = FiscalReportEngine::hasClosedYears($user->id);
        $dobLocked = $hasClosedFiscalYears && filled($user->date_of_birth);
        $adultCutoff = now()->subYears(18)->startOfDay();

        try {
            $request->validate([
                'employment_type' => 'required|in:full_time,part_time',
                'date_of_birth' => array_values(array_filter([
                    $dobLocked ? 'nullable' : 'required_if:employment_type,full_time',
                    'nullable',
                    'date',
                    'before_or_equal:today',
                    $dobLocked ? null : function (string $attribute, mixed $value, \Closure $fail) use ($adultCutoff) {
                        if ($value && Carbon::parse($value)->gt($adultCutoff)) {
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
            ]);
        } catch (ValidationException $e) {
            throw $e->redirectTo(url('/settings#tax'));
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
            try {
                $request->validate([
                    'regime_effective_from' => 'required|date|before_or_equal:today',
                ], [
                    'regime_effective_from.required' => 'Choose the date this tax setup applies from (invoices and expenses before that date keep the previous setup).',
                ]);
            } catch (ValidationException $e) {
                throw $e->redirectTo(url('/settings#tax'));
            }
            $regimeEffectiveFrom = $request->input('regime_effective_from');
            $effectiveYear = FiscalYearGuard::yearFromDate($regimeEffectiveFrom);
            if (FiscalYearGuard::isClosed($user->id, $effectiveYear)) {
                return $this->settingsErrorRedirect('tax', [
                    'regime_effective_from' => "Cannot apply a tax setup change from {$regimeEffectiveFrom} — fiscal year {$effectiveYear} is closed.",
                ]);
            }
            // Capture current tip as baseline before overwriting users.*.
            RegimeHistory::ensureBaseline($user);
            RegimeHistory::applyChange($user, $newRegime, $regimeEffectiveFrom);
        }

        $user->update([
            'employment_type' => $request->employment_type,
            'date_of_birth' => $dateOfBirth,
            'vat_status' => $vatStatus,
            'vat_number' => in_array($vatStatus, ['article_10', 'article_11'], true)
                ? ($request->vat_number ?: null)
                : null,
            'tax_computation' => $request->tax_computation,
            'primary_salary' => $request->primary_salary,
            'max_ssc_paid' => $request->has('max_ssc_paid'),
            'estimated_expenses' => $request->estimated_expenses,
            'estimated_expenses_by_year' => $byYear,
        ]);

        $msg = 'Tax setup saved.';
        if ($regimeChanged && $regimeEffectiveFrom) {
            $msg .= ' Applies from '.$regimeEffectiveFrom.' — earlier open-year documents keep the previous regime.';
        }
        if ($hasClosedFiscalYears) {
            $msg .= ' Closed years stay frozen.';
        }
        if ($dobLocked) {
            $msg .= ' Date of birth was not changed (locked after a year-end close).';
        }

        return $this->settingsRedirect('tax', $msg);
    }

    private function updatePaymentsSection(Request $request, $user)
    {
        try {
            $request->validate([
                'pm_cheque_name' => 'required_if:pm_cheque,1|nullable|string',
                'pm_cheque_address' => 'required_if:pm_cheque,1|nullable|string',
                'bank_names' => 'required_if:pm_bank,1|array',
                'ibans' => 'required_if:pm_bank,1|array',
                'pm_bov_number' => 'required_if:pm_bov,1|nullable|string',
                'pm_revolut_number' => 'required_if:pm_revolut,1|nullable|string',
            ]);
        } catch (ValidationException $e) {
            throw $e->redirectTo(url('/settings#payments'));
        }

        $paymentMethods = [
            'cheque' => $request->has('pm_cheque') ? ['name' => $request->pm_cheque_name, 'address' => $request->pm_cheque_address] : null,
            'bov_mobile' => $request->has('pm_bov') ? $request->pm_bov_number : null,
            'revolut' => $request->has('pm_revolut') ? $request->pm_revolut_number : null,
            'banks' => [],
        ];

        if ($request->has('pm_bank') && $request->bank_names) {
            for ($i = 0; $i < count($request->bank_names); $i++) {
                if (! empty($request->bank_names[$i]) && ! empty($request->ibans[$i])) {
                    $paymentMethods['banks'][] = [
                        'bank' => $request->bank_names[$i],
                        'iban' => $request->ibans[$i],
                    ];
                }
            }
        }

        $user->update([
            'payment_methods' => $paymentMethods,
        ]);

        return $this->settingsRedirect('payments', 'Payment methods saved.');
    }

    public function updateBranding(Request $request)
    {
        $user = Auth::user();
        $canLogo = $user->canAccessStandardTools();

        if (! $canLogo) {
            return redirect('/settings#branding')->withErrors([
                'branding' => 'Document branding is available on Standard and Pro plans.',
            ]);
        }

        try {
            $request->validate([
                'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
                'remove_logo' => 'nullable|boolean',
            ]);
        } catch (ValidationException $e) {
            throw $e->redirectTo(url('/settings#branding'));
        }

        $messages = [];

        if ($request->boolean('remove_logo') && $user->logo_path) {
            TenantStorage::disk()->delete($user->logo_path);
            $user->update(['logo_path' => null]);
            $messages[] = 'Logo removed.';
        }

        if ($request->hasFile('logo')) {
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

        if ($messages === []) {
            return redirect('/settings#branding')->withErrors(['logo' => 'Choose an image to upload, or check a remove option.']);
        }

        return redirect('/settings#branding')->with('success', implode(' ', $messages));
    }

    public function updatePassword(Request $request)
    {
        try {
            $request->validate([
                'current_password' => 'required|current_password',
                'password' => [
                    'required',
                    'confirmed',
                    Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised(),
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e->redirectTo(url('/settings#security'));
        }

        Auth::user()->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect('/settings#security')->with('success', 'Password changed successfully.');
    }
}
