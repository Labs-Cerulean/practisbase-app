<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    // 1. Show the Settings Page
    public function edit()
    {
        // Fetch custom professions for the autocomplete datalist
        $customProfessions = \App\Models\User::whereNotIn('profession', [
            'Medical Professional', 'Architect / Perit', 'Engineer', 'Tutor / Lecturer', 'Other'
        ])->whereNotNull('profession')->distinct()->pluck('profession');

        return view('profile.settings', [
            'user' => Auth::user(),
            'customProfessions' => $customProfessions
        ]);
    }

    // 2. Update Personal & Professional Info
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        // 1. Determine if they are medical for strict VAT enforcement
        $isMedical = $request->profession === 'Medical Professional';

        // 2. Validate all inputs
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'profession' => 'required|string|max:255',
            'warrant_type' => 'nullable|string|max:255',
            'warrant_number' => 'nullable|string|max:255',
            
            // Fiscal Validation
            'employment_type' => 'required|in:full_time,part_time',
            'date_of_birth' => 'required_if:employment_type,full_time|nullable|date',
            'vat_status' => $isMedical ? 'nullable' : 'required|in:article_10,article_11,exempt',
            'vat_number' => 'required_if:vat_status,article_10,article_11|nullable|string',
        ]);

        // 3. Update the User Database
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'profession' => $request->profession,
            'warrant_type' => $request->warrant_type,
            'warrant_number' => $request->warrant_number,
            
            // Fiscal Updates
            'employment_type' => $request->employment_type,
            'date_of_birth' => $request->employment_type === 'full_time' ? $request->date_of_birth : null,
            'vat_status' => $isMedical ? 'exempt' : $request->vat_status,
            'vat_number' => in_array($request->vat_status, ['article_10', 'article_11']) ? $request->vat_number : null,
        ]);

        return back()->with('success', 'Profile updated successfully.');
    }

    // 3. Securely Update Password
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => [
                'required',
                'confirmed', // Requires a 'password_confirmation' field in the form
                Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()
            ],
        ]);

        Auth::user()->update([
            'password' => Hash::make($request->password)
        ]);

        return back()->with('success', 'Password changed successfully.');
    }
}