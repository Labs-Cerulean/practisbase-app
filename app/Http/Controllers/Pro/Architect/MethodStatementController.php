<?php

namespace App\Http\Controllers\Pro\Architect;

use App\Http\Controllers\Controller;
use App\Models\ArchitectMethodStatement;
use App\Models\ArchitectMethodStatementPhoto;
use App\Models\ArchitectPaApplication;
use App\Models\ArchitectProject;
use App\Support\ArchitectMethodStatementBlueprint;
use App\Support\IssueCode;
use App\Support\TenantStorage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MethodStatementController extends Controller
{
    public function index(Request $request)
    {
        $projectId = (int) $request->query('project_id');

        $statements = ArchitectMethodStatement::where('user_id', Auth::id())
            ->with(['project', 'paApplication'])
            ->when($projectId > 0, fn ($q) => $q->where('architect_project_id', $projectId))
            ->orderByDesc('id')
            ->get();

        return view('pro.architect.method-statements-index', [
            'statements' => $statements,
            'projectId' => $projectId ?: null,
        ]);
    }

    public function create(Request $request)
    {
        $starterKey = (string) $request->query('starter', 'demolition');
        $starters = ArchitectMethodStatementBlueprint::starters();
        if (! isset($starters[$starterKey])) {
            $starterKey = 'demolition';
        }
        $starter = $starters[$starterKey];

        return view('pro.architect.method-statements-form', [
            'statement' => null,
            'projects' => ArchitectProject::where('user_id', Auth::id())->with('client')->orderBy('name')->get(),
            'pas' => ArchitectPaApplication::where('user_id', Auth::id())->with('project')->orderByDesc('updated_at')->get(),
            'payload' => $starter['payload'],
            'starters' => $starters,
            'starterKey' => $starterKey,
            'defaultTitle' => $starter['title'],
            'prefill' => [
                'project_id' => $request->query('project_id'),
                'pa_id' => $request->query('pa_id'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateStatement($request);
        $scope = $this->resolveScope(Auth::id(), $validated);

        $statement = ArchitectMethodStatement::create([
            'user_id' => Auth::id(),
            'architect_project_id' => $scope['architect_project_id'],
            'architect_pa_application_id' => $scope['architect_pa_application_id'],
            'title' => $validated['title'],
            'statement_type' => $validated['statement_type'] ?? null,
            'statement_number' => $validated['statement_number'] ?? null,
            'issued_on' => $validated['issued_on'],
            'commencement_note' => $validated['commencement_note'] ?? null,
            'client_name' => $validated['client_name'] ?? null,
            'client_address' => $validated['client_address'] ?? null,
            'project_description' => $validated['project_description'] ?? null,
            'site_address' => $validated['site_address'] ?? null,
            'payload' => ArchitectMethodStatementBlueprint::normalize($validated['payload'] ?? []),
        ]);

        $this->storePhotos($request, $statement);

        return redirect('/pro/architect/method-statements/'.$statement->id)
            ->with('success', 'Draft method statement saved. Edit until you Stamp & issue.');
    }

    public function show(ArchitectMethodStatement $statement)
    {
        $this->assertOwned($statement);
        $statement->load(['project.client', 'paApplication', 'photos']);

        return view('pro.architect.method-statements-show', [
            'statement' => $statement,
            'payload' => $statement->normalizedPayload(),
        ]);
    }

    public function edit(ArchitectMethodStatement $statement)
    {
        $this->assertOwned($statement);
        if (! $statement->isEditable()) {
            return redirect('/pro/architect/method-statements/'.$statement->id)
                ->withErrors(['statement' => 'This method statement was stamped and issued. It can no longer be edited.']);
        }

        return view('pro.architect.method-statements-form', [
            'statement' => $statement,
            'projects' => ArchitectProject::where('user_id', Auth::id())->with('client')->orderBy('name')->get(),
            'pas' => ArchitectPaApplication::where('user_id', Auth::id())->with('project')->orderByDesc('updated_at')->get(),
            'payload' => $statement->normalizedPayload(),
            'starters' => ArchitectMethodStatementBlueprint::starters(),
            'starterKey' => $statement->statement_type,
            'defaultTitle' => $statement->title,
            'prefill' => [
                'project_id' => $statement->architect_project_id,
                'pa_id' => $statement->architect_pa_application_id,
            ],
        ]);
    }

    public function update(Request $request, ArchitectMethodStatement $statement)
    {
        $this->assertOwned($statement);
        if (! $statement->isEditable()) {
            return redirect('/pro/architect/method-statements/'.$statement->id)
                ->withErrors(['statement' => 'This method statement was stamped and issued. It can no longer be edited.']);
        }

        $validated = $this->validateStatement($request);
        $scope = $this->resolveScope(Auth::id(), $validated);

        $statement->update([
            'architect_project_id' => $scope['architect_project_id'],
            'architect_pa_application_id' => $scope['architect_pa_application_id'],
            'title' => $validated['title'],
            'statement_type' => $validated['statement_type'] ?? $statement->statement_type,
            'statement_number' => $validated['statement_number'] ?? null,
            'issued_on' => $validated['issued_on'],
            'commencement_note' => $validated['commencement_note'] ?? null,
            'client_name' => $validated['client_name'] ?? null,
            'client_address' => $validated['client_address'] ?? null,
            'project_description' => $validated['project_description'] ?? null,
            'site_address' => $validated['site_address'] ?? null,
            'payload' => ArchitectMethodStatementBlueprint::normalize($validated['payload'] ?? []),
        ]);

        $this->storePhotos($request, $statement);

        return redirect('/pro/architect/method-statements/'.$statement->id)
            ->with('success', 'Method statement updated.');
    }

    public function stamp(ArchitectMethodStatement $statement)
    {
        $this->assertOwned($statement);
        if ($statement->isStamped()) {
            return back()->withErrors(['statement' => 'Already stamped and issued.']);
        }

        $statement->stamped_at = now();
        $statement->issue_code = IssueCode::allocateForArchitectMethodStatement();
        $statement->save();

        return redirect('/pro/architect/method-statements/'.$statement->id)
            ->with('success', 'Method statement stamped and issued as '.$statement->issue_code.'. It is now locked.');
    }

    public function downloadPdf(ArchitectMethodStatement $statement)
    {
        $this->assertOwned($statement);
        if (! $statement->isStamped()) {
            return redirect('/pro/architect/method-statements/'.$statement->id)
                ->withErrors(['statement' => 'Stamp & issue before downloading the PDF.']);
        }

        $user = Auth::user();
        $statement->load(['photos', 'project.client', 'paApplication']);

        $pdf = Pdf::loadView('pro.architect.method-statements-pdf', [
            'statement' => $statement,
            'payload' => $statement->normalizedPayload(),
            'user' => $user,
        ])->setPaper('a4');

        $safe = preg_replace('/[^A-Za-z0-9\-_]+/', '-', $statement->title) ?: 'method-statement';

        return $pdf->download($safe.'-'.$statement->issue_code.'.pdf');
    }

    public function downloadPhoto(ArchitectMethodStatement $statement, ArchitectMethodStatementPhoto $photo)
    {
        $this->assertOwned($statement);
        if ($photo->architect_method_statement_id !== $statement->id || $photo->user_id !== Auth::id()) {
            abort(403);
        }

        $disk = TenantStorage::disk();
        if (! $disk->exists($photo->file_path)) {
            abort(404);
        }

        return $disk->response($photo->file_path);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateStatement(Request $request): array
    {
        $starterKeys = implode(',', array_keys(ArchitectMethodStatementBlueprint::starters()));

        return $request->validate([
            'title' => 'required|string|max:255',
            'statement_type' => 'nullable|string|in:'.$starterKeys,
            'statement_number' => 'nullable|string|max:120',
            'issued_on' => 'required|date|before_or_equal:today',
            'commencement_note' => 'nullable|string|max:255',
            'client_name' => 'nullable|string|max:255',
            'client_address' => 'nullable|string|max:2000',
            'project_description' => 'nullable|string|max:5000',
            'site_address' => 'nullable|string|max:2000',
            'architect_project_id' => 'nullable|integer',
            'architect_pa_application_id' => 'nullable|integer',
            'payload' => 'nullable|array',
            'payload.appendix_ref' => 'nullable|string|max:1000',
            'payload.legal_footer' => 'nullable|string|max:5000',
            'payload.sections' => 'nullable|array|max:40',
            'payload.sections.*.heading' => 'nullable|string|max:500',
            'payload.sections.*.body' => 'nullable|string|max:20000',
            'photos' => 'nullable|array|max:12',
            'photos.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'photo_captions' => 'nullable|array',
            'photo_captions.*' => 'nullable|string|max:255',
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{architect_project_id: ?int, architect_pa_application_id: ?int}
     */
    private function resolveScope(int $userId, array $validated): array
    {
        $projectId = (int) ($validated['architect_project_id'] ?? 0);
        $paId = (int) ($validated['architect_pa_application_id'] ?? 0);

        $project = null;
        if ($projectId > 0) {
            $project = ArchitectProject::where('user_id', $userId)->where('id', $projectId)->first();
            if (! $project) {
                abort(403);
            }
        }

        $pa = null;
        if ($paId > 0) {
            $pa = ArchitectPaApplication::where('user_id', $userId)->where('id', $paId)->first();
            if (! $pa) {
                abort(403);
            }
            if ($project && $pa->architect_project_id !== $project->id) {
                abort(403);
            }
            if (! $project) {
                $project = ArchitectProject::where('user_id', $userId)->where('id', $pa->architect_project_id)->first();
            }
        }

        return [
            'architect_project_id' => $project?->id,
            'architect_pa_application_id' => $pa?->id,
        ];
    }

    private function storePhotos(Request $request, ArchitectMethodStatement $statement): void
    {
        if (! $request->hasFile('photos')) {
            return;
        }

        $captions = $request->input('photo_captions', []);
        $sort = (int) $statement->photos()->max('sort_order');

        foreach ($request->file('photos') as $i => $file) {
            if (! $file) {
                continue;
            }
            $sort++;
            $path = $file->store(
                TenantStorage::architectMethodStatementsPath($statement->user_id).'/ms_'.$statement->id,
                TenantStorage::diskName()
            );
            ArchitectMethodStatementPhoto::create([
                'user_id' => $statement->user_id,
                'architect_method_statement_id' => $statement->id,
                'file_path' => $path,
                'caption' => is_array($captions) ? trim((string) ($captions[$i] ?? '')) ?: null : null,
                'sort_order' => $sort,
            ]);
        }
    }

    private function assertOwned(ArchitectMethodStatement $statement): void
    {
        if ($statement->user_id !== Auth::id()) {
            abort(403);
        }
    }
}
