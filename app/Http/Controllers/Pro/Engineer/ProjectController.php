<?php

namespace App\Http\Controllers\Pro\Engineer;

use App\Http\Controllers\Controller;
use App\Models\EngineerProject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $showArchived = $request->boolean('archived');

        $projects = EngineerProject::where('user_id', Auth::id())
            ->when(
                $showArchived,
                fn ($q) => $q->where('status', 'archived'),
                fn ($q) => $q->where('status', '!=', 'archived')
            )
            ->orderByDesc('updated_at')
            ->get();

        return view('pro.engineer.projects-index', [
            'projects' => $projects,
            'phases' => EngineerProject::PHASES,
            'disciplines' => EngineerProject::DISCIPLINES,
            'statuses' => EngineerProject::STATUSES,
            'showArchived' => $showArchived,
        ]);
    }

    public function create()
    {
        return view('pro.engineer.projects-form', [
            'project' => null,
            'phases' => EngineerProject::PHASES,
            'disciplines' => EngineerProject::DISCIPLINES,
            'statuses' => EngineerProject::STATUSES,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateProject($request);

        $project = EngineerProject::create([
            'user_id' => Auth::id(),
            ...$validated,
        ]);

        return redirect('/pro/engineer/projects/'.$project->id)
            ->with('success', 'Engineering project created.');
    }

    public function show(EngineerProject $project)
    {
        $this->assertOwned($project);

        return view('pro.engineer.projects-show', [
            'project' => $project,
            'phases' => EngineerProject::PHASES,
            'disciplines' => EngineerProject::DISCIPLINES,
            'statuses' => EngineerProject::STATUSES,
        ]);
    }

    public function edit(EngineerProject $project)
    {
        $this->assertOwned($project);

        return view('pro.engineer.projects-form', [
            'project' => $project,
            'phases' => EngineerProject::PHASES,
            'disciplines' => EngineerProject::DISCIPLINES,
            'statuses' => EngineerProject::STATUSES,
        ]);
    }

    public function update(Request $request, EngineerProject $project)
    {
        $this->assertOwned($project);
        $validated = $this->validateProject($request);
        $project->update($validated);

        return redirect('/pro/engineer/projects/'.$project->id)
            ->with('success', 'Project updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateProject(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'reference_code' => 'nullable|string|max:100',
            'discipline' => 'required|in:'.implode(',', array_keys(EngineerProject::DISCIPLINES)),
            'phase' => 'required|in:'.implode(',', array_keys(EngineerProject::PHASES)),
            'status' => 'required|in:'.implode(',', array_keys(EngineerProject::STATUSES)),
            'notes' => 'nullable|string|max:5000',
        ]);
    }

    private function assertOwned(EngineerProject $project): void
    {
        if ($project->user_id !== Auth::id()) {
            abort(403);
        }
    }
}
