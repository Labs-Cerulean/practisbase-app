<?php

namespace App\Http\Controllers;

use App\Models\BetaInviteCode;
use App\Models\Promotion;
use App\Models\User;
use App\Support\PromotionEngine;
use App\Support\ReferralRewardService;
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
            'promo_code' => 'nullable|string|max:40',
            'invite_code' => 'nullable|string|max:40',
            'ref' => 'nullable|string|max:40',
            'accept_terms' => 'accepted',
            'confirm_sole_trader' => 'accepted',
            'confirm_age_adult' => 'accepted',
            'read_duration_seconds' => 'required|integer',
        ], [
            'confirm_sole_trader.accepted' => 'You must confirm you are registering as a self-employed sole trader, not a limited company.',
            'confirm_age_adult.accepted' => 'You must confirm you are 18 years of age or older. PractisBase does not allow registration by minors.',
        ]);

        $accessRaw = trim((string) (
            $request->input('promo_code')
            ?: $request->input('invite_code')
            ?: $request->query('promo_code', '')
            ?: $request->query('code', '')
        ));
        $refRaw = trim((string) ($request->input('ref') ?: $request->query('ref', '')));

        $user = DB::transaction(function () use ($request, $accessRaw, $refRaw) {
            $invite = null;
            $promo = null;
            $promoEngine = app(PromotionEngine::class);

            if ($accessRaw !== '') {
                $normalizedAccess = BetaInviteCode::normalizeCode($accessRaw);

                $invite = BetaInviteCode::query()
                    ->where('code', $normalizedAccess)
                    ->lockForUpdate()
                    ->first();

                if ($invite && $invite->isRedeemable()) {
                    // Profession access codes unlock Full Pro for free.
                } elseif ($invite) {
                    throw ValidationException::withMessages([
                        'promo_code' => 'That access code is expired, revoked, or already used.',
                    ]);
                } else {
                    $invite = null;
                    $promo = $promoEngine->findRedeemable($accessRaw);
                    if (! $promo) {
                        throw ValidationException::withMessages([
                            'promo_code' => 'That promo or access code is invalid, expired, inactive, or fully used.',
                        ]);
                    }
                }
            }

            $referrer = null;
            if ($refRaw !== '') {
                $refCode = Promotion::normalizeCode($refRaw);
                $referrer = User::query()->where('referral_code', $refCode)->first();
                if (! $referrer) {
                    throw ValidationException::withMessages([
                        'ref' => 'That referral code is not recognised.',
                    ]);
                }
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'terms_accepted_at' => now(),
                'accepted_ip' => $request->ip(),
                'read_duration_seconds' => $request->read_duration_seconds,
                'referral_code' => strtoupper(Str::random(8)),
                'referred_by_id' => $referrer?->id,
                'profession' => $invite ? $invite->profession() : null,
                'tier' => $invite ? $invite->tier() : 'free',
                'beta_invite_code_id' => $invite?->id,
            ]);

            if ($invite) {
                $invite->uses_count = $invite->uses_count + 1;
                $invite->redeemed_by_user_id = $user->id;
                $invite->redeemed_at = now();
                $invite->save();
            }

            if ($promo) {
                $promoEngine->redeemForUser($user, $promo);
            }

            if ($referrer) {
                app(ReferralRewardService::class)->attachPending($referrer, $user);
            }

            return $user;
        });

        Auth::login($user);

        if ($user->beta_invite_code_id) {
            return redirect('/onboarding/financial');
        }

        return redirect('/onboarding/profession');
    }

    public function saveProfession(Request $request)
    {
        $user = Auth::user();

        if ($user->beta_invite_code_id) {
            return redirect('/onboarding/financial')
                ->with('success', 'Your profession is locked to your access code.');
        }

        $request->validate([
            'profession' => 'required|string',
            'custom_profession' => 'required_if:profession,Other|max:255',
            'warrant_choice' => 'nullable|in:main,international,blank',
            'warrant_international' => 'nullable|string|max:255',
            'warrant_number' => 'nullable|string|max:255',
        ]);

        $finalProfession = $request->profession === 'Other' ? $request->custom_profession : $request->profession;

        $warrantType = null;
        $choice = $request->input('warrant_choice', 'blank');
        if ($choice === 'main') {
            $warrantType = match ($request->profession) {
                'Medical Professional' => 'Medical Council Malta',
                'Architect / Perit' => 'Kamra tal-Periti',
                'Engineer' => 'Engineering Board',
                default => null,
            };
        } elseif ($choice === 'international') {
            $intl = trim((string) $request->input('warrant_international', ''));
            $warrantType = $intl !== '' ? $intl : 'International body';
        }

        $user->update([
            'profession' => $finalProfession,
            'warrant_type' => $warrantType,
            'warrant_number' => filled($request->warrant_number) ? trim($request->warrant_number) : null,
        ]);

        return redirect('/onboarding/financial');
    }

    public function saveFinancial(Request $request)
    {
        $user = Auth::user();

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
            'vat_status' => 'required|in:article_10,article_11,exempt',
            'vat_number' => 'nullable|string|max:50',
            'warrant_type' => 'nullable|string|max:255',
            'warrant_number' => 'nullable|string|max:255',
        ]);

        $vatStatus = $request->vat_status;
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
                ->with('success', 'Welcome to PractisBase. Your Pro access is active.');
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

        $tier = \App\Support\TierPolicy::normalize($request->tier);
        if ($tier !== \App\Support\TierPolicy::TIER_FREE && ! $user->canActivatePaidTierWithoutStripe()) {
            throw ValidationException::withMessages([
                'tier' => 'Paid plans need a Founding or access promo code until card billing launches. Select Free, or register with a 6 month promo from Cerulean Labs.',
            ]);
        }

        $user->update([
            'tier' => $tier,
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
