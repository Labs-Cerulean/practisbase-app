<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Support\TierPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{
    // 1. Show the Client Directory (Intelligent Dashboard Edition)
    public function index(Request $request)
    {
        $userId = Auth::id();
        $showArchived = $request->boolean('archived');

        // Eager load invoices and their payments to prevent N+1 issues
        $query = Client::where('user_id', $userId)->with('invoices.payments');

        if ($showArchived) {
            $query->onlyTrashed();
        }

        // --- 1. FILTERING ---
        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) like ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(email) like ?', ["%{$search}%"]);
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // --- 2. INTELLIGENT MATH PER CLIENT ---
        $clients = $query->get()->map(function ($client) {

            $allInvoices = $client->invoices;

            $totalInvoices = $allInvoices->where('type', 'invoice')->sum('total');
            $totalCredits = $allInvoices->where('type', 'credit_note')->sum('total');
            $netInvoiced = $totalInvoices - $totalCredits;

            $topLevelTotal = $allInvoices->whereNull('parent_document_id')->sum('total');
            $totalPipeline = $topLevelTotal - $totalCredits;
            $unbilledPipeline = max(0, $totalPipeline - $netInvoiced);

            $rfpCash = $allInvoices->where('type', 'rfp')->flatMap->payments->where('is_transfer', false)->sum('amount');
            $invoiceCash = $allInvoices->where('type', 'invoice')->flatMap->payments->where('is_transfer', false)->sum('amount');
            $totalPaid = $rfpCash + $invoiceCash;

            $officialDues = max(0, $netInvoiced - max(0, $invoiceCash));
            $unbilledDues = max(0, $unbilledPipeline - max(0, $rfpCash));
            $totalDues = $officialDues + $unbilledDues;

            // Attach metrics dynamically to the client object
            $client->net_invoiced = $netInvoiced;
            $client->unbilled_pipeline = $unbilledPipeline;
            $client->total_paid = $totalPaid;
            $client->total_dues = $totalDues;

            return $client;
        });

        // --- 3. SORTING ---
        $sort = $request->input('sort', 'recent');
        if ($sort === 'name_asc') {
            $clients = $clients->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();
        } elseif ($sort === 'name_desc') {
            $clients = $clients->sortByDesc('name', SORT_NATURAL | SORT_FLAG_CASE)->values();
        } elseif ($sort === 'highest_due') {
            $clients = $clients->sortByDesc('total_dues')->values();
        } else {
            $clients = $clients->sortByDesc('created_at')->values();
        }

        $archivedCount = Client::onlyTrashed()->where('user_id', $userId)->count();

        return view('clients.index', compact('clients', 'showArchived', 'archivedCount'));
    }

    // 2. Show the "Add New Client" Form
    public function create()
    {
        $user = Auth::user();

        if (! $user->canAddClient()) {
            return redirect('/clients')->withErrors([
                'client_limit' => 'Free / Practice plans allow ' . TierPolicy::FREE_CLIENT_LIFETIME_CAP . ' lifetime clients. Deleting a client does not free a slot. Upgrade to Standard or Full Pro for unlimited clients.',
            ]);
        }

        return view('clients.create');
    }

    // 3. Securely Save the Client
    public function store(Request $request)
    {
        $user = Auth::user();

        if (! $user->canAddClient()) {
            return redirect('/clients')->withErrors([
                'client_limit' => 'Free / Practice plans allow ' . TierPolicy::FREE_CLIENT_LIFETIME_CAP . ' lifetime clients. Deleting a client does not free a slot. Upgrade to Standard or Full Pro for unlimited clients.',
            ]);
        }

        $request->validate([
            'type' => 'required|in:individual,company',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'billing_address' => 'nullable|string',
        ]);

        $profileData = [];
        
        if ($request->type === 'company') {
            $profileData['vat_number'] = $request->vat_number;
            $profileData['registration_number'] = $request->registration_number;
            $profileData['contact_person'] = $request->contact_person;
        } else {
            $profileData['id_card_number'] = $request->id_card_number;
        }

        $profileData = Client::billingProfileOnly($profileData);

        DB::transaction(function () use ($user, $request, $profileData) {
            Client::create([
                'user_id' => $user->id,
                'type' => $request->type,
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'billing_address' => $request->billing_address,
                'profile_data' => $profileData,
            ]);

            $user->increment('clients_created_count');
        });

        return redirect('/clients')->with('success', 'Client added successfully!');
    }

    // 4. View a Specific Client Profile + statement of what they owe
    public function show(Client $client)
    {
        $this->authorizeClient($client);

        $documents = $client->invoices()
            ->with([
                'payments' => fn ($q) => $q->orderBy('payment_date')->orderBy('id'),
                'childDocuments' => fn ($q) => $q->where('type', 'invoice'),
            ])
            ->orderBy('issue_date')
            ->orderBy('id')
            ->get();

        $rows = collect();
        $runningOfficial = 0.0;
        $runningRfp = 0.0;

        foreach ($documents as $doc) {
            if ($doc->type === 'credit_note') {
                $runningOfficial = round($runningOfficial - (float) $doc->total, 2);
                $rows->push([
                    'date' => $doc->issue_date,
                    'label' => 'Credit '.$doc->invoice_number,
                    'kind' => 'credit',
                    'debit' => 0.0,
                    'credit' => (float) $doc->total,
                    'official_balance' => $runningOfficial,
                    'rfp_balance' => $runningRfp,
                ]);
                continue;
            }

            if ($doc->type === 'invoice') {
                $runningOfficial = round($runningOfficial + (float) $doc->total, 2);
                $rows->push([
                    'date' => $doc->issue_date,
                    'label' => 'Invoice '.$doc->invoice_number,
                    'kind' => 'invoice',
                    'debit' => (float) $doc->total,
                    'credit' => 0.0,
                    'official_balance' => $runningOfficial,
                    'rfp_balance' => $runningRfp,
                ]);
            } elseif ($doc->type === 'rfp') {
                $converted = (float) $doc->childDocuments->sum('total');
                $remaining = round(max(0, (float) $doc->total - $converted), 2);
                if ($remaining >= 0.009) {
                    $runningRfp = round($runningRfp + $remaining, 2);
                    $rows->push([
                        'date' => $doc->issue_date,
                        'label' => 'RFP '.$doc->invoice_number.' (not yet tax-invoiced)',
                        'kind' => 'rfp',
                        'debit' => $remaining,
                        'credit' => 0.0,
                        'official_balance' => $runningOfficial,
                        'rfp_balance' => $runningRfp,
                    ]);
                } elseif ($doc->status === 'converted') {
                    $rows->push([
                        'date' => $doc->issue_date,
                        'label' => 'RFP '.$doc->invoice_number.' (fully converted)',
                        'kind' => 'rfp',
                        'debit' => 0.0,
                        'credit' => 0.0,
                        'official_balance' => $runningOfficial,
                        'rfp_balance' => $runningRfp,
                    ]);
                }
            }

            foreach ($doc->payments as $payment) {
                if ($payment->is_transfer) {
                    continue;
                }
                $amount = (float) $payment->amount;
                if ($doc->type === 'invoice') {
                    $runningOfficial = round($runningOfficial - $amount, 2);
                } elseif ($doc->type === 'rfp') {
                    $runningRfp = round($runningRfp - $amount, 2);
                }
                $rows->push([
                    'date' => $payment->payment_date,
                    'label' => ($amount < 0 ? 'Refund on ' : 'Payment on ').$doc->invoice_number,
                    'kind' => 'payment',
                    'debit' => $amount < 0 ? abs($amount) : 0.0,
                    'credit' => $amount > 0 ? $amount : 0.0,
                    'official_balance' => $runningOfficial,
                    'rfp_balance' => $runningRfp,
                ]);
            }
        }

        $statement = [
            'rows' => $rows,
            'official_owed' => max(0, $runningOfficial),
            'rfp_owed' => max(0, $runningRfp),
            'total_owed' => max(0, $runningOfficial) + max(0, $runningRfp),
        ];

        return view('clients.show', compact('client', 'statement'));
    }

    // 5. Show the Edit Form
    public function edit(Client $client)
    {
        $this->authorizeClient($client);

        if ($client->trashed()) {
            return redirect("/clients/{$client->id}")->withErrors([
                'archive' => 'Restore this client before editing.',
            ]);
        }

        return view('clients.edit', compact('client'));
    }

    // 6. Securely Update the Client
    public function update(Request $request, Client $client)
    {
        $this->authorizeClient($client);

        if ($client->trashed()) {
            return redirect("/clients/{$client->id}")->withErrors([
                'archive' => 'Restore this client before editing.',
            ]);
        }

        $request->validate([
            'type' => 'required|in:individual,company',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'billing_address' => 'nullable|string',
        ]);

        $profileData = [];

        if ($request->type === 'company') {
            $profileData['vat_number'] = $request->vat_number;
            $profileData['registration_number'] = $request->registration_number;
            $profileData['contact_person'] = $request->contact_person;
        } else {
            $profileData['id_card_number'] = $request->id_card_number;
        }

        $client->update([
            'type' => $request->type,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'billing_address' => $request->billing_address,
            'profile_data' => Client::billingProfileOnly($profileData),
        ]);

        return redirect("/clients/{$client->id}")->with('success', 'Client updated successfully!');
    }

    // Soft-archive: hide from directory; invoice history retained. Does NOT free Free quota.
    public function archive(Client $client)
    {
        $this->authorizeClient($client);

        if ($client->trashed()) {
            return redirect('/clients?archived=1')->with('success', 'Client is already archived.');
        }

        $client->delete();

        return redirect('/clients')->with(
            'success',
            'Client archived. Invoice history is kept. Archiving does not free a Free-plan client slot.'
        );
    }

    public function restore(Client $client)
    {
        $this->authorizeClient($client);

        if (! $client->trashed()) {
            return redirect("/clients/{$client->id}")->with('success', 'Client is already active.');
        }

        $client->restore();

        return redirect("/clients/{$client->id}")->with(
            'success',
            'Client restored to your active directory.'
        );
    }

    private function authorizeClient(Client $client): void
    {
        if ($client->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }
    }
}
