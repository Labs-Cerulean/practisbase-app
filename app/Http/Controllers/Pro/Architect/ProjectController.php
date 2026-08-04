<?php

namespace App\Http\Controllers\Pro\Architect;

use App\Http\Controllers\Controller;
use App\Models\ArchitectClient;
use App\Models\ArchitectProject;
use App\Models\ArchitectSiteParty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $q = trim((string) $request->query('q', ''));

        $projects = ArchitectProject::query()
            ->where('user_id', $user->id)
            ->with(['client', 'paApplications'])
            ->withCount('paApplications')
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.$q.'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('name', 'ilike', $like)
                        ->orWhere('reference_code', 'ilike', $like)
                        ->orWhere('site_locality', 'ilike', $like)
                        ->orWhere('site_address', 'ilike', $like)
                        ->orWhere('site_street', 'ilike', $like)
                        ->orWhereHas('client', fn ($c) => $c->where('name', 'ilike', $like))
                        ->orWhereHas('paApplications', fn ($p) => $p->where('pa_number', 'ilike', $like));
                });
            })
            ->orderByDesc('updated_at')
            ->get();

        return view('pro.architect.projects-index', [
            'projects' => $projects,
            'phases' => ArchitectProject::PHASES,
            'q' => $q,
        ]);
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        $clients = ArchitectClient::where('user_id', $user->id)->orderBy('name')->get();
        $preselect = (int) $request->query('client_id');

        return view('pro.architect.projects-form', [
            'project' => null,
            'clients' => $clients,
            'phases' => ArchitectProject::PHASES,
            'statuses' => ArchitectProject::STATUSES,
            'preselectClientId' => $preselect ?: null,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $validated = $this->validateProject($request, $user->id);

        $project = ArchitectProject::create([
            'user_id' => $user->id,
            ...$validated,
        ]);

        return redirect('/pro/architect/projects/'.$project->id)
            ->with('success', 'Project created. Add a PA number later when it is issued.');
    }

    public function show(ArchitectProject $project)
    {
        $this->assertOwned($project);
        $project->load([
            'client',
            'paApplications' => fn ($q) => $q->orderByDesc('updated_at'),
            'documents' => fn ($q) => $q->whereNull('architect_pa_application_id')->orderByDesc('updated_at'),
            'siteParties' => fn ($q) => $q->orderBy('role_key'),
        ]);

        return view('pro.architect.projects-show', [
            'project' => $project,
            'phases' => ArchitectProject::PHASES,
            'statuses' => ArchitectProject::STATUSES,
            'roles' => ArchitectSiteParty::ROLES,
            'licenceTypes' => ArchitectSiteParty::LICENCE_TYPES,
        ]);
    }

    public function edit(ArchitectProject $project)
    {
        $this->assertOwned($project);
        $clients = ArchitectClient::where('user_id', Auth::id())->orderBy('name')->get();

        return view('pro.architect.projects-form', [
            'project' => $project,
            'clients' => $clients,
            'phases' => ArchitectProject::PHASES,
            'statuses' => ArchitectProject::STATUSES,
            'preselectClientId' => $project->architect_client_id,
        ]);
    }

    public function update(Request $request, ArchitectProject $project)
    {
        $this->assertOwned($project);
        $validated = $this->validateProject($request, Auth::id());
        $project->update($validated);

        return redirect('/pro/architect/projects/'.$project->id)
            ->with('success', 'Project updated.');
    }

    public function storeParty(Request $request, ArchitectProject $project)
    {
        $this->assertOwned($project);

        $validated = $request->validate([
            'role_key' => 'required|in:'.implode(',', array_keys(ArchitectSiteParty::ROLES)),
            'full_name' => 'required|string|max:255',
            'id_card' => 'nullable|string|max:64',
            'mobile' => 'nullable|string|max:64',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:2000',
            'company_name' => 'nullable|string|max:255',
            'licence_type' => 'nullable|in:'.implode(',', array_keys(ArchitectSiteParty::LICENCE_TYPES)),
            'licence_number' => 'nullable|string|max:120',
            'notes' => 'nullable|string|max:2000',
            'architect_pa_application_id' => 'nullable|integer',
        ]);

        if (! empty($validated['architect_pa_application_id'])) {
            $ownsPa = $project->paApplications()->where('id', $validated['architect_pa_application_id'])->exists();
            if (! $ownsPa) {
                abort(403);
            }
        } else {
            $validated['architect_pa_application_id'] = null;
        }

        ArchitectSiteParty::create([
            'user_id' => Auth::id(),
            'architect_project_id' => $project->id,
            ...$validated,
        ]);

        if (! empty($validated['licence_type']) || filled($validated['licence_number'] ?? null)) {
            \App\Models\ArchitectLicenceContact::create([
                'user_id' => Auth::id(),
                'licence_type' => $validated['licence_type'] ?: 'contractor',
                'licence_number' => $validated['licence_number'] ?? null,
                'full_name' => $validated['full_name'],
                'company_name' => $validated['company_name'] ?? null,
                'mobile' => $validated['mobile'] ?? null,
                'source' => 'site_team',
                'last_used_at' => now(),
            ]);
        }

        return redirect('/pro/architect/projects/'.$project->id.'#site-team')
            ->with('success', 'Site party added.');
    }

    public function destroyParty(ArchitectProject $project, ArchitectSiteParty $party)
    {
        $this->assertOwned($project);
        if ($party->user_id !== Auth::id() || $party->architect_project_id !== $project->id) {
            abort(403);
        }
        $party->delete();

        return redirect('/pro/architect/projects/'.$project->id.'#site-team')
            ->with('success', 'Site party removed.');
    }

    private function validateProject(Request $request, int $userId): array
    {
        $validated = $request->validate([
            'architect_client_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'reference_code' => 'nullable|string|max:100',
            'phase' => 'required|in:'.implode(',', array_keys(ArchitectProject::PHASES)),
            'status' => 'required|in:'.implode(',', array_keys(ArchitectProject::STATUSES)),
            'site_premises' => 'nullable|string|max:255',
            'site_street' => 'nullable|string|max:255',
            'site_locality' => 'nullable|string|max:120',
            'site_address' => 'nullable|string|max:2000',
            'commencement_date' => 'nullable|date|before_or_equal:today',
            'notes' => 'nullable|string|max:5000',
        ]);

        $ownsClient = ArchitectClient::where('user_id', $userId)
            ->where('id', $validated['architect_client_id'])
            ->exists();
        if (! $ownsClient) {
            abort(403);
        }

        return $validated;
    }

    private function assertOwned(ArchitectProject $project): void
    {
        if ($project->user_id !== Auth::id()) {
            abort(403);
        }
    }
}
