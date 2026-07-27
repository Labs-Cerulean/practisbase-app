<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClinicalEntry extends Model
{
    protected $fillable = [
        'user_id',
        'vault_id',
        'patient_id',
        'entry_type',
        'entry_date',
        'payload_ciphertext',
        'payload_nonce',
        'issued_at',
        'issued_by_user_id',
        'issue_code',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'issued_at' => 'datetime',
        ];
    }

    public const TYPES = [
        'journal' => 'Journal note',
        'prescription' => 'Digital prescription',
        'referral' => 'Referral letter',
        'certificate' => 'Medical certificate',
    ];

    /** Certificate / declaration kinds stored in encrypted payload (Medical). */
    public const CERTIFICATE_KINDS = [
        'certificate' => 'Certificate',
        'declaration' => 'Declaration',
        'attestation' => 'Attestation',
        'medical_certificate' => 'Medical certificate',
        'fitness' => 'Fitness / clearance',
        'other' => 'Other',
    ];

    /** Types that become immutable after Stamp & issue. */
    public const STAMPABLE_TYPES = [
        'prescription',
        'referral',
        'certificate',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ClinicalAttachment::class);
    }

    public function isStampable(): bool
    {
        return in_array($this->entry_type, self::STAMPABLE_TYPES, true);
    }

    public function isIssued(): bool
    {
        return $this->isStampable() && $this->issued_at !== null;
    }

    public function isEditable(): bool
    {
        return ! $this->isIssued();
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->entry_type] ?? $this->entry_type;
    }

    public static function certificateKindLabel(?string $kind): string
    {
        if ($kind === null || $kind === '') {
            return self::CERTIFICATE_KINDS['medical_certificate'];
        }

        return self::CERTIFICATE_KINDS[$kind] ?? $kind;
    }

    /**
     * Normalise prescription medicines from encrypted payload.
     * Legacy single title/body prescriptions become one medicine line.
     *
     * @param  array<string, mixed>  $payload
     * @return list<array{name: string, strength: string, dose: string, quantity: string, instructions: string}>
     */
    public static function medicinesFromPayload(array $payload): array
    {
        $raw = $payload['medicines'] ?? null;
        if (is_array($raw) && count($raw) > 0) {
            $out = [];
            foreach ($raw as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $name = trim((string) ($row['name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $out[] = [
                    'name' => $name,
                    'strength' => trim((string) ($row['strength'] ?? '')),
                    'dose' => trim((string) ($row['dose'] ?? '')),
                    'quantity' => trim((string) ($row['quantity'] ?? '')),
                    'instructions' => trim((string) ($row['instructions'] ?? '')),
                ];
            }
            if ($out !== []) {
                return $out;
            }
        }

        $title = trim((string) ($payload['title'] ?? ''));
        $body = trim((string) ($payload['body'] ?? ''));
        if ($title === '' && $body === '') {
            return [];
        }

        return [[
            'name' => $title !== '' ? $title : 'Medicine',
            'strength' => '',
            'dose' => '',
            'quantity' => '',
            'instructions' => $body,
        ]];
    }

    public static function prescriptionSummaryTitle(array $medicines, ?string $explicitTitle = null): string
    {
        $explicit = trim((string) $explicitTitle);
        if ($explicit !== '') {
            return $explicit;
        }

        $count = count($medicines);
        if ($count === 0) {
            return 'Prescription';
        }
        if ($count === 1) {
            return $medicines[0]['name'];
        }

        return 'Prescription (' . $count . ' medicines)';
    }
}
