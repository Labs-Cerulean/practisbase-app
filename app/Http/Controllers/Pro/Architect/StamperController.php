<?php

namespace App\Http\Controllers\Pro\Architect;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StamperController extends Controller
{
    public function form()
    {
        return view('pro.architect.stamper', [
            'user' => Auth::user(),
        ]);
    }

    public function generate(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'document_title' => 'required|string|max:255',
            'project_reference' => 'nullable|string|max:100',
            'declaration' => 'nullable|string|max:5000',
            'stamp_date' => 'required|date|before_or_equal:today',
        ]);

        $pdf = Pdf::loadView('pro.architect.stamper-pdf', [
            'user' => $user,
            'documentTitle' => $validated['document_title'],
            'projectReference' => $validated['project_reference'] ?? null,
            'declaration' => $validated['declaration'] ?: 'I hereby certify that this document has been prepared / reviewed under my professional responsibility.',
            'stampDate' => $validated['stamp_date'],
        ]);
        $pdf->setPaper('a4', 'portrait');

        $safe = preg_replace('/[^A-Za-z0-9\-_]+/', '-', $validated['document_title']) ?: 'stamp';

        return $pdf->download('stamp-' . $safe . '.pdf');
    }
}
