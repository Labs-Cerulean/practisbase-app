<?php

namespace App\Http\Controllers;

use App\Models\BetaInviteCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function registerSubmit(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => [
                'required',
                Password::min(12)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
            'invite_code' => 'required|string|max:40',
            'accept_terms' => 'accepted',
            'confirm_sole_trader' => 'accepted',
            'confirm_age_adult' => 'accepted',
            'read_duration_seconds' => 'required|integer',
        ], [
            'invite_code.required' => 'A beta invite code is required to create an account.',
            'confirm_sole_trader.accepted' => 'You must confirm you are registering as a self-employed sole trader, not a limited company.',
            'confirm_age_adult.accepted' => 'You must confirm you are 18 years of age or older. PractisBase does not allow registration by minors.',
        ]);

        $normalized = BetaInviteCode::normalizeCode($request->invite_code);

        $user = DB::transaction(function () use ($request, $normalized) {
            $invite = BetaInviteCode::query()
                ->where('code', $normalized)
                ->lockForUpdate()
                ->first();

            if (! $invite || ! $invite->isRedeemable()) {
                throw ValidationException::withMessages([
                    'invite_code' => 'That invite code is invalid, expired, revoked, or already used.',
                ]);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'terms_accepted_at' => now(),
                'accepted_ip' => $request->ip(),
                'read_duration_seconds' => $request->read_duration_seconds,
                'referral_code' => strtoupper(Str::random(8)),
                'profession' => $invite->profession(),
                'tier' => $invite->tier(),
                'beta_invite_code_id' => $invite->id,
            ]);

            $invite->uses_count = $invite->uses_count + 1;
            $invite->redeemed_by_user_id = $user->id;
            $invite->redeemed_at = now();
            $invite->save();

            return $user;
        });

        Auth::login($user);

        return redirect('/onboarding/financial');
    }

    public function saveProfession(Request $request)
    {
        $user = Auth::user();

        if ($user->beta_invite_code_id) {
            return redirect('/onboarding/financial')
                ->with('success', 'Your profession is locked to your beta invite.');
        }

        $request->validate([
            'profession' => 'required|string',
            'custom_profession' => 'required_if:profession,Other|max:255',
            'warrant_type' => 'nullable|string',
            'warrant_number' => 'nullable|string',
        ]);

        $finalProfession = $request->profession === 'Other' ? $request->custom_profession : $request->profession;

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

        $isMedical = $user->profession === 'Medical Professional';
        $adultCutoff = now()->subYears(18)->startOfDay();

        $request->validate([
            'employment_type' => 'required|in:full_time,part_time',
            'date_of_birth' => [
                'required_if:employment_type,full_time',
                'nullable',
                'date',
                'before_or_equal:today',
                function (string $attribute, mixed $value, \Closure $fail) use ($adultCutoff) {
                    if ($value && \Illuminate\Support\Carbon::parse($value)->gt($adultCutoff)) {
                        $fail('You must be 18 years of age or older to use PractisBase.');
                    }
                },
            ],
            'vat_status' => $isMedical ? 'nullable' : 'required|in:article_10,article_11,exempt',
            'vat_number' => 'nullable|string|max:50',
            'warrant_type' => 'nullable|string|max:255',
            'warrant_number' => 'nullable|string|max:255',
        ]);

        $vatStatus = $isMedical ? 'exempt' : $request->vat_status;
        $vatNumber = in_array($vatStatus, ['article_10', 'article_11'], true)
            ? ($request->vat_number ?: null)
            : null;

        $updates = [
            'employment_type' => $request->employment_type,
            'date_of_birth' => $request->employment_type === 'full_time' ? $request->date_of_birth : null,
            'vat_status' => $vatStatus,
            'vat_number' => $vatNumber,
        ];

        if ($user->beta_invite_code_id) {
            $updates['warrant_type'] = $request->warrant_type;
            $updates['warrant_number'] = $request->warrant_number;
        }

        $user->update($updates);
        $user->refresh();
        \App\Support\RegimeHistory::ensureBaseline($user);

        if ($user->beta_invite_code_id) {
            $home = $user->canAccessCompanyBooks() ? '/company' : '/dashboard';

            return redirect($home)
                ->with('success', 'Welcome to the PractisBase closed beta. Your Pro plan is active.');
        }

        return redirect('/onboarding/plans');
    }

    public function savePlan(Request $request)
    {
        $user = Auth::user();

        if ($user->beta_invite_code_id) {
            $home = $user->canAccessCompanyBooks() ? '/company' : '/dashboard';

            return redirect($home);
        }

        $request->validate([
            'tier' => \App\Support\TierPolicy::validationRule(),
        ]);

        \App\Support\TierPolicy::assertTierAllowedForProfession($user, $request->tier);

        $user->update([
            'tier' => $request->tier,
        ]);

        $home = Auth::user()->canAccessCompanyBooks() ? '/company' : '/dashboard';

        return redirect($home);
    }

    public function loginSubmit(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            $home = Auth::user()->canAccessCompanyBooks() ? '/company' : '/dashboard';

            return redirect()->intended($home);
        }

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
