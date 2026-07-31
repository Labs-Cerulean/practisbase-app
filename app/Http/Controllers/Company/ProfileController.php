<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Support\CompanyBooks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        $profile = CompanyBooks::ensureProfile($user);

        return view('company.profile', [
            'profile' => $profile,
            'periodLabel' => CompanyBooks::periodLabel($profile),
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $profile = CompanyBooks::ensureProfile($user);

        $validated = $request->validate([
            'vat_number' => 'nullable|string|max:64',
            'vat_filing_frequency' => 'required|in:quarterly,monthly',
            'bank_name' => 'nullable|string|max:120',
            'bank_iban' => 'nullable|string|max:64',
            'payment_instructions' => 'nullable|string|max:2000',
        ]);

        $profile->update([
            'vat_number' => $validated['vat_number'] ?: null,
            'vat_filing_frequency' => $validated['vat_filing_frequency'],
            'bank_name' => $validated['bank_name'] ?: null,
            'bank_iban' => $validated['bank_iban'] ?: null,
            'payment_instructions' => $validated['payment_instructions'] ?: null,
        ]);

        return redirect('/company/profile')->with('success', 'Company profile updated.');
    }

    public function markCapitalReceived(Request $request)
    {
        $user = Auth::user();
        $profile = CompanyBooks::ensureProfile($user);

        $validated = $request->validate([
            'share_capital_received_at' => 'required|date|before_or_equal:today|after_or_equal:'.$profile->first_period_start->format('Y-m-d'),
        ]);

        $profile->update([
            'share_capital_received_at' => $validated['share_capital_received_at'],
        ]);

        return redirect('/company')->with('success', 'Share capital marked as received at BOV.');
    }
}
