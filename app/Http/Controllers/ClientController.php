<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Support\TierPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
            $client = Client::create([
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

    // 4. View a Specific Client Profile + open statement + full history
    public function show(Request $request, Client $client)
    {
        $this->authorizeClient($client);

        $tab = $request->input('tab', 'statement');
        if (! in_array($tab, ['statement', 'history'], true)) {
            $tab = 'statement';
        }

        $documents = $client->invoices()
            ->with([
                'payments' => fn ($q) => $q->orderBy('payment_date')->orderBy('id'),
                'childDocuments',
            ])
            ->orderBy('issue_date')
            ->orderBy('id')
            ->get();

        $statement = $this->buildOpenStatement($documents);
        $history = $this->buildTransactionHistory($documents);

        return view('clients.show', compact('client', 'statement', 'history', 'tab'));
    }

    /**
     * @param  Collection<int, Invoice>  $documents
     * @return array{rows: Collection, official_owed: float, rfp_owed: float, total_owed: float}
     */
    private function buildOpenStatement(Collection $documents): array
    {
        $rows = collect();
        $officialOwed = 0.0;
        $rfpOwed = 0.0;

        foreach ($documents as $doc) {
            if ($doc->type === 'invoice') {
                $credits = (float) $doc->childDocuments->where('type', 'credit_note')->sum('total');
                $paid = (float) $doc->payments->where('is_transfer', false)->sum('amount');
                $due = round((float) $doc->total - $credits - $paid, 2);
                if ($due <= 0.009) {
                    continue;
                }
                $officialOwed = round($officialOwed + $due, 2);
                $rows->push([
                    'date' => $doc->issue_date,
                    'label' => $doc->invoice_number,
                    'kind' => 'invoice',
                    'billed' => (float) $doc->total,
                    'credits' => $credits,
                    'paid' => $paid,
                    'due' => $due,
                ]);
            } elseif ($doc->type === 'rfp') {
                $converted = (float) $doc->childDocuments->where('type', 'invoice')->sum('total');
                $remaining = round(max(0, (float) $doc->total - $converted), 2);
                $paid = (float) $doc->payments->where('is_transfer', false)->sum('amount');
                $due = round($remaining - $paid, 2);
                if ($due <= 0.009) {
                    continue;
                }
                $rfpOwed = round($rfpOwed + $due, 2);
                $rows->push([
                    'date' => $doc->issue_date,
                    'label' => $doc->invoice_number.' (RFP)',
                    'kind' => 'rfp',
                    'billed' => $remaining,
                    'credits' => 0.0,
                    'paid' => $paid,
                    'due' => $due,
                ]);
            }
        }

        $rows = $rows->sortBy(fn ($row) => $row['date']->format('Y-m-d').'-'.$row['label'])->values();

        return [
            'rows' => $rows,
            'official_owed' => $officialOwed,
            'rfp_owed' => $rfpOwed,
            'total_owed' => round($officialOwed + $rfpOwed, 2),
        ];
    }

    /**
     * @param  Collection<int, Invoice>  $documents
     * @return array{rows: Collection, official_owed: float, rfp_owed: float, total_owed: float}
     */
    private function buildTransactionHistory(Collection $documents): array
    {
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
                    'note' => null,
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
                    'note' => $doc->parent_document_id ? 'Converted / child of RFP #'.$doc->parent_document_id : null,
                ]);
            } elseif ($doc->type === 'rfp') {
                $converted = (float) $doc->childDocuments->where('type', 'invoice')->sum('total');
                $label = 'RFP '.$doc->invoice_number;
                $label .= ($doc->status === 'converted' || $converted >= (float) $doc->total - 0.009)
                    ? ' (converted)'
                    : ' (pro-forma)';

                $runningRfp = round($runningRfp + (float) $doc->total, 2);
                $rows->push([
                    'date' => $doc->issue_date,
                    'label' => $label,
                    'kind' => 'rfp',
                    'debit' => (float) $doc->total,
                    'credit' => 0.0,
                    'official_balance' => $runningOfficial,
                    'rfp_balance' => $runningRfp,
                    'note' => $converted >= 0.009
                        ? '€'.number_format($converted, 2).' later converted to tax invoice(s)'
                        : null,
                ]);

                if ($converted >= 0.009) {
                    $runningRfp = round($runningRfp - $converted, 2);
                    $rows->push([
                        'date' => $doc->issue_date,
                        'label' => 'RFP '.$doc->invoice_number.' → converted to tax invoice',
                        'kind' => 'convert',
                        'debit' => 0.0,
                        'credit' => $converted,
                        'official_balance' => $runningOfficial,
                        'rfp_balance' => $runningRfp,
                        'note' => 'Removes converted amount from RFP track (invoice rows carry the tax bill)',
                    ]);
                }
            }

            foreach ($doc->payments as $payment) {
                $amount = (float) $payment->amount;
                $isTransfer = (bool) $payment->is_transfer;
                if (! $isTransfer) {
                    if ($doc->type === 'invoice') {
                        $runningOfficial = round($runningOfficial - $amount, 2);
                    } elseif ($doc->type === 'rfp') {
                        $runningRfp = round($runningRfp - $amount, 2);
                    }
                }
                $rows->push([
                    'date' => $payment->payment_date,
                    'label' => ($amount < 0 ? 'Refund on ' : 'Payment on ').$doc->invoice_number,
                    'kind' => $isTransfer ? 'transfer' : 'payment',
                    'debit' => $amount < 0 ? abs($amount) : 0.0,
                    'credit' => $amount > 0 ? $amount : 0.0,
                    'official_balance' => $runningOfficial,
                    'rfp_balance' => $runningRfp,
                    'note' => $isTransfer ? 'Internal transfer — not client cash collected' : null,
                ]);
            }
        }

        return [
            'rows' => $rows,
            'official_owed' => max(0, $runningOfficial),
            'rfp_owed' => max(0, $runningRfp),
            'total_owed' => max(0, $runningOfficial) + max(0, $runningRfp),
        ];
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
