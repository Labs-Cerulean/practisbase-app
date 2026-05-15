<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function registerSubmit(Request $request)
    {
        // 1. Validate the incoming data (Security Check)
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'accept_terms' => 'accepted',
            'read_duration_seconds' => 'required|integer'
        ]);

        // 2. Generate a unique referral code for this user (e.g., "DRBORG-X7K9P")
        $referralCode = strtoupper(Str::random(8));

        // 3. Create the user in the database, including the legal tracking
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            // The password is automatically hashed by the User model's casts, but we use Hash::make for double safety
            'password' => Hash::make($request->password), 
            'terms_accepted_at' => now(),
            'accepted_ip' => $request->ip(),
            'read_duration_seconds' => $request->read_duration_seconds,
            'referral_code' => $referralCode,
            'tier' => 'free', // Explicitly start everyone on free
        ]);

        // 4. Log the user in immediately
        Auth::login($user);

        // 5. Redirect them to Step 2: The Professional Profiler
        return redirect('/onboarding/profession');
    }


}