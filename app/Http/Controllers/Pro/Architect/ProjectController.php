<?php

namespace App\Http\Controllers\Pro\Architect;

use App\Http\Controllers\Controller;
use App\Models\ArchitectProject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = ArchitectProject::where('user_id', Auth::id())
            ->orderByDesc('updated_at')
            ->get();

        return view('pro.architect.projects-index', [
            'projects' => $projects,
            'phases' => ArchitectProject::PHASES,
        ]);
    }

    public function create()
    {
        return view('pro.architect.projects-create', [
            'phases' => ArchitectProject::PHASES,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'reference_code' => 'nullable|string|max:100',
            'phase' => 'required|in:' . implode(',', array_keys(ArchitectProject::PHASES)),
            'notes' => 'nullable|string|max:5000',
        ]);

        ArchitectProject::create([
            'user_id' => Auth::id(),
            'name' => $validated['name'],
            'reference_code' => $validated['reference_code'] ?? null,
            'phase' => $validated['phase'],
            'status' => 'active',
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect('/pro/architect/projects')->with('success', 'Project created.');
    }
}
