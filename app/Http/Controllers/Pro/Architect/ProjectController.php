<?php

namespace App\Http\Controllers\Pro\Architect;

use App\Http\Controllers\Controller;
use App\Models\ArchitectLicenceContact;
use App\Models\ArchitectPaApplication;
use App\Models\ArchitectProject;
use App\Models\ArchitectSiteParty;
use App\Models\Client;
use App\Support\Architect\MapServerLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $q = trim((string) $request->query('q', ''));
        $locality = trim((string) $request->query('locality', ''));
        $clientId = (int) $request->query('client_id', 0);
        $status = trim((string) $request->query('status', ''));
        $paStatus = trim((string) $request->query('pa_status', ''));
        $caseType = strtoupper(trim((string) $request->query('case_type', '')));

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
                        ->orWhereHas('paApplications', function ($p) use ($like) {
                            $p->where('pa_number', 'ilike', $like)
                                ->orWhere('case_number', 'ilike', $like)
                                ->orWhere('title', 'ilike', $like);
                        });
                });
            })
            ->when($locality !== '', fn ($query) => $query->where('site_locality', 'ilike', $locality))
            ->when($clientId > 0, fn ($query) => $query->where('client_id', $clientId))
            ->when($status !== '' && array_key_exists($status, ArchitectProject::STATUSES), fn ($query) => $query->where('status', $status))
            ->when($paStatus !== '' && array_key_exists($paStatus, ArchitectPaApplication::STATUSES), function ($query) use ($paStatus) {
                $query->whereHas('paApplications', fn ($p) => $p->where('status', $paStatus));
            })
            ->when($caseType !== '' && array_key_exists($caseType, ArchitectPaApplication::CASE_TYPES), function ($query) use ($caseType) {
                $query->whereHas('paApplications', fn ($p) => $p->where('case_type', $caseType));
            })
            ->orderByDesc('updated_at')
            ->get();

        $mapPins = $projects
            ->map(fn (ArchitectProject $p) => $p->mapPinPayload())
            ->filter()
            ->values()
            ->all();

        $clients = Client::where('user_id', $user->id)->orderBy('name')->get(['id', 'name']);
        $localities = ArchitectProject::where('user_id', $user->id)
            ->whereNotNull('site_locality')
            ->where('site_locality', '!=', '')
            ->distinct()
            ->orderBy('site_locality')
            ->pluck('site_locality');

        return view('pro.architect.projects-index', [
            'projects' => $projects,
            'phases' => ArchitectProject::PHASES,
            'statuses' => ArchitectProject::STATUSES,
            'paStatuses' => ArchitectPaApplication::STATUSES,
            'caseTypes' => ArchitectPaApplication::CASE_TYPES,
            'clients' => $clients,
            'localities' => $localities,
            'mapPins' => $mapPins,
            'mapServerUrl' => MapServerLink::home(),
            'filters' => [
                'q' => $q,
                'locality' => $locality,
                'client_id' => $clientId > 0 ? $clientId : '',
                'status' => $status,
                'pa_status' => $paStatus,
                'case_type' => $caseType,
            ],
        ]);
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        $clients = Client::where('user_id', $user->id)->orderBy('name')->get();
        $preselect = (int) $request->query('client_id');

        return view('pro.architect.projects-form', [
            'project' => null,
            'clients' => $clients,
            'engagementTypes' => ArchitectProject::ENGAGEMENT_TYPES,
            'phases' => ArchitectProject::PHASES,
            'statuses' => ArchitectProject::STATUSES,
            'preselectClientId' => $preselect ?: null,
            'mapServerUrl' => MapServerLink::home(),
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
            ->with('success', 'Project created. Pin the site on the map and add a PA/PC/DN case when ready.');
    }

    public function show(ArchitectProject $project)
    {
        $this->assertOwned($project);
        $project->load([
            'client',
            'paApplications' => fn ($q) => $q->orderByDesc('updated_at'),
            'documents' => fn ($q) => $q->whereNull('architect_pa_application_id')->orderByDesc('updated_at'),
            'siteParties' => fn ($q) => $q->orderBy('role_key'),
            'conditionReports' => fn ($q) => $q->orderByDesc('updated_at'),
            'methodStatements' => fn ($q) => $q->orderByDesc('updated_at'),
        ]);

        return view('pro.architect.projects-show', [
            'project' => $project,
            'phases' => ArchitectProject::PHASES,
            'statuses' => ArchitectProject::STATUSES,
            'paStatuses' => ArchitectPaApplication::STATUSES,
            'roles' => ArchitectSiteParty::ROLES,
            'licenceTypes' => ArchitectSiteParty::LICENCE_TYPES,
            'mapServerUrl' => MapServerLink::home(),
        ]);
    }

    public function edit(ArchitectProject $project)
    {
        $this->assertOwned($project);
        $clients = Client::where('user_id', Auth::id())->orderBy('name')->get();

        return view('pro.architect.projects-form', [
            'project' => $project,
            'clients' => $clients,
            'engagementTypes' => ArchitectProject::ENGAGEMENT_TYPES,
            'phases' => ArchitectProject::PHASES,
            'statuses' => ArchitectProject::STATUSES,
            'preselectClientId' => $project->client_id,
            'mapServerUrl' => MapServerLink::home(),
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

    public function reverseGeocode(Request $request)
    {
        $validated = $request->validate([
            'lat' => 'required|numeric|between:35.7,36.2',
            'lng' => 'required|numeric|between:14.1,14.7',
        ]);

        try {
            $response = Http::timeout(8)
                ->withHeaders([
                    'User-Agent' => 'PractisBase/1.0 (architect site pin; contact support@practisbase.com)',
                    'Accept' => 'application/json',
                ])
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'format' => 'jsonv2',
                    'lat' => $validated['lat'],
                    'lon' => $validated['lng'],
                    'addressdetails' => 1,
                    'zoom' => 18,
                ]);
        } catch (\Throwable) {
            return response()->json(['ok' => false, 'message' => 'Map lookup unavailable right now.'], 502);
        }

        if (! $response->ok()) {
            return response()->json(['ok' => false, 'message' => 'Map lookup failed.'], 502);
        }

        $address = $response->json('address') ?? [];
        $street = trim(implode(' ', array_filter([
            $address['house_number'] ?? null,
            $address['road'] ?? $address['pedestrian'] ?? $address['path'] ?? null,
        ])));
        $locality = $address['village']
            ?? $address['town']
            ?? $address['city']
            ?? $address['suburb']
            ?? $address['municipality']
            ?? '';

        return response()->json([
            'ok' => true,
            'street' => $street,
            'locality' => is_string($locality) ? $locality : '',
            'display_name' => (string) ($response->json('display_name') ?? ''),
        ]);
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

        ArchitectLicenceContact::rememberForUser((int) Auth::id(), [
            'full_name' => $validated['full_name'],
            'company_name' => $validated['company_name'] ?? null,
            'mobile' => $validated['mobile'] ?? null,
            'email' => $validated['email'] ?? null,
            'id_card' => $validated['id_card'] ?? null,
            'licence_type' => $validated['licence_type'] ?? null,
            'licence_number' => $validated['licence_number'] ?? null,
            'preferred_role_key' => $validated['role_key'],
            'source' => 'site_team',
        ]);

        return redirect('/pro/architect/projects/'.$project->id.'#site-team')
            ->with('success', 'Site party added. Details saved for reuse on other projects.');
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
        $lat = $request->input('latitude');
        $lng = $request->input('longitude');
        $request->merge([
            'latitude' => ($lat === '' || $lat === null) ? null : $lat,
            'longitude' => ($lng === '' || $lng === null) ? null : $lng,
        ]);

        $validated = $request->validate([
            'client_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'reference_code' => 'nullable|string|max:100',
            'engagement_type' => 'required|in:'.implode(',', array_keys(ArchitectProject::ENGAGEMENT_TYPES)),
            'phase' => 'required|in:'.implode(',', array_keys(ArchitectProject::PHASES)),
            'status' => 'required|in:'.implode(',', array_keys(ArchitectProject::STATUSES)),
            'site_premises' => 'nullable|string|max:255',
            'site_street' => 'nullable|string|max:255',
            'site_locality' => 'nullable|string|max:120',
            'site_address' => 'nullable|string|max:2000',
            'latitude' => 'nullable|numeric|between:35.7,36.2',
            'longitude' => 'nullable|numeric|between:14.1,14.7',
            'notes' => 'nullable|string|max:5000',
        ]);

        $ownsClient = Client::where('user_id', $userId)
            ->where('id', $validated['client_id'])
            ->exists();
        if (! $ownsClient) {
            abort(403);
        }

        if (($validated['latitude'] ?? null) === null || ($validated['longitude'] ?? null) === null) {
            $validated['latitude'] = null;
            $validated['longitude'] = null;
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
