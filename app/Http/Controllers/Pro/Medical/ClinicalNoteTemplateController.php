<?php

namespace App\Http\Controllers\Pro\Medical;

use App\Http\Controllers\Controller;
use App\Models\ClinicalNoteTemplate;
use App\Support\ClinicalNoteTemplates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ClinicalNoteTemplateController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $templates = ClinicalNoteTemplate::where('user_id', $user->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('pro.medical.templates-index', [
            'templates' => $templates,
            'builtins' => ClinicalNoteTemplates::builtinOptions(),
            'builtinFields' => [
                ClinicalNoteTemplates::GENERAL => ClinicalNoteTemplates::fieldsListFromMap(
                    ClinicalNoteTemplates::builtinFields(ClinicalNoteTemplates::GENERAL)
                ),
                ClinicalNoteTemplates::GYNAE => ClinicalNoteTemplates::fieldsListFromMap(
                    ClinicalNoteTemplates::builtinFields(ClinicalNoteTemplates::GYNAE)
                ),
                ClinicalNoteTemplates::OBS => ClinicalNoteTemplates::fieldsListFromMap(
                    ClinicalNoteTemplates::builtinFields(ClinicalNoteTemplates::OBS)
                ),
            ],
            'defaultKey' => ClinicalNoteTemplates::normalizeForUser($user, $user->clinical_note_template ?? null),
        ]);
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        $from = (string) $request->query('from', '');
        $fields = [];
        $name = '';

        $builtinKeys = array_keys(ClinicalNoteTemplates::builtinOptions());
        if (in_array($from, $builtinKeys, true)) {
            $fields = ClinicalNoteTemplates::fieldsListFromMap(ClinicalNoteTemplates::builtinFields($from));
            $name = ClinicalNoteTemplates::builtinOptions()[$from] . ' (mine)';
        } elseif (ClinicalNoteTemplates::isCustomKey($from)) {
            $source = ClinicalNoteTemplate::where('user_id', $user->id)
                ->where('id', ClinicalNoteTemplates::customIdFromKey($from))
                ->first();
            if ($source) {
                $fields = $source->normalizedFields();
                $name = $source->name . ' (copy)';
            }
        }

        if ($fields === []) {
            $fields = [
                ['key' => 'presenting_complaint', 'label' => 'Presenting complaint', 'type' => 'text'],
                ['key' => 'exam', 'label' => 'Exam', 'type' => 'text'],
                ['key' => 'plan', 'label' => 'Plan', 'type' => 'bullets'],
            ];
        }

        return view('pro.medical.templates-form', [
            'template' => null,
            'name' => old('name', $name),
            'fields' => old('fields', $fields),
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $validated = $this->validateTemplate($request, $user->id);
        $fields = ClinicalNoteTemplates::sanitizeFieldDefinitions($validated['fields']);

        if ($fields === []) {
            return back()->withErrors(['fields' => 'Add at least one labelled field.'])->withInput();
        }

        $maxSort = (int) ClinicalNoteTemplate::where('user_id', $user->id)->max('sort_order');

        ClinicalNoteTemplate::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'fields_json' => $fields,
            'sort_order' => $maxSort + 1,
        ]);

        return redirect('/pro/medical/templates')
            ->with('success', 'Template saved. It will appear when you add a journal entry.');
    }

    public function edit(ClinicalNoteTemplate $template)
    {
        $user = Auth::user();
        if ((int) $template->user_id !== (int) $user->id) {
            abort(403);
        }

        return view('pro.medical.templates-form', [
            'template' => $template,
            'name' => old('name', $template->name),
            'fields' => old('fields', $template->normalizedFields()),
        ]);
    }

    public function update(Request $request, ClinicalNoteTemplate $template)
    {
        $user = Auth::user();
        if ((int) $template->user_id !== (int) $user->id) {
            abort(403);
        }

        $validated = $this->validateTemplate($request, $user->id, $template->id);
        $fields = ClinicalNoteTemplates::sanitizeFieldDefinitions($validated['fields']);

        if ($fields === []) {
            return back()->withErrors(['fields' => 'Add at least one labelled field.'])->withInput();
        }

        $template->name = $validated['name'];
        $template->fields_json = $fields;
        $template->save();

        return redirect('/pro/medical/templates')
            ->with('success', 'Template updated. Existing notes keep the field labels they were saved with.');
    }

    public function destroy(ClinicalNoteTemplate $template)
    {
        $user = Auth::user();
        if ((int) $template->user_id !== (int) $user->id) {
            abort(403);
        }

        $key = $template->catalogueKey();
        $template->delete();

        if (($user->clinical_note_template ?? '') === $key) {
            $user->clinical_note_template = ClinicalNoteTemplates::GENERAL;
            $user->save();
        }

        return redirect('/pro/medical/templates')
            ->with('success', 'Template deleted. Past journal notes are unchanged.');
    }

    private function validateTemplate(Request $request, int $userId, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('clinical_note_templates', 'name')
                    ->where(fn ($q) => $q->where('user_id', $userId))
                    ->ignore($ignoreId),
            ],
            'fields' => 'required|array|min:1|max:40',
            'fields.*.label' => 'required|string|max:80',
            'fields.*.key' => 'nullable|string|max:40',
            'fields.*.type' => 'nullable|in:text,date,bullets',
        ], [
            'name.unique' => 'You already have a template with that name.',
        ]);
    }
}
