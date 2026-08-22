<?php

namespace App\Http\Controllers\Pro\Architect;

use App\Http\Controllers\Controller;
use App\Models\ArchitectConditionReport;
use App\Models\ArchitectNeighbour;
use App\Models\ArchitectPaApplication;
use App\Models\ArchitectProject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NeighbourController extends Controller
{
    public function store(Request $request, ArchitectProject $project)
    {
        $this->assertProjectOwned($project);
        $validated = $this->validateNeighbour($request, $project);

        $neighbour = ArchitectNeighbour::create([
            'user_id' => Auth::id(),
            'architect_project_id' => $project->id,
            ...$validated,
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'neighbour' => [
                    'id' => $neighbour->id,
                    'address' => $neighbour->addressLine(),
                ],
                'message' => 'Neighbour added to the register.',
            ]);
        }

        return redirect('/pro/architect/projects/'.$project->id.'#neighbours')
            ->with('success', 'Neighbour added to the register.');
    }

    /**
     * Map quick-add: address + pin only; details filled later in the register.
     */
    public function quickStore(Request $request, ArchitectProject $project)
    {
        $this->assertProjectOwned($project);

        $lat = $request->input('latitude');
        $lng = $request->input('longitude');
        $request->merge([
            'latitude' => ($lat === '' || $lat === null) ? null : $lat,
            'longitude' => ($lng === '' || $lng === null) ? null : $lng,
        ]);

        $validated = $request->validate([
            'address' => 'required|string|max:2000',
            'street' => 'nullable|string|max:255',
            'locality' => 'nullable|string|max:120',
            'relation' => 'nullable|in:'.implode(',', array_keys(ArchitectNeighbour::RELATIONS)),
            'latitude' => 'nullable|numeric|between:35.7,36.2',
            'longitude' => 'nullable|numeric|between:14.1,14.7',
            'notes' => 'nullable|string|max:5000',
        ]);

        if (($validated['latitude'] ?? null) === null || ($validated['longitude'] ?? null) === null) {
            $validated['latitude'] = null;
            $validated['longitude'] = null;
        }

        $neighbour = ArchitectNeighbour::create([
            'user_id' => Auth::id(),
            'architect_project_id' => $project->id,
            'address' => $validated['address'],
            'street' => $validated['street'] ?? null,
            'locality' => $validated['locality'] ?? null,
            'relation' => $validated['relation'] ?? 'abutting',
            'status' => 'identified',
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'ok' => true,
            'neighbour' => [
                'id' => $neighbour->id,
                'address' => $neighbour->addressLine(),
                'edit_href' => '/pro/architect/projects/'.$project->id.'#neighbour-'.$neighbour->id,
            ],
            'message' => 'Quick-added to the register. Fill details when ready — you can attach multiple condition reports later (e.g. flats).',
        ]);
    }

    public function update(Request $request, ArchitectProject $project, ArchitectNeighbour $neighbour)
    {
        $this->assertNeighbourOwned($project, $neighbour);
        $validated = $this->validateNeighbour($request, $project, updating: true);
        $neighbour->update($validated);

        return redirect('/pro/architect/projects/'.$project->id.'#neighbours')
            ->with('success', 'Neighbour updated.');
    }

    public function destroy(ArchitectProject $project, ArchitectNeighbour $neighbour)
    {
        $this->assertNeighbourOwned($project, $neighbour);

        ArchitectConditionReport::where('user_id', Auth::id())
            ->where('architect_neighbour_id', $neighbour->id)
            ->update(['architect_neighbour_id' => null]);

        $neighbour->delete();

        return redirect('/pro/architect/projects/'.$project->id.'#neighbours')
            ->with('success', 'Neighbour removed from the register.');
    }

    public function linkReport(Request $request, ArchitectProject $project, ArchitectNeighbour $neighbour)
    {
        $this->assertNeighbourOwned($project, $neighbour);

        $validated = $request->validate([
            'architect_condition_report_id' => 'required|integer',
        ]);

        $report = ArchitectConditionReport::where('user_id', Auth::id())
            ->where('id', $validated['architect_condition_report_id'])
            ->where('architect_project_id', $project->id)
            ->firstOrFail();

        $neighbour->architect_condition_report_id = $report->id;
        $neighbour->advanceStatusTo('report_drafted');
        $neighbour->save();

        $report->architect_neighbour_id = $neighbour->id;
        if (! filled($report->inspected_address)) {
            $report->inspected_address = $neighbour->addressLine();
        }
        $report->save();

        return redirect('/pro/architect/projects/'.$project->id.'#neighbour-'.$neighbour->id)
            ->with('success', 'Condition report linked. You can still add more reports for this property (e.g. other flats).');
    }

    private function validateNeighbour(Request $request, ArchitectProject $project, bool $updating = false): array
    {
        $lat = $request->input('latitude');
        $lng = $request->input('longitude');
        $request->merge([
            'latitude' => ($lat === '' || $lat === null) ? null : $lat,
            'longitude' => ($lng === '' || $lng === null) ? null : $lng,
        ]);

        $validated = $request->validate([
            'address' => ($updating ? 'sometimes|' : '').'required|string|max:2000',
            'premises' => 'nullable|string|max:255',
            'street' => 'nullable|string|max:255',
            'locality' => 'nullable|string|max:120',
            'owner_occupier_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:64',
            'email' => 'nullable|email|max:255',
            'relation' => 'required|in:'.implode(',', array_keys(ArchitectNeighbour::RELATIONS)),
            'status' => 'required|in:'.implode(',', array_keys(ArchitectNeighbour::STATUSES)),
            'appointment_on' => 'nullable|date',
            'notes' => 'nullable|string|max:5000',
            'architect_pa_application_id' => 'nullable|integer',
            'architect_condition_report_id' => 'nullable|integer',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'latitude' => 'nullable|numeric|between:35.7,36.2',
            'longitude' => 'nullable|numeric|between:14.1,14.7',
        ]);

        if (! empty($validated['architect_pa_application_id'])) {
            $ownsPa = ArchitectPaApplication::where('user_id', Auth::id())
                ->where('architect_project_id', $project->id)
                ->where('id', $validated['architect_pa_application_id'])
                ->exists();
            if (! $ownsPa) {
                abort(403);
            }
        } else {
            $validated['architect_pa_application_id'] = null;
        }

        if (array_key_exists('architect_condition_report_id', $validated)) {
            if (! empty($validated['architect_condition_report_id'])) {
                $ownsCr = ArchitectConditionReport::where('user_id', Auth::id())
                    ->where('architect_project_id', $project->id)
                    ->where('id', $validated['architect_condition_report_id'])
                    ->exists();
                if (! $ownsCr) {
                    abort(403);
                }
            } else {
                $validated['architect_condition_report_id'] = null;
            }
        }

        if (($validated['appointment_on'] ?? null) === '') {
            $validated['appointment_on'] = null;
        }

        $lat = $validated['latitude'] ?? null;
        $lng = $validated['longitude'] ?? null;
        if ($lat === null || $lng === null || $lat === '' || $lng === '') {
            $validated['latitude'] = null;
            $validated['longitude'] = null;
        }

        return $validated;
    }

    private function assertProjectOwned(ArchitectProject $project): void
    {
        if ($project->user_id !== Auth::id()) {
            abort(403);
        }
    }

    private function assertNeighbourOwned(ArchitectProject $project, ArchitectNeighbour $neighbour): void
    {
        $this->assertProjectOwned($project);
        if ($neighbour->user_id !== Auth::id() || $neighbour->architect_project_id !== $project->id) {
            abort(403);
        }
    }
}
