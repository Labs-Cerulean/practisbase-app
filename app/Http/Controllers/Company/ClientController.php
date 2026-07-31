<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyClient;
use App\Support\CompanyBooks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        CompanyBooks::ensureProfile($user);

        $clients = CompanyClient::where('user_id', $user->id)
            ->orderBy('name')
            ->get();

        return view('company.clients-index', compact('clients'));
    }

    public function create()
    {
        CompanyBooks::ensureProfile(Auth::user());

        return view('company.clients-form', [
            'client' => null,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        CompanyBooks::ensureProfile($user);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:64',
            'billing_address' => 'required|string|max:2000',
            'vat_number' => 'nullable|string|max:64',
            'registration_number' => 'nullable|string|max:64',
            'notes' => 'nullable|string|max:2000',
        ]);

        CompanyClient::create([
            'user_id' => $user->id,
            ...$validated,
        ]);

        return redirect('/company/clients')->with('success', 'Company client added.');
    }

    public function edit(int $client)
    {
        $user = Auth::user();
        $model = CompanyClient::where('user_id', $user->id)->where('id', $client)->firstOrFail();

        return view('company.clients-form', [
            'client' => $model,
        ]);
    }

    public function update(Request $request, int $client)
    {
        $user = Auth::user();
        $model = CompanyClient::where('user_id', $user->id)->where('id', $client)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:64',
            'billing_address' => 'required|string|max:2000',
            'vat_number' => 'nullable|string|max:64',
            'registration_number' => 'nullable|string|max:64',
            'notes' => 'nullable|string|max:2000',
        ]);

        $model->update($validated);

        return redirect('/company/clients')->with('success', 'Client updated.');
    }
}
