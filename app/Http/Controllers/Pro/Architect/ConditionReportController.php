<?php

namespace App\Http\Controllers\Pro\Architect;

use App\Http\Controllers\Controller;
use App\Models\ArchitectConditionReport;
use App\Models\ArchitectConditionReportPhoto;
use App\Models\ArchitectPaApplication;
use App\Models\ArchitectProject;
use App\Support\ArchitectConditionReportBlueprint;
use App\Support\IssueCode;
use App\Support\PracticeDocumentContext;
use App\Support\TenantStorage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConditionReportController extends Controller
{
    public function index(Request $request)
    {
        $projectId = (int) $request->query('project_id');

        $reports = ArchitectConditionReport::where('user_id', Auth::id())
            ->with(['project', 'paApplication'])
            ->when($projectId > 0, fn ($q) => $q->where('architect_project_id', $projectId))
            ->orderByDesc('id')
            ->get();

        return view('pro.architect.condition-reports-index', [
            'reports' => $reports,
            'projectId' => $projectId ?: null,
        ]);
    }

    public function create(Request $request)
    {
        $projects = ArchitectProject::where('user_id', Auth::id())
            ->where('status', '!=', 'archived')
            ->with('client')
            ->orderBy('name')
            ->get();

        $projectId = (int) $request->query('project_id');
        if ($projectId <= 0 || ! $projects->contains('id', $projectId)) {
            return view('pro.shared.pick-project', [
                'projects' => $projects,
                'heading' => 'Choose a project for this condition report',
                'lead' => 'Condition reports sit under a project so client, site, and filing refs auto-fill.',
                'backHref' => '/pro/architect/condition-reports',
                'createProjectHref' => '/pro/architect/projects/create',
                'continueBase' => '/pro/architect/condition-reports/create?starter='.urlencode((string) $request->query('starter', 'seventh_schedule')),
                'accent' => '#3f6212',
            ]);
        }

        $project = $projects->firstWhere('id', $projectId);
        $starterKey = (string) $request->query('starter', 'seventh_schedule');
        $starters = ArchitectConditionReportBlueprint::starters();
        if (! isset($starters[$starterKey])) {
            $starterKey = 'seventh_schedule';
        }
        $starter = $starters[$starterKey];

        $paId = (int) $request->query('pa_id');
        $pas = ArchitectPaApplication::where('user_id', Auth::id())
            ->where('architect_project_id', $projectId)
            ->orderByDesc('updated_at')
            ->get();
        $pa = $paId > 0 ? $pas->firstWhere('id', $paId) : null;

        $context = PracticeDocumentContext::architectPrefill($project, $pa, 'CR');

        return view('pro.architect.condition-reports-form', [
            'report' => null,
            'projects' => $projects,
            'pas' => $pas,
            'payload' => $starter['payload'],
            'starters' => $starters,
            'starterKey' => $starterKey,
            'defaultTitle' => $starter['title'],
            'prefill' => [
                'project_id' => $projectId,
                'pa_id' => $pa?->id,
            ],
            'context' => $context,
            'projectOptions' => PracticeDocumentContext::projectOptionsPayload($projects, 'arch', 'CR'),
            'commonDefects' => ArchitectConditionReportBlueprint::commonDefectLabels(),
            'photoLinkOptions' => ArchitectConditionReportBlueprint::photoLinkOptions($starter['payload']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateReport($request);
        $scope = $this->resolveScope(Auth::id(), $validated);
        $project = ArchitectProject::where('user_id', Auth::id())
            ->where('id', $scope['architect_project_id'])
            ->firstOrFail();

        $reportNumber = trim((string) ($validated['report_number'] ?? ''));
        if ($reportNumber === '') {
            $reportNumber = PracticeDocumentContext::nextArchitectConditionRef(Auth::id(), $project);
        }

        $report = ArchitectConditionReport::create([
            'user_id' => Auth::id(),
            'architect_project_id' => $scope['architect_project_id'],
            'architect_pa_application_id' => $scope['architect_pa_application_id'],
            'title' => $validated['title'],
            'report_type' => $validated['report_type'] ?? null,
            'report_number' => $reportNumber,
            'inspected_on' => $validated['inspected_on'] ?? null,
            'issued_on' => $validated['issued_on'],
            'client_name' => $validated['client_name'] ?? null,
            'client_address' => $validated['client_address'] ?? null,
            'project_description' => $validated['project_description'] ?? null,
            'inspected_address' => $validated['inspected_address'] ?? null,
            'development_address' => $validated['development_address'] ?? null,
            'payload' => ArchitectConditionReportBlueprint::normalize($validated['payload'] ?? []),
        ]);

        $this->storePhotos($request, $report);

        return redirect('/pro/architect/condition-reports/'.$report->id)
            ->with('success', 'Draft condition report saved. Edit until you Stamp & issue.');
    }

    public function show(ArchitectConditionReport $report)
    {
        $this->assertOwned($report);
        $report->load(['project.client', 'paApplication', 'photos']);

        return view('pro.architect.condition-reports-show', [
            'report' => $report,
            'payload' => $report->normalizedPayload(),
        ]);
    }

    public function edit(ArchitectConditionReport $report)
    {
        $this->assertOwned($report);
        if (! $report->isEditable()) {
            return redirect('/pro/architect/condition-reports/'.$report->id)
                ->withErrors(['report' => 'This report was stamped and issued. It can no longer be edited.']);
        }

        $projects = ArchitectProject::where('user_id', Auth::id())
            ->where(function ($q) use ($report) {
                $q->where('status', '!=', 'archived')
                    ->orWhere('id', $report->architect_project_id);
            })
            ->with('client')
            ->orderBy('name')
            ->get();

        $pas = ArchitectPaApplication::where('user_id', Auth::id())
            ->when($report->architect_project_id, fn ($q) => $q->where('architect_project_id', $report->architect_project_id))
            ->orderByDesc('updated_at')
            ->get();

        $payload = $report->normalizedPayload();

        return view('pro.architect.condition-reports-form', [
            'report' => $report,
            'projects' => $projects,
            'pas' => $pas,
            'payload' => $payload,
            'starters' => ArchitectConditionReportBlueprint::starters(),
            'starterKey' => $report->report_type,
            'defaultTitle' => $report->title,
            'prefill' => [
                'project_id' => $report->architect_project_id,
                'pa_id' => $report->architect_pa_application_id,
            ],
            'context' => [
                'client_name' => $report->client_name,
                'client_address' => $report->client_address,
                'project_description' => $report->project_description,
                'development_address' => $report->development_address,
                'inspected_address' => $report->inspected_address,
                'suggested_ref' => $report->report_number,
            ],
            'projectOptions' => PracticeDocumentContext::projectOptionsPayload($projects, 'arch', 'CR'),
            'commonDefects' => ArchitectConditionReportBlueprint::commonDefectLabels(),
            'photoLinkOptions' => ArchitectConditionReportBlueprint::photoLinkOptions($payload),
        ]);
    }

    public function update(Request $request, ArchitectConditionReport $report)
    {
        $this->assertOwned($report);
        if (! $report->isEditable()) {
            return redirect('/pro/architect/condition-reports/'.$report->id)
                ->withErrors(['report' => 'This report was stamped and issued. It can no longer be edited.']);
        }

        $validated = $this->validateReport($request);
        $scope = $this->resolveScope(Auth::id(), $validated);
        $project = ArchitectProject::where('user_id', Auth::id())
            ->where('id', $scope['architect_project_id'])
            ->firstOrFail();

        $reportNumber = trim((string) ($validated['report_number'] ?? ''));
        if ($reportNumber === '') {
            $reportNumber = $report->report_number
                ?: PracticeDocumentContext::nextArchitectConditionRef(Auth::id(), $project);
        }

        $report->update([
            'architect_project_id' => $scope['architect_project_id'],
            'architect_pa_application_id' => $scope['architect_pa_application_id'],
            'title' => $validated['title'],
            'report_type' => $validated['report_type'] ?? $report->report_type,
            'report_number' => $reportNumber,
            'inspected_on' => $validated['inspected_on'] ?? null,
            'issued_on' => $validated['issued_on'],
            'client_name' => $validated['client_name'] ?? null,
            'client_address' => $validated['client_address'] ?? null,
            'project_description' => $validated['project_description'] ?? null,
            'inspected_address' => $validated['inspected_address'] ?? null,
            'development_address' => $validated['development_address'] ?? null,
            'payload' => ArchitectConditionReportBlueprint::normalize($validated['payload'] ?? []),
        ]);

        $this->storePhotos($request, $report);

        return redirect('/pro/architect/condition-reports/'.$report->id)
            ->with('success', 'Condition report updated.');
    }

    public function stamp(ArchitectConditionReport $report)
    {
        $this->assertOwned($report);
        if ($report->isStamped()) {
            return back()->withErrors(['report' => 'Already stamped and issued.']);
        }
        if (! $report->architect_project_id) {
            return back()->withErrors(['report' => 'Link this report to a project before stamping.']);
        }

        $report->stamped_at = now();
        $report->issue_code = IssueCode::allocateForArchitectConditionReport();
        $report->save();

        return redirect('/pro/architect/condition-reports/'.$report->id)
            ->with('success', 'Condition report stamped and issued as '.$report->issue_code.'. It is now locked.');
    }

    public function downloadPdf(ArchitectConditionReport $report)
    {
        $this->assertOwned($report);
        if (! $report->isStamped()) {
            return redirect('/pro/architect/condition-reports/'.$report->id)
                ->withErrors(['report' => 'Stamp & issue before downloading the PDF.']);
        }

        $user = Auth::user();
        $report->load(['photos', 'project.client', 'paApplication']);

        $pdf = Pdf::loadView('pro.architect.condition-reports-pdf', [
            'report' => $report,
            'payload' => $report->normalizedPayload(),
            'user' => $user,
        ])->setPaper('a4');

        $safe = preg_replace('/[^A-Za-z0-9\-_]+/', '-', $report->title) ?: 'condition-report';

        return $pdf->download($safe.'-'.$report->issue_code.'.pdf');
    }

    public function downloadPhoto(ArchitectConditionReport $report, ArchitectConditionReportPhoto $photo)
    {
        $this->assertOwned($report);
        if ($photo->architect_condition_report_id !== $report->id || $photo->user_id !== Auth::id()) {
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
        $starterKeys = implode(',', array_keys(ArchitectConditionReportBlueprint::starters()));

        return $request->validate([
            'title' => 'required|string|max:255',
            'report_type' => 'nullable|string|in:'.$starterKeys,
            'report_number' => 'nullable|string|max:120',
            'inspected_on' => 'nullable|date|before_or_equal:today',
            'issued_on' => 'required|date|before_or_equal:today',
            'client_name' => 'nullable|string|max:255',
            'client_address' => 'nullable|string|max:2000',
            'project_description' => 'nullable|string|max:5000',
            'inspected_address' => 'nullable|string|max:2000',
            'development_address' => 'nullable|string|max:2000',
            'architect_project_id' => 'required|integer',
            'architect_pa_application_id' => 'nullable|integer',
            'payload' => 'nullable|array',
            'payload.sketch_ref' => 'nullable|string|max:1000',
            'payload.legal_footer' => 'nullable|string|max:5000',
            'payload.defects_heading' => 'nullable|string|max:255',
            'payload.sections' => 'nullable|array|max:20',
            'payload.sections.*.heading' => 'nullable|string|max:500',
            'payload.sections.*.body' => 'nullable|string|max:15000',
            'payload.defects' => 'nullable|array|max:80',
            'payload.defects.*.id' => 'nullable|string|max:32',
            'payload.defects.*.location' => 'nullable|string|max:255',
            'payload.defects.*.defect' => 'nullable|string|max:500',
            'payload.defects.*.photo_ref' => 'nullable|string|max:120',
            'payload.defects.*.notes' => 'nullable|string|max:1000',
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
     * @return array{architect_project_id: int, architect_pa_application_id: ?int}
     */
    private function resolveScope(int $userId, array $validated): array
    {
        $projectId = (int) ($validated['architect_project_id'] ?? 0);
        $paId = (int) ($validated['architect_pa_application_id'] ?? 0);

        $project = ArchitectProject::where('user_id', $userId)->where('id', $projectId)->first();
        if (! $project) {
            abort(403);
        }

        $pa = null;
        if ($paId > 0) {
            $pa = ArchitectPaApplication::where('user_id', $userId)->where('id', $paId)->first();
            if (! $pa || $pa->architect_project_id !== $project->id) {
                abort(403);
            }
        }

        return [
            'architect_project_id' => $project->id,
            'architect_pa_application_id' => $pa?->id,
        ];
    }

    private function storePhotos(Request $request, ArchitectConditionReport $report): void
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
                TenantStorage::architectConditionReportsPath($report->user_id).'/report_'.$report->id,
                TenantStorage::diskName()
            );
            ArchitectConditionReportPhoto::create([
                'user_id' => $report->user_id,
                'architect_condition_report_id' => $report->id,
                'file_path' => $path,
                'caption' => is_array($captions) ? trim((string) ($captions[$i] ?? '')) ?: null : null,
                'linked_row_id' => is_array($linked) ? trim((string) ($linked[$i] ?? '')) ?: null : null,
                'sort_order' => $sort,
            ]);
        }
    }

    private function assertOwned(ArchitectConditionReport $report): void
    {
        if ($report->user_id !== Auth::id()) {
            abort(403);
        }
    }
}
