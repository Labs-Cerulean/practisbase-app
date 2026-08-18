<?php

namespace App\Http\Controllers\Pro\Engineer;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\EngineerProject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $showArchived = $request->boolean('archived');
        $q = trim((string) $request->query('q', ''));

        $projects = EngineerProject::where('user_id', Auth::id())
            ->with(['client', 'paApplications'])
            ->withCount('paApplications')
            ->when(
                $showArchived,
                fn ($query) => $query->where('status', 'archived'),
                fn ($query) => $query->where('status', '!=', 'archived')
            )
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.$q.'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('name', 'ilike', $like)
                        ->orWhere('reference_code', 'ilike', $like)
                        ->orWhere('site_locality', 'ilike', $like)
                        ->orWhere('site_address', 'ilike', $like)
                        ->orWhereHas('client', fn ($c) => $c->where('name', 'ilike', $like))
                        ->orWhereHas('paApplications', fn ($p) => $p->where('pa_number', 'ilike', $like));
                });
            })
            ->orderByDesc('updated_at')
            ->get();

        return view('pro.engineer.projects-index', [
            'projects' => $projects,
            'phases' => EngineerProject::PHASES,
            'disciplines' => EngineerProject::DISCIPLINES,
            'statuses' => EngineerProject::STATUSES,
            'showArchived' => $showArchived,
            'q' => $q,
        ]);
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        $clients = Client::where('user_id', $user->id)->orderBy('name')->get();
        $preselect = (int) $request->query('client_id');

        return view('pro.engineer.projects-form', [
            'project' => null,
            'clients' => $clients,
            'phases' => EngineerProject::PHASES,
            'disciplines' => EngineerProject::DISCIPLINES,
            'statuses' => EngineerProject::STATUSES,
            'preselectClientId' => $preselect ?: null,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateProject($request, Auth::id());

        $project = EngineerProject::create([
            'user_id' => Auth::id(),
            ...$validated,
        ]);

        return redirect('/pro/engineer/projects/'.$project->id)
            ->with('success', 'Engineering project created. Add a PA number later when it is issued.');
    }

    public function show(EngineerProject $project)
    {
        $this->assertOwned($project);
        $project->load([
            'client',
            'paApplications' => fn ($q) => $q->orderByDesc('updated_at'),
            'documents' => fn ($q) => $q->whereNull('engineer_pa_application_id')->orderByDesc('updated_at'),
            'certificates' => fn ($q) => $q->orderByDesc('updated_at'),
            'reports' => fn ($q) => $q->orderByDesc('updated_at'),
        ]);

        return view('pro.engineer.projects-show', [
            'project' => $project,
            'phases' => EngineerProject::PHASES,
            'disciplines' => EngineerProject::DISCIPLINES,
            'statuses' => EngineerProject::STATUSES,
        ]);
    }

    public function edit(EngineerProject $project)
    {
        $this->assertOwned($project);
        $clients = Client::where('user_id', Auth::id())->orderBy('name')->get();

        return view('pro.engineer.projects-form', [
            'project' => $project,
            'clients' => $clients,
            'phases' => EngineerProject::PHASES,
            'disciplines' => EngineerProject::DISCIPLINES,
            'statuses' => EngineerProject::STATUSES,
            'preselectClientId' => $project->client_id,
        ]);
    }

    public function update(Request $request, EngineerProject $project)
    {
        $this->assertOwned($project);
        $validated = $this->validateProject($request, Auth::id());
        $project->update($validated);

        return redirect('/pro/engineer/projects/'.$project->id)
            ->with('success', 'Project updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateProject(Request $request, int $userId): array
    {
        $validated = $request->validate([
            'client_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'reference_code' => 'nullable|string|max:100',
            'discipline' => 'required|string|max:64',
            'phase' => 'required|in:'.implode(',', array_keys(EngineerProject::PHASES)),
            'status' => 'required|in:'.implode(',', array_keys(EngineerProject::STATUSES)),
            'site_premises' => 'nullable|string|max:255',
            'site_street' => 'nullable|string|max:255',
            'site_locality' => 'nullable|string|max:120',
            'site_address' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:5000',
        ]);

        $validated['discipline'] = EngineerProject::normalizeDiscipline($validated['discipline'] ?? null);

        $ownsClient = Client::where('user_id', $userId)
            ->where('id', $validated['client_id'])
            ->exists();
        if (! $ownsClient) {
            abort(403);
        }

        return $validated;
    }

    private function assertOwned(EngineerProject $project): void
    {
        if ($project->user_id !== Auth::id()) {
            abort(403);
        }
    }
}
