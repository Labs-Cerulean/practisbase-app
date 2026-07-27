<?php

namespace App\Http\Controllers\Pro;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Support\IssueCode;
use App\Support\TenantStorage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CertificateController extends Controller
{
    /**
     * Medical authors stampables under the patient; this register is Arch/Eng (+ legacy PDF).
     */
    private function redirectMedicalToStampables()
    {
        $user = Auth::user();
        if ($user && $user->proPackage() === 'med') {
            return redirect('/pro/medical/stampables');
        }

        return null;
    }

    public function index()
    {
        if ($redirect = $this->redirectMedicalToStampables()) {
            return $redirect;
        }

        $certs = Certificate::where('user_id', Auth::id())
            ->orderByDesc('id')
            ->get();

        return view('pro.certificates.index', [
            'certs' => $certs,
            'kinds' => Certificate::KINDS,
        ]);
    }

    public function create()
    {
        if ($redirect = $this->redirectMedicalToStampables()) {
            return $redirect;
        }

        return view('pro.certificates.create', [
            'kinds' => Certificate::KINDS,
        ]);
    }

    public function store(Request $request)
    {
        if ($redirect = $this->redirectMedicalToStampables()) {
            return $redirect;
        }

        $user = Auth::user();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subject_name' => 'nullable|string|max:255',
            'kind' => 'required|in:' . implode(',', array_keys(Certificate::KINDS)),
            'issued_on' => 'required|date|before_or_equal:today',
            'expires_on' => 'nullable|date|after_or_equal:issued_on',
            'notes' => 'nullable|string|max:5000',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store(
                'tenants/' . $user->id . '/certificates',
                TenantStorage::diskName()
            );
        }

        Certificate::create([
            'user_id' => $user->id,
            'title' => $validated['title'],
            'subject_name' => $validated['subject_name'] ?? null,
            'kind' => $validated['kind'],
            'issued_on' => $validated['issued_on'],
            'expires_on' => $validated['expires_on'] ?? null,
            'photo_path' => $photoPath,
            'notes' => $validated['notes'] ?? null,
            'stamped_at' => null,
            'issue_code' => null,
        ]);

        return redirect('/pro/certificates')->with('success', 'Draft certificate saved. Edit until you Stamp & issue.');
    }

    public function edit(Certificate $certificate)
    {
        if ($redirect = $this->redirectMedicalToStampables()) {
            return $redirect;
        }

        if ($certificate->user_id !== Auth::id()) {
            abort(403);
        }

        if (! $certificate->isEditable()) {
            return redirect('/pro/certificates')
                ->withErrors(['certificate' => 'This certificate was stamped and issued. It can no longer be edited.']);
        }

        return view('pro.certificates.edit', [
            'certificate' => $certificate,
            'kinds' => Certificate::KINDS,
        ]);
    }

    public function update(Request $request, Certificate $certificate)
    {
        if ($redirect = $this->redirectMedicalToStampables()) {
            return $redirect;
        }

        if ($certificate->user_id !== Auth::id()) {
            abort(403);
        }

        if (! $certificate->isEditable()) {
            return redirect('/pro/certificates')
                ->withErrors(['certificate' => 'This certificate was stamped and issued. It can no longer be edited.']);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subject_name' => 'nullable|string|max:255',
            'kind' => 'required|in:' . implode(',', array_keys(Certificate::KINDS)),
            'issued_on' => 'required|date|before_or_equal:today',
            'expires_on' => 'nullable|date|after_or_equal:issued_on',
            'notes' => 'nullable|string|max:5000',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if ($request->hasFile('photo')) {
            $certificate->photo_path = $request->file('photo')->store(
                'tenants/' . Auth::id() . '/certificates',
                TenantStorage::diskName()
            );
        }

        $certificate->fill([
            'title' => $validated['title'],
            'subject_name' => $validated['subject_name'] ?? null,
            'kind' => $validated['kind'],
            'issued_on' => $validated['issued_on'],
            'expires_on' => $validated['expires_on'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ])->save();

        return redirect('/pro/certificates')->with('success', 'Draft certificate updated.');
    }

    public function stamp(Certificate $certificate)
    {
        if ($redirect = $this->redirectMedicalToStampables()) {
            return $redirect;
        }

        if ($certificate->user_id !== Auth::id()) {
            abort(403);
        }

        if ($certificate->isStamped()) {
            return back()->withErrors(['certificate' => 'Already stamped and issued.']);
        }

        $certificate->stamped_at = now();
        $certificate->issue_code = IssueCode::allocateForCertificate();
        $certificate->save();

        return redirect('/pro/certificates')
            ->with('success', 'Certificate stamped and issued as ' . $certificate->issue_code . '. It is now locked.');
    }

    public function downloadPdf(Certificate $certificate)
    {
        if ($certificate->user_id !== Auth::id()) {
            abort(403);
        }

        if (! $certificate->isStamped()) {
            if (Auth::user()?->proPackage() === 'med') {
                return redirect('/pro/medical/stampables')
                    ->withErrors(['certificate' => 'Stamp & issue the certificate before downloading the official PDF.']);
            }

            return redirect('/pro/certificates')
                ->withErrors(['certificate' => 'Stamp & issue the certificate before downloading the official PDF.']);
        }

        $user = Auth::user();
        $pdf = Pdf::loadView('pro.certificates.pdf', [
            'user' => $user,
            'certificate' => $certificate,
            'kinds' => Certificate::KINDS,
        ]);
        $pdf->setPaper('a4', 'portrait');

        $safeCode = preg_replace('/[^A-Za-z0-9\-]/', '', $certificate->issue_code) ?: ('cert-' . $certificate->id);
        $filename = 'certificate_' . $safeCode . '.pdf';

        return $pdf->download($filename);
    }

    public function lookup(Request $request)
    {
        if ($redirect = $this->redirectMedicalToStampables()) {
            return $redirect;
        }

        $validated = $request->validate([
            'issue_code' => 'required|string|max:32',
        ]);

        $code = IssueCode::normalize($validated['issue_code']);
        $cert = Certificate::where('user_id', Auth::id())
            ->where('issue_code', $code)
            ->first();

        if (! $cert) {
            return redirect('/pro/certificates')
                ->withErrors(['issue_code' => 'No stamped certificate in your register matches ' . ($code ?: 'that code') . '.']);
        }

        return redirect('/pro/certificates')
            ->with('success', 'Match: ' . $cert->title . ' · ' . $cert->issue_code . ' · stamped ' . $cert->stamped_at->format('d M Y H:i') . ($cert->subject_name ? ' · ' . $cert->subject_name : '') . '.');
    }

    public function downloadPhoto(Certificate $certificate)
    {
        if ($certificate->user_id !== Auth::id()) {
            abort(403);
        }

        if (! $certificate->photo_path || ! TenantStorage::disk()->exists($certificate->photo_path)) {
            abort(404);
        }

        return TenantStorage::disk()->download(
            $certificate->photo_path,
            'certificate-photo-' . $certificate->id . '.' . pathinfo($certificate->photo_path, PATHINFO_EXTENSION)
        );
    }
}
