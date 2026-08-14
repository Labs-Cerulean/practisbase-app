<?php

namespace App\Support;

use App\Models\User;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * Opt-in clinical specialty for Medical Practice tools.
 * General journals stay title + body. Obstetrics & Gynaecology unlocks the clerking proforma.
 */
class MedicalSpecialty
{
    public const GENERAL = '';

    public const OG = 'og';

    public const OPTIONS = [
        self::GENERAL => 'General (any specialty)',
        self::OG => 'Obstetrics & Gynaecology',
    ];

    public const CONSULT_CLERKING = 'clerking';

    public const CONSULT_FOLLOW_UP = 'follow_up';

    /** @var array<string, string> */
    public const STANDING_HISTORY_GENERIC = [
        'pmhx' => 'PMHx',
        'pshx' => 'PSHx',
        'dhx' => 'DHx',
        'shx' => 'SHx',
    ];

    /** @var array<string, string> */
    public const STANDING_HISTORY_OG = [
        'gynae_hx' => 'Gynae Hx',
        'obs_hx' => 'Obs Hx',
    ];

    /** @var array<string, string> */
    public const CONSULT_FIELDS_GENERIC = [
        'presenting_complaint' => 'Presenting complaint',
        'exam' => 'Exam',
        'plan' => 'Plan',
    ];

    /** @var array<string, string> */
    public const CONSULT_FIELDS_OG = [
        'lmp' => 'LMP',
        'ultrasound' => 'US',
    ];

    public static function normalize(?string $value): string
    {
        $value = trim((string) $value);

        return array_key_exists($value, self::OPTIONS) ? $value : self::GENERAL;
    }

    public static function isOg(?string $value): bool
    {
        return self::normalize($value) === self::OG;
    }

    public static function userIsOg(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if (($user->profession ?? '') !== 'Medical Professional') {
            return false;
        }

        return self::isOg($user->medical_specialty ?? null);
    }

    public static function label(?string $value): string
    {
        return self::OPTIONS[self::normalize($value)];
    }

    /**
     * @return array<string, string>
     */
    public static function standingHistoryLabels(bool $og): array
    {
        if ($og) {
            return array_merge(self::STANDING_HISTORY_OG, self::STANDING_HISTORY_GENERIC);
        }

        return self::STANDING_HISTORY_GENERIC;
    }

    /**
     * @return array<string, string>
     */
    public static function consultFieldLabels(bool $og): array
    {
        if ($og) {
            return array_merge(
                ['lmp' => self::CONSULT_FIELDS_OG['lmp']],
                self::CONSULT_FIELDS_GENERIC,
                ['ultrasound' => self::CONSULT_FIELDS_OG['ultrasound']],
            );
        }

        return self::CONSULT_FIELDS_GENERIC;
    }

    public static function nullableString(mixed $value, int $max = 8000): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        if (mb_strlen($text) > $max) {
            $text = mb_substr($text, 0, $max);
        }

