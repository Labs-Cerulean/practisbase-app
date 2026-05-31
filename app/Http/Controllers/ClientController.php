<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientController extends Controller
{
    // 1. Show the Client Directory (Intelligent Dashboard Edition)
    public function index(Request $request)
    {
        $userId = Auth::id();
        
        // Eager load invoices and their payments to prevent N+1 issues
        $query = Client::where('user_id', $userId)->with('invoices.payments');
        
        // --- 1. FILTERING ---
        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where(function($q) use ($search) {
                $q->whereRaw('LOWER(name) like ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(email) like ?', ["%{$search}%"]);
            });
        }
        
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // --- 2. INTELLIGENT MATH PER CLIENT ---
        $clients = $query->get()->map(function($client) {
            
            $allInvoices = $client->invoices;
            
            $totalInvoices = $allInvoices->where('type', 'invoice')->sum('total');
            $totalCredits = $allInvoices->where('type', 'credit_note')->sum('total');
            $netInvoiced = $totalInvoices - $totalCredits;

            $topLevelTotal = $allInvoices->whereNull('parent_document_id')->sum('total');
            $totalPipeline = $topLevelTotal - $totalCredits;
            $unbilledPipeline = max(0, $totalPipeline - $netInvoiced);

            $rfpCash = $allInvoices->where('type', 'rfp')->flatMap->payments->sum('amount');
            $invoiceCash = $allInvoices->where('type', 'invoice')->flatMap->payments->sum('amount');
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

        return view('clients.index', compact('clients'));
    }

    // 2. Show the "Add New Client" Form
    public function create()
    {
        return view('clients.create');
    }

    // 3. Securely Save the Client
    public function store(Request $request)
    {
        // Validate the universal core fields
        $validated = $request->validate([
            'type' => 'required|in:individual,company',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'billing_address' => 'nullable|string',
        ]);

        // Dynamically pack the extra fields into a JSON array based on the type
        $profileData = [];
        
        if ($request->type === 'company') {
            $profileData['vat_number'] = $request->vat_number;
            $profileData['registration_number'] = $request->registration_number;
            $profileData['contact_person'] = $request->contact_person;
        } else {
            // It's an individual
            $profileData['id_card_number'] = $request->id_card_number;
            
            // If the user is a medical professional, grab the clinical fields too
            if (Auth::user()->profession === 'Medical Professional') {
                $profileData['dob'] = $request->dob;
                $profileData['gender'] = $request->gender;
                $profileData['blood_type'] = $request->blood_type;
                $profileData['allergies'] = $request->allergies;
            }
        }

        // Save it to the database
        Client::create([
            'user_id' => Auth::id(),
            'type' => $request->type,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'billing_address' => $request->billing_address,
            'profile_data' => $profileData, // Laravel automatically converts this array to JSON!
        ]);

        return redirect('/clients')->with('success', 'Client added successfully!');
    }

    // 4. View a Specific Client Profile
    public function show(Client $client)
    {
        // Security Check: Does this client belong to the logged-in user?
        if ($client->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        return view('clients.show', compact('client'));
    }

    // 5. Show the Edit Form
    public function edit(Client $client)
    {
        // Security Check
        if ($client->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        return view('clients.edit', compact('client'));
    }

    // 6. Securely Update the Client
    public function update(Request $request, Client $client)
    {
        // Security Check
        if ($client->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
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
            
            if (Auth::user()->profession === 'Medical Professional') {
                $profileData['dob'] = $request->dob;
                $profileData['gender'] = $request->gender;
                $profileData['blood_type'] = $request->blood_type;
                $profileData['allergies'] = $request->allergies;
            }
        }

        $client->update([
            'type' => $request->type,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'billing_address' => $request->billing_address,
            'profile_data' => $profileData,
        ]);

        return redirect("/clients/{$client->id}")->with('success', 'Client updated successfully!');
    }
}