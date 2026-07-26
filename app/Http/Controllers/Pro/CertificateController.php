<?php

namespace App\Http\Controllers\Pro;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Support\TenantStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CertificateController extends Controller
{
    public function index()
    {
        $certs = Certificate::where('user_id', Auth::id())
            ->orderByDesc('issued_on')
            ->get();

        return view('pro.certificates.index', [
            'certs' => $certs,
            'kinds' => Certificate::KINDS,
        ]);
    }

    public function create()
    {
        return view('pro.certificates.create', [
            'kinds' => Certificate::KINDS,
        ]);
    }

    public function store(Request $request)
    {
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
        ]);

        return redirect('/pro/certificates')->with('success', 'Certificate / declaration logged.');
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
