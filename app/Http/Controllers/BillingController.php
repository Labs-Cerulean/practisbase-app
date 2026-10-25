<?php

namespace App\Http\Controllers;

use App\Support\EmailVerification;
use App\Support\StripeBilling;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class BillingController extends Controller
{
    public function success(Request $request)
    {
        $sessionId = (string) $request->query('session_id', '');
        if (! str_starts_with($sessionId, 'cs_')) {
            return redirect('/onboarding/plans')->withErrors([
                'tier' => 'Checkout did not return a session.',
            ]);
        }

        $user = $request->user();
        $returnTo = StripeBilling::completeCheckout($user, $sessionId);
        $user->refresh();

        $message = $user->subscription_status === 'trialing'
            ? 'Card saved. Billing starts when the trial ends.'
            : 'Card saved. Billing has started.';

        if ($returnTo === 'settings') {
            return redirect('/settings#plan')->with('success', $message);
        }

        return redirect(EmailVerification::nextPath($user))->with('success', $message);
    }

    public function webhook(Request $request)
    {
        $secret = (string) config('services.stripe.webhook_secret');
        if ($secret === '') {
            return response('Webhook secret is not configured.', 503);
        }

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature', ''),
                $secret
            );
        } catch (\UnexpectedValueException) {
            return response('Invalid payload.', 400);
        } catch (SignatureVerificationException) {
            return response('Invalid signature.', 400);
        } catch (\Throwable) {
            return response('Invalid signature.', 400);
        }

        try {
            StripeBilling::handleEvent($event);
        } catch (\Throwable $e) {
            report($e);

            return response('Webhook failed.', 500);
        }

        return response('ok', 200);
    }
}
