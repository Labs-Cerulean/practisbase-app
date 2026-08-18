<?php

namespace App\Support;

use App\Models\ClinicalNoteTemplate;
use App\Models\User;

/**
 * Built-in starters + doctor-owned journal templates.
 * Field values stay in encrypted vault payloads; template labels/structure are UI config.
 */
class ClinicalNoteTemplates
{
    public const GENERAL = 'general';

    public const GYNAE = 'gynae';

    public const OBS = 'obs';

    /** @deprecated Kept so older notes with template=gynae_obs still resolve. */
    public const GYNAE_OBS = 'gynae_obs';

    public const FIELD_TYPES = ['text', 'date', 'bullets'];

    /**
     * Starters shown in the template picker (not including legacy aliases).
     *
     * @return array<string, string>
     */
    public static function builtinOptions(): array
    {
        return [
            self::GENERAL => 'General consult',
            self::GYNAE => 'Gynae consult',
            self::OBS => 'Obs consult',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function allBuiltinLabels(): array
    {
        return self::builtinOptions() + [
            self::GYNAE_OBS => 'Gynae / Obs consult',
        ];
    }

    /**
     * @return array<string, array{label: string, type: string}>
     */
    public static function builtinFields(string $key): array
    {
        $core = [
            'presenting_complaint' => ['label' => 'Presenting complaint', 'type' => 'text'],
            'pmhx' => ['label' => 'PMHx', 'type' => 'text'],
            'pshx' => ['label' => 'PSHx', 'type' => 'text'],
            'dhx' => ['label' => 'DHx', 'type' => 'text'],
            'shx' => ['label' => 'SHx', 'type' => 'text'],
            'exam' => ['label' => 'Exam', 'type' => 'text'],
            'plan' => ['label' => 'Plan', 'type' => 'bullets'],
        ];

        if ($key === self::GYNAE) {
            return [
                'lmp' => ['label' => 'LMP', 'type' => 'date'],
                'presenting_complaint' => $core['presenting_complaint'],
                'gynae_hx' => ['label' => 'Gynae Hx', 'type' => 'text'],
                'pmhx' => $core['pmhx'],
                'pshx' => $core['pshx'],
                'dhx' => $core['dhx'],
                'shx' => $core['shx'],
                'exam' => $core['exam'],
                'us' => ['label' => 'US', 'type' => 'text'],
                'plan' => $core['plan'],
            ];
        }

        if ($key === self::OBS) {
            return [
                'lmp' => ['label' => 'LMP', 'type' => 'date'],
                'edd' => ['label' => 'EDD', 'type' => 'date'],
                'presenting_complaint' => $core['presenting_complaint'],
                'obs_hx' => ['label' => 'Obs Hx', 'type' => 'text'],
                'pmhx' => $core['pmhx'],
                'pshx' => $core['pshx'],
                'dhx' => $core['dhx'],
                'shx' => $core['shx'],
                'exam' => $core['exam'],
                'us' => ['label' => 'US', 'type' => 'text'],
                'plan' => $core['plan'],
            ];
        }

        if ($key === self::GYNAE_OBS) {
            return [
                'lmp' => ['label' => 'LMP', 'type' => 'date'],
                'presenting_complaint' => $core['presenting_complaint'],
                'gynae_hx' => ['label' => 'Gynae Hx', 'type' => 'text'],
                'obs_hx' => ['label' => 'Obs Hx', 'type' => 'text'],
                'pmhx' => $core['pmhx'],
                'pshx' => $core['pshx'],
                'dhx' => $core['dhx'],
                'shx' => $core['shx'],
                'exam' => $core['exam'],
                'us' => ['label' => 'US', 'type' => 'text'],
                'plan' => $core['plan'],
            ];
        }

        return $core;
    }

    public static function customKey(int $id): string
    {
        return 'custom:' . $id;
    }

    public static function isCustomKey(?string $key): bool
    {
        return is_string($key) && preg_match('/^custom:\d+$/', $key) === 1;
    }

    public static function customIdFromKey(?string $key): ?int
    {
        if (! self::isCustomKey($key)) {
            return null;
        }

        return (int) substr((string) $key, 7);
    }

    /**
     * Catalogue for a doctor: built-in starters + their custom templates.
     *
     * @return list<array{key: string, name: string, builtin: bool, fields: list<array{key: string, label: string, type: string}>}>
     */
    public static function catalogueForUser(User $user): array
    {
        $out = [];

        foreach (self::builtinOptions() as $key => $name) {
            $out[] = [
                'key' => $key,
                'name' => $name,
                'builtin' => true,
                'fields' => self::fieldsListFromMap(self::builtinFields($key)),
            ];
        }

        $customs = ClinicalNoteTemplate::where('user_id', $user->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        foreach ($customs as $template) {
            $out[] = [
                'key' => self::customKey((int) $template->id),
                'name' => $template->name,
                'builtin' => false,
                'fields' => $template->normalizedFields(),
            ];
        }

        return $out;
    }

    /**
     * Catalogue plus a legacy builtin when editing an older note that still uses it.
     *
     * @return list<array{key: string, name: string, builtin: bool, fields: list<array{key: string, label: string, type: string}>}>
     */
    public static function catalogueForUserIncluding(?string $extraKey, User $user): array
    {
        $catalogue = self::catalogueForUser($user);
        if ($extraKey === self::GYNAE_OBS) {
            foreach ($catalogue as $row) {
                if ($row['key'] === self::GYNAE_OBS) {
                    return $catalogue;
                }
            }
            $catalogue[] = [
                'key' => self::GYNAE_OBS,
                'name' => self::allBuiltinLabels()[self::GYNAE_OBS] . ' (legacy)',
                'builtin' => true,
                'fields' => self::fieldsListFromMap(self::builtinFields(self::GYNAE_OBS)),
            ];
        }

        return $catalogue;
    }

    /**
     * @return array<string, string>
     */
    public static function optionsForUser(User $user): array
    {
        $options = [];
        foreach (self::catalogueForUser($user) as $row) {
            $options[$row['key']] = $row['name'];
        }

        return $options;
    }

    /**
     * @return array{key: string, name: string, builtin: bool, fields: list<array{key: string, label: string, type: string}>}
     */
    public static function resolveForUser(User $user, ?string $key): array
    {
        $catalogue = self::catalogueForUser($user);
        $wanted = $key ?: self::GENERAL;

        // Older notes still carry the combined starter key.
        if ($wanted === self::GYNAE_OBS) {
            return [
                'key' => self::GYNAE_OBS,
                'name' => self::allBuiltinLabels()[self::GYNAE_OBS],
                'builtin' => true,
                'fields' => self::fieldsListFromMap(self::builtinFields(self::GYNAE_OBS)),
            ];
        }

        foreach ($catalogue as $row) {
            if ($row['key'] === $wanted) {
                return $row;
            }
        }

        return $catalogue[0];
    }

    public static function normalizeForUser(User $user, ?string $key): string
    {
        $key = self::migrateBuiltinKey($key);

        return self::resolveForUser($user, $key)['key'];
    }

    /**
     * Legacy helpers used where only builtins are needed (e.g. import).
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return self::builtinOptions();
    }

    public static function normalize(?string $template): string
    {
        $key = self::migrateBuiltinKey($template ?: self::GENERAL);
        $known = self::allBuiltinLabels();

        return array_key_exists($key, $known) ? $key : self::GENERAL;
    }

    /**
     * Map retired combined key to Gynae when picking a default for new notes.
     */
    public static function migrateBuiltinKey(?string $key): string
    {
        if ($key === self::GYNAE_OBS) {
            return self::GYNAE;
        }

        return $key ?: self::GENERAL;
    }

    /**
     * @return array<string, array{label: string, type: string}>
     */
    public static function fields(string $template): array
    {
        return self::builtinFields(self::normalize($template));
    }

    /**
     * @param  list<array{key: string, label: string, type?: string}>  $fieldDefs
     * @param  array<string, mixed>  $input
     * @return array<string, string>
     */
    public static function extractFieldsFromDefs(array $fieldDefs, array $input): array
    {
        $out = [];

        foreach ($fieldDefs as $def) {
            $key = $def['key'];
            $value = $input[$key] ?? '';
            if (! is_string($value)) {
                $value = is_scalar($value) ? (string) $value : '';
            }
            $out[$key] = trim($value);
        }

        return $out;
    }

    /**
     * @param  list<array{key: string, label: string, type?: string}>  $fieldDefs
     * @param  array<string, string>  $fields
     */
    public static function composeBodyFromDefs(array $fieldDefs, array $fields, ?string $extraBody = null): string
    {
        $lines = [];

        foreach ($fieldDefs as $def) {
            $value = trim((string) ($fields[$def['key']] ?? ''));
            if ($value === '') {
                continue;
            }
            $type = self::normalizeFieldType($def['type'] ?? 'text');
            if ($type === 'bullets') {
                $value = self::formatBullets($value);
            } elseif ($type === 'date') {
                $value = self::formatDateValue($value);
            }
            $lines[] = $def['label'] . ":\n" . $value;
        }

        $extra = trim((string) $extraBody);
        if ($extra !== '') {
            $lines[] = $extra;
        }

        return implode("\n\n", $lines);
    }

    /**
     * @param  list<array{key: string, label: string, type?: string}>  $fieldDefs
     * @param  array<string, string>  $fields
     * @return array{
     *   title: string,
     *   body: string,
     *   template: string,
     *   template_name: string,
     *   fields: array<string, string>,
     *   field_defs: list<array{key: string, label: string, type: string}>,
     *   extra: string
     * }
     */
    public static function buildPayloadFromResolved(array $resolved, string $title, array $fields, ?string $extraBody = null): array
    {
        $cleanFields = self::extractFieldsFromDefs($resolved['fields'], $fields);
        $extra = trim((string) $extraBody);

        return [
            'title' => $title,
            'body' => self::composeBodyFromDefs($resolved['fields'], $cleanFields, $extra !== '' ? $extra : null),
            'template' => $resolved['key'],
            'template_name' => $resolved['name'],
            'fields' => $cleanFields,
            'field_defs' => $resolved['fields'],
            'extra' => $extra,
        ];
    }

    /**
     * Back-compat for builtin-only callers (import / older code paths).
     *
     * @param  array<string, mixed>  $input
     * @return array<string, string>
     */
    public static function extractFields(string $template, array $input): array
    {
        return self::extractFieldsFromDefs(self::fieldsListFromMap(self::fields($template)), $input);
    }

    /**
     * @param  array<string, string>  $fields
     */
    public static function composeBody(string $template, array $fields, ?string $extraBody = null): string
    {
        return self::composeBodyFromDefs(self::fieldsListFromMap(self::fields($template)), $fields, $extraBody);
    }

    /**
     * @param  array<string, string>  $fields
     * @return array{title: string, body: string, template: string, template_name: string, fields: array<string, string>, field_defs: list<array{key: string, label: string, type: string}>, extra: string}
     */
    public static function buildPayload(string $template, string $title, array $fields, ?string $extraBody = null): array
    {
        $key = self::normalize($template);
        $resolved = [
            'key' => $key,
            'name' => self::allBuiltinLabels()[$key] ?? self::builtinOptions()[self::GENERAL],
            'builtin' => true,
            'fields' => self::fieldsListFromMap(self::builtinFields($key)),
        ];

        return self::buildPayloadFromResolved($resolved, $title, $fields, $extraBody);
    }

    /**
     * @param  array<string, array{label: string, type?: string, rows?: int}>  $map
     * @return list<array{key: string, label: string, type: string}>
     */
    public static function fieldsListFromMap(array $map): array
    {
        $out = [];
        foreach ($map as $key => $meta) {
            $out[] = [
                'key' => (string) $key,
                'label' => (string) ($meta['label'] ?? $key),
                'type' => self::normalizeFieldType($meta['type'] ?? 'text'),
            ];
        }

        return $out;
    }

    /**
     * Normalize posted custom field definitions from the template builder.
     *
     * @param  mixed  $raw
     * @return list<array{key: string, label: string, type: string}>
     */
    public static function sanitizeFieldDefinitions(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        $usedKeys = [];

        foreach ($raw as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $label = trim((string) ($row['label'] ?? ''));
            if ($label === '') {
                continue;
            }

            $key = trim((string) ($row['key'] ?? ''));
            if ($key === '' || ! preg_match('/^[a-z][a-z0-9_]{0,40}$/', $key)) {
                $key = self::slugKey($label, $index);
            }

            $base = $key;
            $n = 2;
            while (isset($usedKeys[$key])) {
                $key = $base . '_' . $n;
                $n++;
            }
            $usedKeys[$key] = true;

            $out[] = [
                'key' => $key,
                'label' => mb_substr($label, 0, 80),
                'type' => self::normalizeFieldType($row['type'] ?? 'text'),
            ];
        }

        return $out;
    }

    public static function normalizeFieldType(mixed $type): string
    {
        $type = strtolower(trim((string) $type));

        return in_array($type, self::FIELD_TYPES, true) ? $type : 'text';
    }

    public static function formatBullets(string $value): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $value) ?: [];
        $out = [];
        $n = 1;
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $line = preg_replace('/^\d+[\.\)]\s*/', '', $line) ?? $line;
            // PCRE2 needs \x{2022} for the bullet character (not \u2022).
            $line = preg_replace('/^[\-\*\x{2022}]\s*/u', '', $line) ?? $line;
            $out[] = $n . '. ' . trim($line);
            $n++;
        }

        return $out === [] ? trim($value) : implode("\n", $out);
    }

    public static function formatDateValue(string $value): string
    {
        try {
            return \Illuminate\Support\Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable) {
            return $value;
        }
    }

    private static function slugKey(string $label, int $index): string
    {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $label) ?? '');
        $slug = trim($slug, '_');
        if ($slug === '' || ! preg_match('/^[a-z]/', $slug)) {
            $slug = 'field_' . ($index + 1);
        }

        return mb_substr($slug, 0, 40);
    }
}
