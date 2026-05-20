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

        $request->validate([
            'name' => 'required|string|max:255',
            // Ensure the email is unique, but ignore the current user's email
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'profession' => 'required|string|max:255',
            'warrant_type' => 'nullable|string|max:255',
            'warrant_number' => 'nullable|string|max:255',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'profession' => $request->profession,
            'warrant_type' => $request->warrant_type,
            'warrant_number' => $request->warrant_number,
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