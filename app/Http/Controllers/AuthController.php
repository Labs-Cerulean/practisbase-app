<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function registerSubmit(Request $request)
    {
        // 1. Strict Enterprise Validation
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => [
                'required',
                Password::min(12) // Minimum 12 characters is the new standard
                    ->mixedCase() // Requires at least one uppercase and one lowercase
                    ->numbers()   // Requires at least one number
                    ->symbols()   // Requires at least one symbol
                    ->uncompromised() // Checks HaveIBeenPwned API to ensure it wasn't in a data leak
            ],
            'accept_terms' => 'accepted',
            'confirm_sole_trader' => 'accepted',
            'read_duration_seconds' => 'required|integer'
        ], [
            'confirm_sole_trader.accepted' => 'You must confirm you are registering as a self-employed sole trader, not a limited company.',
        ]);

        // 2. Generate a unique referral code
        $referralCode = strtoupper(Str::random(8));

        // 3. Create the user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password), 
            'terms_accepted_at' => now(),
            'accepted_ip' => $request->ip(),
            'read_duration_seconds' => $request->read_duration_seconds,
            'referral_code' => $referralCode,
            'tier' => 'free',
        ]);

        // 4. Log the user in
        Auth::login($user);

        // 5. Redirect to Step 2
        return redirect('/onboarding/profession');
    }

    public function saveProfession(Request $request)
    {
        $request->validate([
            'profession' => 'required|string',
            'custom_profession' => 'required_if:profession,Other|max:255',
            'warrant_type' => 'nullable|string',
            'warrant_number' => 'nullable|string',
        ]);

        $user = Auth::user();
        
        // If they chose "Other", we save what they typed. Otherwise, save the radio button choice.
        $finalProfession = $request->profession === 'Other' ? $request->custom_profession : $request->profession;
        
        // Save the data
        $user->update([
            'profession' => $finalProfession,
            'warrant_type' => $request->warrant_type,
            'warrant_number' => $request->warrant_number,
        ]);

        return redirect('/onboarding/financial');
    }

    public function saveFinancial(Request $request)
    {
        $user = Auth::user();
        
        // If they are a medical professional, the law dictates they are VAT Exempt (Fifth Schedule).
        $isMedical = $user->profession === 'Medical Professional';

        $request->validate([
            'employment_type' => 'required|in:full_time,part_time',
            'date_of_birth' => 'required_if:employment_type,full_time|nullable|date',
            // If medical, we ignore their VAT input and force exempt. Otherwise, they must pick one.
            'vat_status' => $isMedical ? 'nullable' : 'required|in:article_10,article_11,exempt',
            // Optional at onboarding — many starters do not have an MT number yet.
            'vat_number' => 'nullable|string|max:50',
        ]);

        $vatStatus = $isMedical ? 'exempt' : $request->vat_status;
        $vatNumber = in_array($vatStatus, ['article_10', 'article_11'], true)
            ? ($request->vat_number ?: null)
            : null;

        $user->update([
            'employment_type' => $request->employment_type,
            'date_of_birth' => $request->employment_type === 'full_time' ? $request->date_of_birth : null,
            'vat_status' => $vatStatus,
            'vat_number' => $vatNumber,
        ]);

        return redirect('/onboarding/plans');
    }

    public function savePlan(Request $request)
    {
        $request->validate([
            'tier' => 'required|in:free,standard,pro-med,pro-arch,pro-eng'
        ]);

        $user = Auth::user();
        \App\Support\TierPolicy::assertTierAllowedForProfession($user, $request->tier);

        $user->update([
            'tier' => $request->tier
        ]);

        return redirect('/dashboard');
    }

    public function loginSubmit(Request $request)
    {
        // 1. Validate the input
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // 2. Attempt to log the user in
        if (Auth::attempt($credentials)) {
            // Success! Regenerate the session to prevent security fixation attacks
            $request->session()->regenerate();

            // Send them directly to the dashboard
            return redirect()->intended('/dashboard');
        }

        // 3. Failure! Kick them back with an error message
        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }


}