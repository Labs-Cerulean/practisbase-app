<?php

namespace App\Http\Controllers\Pro\Architect;

use App\Http\Controllers\Controller;
use App\Models\ArchitectPaApplication;
use App\Models\ArchitectProject;
use App\Support\Architect\EappsCaseUrl;
use App\Support\Architect\MapServerLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PaApplicationController extends Controller
{
    public function create(ArchitectProject $project)
    {
        $this->assertProjectOwned($project);

        return view('pro.architect.pa-form', [
            'project' => $project,
            'pa' => null,
            'caseTypes' => ArchitectPaApplication::CASE_TYPES,
            'statuses' => ArchitectPaApplication::STATUSES,
        ]);
    }

    public function store(Request $request, ArchitectProject $project)
    {
        $this->assertProjectOwned($project);
        $validated = $this->validatePa($request, Auth::id());

        $pa = ArchitectPaApplication::create([
            'user_id' => Auth::id(),
            'architect_project_id' => $project->id,
            ...$validated,
        ]);

        return redirect('/pro/architect/pa/'.$pa->id)
            ->with('success', 'Case saved. You can add the number later if it is still pending.');
    }

    public function show(ArchitectPaApplication $pa)
    {
        $this->assertPaOwned($pa);
        $pa->load([
            'project.client',
            'documents' => fn ($q) => $q->orderByDesc('updated_at'),
        ]);

        return view('pro.architect.pa-show', [
            'pa' => $pa,
            'project' => $pa->project,
            'caseTypes' => ArchitectPaApplication::CASE_TYPES,
            'statuses' => ArchitectPaApplication::STATUSES,
            'eappsUrl' => $pa->eappsUrl(),
            'mapServerUrl' => MapServerLink::home(),
        ]);
    }

    public function edit(ArchitectPaApplication $pa)
    {
        $this->assertPaOwned($pa);

        return view('pro.architect.pa-form', [
            'project' => $pa->project,
            'pa' => $pa,
            'caseTypes' => ArchitectPaApplication::CASE_TYPES,
            'statuses' => ArchitectPaApplication::STATUSES,
        ]);
    }

    public function update(Request $request, ArchitectPaApplication $pa)
    {
        $this->assertPaOwned($pa);
        $validated = $this->validatePa($request, Auth::id(), $pa->id);
        $pa->update($validated);

        return redirect('/pro/architect/pa/'.$pa->id)
            ->with('success', 'Case updated.');
    }

    private function validatePa(Request $request, int $userId, ?int $ignoreId = null): array
    {
        $caseType = strtoupper(trim((string) $request->input('case_type', 'PA')));
        $caseNumberRaw = trim((string) $request->input('case_number', ''));
        $caseYear = trim((string) $request->input('case_year', ''));
        $legacyNumber = trim((string) $request->input('pa_number', ''));

        if ($caseNumberRaw === '' && $caseYear === '' && $legacyNumber !== '') {
            $parsed = EappsCaseUrl::parse($legacyNumber);
            if ($parsed) {
                $caseType = $parsed['case_type'];
                $caseNumberRaw = $parsed['case_number'];
                $caseYear = $parsed['case_year'];
            }
        }

        if (strlen($caseYear) === 4) {
            $caseYear = substr($caseYear, -2);
        }

        $caseNumber = $caseNumberRaw !== '' ? EappsCaseUrl::padCaseNumber($caseNumberRaw) : null;
        $caseYear = $caseYear !== '' ? $caseYear : null;
        $paNumber = null;
        if ($caseNumber && $caseYear) {
            $paNumber = EappsCaseUrl::formatDisplay($caseType, $caseNumber, $caseYear);
        }

        $request->merge([
            'case_type' => $caseType !== '' ? $caseType : 'PA',
            'case_number' => $caseNumber,
            'case_year' => $caseYear,
            'pa_number' => $paNumber,
        ]);

        $validated = $request->validate([
            'case_type' => 'required|in:'.implode(',', array_keys(ArchitectPaApplication::CASE_TYPES)),
            'case_number' => 'nullable|string|max:16',
            'case_year' => ['nullable', 'regex:/^\d{2}$/'],
            'pa_number' => [
                'nullable',
                'string',
                'max:120',
                Rule::unique('architect_pa_applications', 'pa_number')
                    ->where(fn ($q) => $q->where('user_id', $userId))
                    ->ignore($ignoreId),
            ],
            'title' => 'nullable|string|max:255',
            'status' => 'required|in:'.implode(',', array_keys(ArchitectPaApplication::STATUSES)),
            'works_commencement_date' => 'nullable|date|before_or_equal:today',
            'notes' => 'nullable|string|max:5000',
        ]);

        if (filled($validated['case_number'] ?? null) xor filled($validated['case_year'] ?? null)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'case_number' => 'Enter both case number and year (e.g. 00525 and 22), or leave both blank if pending.',
            ]);
        }

        return $validated;
    }

    private function assertProjectOwned(ArchitectProject $project): void
    {
        if ($project->user_id !== Auth::id()) {
            abort(403);
        }
    }

    private function assertPaOwned(ArchitectPaApplication $pa): void
    {
        if ($pa->user_id !== Auth::id()) {
            abort(403);
        }
    }
}
