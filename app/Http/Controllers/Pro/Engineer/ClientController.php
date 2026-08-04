<?php

namespace App\Http\Controllers\Pro\Engineer;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\EngineerClient;
use App\Models\EngineerProject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $q = trim((string) $request->query('q', ''));

        $clients = EngineerClient::query()
            ->where('user_id', $user->id)
            ->withCount('projects')
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.$q.'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('name', 'ilike', $like)
                        ->orWhere('locality', 'ilike', $like)
                        ->orWhere('email', 'ilike', $like)
                        ->orWhere('phone', 'ilike', $like)
                        ->orWhere('id_card', 'ilike', $like);
                });
            })
            ->orderBy('name')
            ->get();

        $orphanProjects = EngineerProject::query()
            ->where('user_id', $user->id)
            ->whereNull('engineer_client_id')
            ->count();

        return view('pro.engineer.clients-index', compact('clients', 'q', 'orphanProjects'));
    }

    public function create()
    {
        $billingClients = Client::where('user_id', Auth::id())->orderBy('name')->get(['id', 'name']);

        return view('pro.engineer.clients-form', [
            'client' => null,
            'billingClients' => $billingClients,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $validated = $this->validateClient($request, $user->id);

        $client = EngineerClient::create([
            'user_id' => $user->id,
            ...$validated,
        ]);

        return redirect('/pro/engineer/clients/'.$client->id)
            ->with('success', 'Client saved.');
    }

    public function show(EngineerClient $client)
    {
        $this->assertOwned($client);

        $client->load([
            'projects' => fn ($q) => $q->orderByDesc('updated_at')->withCount('paApplications'),
            'documents' => fn ($q) => $q->orderByDesc('updated_at')->limit(20),
        ]);

        return view('pro.engineer.clients-show', [
            'client' => $client,
            'phases' => EngineerProject::PHASES,
            'disciplines' => EngineerProject::DISCIPLINES,
        ]);
    }

    public function edit(EngineerClient $client)
    {
        $this->assertOwned($client);
        $billingClients = Client::where('user_id', Auth::id())->orderBy('name')->get(['id', 'name']);

        return view('pro.engineer.clients-form', [
            'client' => $client,
            'billingClients' => $billingClients,
        ]);
    }

    public function update(Request $request, EngineerClient $client)
    {
        $this->assertOwned($client);
        $validated = $this->validateClient($request, Auth::id());
        $client->update($validated);

        return redirect('/pro/engineer/clients/'.$client->id)
            ->with('success', 'Client updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateClient(Request $request, int $userId): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'id_card' => 'nullable|string|max:64',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:64',
            'address' => 'nullable|string|max:2000',
            'locality' => 'nullable|string|max:120',
            'billing_client_id' => 'nullable|integer',
            'notes' => 'nullable|string|max:5000',
        ]);

        if (! empty($validated['billing_client_id'])) {
            $owns = Client::where('user_id', $userId)->where('id', $validated['billing_client_id'])->exists();
            if (! $owns) {
                abort(403);
            }
        } else {
            $validated['billing_client_id'] = null;
        }

        return $validated;
    }

    private function assertOwned(EngineerClient $client): void
    {
        if ($client->user_id !== Auth::id()) {
            abort(403);
        }
    }
}
