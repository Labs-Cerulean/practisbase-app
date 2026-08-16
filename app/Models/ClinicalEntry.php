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
        'journal' => 'Patient notes',
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

    /**
     * Shared professional colour tokens for clinical entry / stampable cards.
     *
     * @return array{accent: string, badge_bg: string, badge_fg: string, card_bg: string, border: string, soft: string}
     */
    public static function typeChrome(string $entryType): array
    {
        return match ($entryType) {
            'prescription' => [
                'accent' => '#1d4ed8',
                'badge_bg' => '#1d4ed8',
                'badge_fg' => '#ffffff',
                'card_bg' => '#eff6ff',
                'border' => '#93c5fd',
                'soft' => '#dbeafe',
            ],
            'referral' => [
                'accent' => '#0e7490',
                'badge_bg' => '#0e7490',
                'badge_fg' => '#ffffff',
                'card_bg' => '#ecfeff',
                'border' => '#67e8f9',
                'soft' => '#cffafe',
            ],
            'certificate' => [
                'accent' => '#15803d',
                'badge_bg' => '#15803d',
                'badge_fg' => '#ffffff',
                'card_bg' => '#f0fdf4',
                'border' => '#86efac',
                'soft' => '#dcfce7',
            ],
            'legacy_certificate' => [
                'accent' => '#a16207',
                'badge_bg' => '#a16207',
                'badge_fg' => '#ffffff',
                'card_bg' => '#fffbeb',
                'border' => '#fcd34d',
                'soft' => '#fef3c7',
            ],
            default => [
                'accent' => '#64748b',
                'badge_bg' => '#e2e8f0',
                'badge_fg' => '#0f172a',
                'card_bg' => '#f8fafc',
                'border' => '#cbd5e1',
                'soft' => '#f1f5f9',
            ],
        };
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
