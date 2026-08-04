<?php

namespace App\Http\Controllers\Pro\Engineer;

use App\Http\Controllers\Controller;
use App\Models\EngineerPaApplication;
use App\Models\EngineerProject;
use App\Models\EngineerReport;
use App\Models\EngineerReportPhoto;
use App\Support\EngineerReportBlueprint;
use App\Support\IssueCode;
use App\Support\PracticeDocumentContext;
use App\Support\TenantStorage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $projectId = (int) $request->query('project_id');

        $reports = EngineerReport::where('user_id', Auth::id())
            ->with(['project', 'paApplication'])
            ->when($projectId > 0, fn ($q) => $q->where('engineer_project_id', $projectId))
            ->orderByDesc('id')
            ->get();

        return view('pro.engineer.reports-index', [
            'reports' => $reports,
            'projectId' => $projectId ?: null,
        ]);
    }

    public function create(Request $request)
    {
        $projects = EngineerProject::where('user_id', Auth::id())
            ->where('status', '!=', 'archived')
            ->with('client')
            ->orderBy('name')
            ->get();

        $projectId = (int) $request->query('project_id');
        if ($projectId <= 0 || ! $projects->contains('id', $projectId)) {
            return view('pro.shared.pick-project', [
                'projects' => $projects,
                'heading' => 'Choose a project for this specialised report',
                'lead' => 'Specialised reports sit under a project so client, premises, contact, and filing refs auto-fill.',
                'backHref' => '/pro/engineer/reports',
                'createProjectHref' => '/pro/engineer/projects/create',
                'continueBase' => '/pro/engineer/reports/create?starter='.urlencode((string) $request->query('starter', 'blank')),
                'accent' => 'var(--primary-cerulean)',
            ]);
        }

        $project = $projects->firstWhere('id', $projectId);
        $starterKey = (string) $request->query('starter', 'blank');
        $starters = EngineerReportBlueprint::starters();
        if (! isset($starters[$starterKey])) {
            $starterKey = 'blank';
        }
        $starter = $starters[$starterKey];
        $paId = (int) $request->query('pa_id');
        $pas = EngineerPaApplication::where('user_id', Auth::id())
            ->where('engineer_project_id', $projectId)
            ->orderByDesc('updated_at')
            ->get();
        $pa = $paId > 0 ? $pas->firstWhere('id', $paId) : null;
        $context = PracticeDocumentContext::engineerPrefill($project, $pa, 'ER');
        $payload = $this->prefillPermissionAttributes($starter['payload'], $context);

        return view('pro.engineer.reports-form', [
            'report' => null,
            'projects' => $projects,
            'pas' => $pas,
            'payload' => $payload,
            'starters' => $starters,
            'starterKey' => $starterKey,
            'defaultTitle' => $starter['title'],
            'prefill' => [
                'project_id' => $projectId,
                'pa_id' => $pa?->id,
            ],
            'context' => $context,
            'projectOptions' => PracticeDocumentContext::projectOptionsPayload($projects, 'eng', 'ER'),
            'commonChecklistItems' => EngineerReportBlueprint::commonChecklistItems($starterKey),
            'photoLinkOptions' => EngineerReportBlueprint::photoLinkOptions($payload),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateReport($request);
        $scope = $this->resolveScope(Auth::id(), $validated);
        $project = EngineerProject::where('user_id', Auth::id())
            ->where('id', $scope['engineer_project_id'])
            ->firstOrFail();
        $reportNumber = trim((string) ($validated['report_number'] ?? ''));
        if ($reportNumber === '') {
            $reportNumber = PracticeDocumentContext::nextEngineerReportRef(Auth::id(), $project);
        }

        $report = EngineerReport::create([
            'user_id' => Auth::id(),
            'engineer_project_id' => $scope['engineer_project_id'],
            'engineer_pa_application_id' => $scope['engineer_pa_application_id'],
            'title' => $validated['title'],
            'report_type' => $validated['report_type'] ?? null,
            'report_number' => $reportNumber,
            'surveyed_on' => $validated['surveyed_on'] ?? null,
            'issued_on' => $validated['issued_on'],
            'conclusion' => $validated['conclusion'] ?? null,
            'client_name' => $validated['client_name'] ?? null,
            'client_address' => $validated['client_address'] ?? null,
            'contact_person' => $validated['contact_person'] ?? null,
            'contact_phone' => $validated['contact_phone'] ?? null,
            'site_address' => $validated['site_address'] ?? null,
            'payload' => EngineerReportBlueprint::normalize($validated['payload'] ?? []),
        ]);

        $this->storePhotos($request, $report);

        return redirect('/pro/engineer/reports/'.$report->id)
            ->with('success', 'Draft report saved. Edit until you Stamp & issue.');
    }

    public function show(EngineerReport $report)
    {
        $this->assertOwned($report);
        $report->load(['project.client', 'paApplication', 'photos']);

        return view('pro.engineer.reports-show', [
            'report' => $report,
            'payload' => $report->normalizedPayload(),
        ]);
    }

    public function edit(EngineerReport $report)
    {
        $this->assertOwned($report);
        if (! $report->isEditable()) {
            return redirect('/pro/engineer/reports/'.$report->id)
                ->withErrors(['report' => 'This report was stamped and issued. It can no longer be edited.']);
        }

        $projects = EngineerProject::where('user_id', Auth::id())
            ->where(function ($q) use ($report) {
                $q->where('status', '!=', 'archived')
                    ->orWhere('id', $report->engineer_project_id);
            })
            ->with('client')
            ->orderBy('name')
            ->get();
        $pas = EngineerPaApplication::where('user_id', Auth::id())
            ->where('engineer_project_id', $report->engineer_project_id)
            ->orderByDesc('updated_at')
            ->get();
        $payload = $report->normalizedPayload();

        return view('pro.engineer.reports-form', [
            'report' => $report,
            'projects' => $projects,
            'pas' => $pas,
            'payload' => $payload,
            'starters' => EngineerReportBlueprint::starters(),
            'starterKey' => $report->report_type,
            'defaultTitle' => $report->title,
            'prefill' => [
                'project_id' => $report->engineer_project_id,
                'pa_id' => $report->engineer_pa_application_id,
            ],
            'context' => [
                'client_name' => $report->client_name,
                'client_address' => $report->client_address,
                'contact_person' => $report->contact_person,
                'contact_phone' => $report->contact_phone,
                'site_address' => $report->site_address,
                'suggested_ref' => $report->report_number,
                'pa_number' => $report->paApplication?->pa_number ?? '',
            ],
            'projectOptions' => PracticeDocumentContext::projectOptionsPayload($projects, 'eng', 'ER'),
            'commonChecklistItems' => EngineerReportBlueprint::commonChecklistItems((string) $report->report_type),
            'photoLinkOptions' => EngineerReportBlueprint::photoLinkOptions($payload),
        ]);
    }

    public function update(Request $request, EngineerReport $report)
    {
        $this->assertOwned($report);
        if (! $report->isEditable()) {
            return redirect('/pro/engineer/reports/'.$report->id)
                ->withErrors(['report' => 'This report was stamped and issued. It can no longer be edited.']);
        }

        $validated = $this->validateReport($request);
        $scope = $this->resolveScope(Auth::id(), $validated);
        $project = EngineerProject::where('user_id', Auth::id())
            ->where('id', $scope['engineer_project_id'])
            ->firstOrFail();
        $reportNumber = trim((string) ($validated['report_number'] ?? ''));
        if ($reportNumber === '') {
            $reportNumber = $report->report_number
                ?: PracticeDocumentContext::nextEngineerReportRef(Auth::id(), $project);
        }

        $report->update([
            'engineer_project_id' => $scope['engineer_project_id'],
            'engineer_pa_application_id' => $scope['engineer_pa_application_id'],
            'title' => $validated['title'],
            'report_type' => $validated['report_type'] ?? $report->report_type,
            'report_number' => $reportNumber,
            'surveyed_on' => $validated['surveyed_on'] ?? null,
            'issued_on' => $validated['issued_on'],
            'conclusion' => $validated['conclusion'] ?? null,
            'client_name' => $validated['client_name'] ?? null,
            'client_address' => $validated['client_address'] ?? null,
            'contact_person' => $validated['contact_person'] ?? null,
            'contact_phone' => $validated['contact_phone'] ?? null,
            'site_address' => $validated['site_address'] ?? null,
            'payload' => EngineerReportBlueprint::normalize($validated['payload'] ?? []),
        ]);

        $this->storePhotos($request, $report);

        return redirect('/pro/engineer/reports/'.$report->id)
            ->with('success', 'Report updated.');
    }

    public function stamp(EngineerReport $report)
    {
        $this->assertOwned($report);
        if ($report->isStamped()) {
            return back()->withErrors(['report' => 'Already stamped and issued.']);
        }
        if (! $report->engineer_project_id) {
            return back()->withErrors(['report' => 'Link this report to a project before stamping.']);
        }

        $report->stamped_at = now();
        $report->issue_code = IssueCode::allocateForEngineerReport();
        $report->save();

        return redirect('/pro/engineer/reports/'.$report->id)
            ->with('success', 'Report stamped and issued as '.$report->issue_code.'. It is now locked.');
    }

    public function downloadPdf(EngineerReport $report)
    {
        $this->assertOwned($report);
        if (! $report->isStamped()) {
            return redirect('/pro/engineer/reports/'.$report->id)
                ->withErrors(['report' => 'Stamp & issue before downloading the PDF.']);
        }

        $user = Auth::user();
        $report->load(['photos', 'project.client', 'paApplication']);

        $pdf = Pdf::loadView('pro.engineer.reports-pdf', [
            'report' => $report,
            'payload' => $report->normalizedPayload(),
            'user' => $user,
        ])->setPaper('a4');

        $safe = preg_replace('/[^A-Za-z0-9\-_]+/', '-', $report->title) ?: 'report';

        return $pdf->download($safe.'-'.$report->issue_code.'.pdf');
    }

    public function downloadPhoto(EngineerReport $report, EngineerReportPhoto $photo)
    {
        $this->assertOwned($report);
        if ($photo->engineer_report_id !== $report->id || $photo->user_id !== Auth::id()) {
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
    private function validateReport(Request $request): array
    {
        $starterKeys = implode(',', array_keys(EngineerReportBlueprint::starters()));

        return $request->validate([
            'title' => 'required|string|max:255',
            'report_type' => 'nullable|string|in:'.$starterKeys,
            'report_number' => 'nullable|string|max:120',
            'surveyed_on' => 'nullable|date|before_or_equal:today',
            'issued_on' => 'required|date|before_or_equal:today',
            'conclusion' => 'nullable|string|max:120',
            'client_name' => 'nullable|string|max:255',
            'client_address' => 'nullable|string|max:2000',
            'contact_person' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:64',
            'site_address' => 'nullable|string|max:2000',
            'engineer_project_id' => 'required|integer',
            'engineer_pa_application_id' => 'nullable|integer',
            'payload' => 'nullable|array',
            'payload.subject_heading' => 'nullable|string|max:255',
            'payload.highlight_label' => 'nullable|string|max:255',
            'payload.highlight_value' => 'nullable|string|max:255',
            'payload.checklist_heading' => 'nullable|string|max:255',
            'payload.measurements_heading' => 'nullable|string|max:255',
            'payload.legal_footer' => 'nullable|string|max:5000',
            'payload.attributes' => 'nullable|array|max:40',
            'payload.attributes.*.label' => 'nullable|string|max:255',
            'payload.attributes.*.value' => 'nullable|string|max:2000',
            'payload.checklist' => 'nullable|array|max:60',
            'payload.checklist.*.id' => 'nullable|string|max:32',
            'payload.checklist.*.item' => 'nullable|string|max:500',
            'payload.checklist.*.outcome' => 'nullable|string|max:120',
            'payload.checklist.*.comments' => 'nullable|string|max:1000',
            'payload.measurements' => 'nullable|array|max:80',
            'payload.measurements.*.id' => 'nullable|string|max:32',
            'payload.measurements.*.location' => 'nullable|string|max:255',
            'payload.measurements.*.parameter' => 'nullable|string|max:255',
            'payload.measurements.*.reading' => 'nullable|string|max:120',
            'payload.measurements.*.unit' => 'nullable|string|max:64',
            'payload.measurements.*.limit' => 'nullable|string|max:120',
            'payload.measurements.*.result' => 'nullable|string|max:120',
            'payload.sections' => 'nullable|array|max:20',
            'payload.sections.*.heading' => 'nullable|string|max:255',
            'payload.sections.*.body' => 'nullable|string|max:15000',
            'photos' => 'nullable|array|max:12',
            'photos.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'photo_captions' => 'nullable|array',
            'photo_captions.*' => 'nullable|string|max:255',
            'photo_linked_row_ids' => 'nullable|array',
            'photo_linked_row_ids.*' => 'nullable|string|max:64',
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{engineer_project_id: int, engineer_pa_application_id: ?int}
     */
    private function resolveScope(int $userId, array $validated): array
    {
        $projectId = (int) ($validated['engineer_project_id'] ?? 0);
        $paId = (int) ($validated['engineer_pa_application_id'] ?? 0);

        $project = EngineerProject::where('user_id', $userId)->where('id', $projectId)->first();
        if (! $project) {
            abort(403);
        }

        $pa = null;
        if ($paId > 0) {
            $pa = EngineerPaApplication::where('user_id', $userId)->where('id', $paId)->first();
            if (! $pa) {
                abort(403);
            }
            if ($pa->engineer_project_id !== $project->id) {
                abort(403);
            }
        }

        return [
            'engineer_project_id' => $project->id,
            'engineer_pa_application_id' => $pa?->id,
        ];
    }

    private function storePhotos(Request $request, EngineerReport $report): void
    {
        if (! $request->hasFile('photos')) {
            return;
        }

        $captions = $request->input('photo_captions', []);
        $linked = $request->input('photo_linked_row_ids', []);
        $sort = (int) $report->photos()->max('sort_order');

        foreach ($request->file('photos') as $i => $file) {
            if (! $file) {
                continue;
            }
            $sort++;
            $path = $file->store(
                TenantStorage::engineerReportsPath($report->user_id).'/report_'.$report->id,
                TenantStorage::diskName()
            );
            EngineerReportPhoto::create([
                'user_id' => $report->user_id,
                'engineer_report_id' => $report->id,
                'file_path' => $path,
                'caption' => is_array($captions) ? trim((string) ($captions[$i] ?? '')) ?: null : null,
                'linked_row_id' => is_array($linked) ? trim((string) ($linked[$i] ?? '')) ?: null : null,
                'sort_order' => $sort,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function prefillPermissionAttributes(array $payload, array $context): array
    {
        foreach (($payload['attributes'] ?? []) as $i => $row) {
            $label = strtolower(trim((string) ($row['label'] ?? '')));
            if ($label === 'pa / permission reference' && trim((string) ($row['value'] ?? '')) === '') {
                $payload['attributes'][$i]['value'] = (string) ($context['pa_number'] ?? '');
            }
            if ($label === 'full address of premises' && trim((string) ($row['value'] ?? '')) === '') {
                $payload['attributes'][$i]['value'] = (string) ($context['site_address'] ?? '');
            }
        }

        return $payload;
    }

    private function assertOwned(EngineerReport $report): void
    {
        if ($report->user_id !== Auth::id()) {
            abort(403);
        }
    }
}
