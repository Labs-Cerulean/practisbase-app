<?php

namespace App\Http\Controllers\Pro\Engineer;

use App\Http\Controllers\Controller;
use App\Models\EngineerProject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = EngineerProject::where('user_id', Auth::id())
            ->orderByDesc('updated_at')
            ->get();

        return view('pro.engineer.projects-index', [
            'projects' => $projects,
            'phases' => EngineerProject::PHASES,
            'disciplines' => EngineerProject::DISCIPLINES,
        ]);
    }

    public function create()
    {
        return view('pro.engineer.projects-create', [
            'phases' => EngineerProject::PHASES,
            'disciplines' => EngineerProject::DISCIPLINES,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'reference_code' => 'nullable|string|max:100',
            'discipline' => 'required|in:' . implode(',', array_keys(EngineerProject::DISCIPLINES)),
            'phase' => 'required|in:' . implode(',', array_keys(EngineerProject::PHASES)),
            'notes' => 'nullable|string|max:5000',
        ]);

        EngineerProject::create([
            'user_id' => Auth::id(),
            'name' => $validated['name'],
            'reference_code' => $validated['reference_code'] ?? null,
            'discipline' => $validated['discipline'],
            'phase' => $validated['phase'],
            'status' => 'active',
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect('/pro/engineer/projects')->with('success', 'Engineering project created.');
    }
}
