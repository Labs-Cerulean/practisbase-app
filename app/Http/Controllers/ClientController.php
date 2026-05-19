<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientController extends Controller
{
    // 1. Show the Client Directory
    public function index()
    {
        // Only fetch clients that belong to the logged-in professional
        $clients = Client::where('user_id', Auth::id())->latest()->get();
        
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
}