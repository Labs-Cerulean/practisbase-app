<?php

namespace App\Http\Controllers\Pro\Architect;

use App\Http\Controllers\Controller;
use App\Models\ArchitectClient;
use App\Models\ArchitectPaApplication;
use App\Models\ArchitectProject;
use App\Support\Architect\BcaTemplateCatalog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TemplateController extends Controller
{
    public function index()
    {
        $templates = collect(BcaTemplateCatalog::all())->groupBy('group');
        $clients = ArchitectClient::where('user_id', Auth::id())->orderBy('name')->get();
        $projects = ArchitectProject::where('user_id', Auth::id())->with('client')->orderBy('name')->get();
        $pas = ArchitectPaApplication::where('user_id', Auth::id())->with('project')->orderBy('pa_number')->get();

        return view('pro.architect.templates-index', [
            'groups' => $templates,
            'clients' => $clients,
            'projects' => $projects,
            'pas' => $pas,
            'registerUrls' => BcaTemplateCatalog::REGISTER_URLS,
        ]);
    }

    public function downloadBlank(string $key): BinaryFileResponse
    {
        $tpl = BcaTemplateCatalog::find($key);
        abort_unless($tpl && $tpl['blank_file'], 404);

        $path = BcaTemplateCatalog::blankPath($tpl['blank_file']);
        abort_unless(is_file($path), 404);

        return response()->download($path, $tpl['blank_file']);
    }

    public function generate(Request $request, string $key)
    {
        $tpl = BcaTemplateCatalog::find($key);
        abort_unless($tpl && $tpl['fillable'], 404);

        $user = Auth::user();
        $validated = $request->validate([
            'architect_client_id' => 'nullable|integer',
            'architect_project_id' => 'nullable|integer',
            'architect_pa_application_id' => 'nullable|integer',
            'extra_text' => 'nullable|string|max:5000',
            'ds_number' => 'nullable|string|max:120',
            'works_description' => 'nullable|string|max:5000',
            'reasons' => 'nullable|string|max:5000',
            'mitigation' => 'nullable|string|max:5000',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'third_party_count' => 'nullable|string|max:40',
            'complex_count' => 'nullable|string|max:40',
        ]);

        $client = null;
        $project = null;
        $pa = null;

        if (! empty($validated['architect_pa_application_id'])) {
            $pa = ArchitectPaApplication::where('user_id', $user->id)
                ->where('id', $validated['architect_pa_application_id'])
                ->with('project.client')
                ->firstOrFail();
            $project = $pa->project;
            $client = $project?->client;
        } elseif (! empty($validated['architect_project_id'])) {
            $project = ArchitectProject::where('user_id', $user->id)
                ->where('id', $validated['architect_project_id'])
                ->with('client')
                ->firstOrFail();
            $client = $project->client;
        } elseif (! empty($validated['architect_client_id'])) {
            $client = ArchitectClient::where('user_id', $user->id)
                ->where('id', $validated['architect_client_id'])
                ->firstOrFail();
        }

        $context = BcaTemplateCatalog::prefillContext($user, $client, $project, $pa);
        $context['template'] = $tpl;
        $context['extra_text'] = $validated['extra_text'] ?? '';
        $context['ds_number'] = $validated['ds_number'] ?? '';
        $context['works_description'] = $validated['works_description'] ?? '';
        $context['reasons'] = $validated['reasons'] ?? '';
        $context['mitigation'] = $validated['mitigation'] ?? '';
        $context['start_date'] = ! empty($validated['start_date']) ? date('d/m/Y', strtotime($validated['start_date'])) : '';
        $context['end_date'] = ! empty($validated['end_date']) ? date('d/m/Y', strtotime($validated['end_date'])) : '';
        $context['third_party_count'] = $validated['third_party_count'] ?? '';
        $context['complex_count'] = $validated['complex_count'] ?? '';

        $pdf = Pdf::loadView('pro.architect.pdf.template-fill', $context)
            ->setPaper('a4');

        $filename = $tpl['key'].'_'.now()->format('Ymd_His').'.pdf';

        return $pdf->download($filename);
    }
}
