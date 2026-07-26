<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        $allowedTiers = \App\Support\TierPolicy::allowedTiersForProfession($user->profession);

        return view('profile.settings', [
            'user' => $user,
            'allowedTiers' => $allowedTiers,
        ]);
    }

    public function updatePlan(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'tier' => 'required|in:free,standard,pro-med,pro-arch,pro-eng',
        ]);

        \App\Support\TierPolicy::assertTierAllowedForProfession($user, $request->tier);

        $user->update([
            'tier' => $request->tier,
        ]);

        return back()->with('success', 'Plan updated (DEV mode — Stripe not connected yet). Your entitlements now follow: ' . $request->tier . '.');
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $isMedical = $user->profession === 'Medical Professional';

        if (!$request->has('pm_cheque') && !$request->has('pm_bank') && !$request->has('pm_bov') && !$request->has('pm_revolut')) {
            return back()->withErrors(['payment_error' => 'You must enable at least one payment method for your invoices.'])->withInput();
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'warrant_type' => 'nullable|string|max:255',
            'warrant_number' => 'nullable|string|max:255',
            'employment_type' => 'required|in:full_time,part_time',
            'date_of_birth' => 'required_if:employment_type,full_time|nullable|date',
            'vat_status' => $isMedical ? 'nullable' : 'required|in:article_10,article_11,exempt',
            'vat_number' => 'required_if:vat_status,article_10,article_11|nullable|string',
            'tax_computation' => 'required|in:single,married,parent',
            'primary_salary' => 'required|numeric|min:0',
            'estimated_expenses' => 'required|numeric|min:0',
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

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'warrant_type' => $request->warrant_type,
            'warrant_number' => $request->warrant_number,
            'employment_type' => $request->employment_type,
            'date_of_birth' => $request->employment_type === 'full_time' ? $request->date_of_birth : null,
            'vat_status' => $isMedical ? 'exempt' : $request->vat_status,
            'vat_number' => in_array($request->vat_status, ['article_10', 'article_11']) ? $request->vat_number : null,
            'payment_methods' => $paymentMethods,
            'tax_computation' => $request->tax_computation,
            'primary_salary' => $request->primary_salary,
            'max_ssc_paid' => $request->has('max_ssc_paid'),
            'estimated_expenses' => $request->estimated_expenses,
        ]);

        return back()->with('success', 'Profile updated successfully.');
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