        return $text;
    }

    /**
     * Merge chart fields into the existing encrypted patient payload.
     * Keys omitted from $incoming are left untouched so a general doctor cannot wipe O&G history.
     *
     * @param  array<string, mixed>  $existing
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    public static function mergePatientPayload(array $existing, array $incoming, bool $og): array
    {
        $payload = $existing;

        $coreKeys = [
            'display_name',
            'date_of_birth',
            'notes',
            'id_number',
            'phone',
            'address',
            'approx_age',
            'pmhx',
            'pshx',
            'dhx',
            'shx',
        ];

        foreach ($coreKeys as $key) {
            if (array_key_exists($key, $incoming)) {
                $max = in_array($key, ['display_name', 'id_number', 'phone', 'approx_age'], true) ? 255 : 8000;
                if ($key === 'address') {
                    $max = 2000;
                }
                if ($key === 'notes') {
                    $max = 2000;
                }
                $payload[$key] = self::nullableString($incoming[$key] ?? null, $max);
            }
        }

        if ($og) {
            foreach (['gynae_hx', 'obs_hx', 'lmp'] as $key) {
                if (array_key_exists($key, $incoming)) {
                    $max = $key === 'lmp' ? 255 : 8000;
                    $payload[$key] = self::nullableString($incoming[$key] ?? null, $max);
                }
            }
        }

        $name = trim((string) ($payload['display_name'] ?? ''));
        $payload['display_name'] = $name !== '' ? $name : 'Patient';

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public static function buildJournalPayload(array $validated, bool $og): array
    {
        $title = trim((string) ($validated['title'] ?? ''));
        $consultNotes = trim((string) ($validated['consult_notes'] ?? ''));
        $legacyBody = trim((string) ($validated['body'] ?? ''));

        $payload = [
            'title' => $title,
            'body' => $legacyBody,
        ];

        if (! $og) {
            return $payload;
        }

        $kind = ($validated['consult_kind'] ?? '') === self::CONSULT_CLERKING
            ? self::CONSULT_CLERKING
            : self::CONSULT_FOLLOW_UP;

        $structured = [
            'consult_kind' => $kind,
            'lmp' => self::nullableString($validated['lmp'] ?? null, 255),
            'presenting_complaint' => self::nullableString($validated['presenting_complaint'] ?? null),
            'exam' => self::nullableString($validated['exam'] ?? null),
            'ultrasound' => self::nullableString($validated['ultrasound'] ?? null),
            'plan' => self::nullableString($validated['plan'] ?? null),
            'consult_notes' => self::nullableString($consultNotes !== '' ? $consultNotes : null),
        ];

        if ($title === '') {
            $title = self::defaultJournalTitle($structured);
        }

        $payload['title'] = $title;
        $payload['body'] = self::composeConsultBody($structured);
        $payload = array_merge($payload, $structured);

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    public static function defaultJournalTitle(array $fields): string
    {
        $pc = trim(preg_replace('/\s+/u', ' ', (string) ($fields['presenting_complaint'] ?? '')) ?? '');
        if ($pc !== '') {
            return mb_strlen($pc) > 80 ? mb_substr($pc, 0, 77).'…' : $pc;
        }

        return (($fields['consult_kind'] ?? '') === self::CONSULT_CLERKING) ? 'Consult' : 'Follow-up';
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    public static function composeConsultBody(array $fields): string
    {
        $chunks = [];
        foreach (self::consultFieldLabels(true) as $key => $label) {
            $value = trim((string) ($fields[$key] ?? ''));
            if ($value !== '') {
                $chunks[] = $label.":\n".$value;
            }
        }

        $notes = trim((string) ($fields['consult_notes'] ?? ''));
        if ($notes !== '') {
            $chunks[] = "Notes:\n".$notes;
        }

        return implode("\n\n", $chunks);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function journalHasContent(array $validated, bool $og): bool
    {
        foreach (['title', 'body', 'consult_notes'] as $key) {
            if (trim((string) ($validated[$key] ?? '')) !== '') {
                return true;
            }
        }

        if (! $og) {
            return false;
        }

        foreach (['lmp', 'presenting_complaint', 'exam', 'ultrasound', 'plan'] as $key) {
            if (trim((string) ($validated[$key] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function hasStructuredConsult(array $payload): bool
    {
        if (($payload['consult_kind'] ?? '') === self::CONSULT_CLERKING
            || ($payload['consult_kind'] ?? '') === self::CONSULT_FOLLOW_UP) {
            return true;
        }

        foreach (['lmp', 'presenting_complaint', 'exam', 'ultrasound', 'plan', 'consult_notes'] as $key) {
            if (trim((string) ($payload[$key] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{key: string, label: string, value: string}>
     */
    public static function filledConsultRows(array $payload): array
    {
        $rows = [];
        foreach (self::consultFieldLabels(true) as $key => $label) {
            $value = trim((string) ($payload[$key] ?? ''));
            if ($value !== '') {
                $rows[] = ['key' => $key, 'label' => $label, 'value' => $value];
            }
        }

        $notes = trim((string) ($payload['consult_notes'] ?? ''));
        if ($notes !== '') {
            $rows[] = ['key' => 'consult_notes', 'label' => 'Notes', 'value' => $notes];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{key: string, label: string, value: string}>
     */
    public static function filledHistoryRows(array $payload, bool $og): array
    {
        $rows = [];
        foreach (self::standingHistoryLabels($og) as $key => $label) {
            $value = trim((string) ($payload[$key] ?? ''));
            if ($value !== '') {
                $rows[] = ['key' => $key, 'label' => $label, 'value' => $value];
            }
        }

        return $rows;
    }

    /**
     * Standing-history keys submitted with a full clerking, to copy onto the patient chart.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public static function standingHistoryFromConsult(array $validated, bool $og): array
    {
        $incoming = [];
        foreach (array_keys(self::STANDING_HISTORY_GENERIC) as $key) {
            if (array_key_exists($key, $validated)) {
                $incoming[$key] = $validated[$key];
            }
        }

        if ($og) {
            foreach (array_keys(self::STANDING_HISTORY_OG) as $key) {
                if (array_key_exists($key, $validated)) {
                    $incoming[$key] = $validated[$key];
                }
            }
            if (array_key_exists('lmp', $validated) && trim((string) $validated['lmp']) !== '') {
                $incoming['lmp'] = $validated['lmp'];
            }
        }

        return $incoming;
    }

    public static function ageLabel(?string $dateOfBirth, ?string $approxAge = null, ?DateTimeInterface $asOf = null): ?string
    {
        $dob = trim((string) $dateOfBirth);
        if ($dob !== '') {
            $datePart = substr($dob, 0, 10);
            $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $datePart);
            if ($parsed instanceof DateTimeImmutable && $parsed->format('Y-m-d') === $datePart) {
                $asOfDate = $asOf
                    ? DateTimeImmutable::createFromInterface($asOf)->setTime(0, 0)
                    : new DateTimeImmutable('today');
                if ($parsed <= $asOfDate) {
                    return (string) $parsed->diff($asOfDate)->y;
                }
            }
        }

        $approx = trim((string) $approxAge);

        return $approx !== '' ? $approx : null;
    }

    public static function consultKindLabel(?string $kind): string
    {
        return $kind === self::CONSULT_CLERKING ? 'Full clerking' : 'Follow-up';
    }
}
