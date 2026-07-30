<?php

namespace App\Http\Controllers\Pro\Architect;

use App\Http\Controllers\Controller;
use App\Models\ArchitectPaApplication;
use App\Models\ArchitectProject;
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
            ->with('success', 'PA application saved.');
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
            'statuses' => ArchitectPaApplication::STATUSES,
        ]);
    }

    public function edit(ArchitectPaApplication $pa)
    {
        $this->assertPaOwned($pa);

        return view('pro.architect.pa-form', [
            'project' => $pa->project,
            'pa' => $pa,
            'statuses' => ArchitectPaApplication::STATUSES,
        ]);
    }

    public function update(Request $request, ArchitectPaApplication $pa)
    {
        $this->assertPaOwned($pa);
        $validated = $this->validatePa($request, Auth::id(), $pa->id);
        $pa->update($validated);

        return redirect('/pro/architect/pa/'.$pa->id)
            ->with('success', 'PA application updated.');
    }

    private function validatePa(Request $request, int $userId, ?int $ignoreId = null): array
    {
        return $request->validate([
            'pa_number' => [
                'required',
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
