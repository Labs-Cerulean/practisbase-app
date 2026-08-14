<?php

namespace App\Support;

/**
 * Specialty-aware clinical note field definitions.
 * Structured values live inside the encrypted vault payload only —
 * never as cleartext specialty columns.
 */
class ClinicalNoteTemplates
{
    public const GENERAL = 'general';

    public const GYNAE_OBS = 'gynae_obs';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::GENERAL => 'General consult',
            self::GYNAE_OBS => 'Gynae / Obs consult',
        ];
    }

    public static function normalize(?string $template): string
    {
        $key = $template ?: self::GENERAL;

        return array_key_exists($key, self::options()) ? $key : self::GENERAL;
    }

    /**
     * Core fields shared by all doctors, plus specialty add-ons.
     *
     * @return array<string, array{label: string, rows: int, specialty?: bool}>
     */
    public static function fields(string $template): array
    {
        $template = self::normalize($template);

        $core = [
            'presenting_complaint' => ['label' => 'Presenting complaint', 'rows' => 3],
            'pmhx' => ['label' => 'PMHx', 'rows' => 2],
            'pshx' => ['label' => 'PSHx', 'rows' => 2],
            'dhx' => ['label' => 'DHx', 'rows' => 2],
            'shx' => ['label' => 'SHx', 'rows' => 2],
            'exam' => ['label' => 'Exam', 'rows' => 3],
            'plan' => ['label' => 'Plan', 'rows' => 3],
        ];

        if ($template === self::GYNAE_OBS) {
            return [
                'lmp' => ['label' => 'LMP', 'rows' => 1, 'specialty' => true],
                'presenting_complaint' => $core['presenting_complaint'],
                'gynae_hx' => ['label' => 'Gynae Hx', 'rows' => 3, 'specialty' => true],
                'obs_hx' => ['label' => 'Obs Hx', 'rows' => 3, 'specialty' => true],
                'pmhx' => $core['pmhx'],
                'pshx' => $core['pshx'],
                'dhx' => $core['dhx'],
                'shx' => $core['shx'],
                'exam' => $core['exam'],
                'us' => ['label' => 'US', 'rows' => 3, 'specialty' => true],
                'plan' => $core['plan'],
            ];
        }

        return $core;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, string>
     */
    public static function extractFields(string $template, array $input): array
    {
        $out = [];

        foreach (array_keys(self::fields($template)) as $key) {
            $value = $input[$key] ?? '';
            if (! is_string($value)) {
                $value = is_scalar($value) ? (string) $value : '';
            }
            $out[$key] = trim($value);
        }

        return $out;
    }

    /**
     * Build a readable body for PDF / backup / legacy viewers.
     *
     * @param  array<string, string>  $fields
     */
    public static function composeBody(string $template, array $fields, ?string $extraBody = null): string
    {
        $lines = [];

        foreach (self::fields($template) as $key => $meta) {
            $value = trim((string) ($fields[$key] ?? ''));
            if ($value === '') {
                continue;
            }
            $lines[] = $meta['label'] . ":\n" . $value;
        }

        $extra = trim((string) $extraBody);
        if ($extra !== '') {
            $lines[] = $extra;
        }

        return implode("\n\n", $lines);
    }

    /**
     * @param  array<string, string>  $fields
     * @return array{title: string, body: string, template: string, fields: array<string, string>, extra: string}
     */
    public static function buildPayload(string $template, string $title, array $fields, ?string $extraBody = null): array
    {
        $template = self::normalize($template);
        $cleanFields = self::extractFields($template, $fields);
        $extra = trim((string) $extraBody);

        return [
            'title' => $title,
            'body' => self::composeBody($template, $cleanFields, $extra !== '' ? $extra : null),
            'template' => $template,
            'fields' => $cleanFields,
            'extra' => $extra,
        ];
    }
}
