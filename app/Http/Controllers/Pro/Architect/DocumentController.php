<?php

namespace App\Http\Controllers\Pro\Architect;

use App\Http\Controllers\Controller;
use App\Models\ArchitectDocument;
use App\Models\ArchitectDocumentRevision;
use App\Models\ArchitectPaApplication;
use App\Models\ArchitectProject;
use App\Models\Client;
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

        $documents = ArchitectDocument::query()
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
            ->when($scope === 'client', fn ($q) => $q->whereNotNull('client_id')->whereNull('architect_project_id'))
            ->when($scope === 'project', fn ($q) => $q->whereNotNull('architect_project_id')->whereNull('architect_pa_application_id'))
            ->when($scope === 'pa', fn ($q) => $q->whereNotNull('architect_pa_application_id'))
            ->orderByDesc('updated_at')
            ->limit(200)
            ->get();

        return view('pro.architect.documents-index', [
            'documents' => $documents,
            'q' => $q,
            'scope' => $scope,
            'categories' => ArchitectDocument::CATEGORIES,
            'statuses' => ArchitectDocument::STATUSES,
        ]);
    }

    public function create(Request $request)
    {
        $user = Auth::user();

        return view('pro.architect.documents-form', [
            'document' => null,
            'clients' => Client::where('user_id', $user->id)->orderBy('name')->get(),
            'projects' => ArchitectProject::where('user_id', $user->id)->with('client')->orderBy('name')->get(),
            'pas' => ArchitectPaApplication::where('user_id', $user->id)->orderBy('pa_number')->get(),
            'categories' => ArchitectDocument::CATEGORIES,
            'statuses' => ArchitectDocument::STATUSES,
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

        $document = ArchitectDocument::create([
            'user_id' => $user->id,
            'client_id' => $scope['client_id'],
            'architect_project_id' => $scope['architect_project_id'],
            'architect_pa_application_id' => $scope['architect_pa_application_id'],
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

    public function show(ArchitectDocument $document)
    {
        $this->assertOwned($document);
        $document->load(['client', 'project.client', 'paApplication', 'revisions']);

        return view('pro.architect.documents-show', [
            'document' => $document,
            'categories' => ArchitectDocument::CATEGORIES,
            'statuses' => ArchitectDocument::STATUSES,
        ]);
    }

    public function update(Request $request, ArchitectDocument $document)
    {
        $this->assertOwned($document);
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'doc_type' => 'required|string|max:80',
            'category' => 'required|in:'.implode(',', array_keys(ArchitectDocument::CATEGORIES)),
            'status' => 'required|in:'.implode(',', array_keys(ArchitectDocument::STATUSES)),
            'doc_code' => 'nullable|string|max:80',
            'notes' => 'nullable|string|max:5000',
        ]);
        $document->update($validated);

        return redirect('/pro/architect/documents/'.$document->id)
            ->with('success', 'Document details updated.');
    }

    public function uploadRevision(Request $request, ArchitectDocument $document)
    {
        $this->assertOwned($document);
        $request->validate([
            'file' => 'required|file|max:20480',
            'change_note' => 'nullable|string|max:500',
            'status' => 'nullable|in:'.implode(',', array_keys(ArchitectDocument::STATUSES)),
        ]);

        $next = ((int) $document->current_revision) + 1;
        $this->storeRevision($request, $document, $next, $request->input('change_note'));
        if ($request->filled('status')) {
            $document->status = $request->input('status');
            $document->save();
        }

        return redirect('/pro/architect/documents/'.$document->id)
            ->with('success', 'Revision '.$next.' uploaded.');
    }

    public function download(ArchitectDocument $document, ArchitectDocumentRevision $revision): StreamedResponse
    {
        $this->assertOwned($document);
        if ($revision->architect_document_id !== $document->id || $revision->user_id !== Auth::id()) {
            abort(403);
        }

        $disk = TenantStorage::disk();
        if (! $disk->exists($revision->file_path)) {
            abort(404);
        }

        return $disk->download($revision->file_path, $revision->original_name);
    }

    private function storeRevision(Request $request, ArchitectDocument $document, int $revNo, ?string $changeNote): void
    {
        $file = $request->file('file');
        $ext = $file->getClientOriginalExtension() ?: 'bin';
        $path = TenantStorage::architectDocumentsPath($document->user_id)
            .'/doc_'.$document->id
            .'/rev_'.$revNo.'_'.Str::random(8).'.'.$ext;

        TenantStorage::disk()->putFileAs(
            dirname($path),
            $file,
            basename($path)
        );

        ArchitectDocumentRevision::create([
            'user_id' => $document->user_id,
            'architect_document_id' => $document->id,
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

    private function validateMeta(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'doc_type' => 'required|string|max:80',
            'category' => 'required|in:'.implode(',', array_keys(ArchitectDocument::CATEGORIES)),
            'status' => 'required|in:'.implode(',', array_keys(ArchitectDocument::STATUSES)),
            'doc_code' => 'nullable|string|max:80',
            'notes' => 'nullable|string|max:5000',
            'scope_level' => 'required|in:client,project,pa',
            'client_id' => 'nullable|integer',
            'architect_project_id' => 'nullable|integer',
            'architect_pa_application_id' => 'nullable|integer',
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{client_id: ?int, architect_project_id: ?int, architect_pa_application_id: ?int}
     */
    private function resolveScope(int $userId, array $validated): array
    {
        $level = $validated['scope_level'];

        if ($level === 'client') {
            $clientId = (int) ($validated['client_id'] ?? 0);
            $client = Client::where('user_id', $userId)->where('id', $clientId)->firstOrFail();

            return [
                'client_id' => $client->id,
                'architect_project_id' => null,
                'architect_pa_application_id' => null,
            ];
        }

        if ($level === 'project') {
            $projectId = (int) ($validated['architect_project_id'] ?? 0);
            $project = ArchitectProject::where('user_id', $userId)->where('id', $projectId)->firstOrFail();

            return [
                'client_id' => $project->client_id,
                'architect_project_id' => $project->id,
                'architect_pa_application_id' => null,
            ];
        }

        $paId = (int) ($validated['architect_pa_application_id'] ?? 0);
        $pa = ArchitectPaApplication::where('user_id', $userId)->where('id', $paId)->with('project')->firstOrFail();

        return [
            'client_id' => $pa->project?->client_id,
            'architect_project_id' => $pa->architect_project_id,
            'architect_pa_application_id' => $pa->id,
        ];
    }

    private function redirectFor(ArchitectDocument $document): string
    {
        if ($document->architect_pa_application_id) {
            return '/pro/architect/pa/'.$document->architect_pa_application_id;
        }
        if ($document->architect_project_id) {
            return '/pro/architect/projects/'.$document->architect_project_id;
        }

        return '/clients/'.$document->client_id;
    }

    private function assertOwned(ArchitectDocument $document): void
    {
        if ($document->user_id !== Auth::id()) {
            abort(403);
        }
    }
}
