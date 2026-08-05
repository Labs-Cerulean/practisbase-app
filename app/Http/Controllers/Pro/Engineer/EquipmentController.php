<?php

namespace App\Http\Controllers\Pro\Engineer;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\EngineerCertificate;
use App\Models\EngineerEquipment;
use App\Models\Invoice;
use App\Support\EngineerCertificateBlueprint;
use App\Support\EquipmentAssetCode;
use App\Support\DocumentNumber;
use App\Support\FiscalYearGuard;
use App\Support\RegimeHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EquipmentController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();
        $clientId = (int) $request->query('client_id');
        $status = $request->query('status', 'active');

        $items = EngineerEquipment::query()
            ->where('user_id', $userId)
            ->with('client')
            ->when($clientId > 0, fn ($q) => $q->where('client_id', $clientId))
            ->when($status !== 'all' && array_key_exists($status, EngineerEquipment::STATUSES), fn ($q) => $q->where('status', $status))
            ->orderByRaw('next_due_on ASC NULLS LAST')
            ->orderBy('name')
            ->paginate(40)
            ->withQueryString();

        $dueCount = EngineerEquipment::query()
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->whereNotNull('next_due_on')
            ->whereDate('next_due_on', '<=', now()->addDays(30)->toDateString())
            ->count();

        return view('pro.engineer.equipment-index', [
            'items' => $items,
            'clients' => $this->clientsForUser($userId),
            'categories' => EngineerEquipment::CATEGORIES,
            'statuses' => EngineerEquipment::STATUSES,
            'filterClientId' => $clientId ?: null,
            'filterStatus' => $status,
            'dueCount' => $dueCount,
        ]);
    }

    public function due(Request $request)
    {
        $userId = Auth::id();
        $days = max(7, min(90, (int) $request->query('days', 30)));

        $items = EngineerEquipment::query()
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->whereNotNull('next_due_on')
            ->whereDate('next_due_on', '<=', now()->addDays($days)->toDateString())
            ->with('client')
            ->orderBy('next_due_on')
            ->orderBy('name')
            ->get();

        return view('pro.engineer.equipment-due', [
            'items' => $items,
            'days' => $days,
            'categories' => EngineerEquipment::CATEGORIES,
        ]);
    }

    public function create()
    {
        return view('pro.engineer.equipment-form', [
            'equipment' => null,
            'clients' => $this->clientsForUser(Auth::id()),
            'categories' => EngineerEquipment::CATEGORIES,
            'statuses' => EngineerEquipment::STATUSES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateEquipment($request);
        $client = $this->ownedClient(Auth::id(), (int) $data['client_id']);

        $equipment = EngineerEquipment::create([
            'user_id' => Auth::id(),
            'client_id' => $client->id,
            'category' => $data['category'],
            'name' => $data['name'],
            'make' => $data['make'] ?? null,
            'model' => $data['model'] ?? null,
            'serial_number' => $data['serial_number'] ?? null,
            'asset_code' => EquipmentAssetCode::next(Auth::id()),
            'capacity_rating' => $data['capacity_rating'] ?? null,
            'year_of_manufacture' => $data['year_of_manufacture'] ?? null,
            'site_location' => $data['site_location'] ?? null,
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect('/pro/engineer/equipment/'.$equipment->id)
            ->with('success', 'Equipment registered as '.$equipment->asset_code.'.');
    }

    public function show(int $id)
    {
        $equipment = $this->ownedEquipment(Auth::id(), $id);
        $equipment->load(['client', 'certificates' => fn ($q) => $q->orderByDesc('id')->limit(20)]);

        return view('pro.engineer.equipment-show', [
            'equipment' => $equipment,
            'categories' => EngineerEquipment::CATEGORIES,
            'statuses' => EngineerEquipment::STATUSES,
        ]);
    }

    public function edit(int $id)
    {
        $equipment = $this->ownedEquipment(Auth::id(), $id);

        return view('pro.engineer.equipment-form', [
            'equipment' => $equipment,
            'clients' => $this->clientsForUser(Auth::id()),
            'categories' => EngineerEquipment::CATEGORIES,
            'statuses' => EngineerEquipment::STATUSES,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $equipment = $this->ownedEquipment(Auth::id(), $id);
        $data = $this->validateEquipment($request);
        $client = $this->ownedClient(Auth::id(), (int) $data['client_id']);

        $equipment->update([
            'client_id' => $client->id,
            'category' => $data['category'],
            'name' => $data['name'],
            'make' => $data['make'] ?? null,
            'model' => $data['model'] ?? null,
            'serial_number' => $data['serial_number'] ?? null,
            'capacity_rating' => $data['capacity_rating'] ?? null,
            'year_of_manufacture' => $data['year_of_manufacture'] ?? null,
            'site_location' => $data['site_location'] ?? null,
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect('/pro/engineer/equipment/'.$equipment->id)
            ->with('success', 'Equipment updated.');
    }

    public function createCertificate(int $id)
    {
        $equipment = $this->ownedEquipment(Auth::id(), $id);
        $equipment->load('client');
        $starter = EngineerCertificateBlueprint::starters()['equipment'];
        $payload = $this->payloadFromEquipment($equipment, $starter['payload']);

        return view('pro.engineer.equipment-cert-form', [
            'equipment' => $equipment,
            'certificate' => null,
            'payload' => $payload,
            'defaultTitle' => $starter['title'].' — '.$equipment->name,
            'commonChecklistItems' => EngineerCertificateBlueprint::commonChecklistItems('equipment'),
        ]);
    }

    public function storeCertificate(Request $request, int $id)
    {
        $equipment = $this->ownedEquipment(Auth::id(), $id);
        $equipment->load('client');
        $validated = $this->validateCert($request);

        $certificate = EngineerCertificate::create([
            'user_id' => Auth::id(),
            'equipment_id' => $equipment->id,
            'engineer_project_id' => null,
            'engineer_pa_application_id' => null,
            'title' => $validated['title'],
            'certificate_number' => $validated['certificate_number'] ?: ($equipment->asset_code.'-'.now()->format('Ymd')),
            'inspected_on' => $validated['inspected_on'] ?? null,
            'issued_on' => $validated['issued_on'],
            'expires_on' => $validated['expires_on'] ?? null,
            'next_inspection_on' => $validated['next_inspection_on'] ?? null,
            'outcome' => $validated['outcome'] ?? null,
            'holder_name' => $validated['holder_name'] ?? $equipment->client->name,
            'holder_address' => $validated['holder_address'] ?? $equipment->client->billing_address,
            'contact_person' => $validated['contact_person'] ?? ($equipment->client->profile_data['contact_person'] ?? null),
            'contact_phone' => $validated['contact_phone'] ?? $equipment->client->phone,
            'site_address' => $validated['site_address'] ?? $equipment->site_location,
            'payload' => EngineerCertificateBlueprint::normalize($validated['payload'] ?? []),
        ]);

        return redirect('/pro/engineer/certificates/'.$certificate->id)
            ->with('success', 'Draft equipment certificate saved. Stamp & issue when ready.');
    }

    public function renew(int $id)
    {
        $equipment = $this->ownedEquipment(Auth::id(), $id);
        $equipment->load('client');

        $previous = EngineerCertificate::query()
            ->where('user_id', Auth::id())
            ->where('equipment_id', $equipment->id)
            ->whereNotNull('stamped_at')
            ->orderByDesc('issued_on')
            ->orderByDesc('id')
            ->first();

        if (! $previous) {
            return redirect('/pro/engineer/equipment/'.$equipment->id.'/certificates/create')
                ->with('success', 'No prior issued certificate — start a new inspection.');
        }

        $payload = $previous->normalizedPayload();
        $issued = now()->toDateString();
        $expires = $previous->expires_on && $previous->issued_on
            ? now()->addDays(max(1, $previous->issued_on->diffInDays($previous->expires_on)))->toDateString()
            : now()->addYear()->toDateString();
        $next = $previous->next_inspection_on && $previous->issued_on
            ? now()->addDays(max(1, $previous->issued_on->diffInDays($previous->next_inspection_on)))->toDateString()
            : $expires;

        $certificate = EngineerCertificate::create([
            'user_id' => Auth::id(),
            'equipment_id' => $equipment->id,
            'title' => $previous->title,
            'certificate_number' => $equipment->asset_code.'-'.now()->format('Ymd'),
            'inspected_on' => $issued,
            'issued_on' => $issued,
            'expires_on' => $expires,
            'next_inspection_on' => $next,
            'outcome' => null,
            'holder_name' => $previous->holder_name ?: $equipment->client->name,
            'holder_address' => $previous->holder_address ?: $equipment->client->billing_address,
            'contact_person' => $previous->contact_person,
            'contact_phone' => $previous->contact_phone,
            'site_address' => $equipment->site_location ?: $previous->site_address,
            'payload' => $payload,
        ]);

        return redirect('/pro/engineer/certificates/'.$certificate->id.'/edit')
            ->with('success', 'Renewal draft created from the last issued certificate. Update findings, then stamp.');
    }

    public function createRfp(Request $request, int $id)
    {
        $equipment = $this->ownedEquipment(Auth::id(), $id);
        $equipment->load('client');
        $user = Auth::user();

        $data = $request->validate([
            'description' => 'required|string|max:500',
            'amount' => 'required|numeric|min:0.01|max:999999.99',
            'apply_vat' => 'nullable|boolean',
            'due_days' => 'nullable|integer|min:1|max:90',
        ]);

        $issueDate = now()->toDateString();
        if ($lockError = FiscalYearGuard::ensureOpen($user->id, FiscalYearGuard::yearFromDate($issueDate))) {
            return back()->withErrors(['rfp' => $lockError]);
        }

        $amount = round((float) $data['amount'], 2);
        $items = [[
            'description' => $data['description'],
            'quantity' => 1.0,
            'unit_price' => $amount,
            'row_total' => $amount,
        ]];
        $subtotal = $amount;
        $vatTotal = 0.0;
        $regime = RegimeHistory::forDate($user, $issueDate);
        $regimeVat = $regime['vat_status'] ?? $user->vat_status;
        if ($regimeVat === 'article_10' && $request->boolean('apply_vat')) {
            $vatTotal = round($subtotal * 0.18, 2);
        }
        $total = round($subtotal + $vatTotal, 2);
        $dueDays = (int) ($data['due_days'] ?? 14);

        $invoice = Invoice::create([
            'user_id' => $user->id,
            'client_id' => $equipment->client_id,
            'type' => 'rfp',
            'invoice_number' => DocumentNumber::next($user->id, 'rfp', FiscalYearGuard::yearFromDate($issueDate)),
            'issue_date' => $issueDate,
            'due_date' => now()->addDays($dueDays)->toDateString(),
            'subtotal' => $subtotal,
            'vat_total' => $vatTotal,
            'total' => $total,
            'status' => 'unpaid',
            'items' => $items,
            'notes' => 'Equipment '.$equipment->asset_code.($equipment->serial_number ? ' / S/N '.$equipment->serial_number : ''),
        ]);

        return redirect('/ledger')
            ->with('success', 'RFP '.$invoice->invoice_number.' created for '.$equipment->client->name.'.');
    }

    /**
     * Called after an equipment-linked certificate is stamped.
     */
    public static function syncDueDates(EngineerEquipment $equipment, EngineerCertificate $certificate): void
    {
        $due = $certificate->next_inspection_on ?? $certificate->expires_on;
        $equipment->update([
            'last_certified_on' => $certificate->issued_on?->toDateString() ?? now()->toDateString(),
            'next_due_on' => $due?->toDateString(),
        ]);
    }

    private function validateEquipment(Request $request): array
    {
        return $request->validate([
            'client_id' => 'required|integer',
            'category' => 'required|in:'.implode(',', array_keys(EngineerEquipment::CATEGORIES)),
            'name' => 'required|string|max:255',
            'make' => 'nullable|string|max:120',
            'model' => 'nullable|string|max:120',
            'serial_number' => 'nullable|string|max:120',
            'capacity_rating' => 'nullable|string|max:120',
            'year_of_manufacture' => 'nullable|integer|min:1950|max:'.((int) date('Y') + 1),
            'site_location' => 'nullable|string|max:2000',
            'status' => 'required|in:'.implode(',', array_keys(EngineerEquipment::STATUSES)),
            'notes' => 'nullable|string|max:5000',
        ]);
    }

    private function validateCert(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'certificate_number' => 'nullable|string|max:120',
            'inspected_on' => 'nullable|date|before_or_equal:today',
            'issued_on' => 'required|date|before_or_equal:today',
            'expires_on' => 'nullable|date|after_or_equal:issued_on',
            'next_inspection_on' => 'nullable|date',
            'outcome' => 'nullable|string|max:120',
            'holder_name' => 'nullable|string|max:255',
            'holder_address' => 'nullable|string|max:2000',
            'contact_person' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:64',
            'site_address' => 'nullable|string|max:2000',
            'payload' => 'nullable|array',
            'payload.subject_heading' => 'nullable|string|max:255',
            'payload.highlight_label' => 'nullable|string|max:255',
            'payload.highlight_value' => 'nullable|string|max:255',
            'payload.checklist_heading' => 'nullable|string|max:255',
            'payload.legal_footer' => 'nullable|string|max:5000',
            'payload.attributes' => 'nullable|array|max:40',
            'payload.attributes.*.label' => 'nullable|string|max:255',
            'payload.attributes.*.value' => 'nullable|string|max:2000',
            'payload.checklist' => 'nullable|array|max:60',
            'payload.checklist.*.id' => 'nullable|string|max:32',
            'payload.checklist.*.item' => 'nullable|string|max:500',
            'payload.checklist.*.outcome' => 'nullable|string|max:120',
            'payload.checklist.*.comments' => 'nullable|string|max:2000',
            'payload.sections' => 'nullable|array|max:20',
            'payload.sections.*.heading' => 'nullable|string|max:255',
            'payload.sections.*.body' => 'nullable|string|max:10000',
        ]);
    }

    /**
     * @param  array<string, mixed>  $base
     * @return array<string, mixed>
     */
    private function payloadFromEquipment(EngineerEquipment $equipment, array $base): array
    {
        $attrs = [
            ['label' => 'Type / category', 'value' => $equipment->categoryLabel()],
            ['label' => 'Manufacturer / model', 'value' => trim(($equipment->make ?? '').' '.($equipment->model ?? ''))],
            ['label' => 'Inventory / serial', 'value' => $equipment->serial_number ?: $equipment->asset_code],
            ['label' => 'Asset code', 'value' => $equipment->asset_code],
            ['label' => 'Capacity / rating', 'value' => $equipment->capacity_rating ?? ''],
            ['label' => 'Year of manufacture', 'value' => $equipment->year_of_manufacture ? (string) $equipment->year_of_manufacture : ''],
            ['label' => 'Current location', 'value' => $equipment->site_location ?? ''],
        ];

        $base['subject_heading'] = $base['subject_heading'] ?? 'Subject / equipment';
        $base['attributes'] = $attrs;

        return EngineerCertificateBlueprint::normalize($base);
    }

    private function clientsForUser(int $userId)
    {
        return Client::where('user_id', $userId)->orderBy('name')->get(['id', 'name']);
    }

    private function ownedClient(int $userId, int $clientId): Client
    {
        return Client::where('user_id', $userId)->where('id', $clientId)->firstOrFail();
    }

    private function ownedEquipment(int $userId, int $id): EngineerEquipment
    {
        return EngineerEquipment::where('user_id', $userId)->where('id', $id)->firstOrFail();
    }
}
