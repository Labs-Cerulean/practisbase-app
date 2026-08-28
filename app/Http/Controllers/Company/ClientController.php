<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyClient;
use App\Models\CompanyInvoice;
use App\Support\CompanyBooks;
use App\Support\CompanyClientStatement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        CompanyBooks::ensureProfile($user);

        $clients = CompanyClient::where('user_id', $user->id)
            ->with(['invoices.payments', 'invoices.childDocuments'])
            ->orderBy('name')
            ->get()
            ->map(function (CompanyClient $client) {
                $docs = $client->invoices;
                $open = CompanyClientStatement::openStatement($docs);
                $client->open_due = $open['total_owed'];

                return $client;
            });

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

        $client = CompanyClient::create([
            'user_id' => $user->id,
            ...$validated,
        ]);

        return redirect('/company/clients/'.$client->id)->with('success', 'Company client added.');
    }

    public function show(Request $request, int $client)
    {
        $user = Auth::user();
        CompanyBooks::ensureProfile($user);
        $model = CompanyClient::where('user_id', $user->id)->where('id', $client)->firstOrFail();

        $tab = $request->input('tab', 'statement');
        if (! in_array($tab, ['statement', 'history'], true)) {
            $tab = 'statement';
        }

        $documents = CompanyInvoice::with([
            'payments' => fn ($q) => $q->orderBy('payment_date')->orderBy('id'),
            'childDocuments',
        ])
            ->where('user_id', $user->id)
            ->where('company_client_id', $model->id)
            ->orderBy('issue_date')
            ->orderBy('id')
            ->get();

        $statement = CompanyClientStatement::openStatement($documents);
        $history = CompanyClientStatement::transactionHistory($documents);

        return view('company.clients-show', [
            'client' => $model,
            'statement' => $statement,
            'history' => $history,
            'tab' => $tab,
        ]);
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

        return redirect('/company/clients/'.$model->id)->with('success', 'Client updated.');
    }
}
