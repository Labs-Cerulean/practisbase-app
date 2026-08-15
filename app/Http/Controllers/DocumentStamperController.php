<?php

namespace App\Http\Controllers;

use App\Models\DocumentStamp;
use Illuminate\Support\Facades\Auth;

class DocumentStamperController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $stamps = DocumentStamp::query()
            ->where('user_id', $user->id)
            ->orderByDesc('is_default')
            ->orderBy('label')
            ->get();

        $payload = $stamps->map(fn (DocumentStamp $stamp) => $stamp->toStamperPayload())->values()->all();

        return view('stamper.index', [
            'stamps' => $stamps,
            'stampPayload' => $payload,
        ]);
    }
}
