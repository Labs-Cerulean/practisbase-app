<?php

namespace App\Http\Controllers\Pro\Medical;

use App\Http\Controllers\Controller;
use App\Models\ClinicalAttachment;
use App\Models\ClinicalEntry;
use App\Models\Client;
use App\Models\MedicalVault;
use App\Models\Patient;
use App\Support\IssueCode;
use App\Support\MedicalVaultCrypto;
use App\Support\TierPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PatientController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $key = MedicalVaultCrypto::keyFromSession(session('medical_vault_key'));
        $vault = MedicalVault::activeForUser($user->id);

        $patients = Patient::where('user_id', $user->id)
            ->when($vault, fn ($q) => $q->where('vault_id', $vault->id))
            ->with('billingClient')
            ->orderByDesc('id')
            ->get();

        $entryStats = ClinicalEntry::where('user_id', $user->id)
            ->when($vault, fn ($q) => $q->where('vault_id', $vault->id))
            ->selectRaw('patient_id, entry_type, COUNT(*) as total')
            ->groupBy('patient_id', 'entry_type')
            ->get()
            ->groupBy('patient_id');

        $attachmentCounts = ClinicalAttachment::where('user_id', $user->id)
            ->when($vault, fn ($q) => $q->where('vault_id', $vault->id))
            ->selectRaw('patient_id, COUNT(*) as total')
            ->groupBy('patient_id')
            ->pluck('total', 'patient_id');

        $rows = $patients->map(function (Patient $patient) use ($key, $entryStats, $attachmentCounts) {
            try {
                $payload = MedicalVaultCrypto::decrypt($patient->payload_ciphertext, $patient->payload_nonce, $key);
            } catch (\Throwable) {
                $payload = ['display_name' => '[Unable to decrypt]', 'date_of_birth' => null, 'notes' => null];
            }

            $byType = ($entryStats->get($patient->id) ?? collect())->pluck('total', 'entry_type');

            return [
                'model' => $patient,
                'display_name' => $payload['display_name'] ?? 'Patient',
                'date_of_birth' => $payload['date_of_birth'] ?? null,
                'notes' => $payload['notes'] ?? '',
                'public_ref' => $patient->public_ref,
                'linked' => (bool) $patient->billing_client_id,
                'client_name' => $patient->billingClient?->name,
                'journal_count' => (int) ($byType['journal'] ?? 0),
                'prescription_count' => (int) ($byType['prescription'] ?? 0),
                'referral_count' => (int) ($byType['referral'] ?? 0),
                'certificate_count' => (int) ($byType['certificate'] ?? 0),
                'attachment_count' => (int) ($attachmentCounts[$patient->id] ?? 0),
                'created_ts' => optional($patient->created_at)->timestamp ?? $patient->id,
            ];
        });

        return view('pro.medical.patients-index', [
            'rows' => $rows,
            'user' => $user,
            'vault' => $vault,
            'backupOverdue' => $vault ? $vault->isBackupOverdue() : false,
        ]);
    }

    /**
     * Practitioner-side authenticity check: match an issue code to a stamped clinical document.
     */
    public function lookupIssueCode(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'issue_code' => 'required|string|max:32',
        ]);

        $code = IssueCode::normalize($validated['issue_code']);
        $entry = ClinicalEntry::where('user_id', $user->id)
            ->where('issue_code', $code)
            ->first();

        if (! $entry) {
            return redirect('/pro/medical/patients')
                ->withErrors(['issue_code' => 'No stamped clinical document in your vault matches ' . ($code ?: 'that code') . '. If this code was presented as a reprint, treat it as unverified.']);
        }

        return redirect('/pro/medical/patients/' . $entry->patient_id)
            ->with('success', 'Authenticity match: ' . $entry->issue_code . ' · ' . $entry->typeLabel() . ' · issued ' . optional($entry->issued_at)->format('d M Y H:i') . '. Open the entry below to compare with the presented copy.');
    }

    public function create()
    {
        $user = Auth::user();

        $clients = Client::where('user_id', $user->id)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone']);

        $linkedClientIds = Patient::where('user_id', $user->id)
            ->whereNotNull('billing_client_id')
            ->pluck('billing_client_id')
            ->all();

        return view('pro.medical.patients-create', [
            'clients' => $clients,
            'linkedClientIds' => $linkedClientIds,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $vault = MedicalVault::activeForUser($user->id);
        $key = MedicalVaultCrypto::keyFromSession(session('medical_vault_key'));

        if (! $vault || ! $key) {
            return redirect('/pro/medical/vault/unlock');
        }

        $validated = $request->validate([
            'display_name' => 'required|string|max:255',
            'date_of_birth' => 'nullable|date|before_or_equal:today',
            'notes' => 'nullable|string|max:2000',
            'billing_client_id' => [
                'nullable',
                'integer',
                Rule::exists('clients', 'id')->where(fn ($q) => $q->where('user_id', $user->id)->whereNull('deleted_at')),
            ],
        ]);

        $billingClientId = $validated['billing_client_id'] ?? null;

        if ($billingClientId) {
            $alreadyLinked = Patient::where('user_id', $user->id)
                ->where('billing_client_id', $billingClientId)
                ->exists();

            if ($alreadyLinked) {
                return back()->withErrors([
                    'billing_client_id' => 'That billing client is already linked to another patient in your vault.',
                ])->withInput();
            }
        }

        $encrypted = MedicalVaultCrypto::encrypt([
            'display_name' => $validated['display_name'],
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ], $key);

        $patient = Patient::create([
            'user_id' => $user->id,
            'vault_id' => $vault->id,
            'public_ref' => 'PAT-' . strtoupper(Str::random(8)),
            'billing_client_id' => $billingClientId,
            'payload_ciphertext' => $encrypted['ciphertext'],
            'payload_nonce' => $encrypted['nonce'],
        ]);

        return redirect('/pro/medical/patients/' . $patient->id)
            ->with('success', 'Patient record created in the encrypted vault.');
    }

    public function updateBillingLink(Request $request, Patient $patient)
    {
        $user = Auth::user();
        if ($patient->user_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'billing_client_id' => [
                'nullable',
                'integer',
                Rule::exists('clients', 'id')->where(fn ($q) => $q->where('user_id', $user->id)->whereNull('deleted_at')),
            ],
        ]);

        $billingClientId = $validated['billing_client_id'] ?? null;

        if ($billingClientId) {
            $alreadyLinked = Patient::where('user_id', $user->id)
                ->where('billing_client_id', $billingClientId)
                ->where('id', '!=', $patient->id)
                ->exists();

            if ($alreadyLinked) {
                return back()->withErrors([
                    'billing_client_id' => 'That billing client is already linked to another patient.',
                ]);
            }
        }

        $patient->billing_client_id = $billingClientId;
        $patient->save();

        return back()->with('success', $billingClientId
            ? 'Patient linked to billing client. Clinical data stays in the vault; invoices stay on the client.'
            : 'Billing client link removed.');
    }

    public function createBillingClient(Request $request, Patient $patient)
    {
        $user = Auth::user();
        if ($patient->user_id !== $user->id) {
            abort(403);
        }

        if ($patient->billing_client_id) {
            return back()->withErrors([
                'billing_client' => 'This patient is already linked to a billing Client.',
            ]);
        }

        if (! $user->canAddClient()) {
            return back()->withErrors([
                'billing_client' => 'Free plan allows ' . TierPolicy::FREE_CLIENT_LIFETIME_CAP . ' lifetime clients. Upgrade to Standard or Pro, or unlink/archive is not enough — deletes do not free a slot.',
            ]);
        }

        $key = MedicalVaultCrypto::keyFromSession(session('medical_vault_key'));
        if (! $key) {
            return redirect('/pro/medical/vault/unlock');
        }

        $validated = $request->validate([
            'type' => 'required|in:individual,company',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'billing_address' => 'nullable|string|max:2000',
            'id_card_number' => 'nullable|string|max:100',
            'vat_number' => 'nullable|string|max:100',
            'registration_number' => 'nullable|string|max:100',
            'contact_person' => 'nullable|string|max:255',
        ]);

        $profileData = [];
        if ($validated['type'] === 'company') {
            $profileData['vat_number'] = $validated['vat_number'] ?? null;
            $profileData['registration_number'] = $validated['registration_number'] ?? null;
            $profileData['contact_person'] = $validated['contact_person'] ?? null;
        } else {
            $profileData['id_card_number'] = $validated['id_card_number'] ?? null;
        }
        $profileData = Client::billingProfileOnly($profileData);

        DB::transaction(function () use ($user, $patient, $validated, $profileData) {
            $client = Client::create([
                'user_id' => $user->id,
                'type' => $validated['type'],
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'billing_address' => $validated['billing_address'] ?? null,
                'profile_data' => $profileData,
            ]);

            $user->increment('clients_created_count');

            $patient->billing_client_id = $client->id;
            $patient->save();
        });

        return back()->with('success', 'Billing Client created and linked. Clinical data stays in the vault; use the Client for invoices.');
    }

    public function show(Patient $patient)
    {
        $user = Auth::user();
        if ($patient->user_id !== $user->id) {
            abort(403);
        }

        $key = MedicalVaultCrypto::keyFromSession(session('medical_vault_key'));
        $payload = MedicalVaultCrypto::decrypt($patient->payload_ciphertext, $patient->payload_nonce, $key);

        $patient->load('billingClient');

        $clients = Client::where('user_id', $user->id)->orderBy('name')->get(['id', 'name']);
        $linkedClientIds = Patient::where('user_id', $user->id)
            ->whereNotNull('billing_client_id')
            ->where('id', '!=', $patient->id)
            ->pluck('billing_client_id')
            ->all();

        $attachmentsByEntry = ClinicalAttachment::where('user_id', $user->id)
            ->where('patient_id', $patient->id)
            ->orderBy('id')
            ->get()
            ->groupBy('clinical_entry_id');

        $entries = ClinicalEntry::where('user_id', $user->id)
            ->where('patient_id', $patient->id)
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->get()
            ->map(function (ClinicalEntry $entry) use ($key, $attachmentsByEntry) {
                try {
                    $data = MedicalVaultCrypto::decrypt($entry->payload_ciphertext, $entry->payload_nonce, $key);
                } catch (\Throwable) {
                    $data = ['title' => '[Unable to decrypt]', 'body' => ''];
                }

                $attachments = ($attachmentsByEntry->get($entry->id) ?? collect())->map(function (ClinicalAttachment $attachment) use ($key) {
                    try {
                        $meta = MedicalVaultCrypto::decrypt($attachment->meta_ciphertext, $attachment->meta_nonce, $key);
                    } catch (\Throwable) {
                        $meta = ['original_name' => '[Unable to decrypt]', 'mime' => 'unknown'];
                    }

                    return [
                        'id' => $attachment->id,
                        'name' => $meta['original_name'] ?? 'Attachment',
                        'mime' => $meta['mime'] ?? 'unknown',
                        'byte_size' => $attachment->byte_size,
                    ];
                });

                return [
                    'model' => $entry,
                    'title' => $data['title'] ?? 'Entry',
                    'body' => $data['body'] ?? '',
                    'type_label' => ClinicalEntry::TYPES[$entry->entry_type] ?? $entry->entry_type,
                    'attachments' => $attachments,
                    'is_stampable' => $entry->isStampable(),
                    'is_issued' => $entry->isIssued(),
                    'is_editable' => $entry->isEditable(),
                    'issued_at' => $entry->issued_at,
                    'issue_code' => $entry->issue_code,
                ];
            });

        return view('pro.medical.patients-show', [
            'patient' => $patient,
            'payload' => $payload,
            'entries' => $entries,
            'clients' => $clients,
            'linkedClientIds' => $linkedClientIds,
            'canAddClient' => $user->canAddClient(),
            'entryTypes' => ClinicalEntry::TYPES,
        ]);
    }

    public function edit(Patient $patient)
    {
        $user = Auth::user();
        if ($patient->user_id !== $user->id) {
            abort(403);
        }

        $key = MedicalVaultCrypto::keyFromSession(session('medical_vault_key'));
        if (! $key) {
            return redirect('/pro/medical/vault/unlock');
        }

        $payload = MedicalVaultCrypto::decrypt($patient->payload_ciphertext, $patient->payload_nonce, $key);

        return view('pro.medical.patients-edit', [
            'patient' => $patient,
            'payload' => $payload,
        ]);
    }

    public function update(Request $request, Patient $patient)
    {
        $user = Auth::user();
        if ($patient->user_id !== $user->id) {
            abort(403);
        }

        $vault = MedicalVault::activeForUser($user->id);
        $key = MedicalVaultCrypto::keyFromSession(session('medical_vault_key'));

        if (! $vault || ! $key) {
            return redirect('/pro/medical/vault/unlock');
        }

        $validated = $request->validate([
            'display_name' => 'required|string|max:255',
            'date_of_birth' => 'nullable|date|before_or_equal:today',
            'notes' => 'nullable|string|max:2000',
        ]);

        $encrypted = MedicalVaultCrypto::encrypt([
            'display_name' => $validated['display_name'],
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ], $key);

        $patient->payload_ciphertext = $encrypted['ciphertext'];
        $patient->payload_nonce = $encrypted['nonce'];
        $patient->save();

        return redirect('/pro/medical/patients/' . $patient->id)
            ->with('success', 'Patient record updated.');
    }
}
