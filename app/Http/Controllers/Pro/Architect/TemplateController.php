<?php

namespace App\Http\Controllers\Pro\Architect;

use App\Http\Controllers\Controller;
use App\Models\ArchitectPaApplication;
use App\Models\ArchitectProject;
use App\Models\Client;
use App\Support\Architect\BcaTemplateCatalog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TemplateController extends Controller
{
    public function index()
    {
        $templates = collect(BcaTemplateCatalog::all())->groupBy('group');

        return view('pro.architect.templates-index', [
            'groups' => $templates,
            'registerUrls' => BcaTemplateCatalog::REGISTER_URLS,
        ]);
    }

    public function fill(Request $request, string $key)
    {
        $tpl = BcaTemplateCatalog::find($key);
        abort_unless($tpl && $tpl['fillable'], 404);

        $userId = Auth::id();
        $clients = Client::where('user_id', $userId)->orderBy('name')->get(['id', 'name']);
        $projects = ArchitectProject::where('user_id', $userId)
            ->orderBy('name')
            ->get(['id', 'name', 'client_id', 'site_locality', 'reference_code']);
        $pas = ArchitectPaApplication::where('user_id', $userId)
            ->orderBy('pa_number')
            ->get(['id', 'pa_number', 'title', 'architect_project_id']);

        $projectCascade = $projects->map(function ($p) {
            $label = $p->name;
            if ($p->site_locality) {
                $label .= ' · '.$p->site_locality;
            }
            if ($p->reference_code) {
                $label .= ' · '.$p->reference_code;
            }

            return [
                'id' => $p->id,
                'name' => $p->name,
                'client_id' => $p->client_id,
                'label' => $label,
            ];
        })->values()->all();

        $paCascade = $pas->map(function ($pa) {
            $label = $pa->pa_number;
            if ($pa->title) {
                $label .= ' · '.$pa->title;
            }

            return [
                'id' => $pa->id,
                'project_id' => $pa->architect_project_id,
                'label' => $label,
            ];
        })->values()->all();

        return view('pro.architect.templates-fill', [
            'template' => $tpl,
            'fields' => BcaTemplateCatalog::resolvedFields($tpl),
            'clients' => $clients,
            'projectCascade' => $projectCascade,
            'paCascade' => $paCascade,
            'preselect' => [
                'client_id' => $request->query('client_id'),
                'project_id' => $request->query('project_id'),
                'pa_id' => $request->query('pa_id'),
            ],
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
        $fields = BcaTemplateCatalog::resolvedFields($tpl);
        $rules = $this->validationRulesFor($fields);
        $validated = $request->validate($rules);

        $needsClient = $this->fieldRequired($fields, 'client') || $this->fieldPresent($fields, 'client');
        $needsProject = $this->fieldRequired($fields, 'project') || $this->fieldPresent($fields, 'project');
        $needsPa = $this->fieldRequired($fields, 'pa') || $this->fieldPresent($fields, 'pa');

        $client = null;
        $project = null;
        $pa = null;

        if ($needsPa && ! empty($validated['architect_pa_application_id'])) {
            $pa = ArchitectPaApplication::where('user_id', $user->id)
                ->where('id', $validated['architect_pa_application_id'])
                ->with('project.client')
                ->firstOrFail();
            $project = $pa->project;
            $client = $project?->client;
        } elseif ($needsProject && ! empty($validated['architect_project_id'])) {
            $project = ArchitectProject::where('user_id', $user->id)
                ->where('id', $validated['architect_project_id'])
                ->with('client')
                ->firstOrFail();
            $client = $project->client;
        } elseif ($needsClient && ! empty($validated['client_id'])) {
            $client = Client::where('user_id', $user->id)
                ->where('id', $validated['client_id'])
                ->firstOrFail();
        }

        $this->assertRequiredScope($fields, $client, $project, $pa);

        if ($client && $project && (int) $project->client_id !== (int) $client->id) {
            throw ValidationException::withMessages([
                'architect_project_id' => 'That project does not belong to the selected client.',
            ]);
        }
        if ($project && $pa && (int) $pa->architect_project_id !== (int) $project->id) {
            throw ValidationException::withMessages([
                'architect_pa_application_id' => 'That PA does not belong to the selected project.',
            ]);
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

        if (! empty($validated['commencement_override'])) {
            $context['commencement_date'] = date('d/m/Y', strtotime($validated['commencement_override']));
        }

        $pdf = Pdf::loadView('pro.architect.pdf.template-fill', $context)
            ->setPaper('a4');

        $filename = $tpl['key'].'_'.now()->format('Ymd_His').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * @param  list<array{name: string, required: bool, type: string}>  $fields
     * @return array<string, string>
     */
    private function validationRulesFor(array $fields): array
    {
        $rules = [
            'client_id' => 'nullable|integer',
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
            'commencement_override' => 'nullable|date|before_or_equal:today',
        ];

        foreach ($fields as $field) {
            $name = $field['name'];
            $map = [
                'client' => 'client_id',
                'project' => 'architect_project_id',
                'pa' => 'architect_pa_application_id',
            ];
            $input = $map[$name] ?? $name;
            if ($field['required']) {
                if (isset($map[$name])) {
                    $rules[$input] = 'required|integer';
                } elseif (($field['type'] ?? '') === 'date') {
                    $rules[$input] = 'required|date';
                } else {
                    $rules[$input] = 'required|string|max:5000';
                }
            }
        }

        return $rules;
    }

    /**
     * @param  list<array{name: string, required: bool}>  $fields
     */
    private function fieldRequired(array $fields, string $name): bool
    {
        foreach ($fields as $field) {
            if ($field['name'] === $name) {
                return (bool) $field['required'];
            }
        }

        return false;
    }

    /**
     * @param  list<array{name: string}>  $fields
     */
    private function fieldPresent(array $fields, string $name): bool
    {
        foreach ($fields as $field) {
            if ($field['name'] === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<array{name: string, required: bool}>  $fields
     */
    private function assertRequiredScope(array $fields, $client, $project, $pa): void
    {
        $messages = [];
        if ($this->fieldRequired($fields, 'client') && ! $client) {
            $messages['client_id'] = 'Select a client.';
        }
        if ($this->fieldRequired($fields, 'project') && ! $project) {
            $messages['architect_project_id'] = 'Select a project.';
        }
        if ($this->fieldRequired($fields, 'pa') && ! $pa) {
            $messages['architect_pa_application_id'] = 'Select a PA number.';
        }
        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }
}
