<?php

namespace App\Http\Controllers\Pro\Engineer;

use App\Http\Controllers\Controller;
use App\Models\EngineerCertification;
use App\Support\TenantStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CertificationController extends Controller
{
    public function index()
    {
        $certs = EngineerCertification::where('user_id', Auth::id())
            ->orderByDesc('issued_on')
            ->get();

        return view('pro.engineer.certs-index', compact('certs'));
    }

    public function create()
    {
        return view('pro.engineer.certs-create');
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subject_name' => 'nullable|string|max:255',
            'issued_on' => 'required|date|before_or_equal:today',
            'expires_on' => 'nullable|date|after_or_equal:issued_on',
            'notes' => 'nullable|string|max:5000',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store(
                'tenants/' . $user->id . '/certifications',
                TenantStorage::diskName()
            );
        }

        EngineerCertification::create([
            'user_id' => $user->id,
            'title' => $validated['title'],
            'subject_name' => $validated['subject_name'] ?? null,
            'issued_on' => $validated['issued_on'],
            'expires_on' => $validated['expires_on'] ?? null,
            'photo_path' => $photoPath,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect('/pro/engineer/certifications')->with('success', 'Certification logged.');
    }

    public function downloadPhoto(EngineerCertification $certification)
    {
        if ($certification->user_id !== Auth::id()) {
            abort(403);
        }

        if (! $certification->photo_path || ! TenantStorage::disk()->exists($certification->photo_path)) {
            abort(404);
        }

        return TenantStorage::disk()->download(
            $certification->photo_path,
            'cert-photo-' . $certification->id . '.' . pathinfo($certification->photo_path, PATHINFO_EXTENSION)
        );
    }
}
