<?php

namespace App\Http\Controllers\Pro\Engineer;

use App\Http\Controllers\Controller;
use App\Models\EngineerPaApplication;
use App\Models\EngineerProject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PaApplicationController extends Controller
{
    public function create(EngineerProject $project)
    {
        $this->assertProjectOwned($project);

        return view('pro.engineer.pa-form', [
            'project' => $project,
            'pa' => null,
            'statuses' => EngineerPaApplication::STATUSES,
        ]);
    }

    public function store(Request $request, EngineerProject $project)
    {
        $this->assertProjectOwned($project);
        $validated = $this->validatePa($request, Auth::id());

        $pa = EngineerPaApplication::create([
            'user_id' => Auth::id(),
            'engineer_project_id' => $project->id,
            ...$validated,
        ]);

        return redirect('/pro/engineer/pa/'.$pa->id)
            ->with('success', 'PA record saved. You can add the number later if it is still pending.');
    }

    public function show(EngineerPaApplication $pa)
    {
        $this->assertPaOwned($pa);
        $pa->load(['project.client']);

        return view('pro.engineer.pa-show', [
            'pa' => $pa,
            'project' => $pa->project,
            'statuses' => EngineerPaApplication::STATUSES,
        ]);
    }

    public function edit(EngineerPaApplication $pa)
    {
        $this->assertPaOwned($pa);

        return view('pro.engineer.pa-form', [
            'project' => $pa->project,
            'pa' => $pa,
            'statuses' => EngineerPaApplication::STATUSES,
        ]);
    }

    public function update(Request $request, EngineerPaApplication $pa)
    {
        $this->assertPaOwned($pa);
        $validated = $this->validatePa($request, Auth::id(), $pa->id);
        $pa->update($validated);

        return redirect('/pro/engineer/pa/'.$pa->id)
            ->with('success', 'PA record updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePa(Request $request, int $userId, ?int $ignoreId = null): array
    {
        $request->merge([
            'pa_number' => filled($request->input('pa_number'))
                ? trim((string) $request->input('pa_number'))
                : null,
        ]);

        return $request->validate([
            'pa_number' => [
                'nullable',
                'string',
                'max:120',
                Rule::unique('engineer_pa_applications', 'pa_number')
                    ->where(fn ($q) => $q->where('user_id', $userId))
                    ->ignore($ignoreId),
            ],
            'title' => 'nullable|string|max:255',
            'status' => 'required|in:'.implode(',', array_keys(EngineerPaApplication::STATUSES)),
            'works_commencement_date' => 'nullable|date|before_or_equal:today',
            'notes' => 'nullable|string|max:5000',
        ]);
    }

    private function assertProjectOwned(EngineerProject $project): void
    {
        if ($project->user_id !== Auth::id()) {
            abort(403);
        }
    }

    private function assertPaOwned(EngineerPaApplication $pa): void
    {
        if ($pa->user_id !== Auth::id()) {
            abort(403);
        }
    }
}
