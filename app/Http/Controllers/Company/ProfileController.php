<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Support\CompanyBooks;
use App\Support\CompanyLedger;
use App\Support\TenantStorage;
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

    public function updateLogo(Request $request)
    {
        $user = Auth::user();
        $profile = CompanyBooks::ensureProfile($user);

        $request->validate([
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'remove_logo' => 'nullable|boolean',
        ]);

        if ($request->boolean('remove_logo') && $profile->logo_path) {
            TenantStorage::disk()->delete($profile->logo_path);
            $profile->update(['logo_path' => null]);

            return redirect('/company/profile')->with('success', 'Company logo removed.');
        }

        if (! $request->hasFile('logo')) {
            return back()->withErrors(['logo' => 'Choose an image to upload (JPG, PNG, or WebP, max 2 MB).']);
        }

        if ($profile->logo_path) {
            TenantStorage::disk()->delete($profile->logo_path);
        }

        $path = $request->file('logo')->store(
            TenantStorage::companyBrandingPath($user->id),
            TenantStorage::diskName()
        );

        $profile->update(['logo_path' => $path]);

        return redirect('/company/profile')->with('success', 'Company logo uploaded. It will appear on invoice PDFs.');
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

        CompanyLedger::ensureChart($user);
        CompanyLedger::postShareCapital($profile->fresh());

        return redirect('/company')->with('success', 'Share capital marked as received and posted to the ledger.');
    }
}
