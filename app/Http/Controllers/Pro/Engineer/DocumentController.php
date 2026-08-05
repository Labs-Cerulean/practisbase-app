<?php

namespace App\Http\Controllers\Pro\Engineer;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\EngineerDocument;
use App\Models\EngineerDocumentRevision;
use App\Models\EngineerPaApplication;
use App\Models\EngineerProject;
use App\Support\TenantStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $q = trim((string) $request->query('q', ''));
        $scope = $request->query('scope');

        $documents = EngineerDocument::query()
            ->where('user_id', $user->id)
            ->with(['client', 'project', 'paApplication', 'revisions' => fn ($r) => $r->orderByDesc('revision_no')->limit(1)])
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.$q.'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('title', 'ilike', $like)
                        ->orWhere('doc_code', 'ilike', $like)
                        ->orWhere('doc_type', 'ilike', $like)
                        ->orWhere('notes', 'ilike', $like);
                });
            })
            ->when($scope === 'client', fn ($query) => $query->whereNotNull('client_id')->whereNull('engineer_project_id'))
            ->when($scope === 'project', fn ($query) => $query->whereNotNull('engineer_project_id')->whereNull('engineer_pa_application_id'))
            ->when($scope === 'pa', fn ($query) => $query->whereNotNull('engineer_pa_application_id'))
            ->orderByDesc('updated_at')
            ->limit(200)
            ->get();

        return view('pro.engineer.documents-index', [
            'documents' => $documents,
            'q' => $q,
            'scope' => $scope,
            'categories' => EngineerDocument::CATEGORIES,
            'statuses' => EngineerDocument::STATUSES,
        ]);
    }

    public function create(Request $request)
    {
        $user = Auth::user();

        return view('pro.engineer.documents-form', [
            'document' => null,
            'clients' => Client::where('user_id', $user->id)->orderBy('name')->get(),
            'projects' => EngineerProject::where('user_id', $user->id)->with('client')->orderBy('name')->get(),
            'pas' => EngineerPaApplication::where('user_id', $user->id)->with('project')->orderByDesc('updated_at')->get(),
            'categories' => EngineerDocument::CATEGORIES,
            'statuses' => EngineerDocument::STATUSES,
            'prefill' => [
                'client_id' => $request->query('client_id'),
                'project_id' => $request->query('project_id'),
                'pa_id' => $request->query('pa_id'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $validated = $this->validateMeta($request);
        $scope = $this->resolveScope($user->id, $validated);

        $request->validate([
            'file' => 'required|file|max:20480',
            'change_note' => 'nullable|string|max:500',
        ]);

        $document = EngineerDocument::create([
            'user_id' => $user->id,
            'client_id' => $scope['client_id'],
            'engineer_project_id' => $scope['engineer_project_id'],
            'engineer_pa_application_id' => $scope['engineer_pa_application_id'],
            'title' => $validated['title'],
            'doc_type' => $validated['doc_type'],
            'category' => $validated['category'],
            'status' => $validated['status'],
            'doc_code' => $validated['doc_code'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'current_revision' => 0,
        ]);

        $this->storeRevision($request, $document, 1, $request->input('change_note'));

        return redirect($this->redirectFor($document))
            ->with('success', 'Document uploaded as Rev 1.');
    }

    public function show(EngineerDocument $document)
    {
        $this->assertOwned($document);
        $document->load(['client', 'project.client', 'paApplication', 'revisions']);

        return view('pro.engineer.documents-show', [
            'document' => $document,
            'categories' => EngineerDocument::CATEGORIES,
            'statuses' => EngineerDocument::STATUSES,
        ]);
    }

    public function update(Request $request, EngineerDocument $document)
    {
        $this->assertOwned($document);
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'doc_type' => 'required|string|max:80',
            'category' => 'required|in:'.implode(',', array_keys(EngineerDocument::CATEGORIES)),
            'status' => 'required|in:'.implode(',', array_keys(EngineerDocument::STATUSES)),
            'doc_code' => 'nullable|string|max:80',
            'notes' => 'nullable|string|max:5000',
        ]);
        $document->update($validated);

        return redirect('/pro/engineer/documents/'.$document->id)
            ->with('success', 'Document details updated.');
    }

    public function uploadRevision(Request $request, EngineerDocument $document)
    {
        $this->assertOwned($document);
        $request->validate([
            'file' => 'required|file|max:20480',
            'change_note' => 'nullable|string|max:500',
            'status' => 'nullable|in:'.implode(',', array_keys(EngineerDocument::STATUSES)),
        ]);

        $next = ((int) $document->current_revision) + 1;
        $this->storeRevision($request, $document, $next, $request->input('change_note'));
        if ($request->filled('status')) {
            $document->status = $request->input('status');
            $document->save();
        }

        return redirect('/pro/engineer/documents/'.$document->id)
            ->with('success', 'Revision '.$next.' uploaded.');
    }

    public function download(EngineerDocument $document, EngineerDocumentRevision $revision): StreamedResponse
    {
        $this->assertOwned($document);
        if ($revision->engineer_document_id !== $document->id || $revision->user_id !== Auth::id()) {
            abort(403);
        }

        $disk = TenantStorage::disk();
        if (! $disk->exists($revision->file_path)) {
            abort(404);
        }

        return $disk->download($revision->file_path, $revision->original_name);
    }

    private function storeRevision(Request $request, EngineerDocument $document, int $revNo, ?string $changeNote): void
    {
        $file = $request->file('file');
        $ext = $file->getClientOriginalExtension() ?: 'bin';
        $path = TenantStorage::engineerDocumentsPath($document->user_id)
            .'/doc_'.$document->id
            .'/rev_'.$revNo.'_'.Str::random(8).'.'.$ext;

        TenantStorage::disk()->putFileAs(
            dirname($path),
            $file,
            basename($path)
        );

        EngineerDocumentRevision::create([
            'user_id' => $document->user_id,
            'engineer_document_id' => $document->id,
            'revision_no' => $revNo,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => $file->getSize(),
            'change_note' => $changeNote,
            'uploaded_by_user_id' => Auth::id(),
        ]);

        $document->current_revision = $revNo;
        $document->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function validateMeta(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'doc_type' => 'required|string|max:80',
            'category' => 'required|in:'.implode(',', array_keys(EngineerDocument::CATEGORIES)),
            'status' => 'required|in:'.implode(',', array_keys(EngineerDocument::STATUSES)),
            'doc_code' => 'nullable|string|max:80',
            'notes' => 'nullable|string|max:5000',
            'scope_level' => 'required|in:client,project,pa',
            'client_id' => 'nullable|integer',
            'engineer_project_id' => 'nullable|integer',
            'engineer_pa_application_id' => 'nullable|integer',
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{client_id: ?int, engineer_project_id: ?int, engineer_pa_application_id: ?int}
     */
    private function resolveScope(int $userId, array $validated): array
    {
        $level = $validated['scope_level'];

        if ($level === 'client') {
            $clientId = (int) ($validated['client_id'] ?? 0);
            $client = Client::where('user_id', $userId)->where('id', $clientId)->firstOrFail();

            return [
                'client_id' => $client->id,
                'engineer_project_id' => null,
                'engineer_pa_application_id' => null,
            ];
        }

        if ($level === 'project') {
            $projectId = (int) ($validated['engineer_project_id'] ?? 0);
            $project = EngineerProject::where('user_id', $userId)->where('id', $projectId)->firstOrFail();

            return [
                'client_id' => $project->client_id,
                'engineer_project_id' => $project->id,
                'engineer_pa_application_id' => null,
            ];
        }

        $paId = (int) ($validated['engineer_pa_application_id'] ?? 0);
        $pa = EngineerPaApplication::where('user_id', $userId)->where('id', $paId)->with('project')->firstOrFail();

        return [
            'client_id' => $pa->project?->client_id,
            'engineer_project_id' => $pa->engineer_project_id,
            'engineer_pa_application_id' => $pa->id,
        ];
    }

    private function redirectFor(EngineerDocument $document): string
    {
        if ($document->engineer_pa_application_id) {
            return '/pro/engineer/pa/'.$document->engineer_pa_application_id;
        }
        if ($document->engineer_project_id) {
            return '/pro/engineer/projects/'.$document->engineer_project_id;
        }

        return '/clients/'.$document->client_id;
    }

    private function assertOwned(EngineerDocument $document): void
    {
        if ($document->user_id !== Auth::id()) {
            abort(403);
        }
    }
}
