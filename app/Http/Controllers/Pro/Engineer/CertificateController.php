<?php

namespace App\Http\Controllers\Pro\Engineer;

use App\Http\Controllers\Controller;
use App\Models\EngineerCertificate;
use App\Models\EngineerCertificatePhoto;
use App\Models\EngineerPaApplication;
use App\Models\EngineerProject;
use App\Support\EngineerCertificateBlueprint;
use App\Support\IssueCode;
use App\Support\TenantStorage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CertificateController extends Controller
{
    public function index(Request $request)
    {
        $projectId = (int) $request->query('project_id');

        $certs = EngineerCertificate::where('user_id', Auth::id())
            ->with(['project', 'paApplication'])
            ->when($projectId > 0, fn ($q) => $q->where('engineer_project_id', $projectId))
            ->orderByDesc('id')
            ->get();

        return view('pro.engineer.certificates-index', [
            'certs' => $certs,
            'projectId' => $projectId ?: null,
        ]);
    }

    public function create(Request $request)
    {
        $starterKey = (string) $request->query('starter', 'blank');
        $starters = EngineerCertificateBlueprint::starters();
        if (! isset($starters[$starterKey])) {
            $starterKey = 'blank';
        }
        $starter = $starters[$starterKey];

        return view('pro.engineer.certificates-form', [
            'certificate' => null,
            'projects' => EngineerProject::where('user_id', Auth::id())->with('client')->orderBy('name')->get(),
            'pas' => EngineerPaApplication::where('user_id', Auth::id())->with('project')->orderByDesc('updated_at')->get(),
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
        $validated = $this->validateCertificate($request);
        $scope = $this->resolveScope(Auth::id(), $validated);

        $certificate = EngineerCertificate::create([
            'user_id' => Auth::id(),
            'engineer_project_id' => $scope['engineer_project_id'],
            'engineer_pa_application_id' => $scope['engineer_pa_application_id'],
            'title' => $validated['title'],
            'certificate_number' => $validated['certificate_number'] ?? null,
            'inspected_on' => $validated['inspected_on'] ?? null,
            'issued_on' => $validated['issued_on'],
            'expires_on' => $validated['expires_on'] ?? null,
            'next_inspection_on' => $validated['next_inspection_on'] ?? null,
            'outcome' => $validated['outcome'] ?? null,
            'holder_name' => $validated['holder_name'] ?? null,
            'holder_address' => $validated['holder_address'] ?? null,
            'contact_person' => $validated['contact_person'] ?? null,
            'contact_phone' => $validated['contact_phone'] ?? null,
            'site_address' => $validated['site_address'] ?? null,
            'payload' => EngineerCertificateBlueprint::normalize($validated['payload'] ?? []),
        ]);

        $this->storePhotos($request, $certificate);

        return redirect('/pro/engineer/certificates/'.$certificate->id)
            ->with('success', 'Draft certificate saved. Edit until you Stamp & issue.');
    }

    public function show(EngineerCertificate $certificate)
    {
        $this->assertOwned($certificate);
        $certificate->load(['project.client', 'paApplication', 'photos']);

        return view('pro.engineer.certificates-show', [
            'certificate' => $certificate,
            'payload' => $certificate->normalizedPayload(),
        ]);
    }

    public function edit(EngineerCertificate $certificate)
    {
        $this->assertOwned($certificate);
        if (! $certificate->isEditable()) {
            return redirect('/pro/engineer/certificates/'.$certificate->id)
                ->withErrors(['certificate' => 'This certificate was stamped and issued. It can no longer be edited.']);
        }

        return view('pro.engineer.certificates-form', [
            'certificate' => $certificate,
            'projects' => EngineerProject::where('user_id', Auth::id())->with('client')->orderBy('name')->get(),
            'pas' => EngineerPaApplication::where('user_id', Auth::id())->with('project')->orderByDesc('updated_at')->get(),
            'payload' => $certificate->normalizedPayload(),
            'starters' => EngineerCertificateBlueprint::starters(),
            'starterKey' => null,
            'defaultTitle' => $certificate->title,
            'prefill' => [
                'project_id' => $certificate->engineer_project_id,
                'pa_id' => $certificate->engineer_pa_application_id,
            ],
        ]);
    }

    public function update(Request $request, EngineerCertificate $certificate)
    {
        $this->assertOwned($certificate);
        if (! $certificate->isEditable()) {
            return redirect('/pro/engineer/certificates/'.$certificate->id)
                ->withErrors(['certificate' => 'This certificate was stamped and issued. It can no longer be edited.']);
        }

        $validated = $this->validateCertificate($request);
        $scope = $this->resolveScope(Auth::id(), $validated);

        $certificate->update([
            'engineer_project_id' => $scope['engineer_project_id'],
            'engineer_pa_application_id' => $scope['engineer_pa_application_id'],
            'title' => $validated['title'],
            'certificate_number' => $validated['certificate_number'] ?? null,
            'inspected_on' => $validated['inspected_on'] ?? null,
            'issued_on' => $validated['issued_on'],
            'expires_on' => $validated['expires_on'] ?? null,
            'next_inspection_on' => $validated['next_inspection_on'] ?? null,
            'outcome' => $validated['outcome'] ?? null,
            'holder_name' => $validated['holder_name'] ?? null,
            'holder_address' => $validated['holder_address'] ?? null,
            'contact_person' => $validated['contact_person'] ?? null,
            'contact_phone' => $validated['contact_phone'] ?? null,
            'site_address' => $validated['site_address'] ?? null,
            'payload' => EngineerCertificateBlueprint::normalize($validated['payload'] ?? []),
        ]);

        $this->storePhotos($request, $certificate);

        return redirect('/pro/engineer/certificates/'.$certificate->id)
            ->with('success', 'Certificate updated.');
    }

    public function stamp(EngineerCertificate $certificate)
    {
        $this->assertOwned($certificate);
        if ($certificate->isStamped()) {
            return back()->withErrors(['certificate' => 'Already stamped and issued.']);
        }

        $certificate->stamped_at = now();
        $certificate->issue_code = IssueCode::allocateForEngineerCertificate();
        $certificate->save();

        return redirect('/pro/engineer/certificates/'.$certificate->id)
            ->with('success', 'Certificate stamped and issued as '.$certificate->issue_code.'. It is now locked.');
    }

    public function downloadPdf(EngineerCertificate $certificate)
    {
        $this->assertOwned($certificate);
        if (! $certificate->isStamped()) {
            return redirect('/pro/engineer/certificates/'.$certificate->id)
                ->withErrors(['certificate' => 'Stamp & issue before downloading the PDF.']);
        }

        $user = Auth::user();
        $certificate->load(['photos', 'project.client', 'paApplication']);

        $pdf = Pdf::loadView('pro.engineer.certificates-pdf', [
            'certificate' => $certificate,
            'payload' => $certificate->normalizedPayload(),
            'user' => $user,
        ])->setPaper('a4');

        $safe = preg_replace('/[^A-Za-z0-9\-_]+/', '-', $certificate->title) ?: 'certificate';

        return $pdf->download($safe.'-'.$certificate->issue_code.'.pdf');
    }

    public function downloadPhoto(EngineerCertificate $certificate, EngineerCertificatePhoto $photo)
    {
        $this->assertOwned($certificate);
        if ($photo->engineer_certificate_id !== $certificate->id || $photo->user_id !== Auth::id()) {
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
    private function validateCertificate(Request $request): array
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'certificate_number' => 'nullable|string|max:120',
            'inspected_on' => 'nullable|date|before_or_equal:today',
            'issued_on' => 'required|date|before_or_equal:today',
            'expires_on' => 'nullable|date|after_or_equal:issued_on',
            'next_inspection_on' => 'nullable|date',
            'outcome' => 'nullable|string|max:120',
            'holder_name' => 'nullable|string|max:255',
            'holder_address' => 'nullable|string|max:2000',
            'contact_person' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:64',
            'site_address' => 'nullable|string|max:2000',
            'engineer_project_id' => 'nullable|integer',
            'engineer_pa_application_id' => 'nullable|integer',
            'payload' => 'nullable|array',
            'payload.subject_heading' => 'nullable|string|max:255',
            'payload.highlight_label' => 'nullable|string|max:255',
            'payload.highlight_value' => 'nullable|string|max:255',
            'payload.checklist_heading' => 'nullable|string|max:255',
            'payload.legal_footer' => 'nullable|string|max:5000',
            'payload.attributes' => 'nullable|array|max:40',
            'payload.attributes.*.label' => 'nullable|string|max:255',
            'payload.attributes.*.value' => 'nullable|string|max:2000',
            'payload.checklist' => 'nullable|array|max:60',
            'payload.checklist.*.item' => 'nullable|string|max:500',
            'payload.checklist.*.outcome' => 'nullable|string|max:120',
            'payload.checklist.*.comments' => 'nullable|string|max:1000',
            'payload.sections' => 'nullable|array|max:20',
            'payload.sections.*.heading' => 'nullable|string|max:255',
            'payload.sections.*.body' => 'nullable|string|max:10000',
            'photos' => 'nullable|array|max:12',
            'photos.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'photo_captions' => 'nullable|array',
            'photo_captions.*' => 'nullable|string|max:255',
        ]);

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{engineer_project_id: ?int, engineer_pa_application_id: ?int}
     */
    private function resolveScope(int $userId, array $validated): array
    {
        $projectId = (int) ($validated['engineer_project_id'] ?? 0);
        $paId = (int) ($validated['engineer_pa_application_id'] ?? 0);

        $project = null;
        if ($projectId > 0) {
            $project = EngineerProject::where('user_id', $userId)->where('id', $projectId)->first();
            if (! $project) {
                abort(403);
            }
        }

        $pa = null;
        if ($paId > 0) {
            $pa = EngineerPaApplication::where('user_id', $userId)->where('id', $paId)->first();
            if (! $pa) {
                abort(403);
            }
            if ($project && $pa->engineer_project_id !== $project->id) {
                abort(403);
            }
            if (! $project) {
                $project = EngineerProject::where('user_id', $userId)->where('id', $pa->engineer_project_id)->first();
            }
        }

        return [
            'engineer_project_id' => $project?->id,
            'engineer_pa_application_id' => $pa?->id,
        ];
    }

    private function storePhotos(Request $request, EngineerCertificate $certificate): void
    {
        if (! $request->hasFile('photos')) {
            return;
        }

        $captions = $request->input('photo_captions', []);
        $sort = (int) $certificate->photos()->max('sort_order');

        foreach ($request->file('photos') as $i => $file) {
            if (! $file) {
                continue;
            }
            $sort++;
            $path = $file->store(
                TenantStorage::engineerCertificatesPath($certificate->user_id).'/cert_'.$certificate->id,
                TenantStorage::diskName()
            );
            EngineerCertificatePhoto::create([
                'user_id' => $certificate->user_id,
                'engineer_certificate_id' => $certificate->id,
                'file_path' => $path,
                'caption' => is_array($captions) ? trim((string) ($captions[$i] ?? '')) ?: null : null,
                'sort_order' => $sort,
            ]);
        }
    }

    private function assertOwned(EngineerCertificate $certificate): void
    {
        if ($certificate->user_id !== Auth::id()) {
            abort(403);
        }
    }
}
