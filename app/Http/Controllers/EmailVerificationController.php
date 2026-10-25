<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\EmailVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailVerificationController extends Controller
{
    public function notice(Request $request)
    {
        $user = $request->user();

        if (! $user->mustVerifyEmailNow()) {
            return redirect(EmailVerification::nextPath($user));
        }

        return view('auth.verify-email', ['email' => $user->email]);
    }

    public function resend(Request $request)
    {
        $user = $request->user();

        if (! $user->mustVerifyEmailNow()) {
            return redirect(EmailVerification::nextPath($user));
        }

        try {
            EmailVerification::send($user);
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors([
                'email' => 'The confirmation email could not be sent.',
            ]);
        }

        return back()->with('status', 'Confirmation link sent.');
    }

    public function verify(Request $request, string $id, string $hash)
    {
        $user = User::query()->whereKey($id)->first();

        if (! $user || ! hash_equals(sha1($user->email), $hash)) {
            abort(403);
        }

        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(EmailVerification::nextPath($user->fresh()))
            ->with('success', 'Email confirmed.');
    }
}
