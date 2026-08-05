<?php

namespace App\Support;

/**
 * Generic engineer specialised report body.
 * One builder covers fire, noise, ventilation, lighting, etc. via flexible blocks.
 */
class EngineerReportBlueprint
{
    /**
     * @return array{id: string, item: string, outcome: string, comments: string}
     */
    public static function blankChecklistRow(): array
    {
        return [
            'id' => PracticeDocumentContext::newRowId('c'),
            'item' => '',
            'outcome' => '',
            'comments' => '',
        ];
    }

    /**
     * @return array{id: string, location: string, parameter: string, reading: string, unit: string, limit: string, result: string}
     */
    public static function blankMeasurementRow(): array
    {
        return [
            'id' => PracticeDocumentContext::newRowId('m'),
            'location' => '',
            'parameter' => '',
            'reading' => '',
            'unit' => '',
            'limit' => '',
            'result' => '',
        ];
    }

    /**
     * Optional helper items — not seeded into new reports.
     *
     * @return list<string>
     */
    public static function commonChecklistItems(string $starterKey): array
    {
        return match ($starterKey) {
            'fire' => [
                'Means of escape / travel distances',
                'Fire detection / alarm provision',
                'Emergency lighting',
                'Fire-fighting equipment / access',
                'Compartmentation / fire doors',
                'Signage',
            ],
            'noise' => [
                'Meter calibration verified',
                'Background / ambient measured',
                'Source operating conditions noted',
            ],
            'ventilation' => [
                'Supply / extract operation',
                'Filters / coils condition',
                'Controls / interlocks',
                'Fresh air provision vs design',
                'Transfer / make-up paths',
            ],
            'lighting' => [
                'General illuminance adequacy',
                'Uniformity',
                'Glare / veiling reflections',
                'Emergency lighting (if applicable)',
                'Controls / switching',
            ],
            default => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    public static function emptyPayload(): array
    {
        return [
            'subject_heading' => 'Survey particulars',
            'attributes' => [
                ['label' => 'Premises use', 'value' => ''],
                ['label' => 'Standards / references', 'value' => ''],
            ],
            'highlight_label' => 'Overall conclusion',
            'highlight_value' => '',
            'include_checklist' => false,
            'include_measurements' => false,
            'checklist_heading' => 'Inspection checklist',
            'checklist' => [self::blankChecklistRow()],
            'measurements_heading' => 'Measurements / readings',
            'measurements' => [self::blankMeasurementRow()],
            'sections' => [
                ['heading' => 'Scope of survey', 'body' => ''],
                ['heading' => 'Findings', 'body' => ''],
                ['heading' => 'Recommendations', 'body' => ''],
            ],
            'legal_footer' => '',
        ];
    }

    /**
     * Optional starters — seed the same generic form, not separate report products.
     *
     * @return array<string, array{label: string, title: string, payload: array<string, mixed>}>
     */
    public static function starters(): array
    {
        return [
            'blank' => [
                'label' => 'Blank',
                'title' => 'Specialised report',
                'payload' => self::emptyPayload(),
            ],
            'fire' => [
                'label' => 'Fire safety',
                'title' => 'Fire safety report',
                'payload' => [
                    'subject_heading' => 'Survey particulars',
                    'attributes' => [
                        ['label' => 'Premises use', 'value' => ''],
                        ['label' => 'Occupancy / capacity', 'value' => ''],
                        ['label' => 'Number of storeys', 'value' => ''],
                        ['label' => 'Standards referenced', 'value' => ''],
                        ['label' => 'PA / permission reference', 'value' => ''],
                    ],
                    'highlight_label' => 'Overall conclusion',
                    'highlight_value' => '',
                    'include_checklist' => true,
                    'include_measurements' => false,
                    'checklist_heading' => 'Fire safety inspection items',
                    'checklist' => [self::blankChecklistRow()],
                    'measurements_heading' => 'Measurements / readings',
                    'measurements' => [self::blankMeasurementRow()],
                    'sections' => [
                        ['heading' => 'Scope of survey', 'body' => 'Means of escape and fire safety provisions inspected for the areas listed in scope.'],
                        ['heading' => 'Findings', 'body' => ''],
                        ['heading' => 'Recommendations', 'body' => ''],
                    ],
                    'legal_footer' => 'Prepared for the exclusive use of the client. Third parties shall not rely upon this document. Formulated on information available at the time of survey.',
                ],
            ],
            'noise' => [
                'label' => 'Noise',
                'title' => 'Noise assessment report',
                'payload' => [
                    'subject_heading' => 'Survey particulars',
                    'attributes' => [
                        ['label' => 'Source / activity assessed', 'value' => ''],
                        ['label' => 'Receptor locations', 'value' => ''],
                        ['label' => 'Instrument / meter', 'value' => ''],
                        ['label' => 'Calibration reference', 'value' => ''],
                        ['label' => 'Weather / wind', 'value' => ''],
                        ['label' => 'Standards / criteria referenced', 'value' => ''],
                    ],
                    'highlight_label' => 'Overall conclusion',
                    'highlight_value' => '',
                    'include_checklist' => true,
                    'include_measurements' => true,
                    'checklist_heading' => 'Assessment checks',
                    'checklist' => [self::blankChecklistRow()],
                    'measurements_heading' => 'Noise measurements',
                    'measurements' => [self::blankMeasurementRow()],
                    'sections' => [
                        ['heading' => 'Methodology', 'body' => ''],
                        ['heading' => 'Findings', 'body' => ''],
                        ['heading' => 'Conclusions & recommendations', 'body' => ''],
                    ],
                    'legal_footer' => 'Measurements reflect conditions at the time of survey only. Prepared for the exclusive use of the client.',
                ],
            ],
            'ventilation' => [
                'label' => 'Ventilation',
                'title' => 'Ventilation assessment report',
                'payload' => [
                    'subject_heading' => 'Survey particulars',
                    'attributes' => [
                        ['label' => 'System type', 'value' => ''],
                        ['label' => 'Areas served', 'value' => ''],
                        ['label' => 'Design occupancy', 'value' => ''],
                        ['label' => 'Standards referenced', 'value' => ''],
                        ['label' => 'Instrument / method', 'value' => ''],
                    ],
                    'highlight_label' => 'Overall conclusion',
                    'highlight_value' => '',
                    'include_checklist' => true,
                    'include_measurements' => true,
                    'checklist_heading' => 'Ventilation inspection items',
                    'checklist' => [self::blankChecklistRow()],
                    'measurements_heading' => 'Airflow / ventilation readings',
                    'measurements' => [self::blankMeasurementRow()],
                    'sections' => [
                        ['heading' => 'Scope of survey', 'body' => ''],
                        ['heading' => 'Findings', 'body' => ''],
                        ['heading' => 'Recommendations', 'body' => ''],
                    ],
                    'legal_footer' => 'Prepared for the exclusive use of the client. Readings reflect conditions at the time of survey only.',
                ],
            ],
            'lighting' => [
                'label' => 'Lighting',
                'title' => 'Lighting assessment report',
                'payload' => [
                    'subject_heading' => 'Survey particulars',
                    'attributes' => [
                        ['label' => 'Areas assessed', 'value' => ''],
                        ['label' => 'Task / activity', 'value' => ''],
                        ['label' => 'Instrument / meter', 'value' => ''],
                        ['label' => 'Standards / target lux', 'value' => ''],
                        ['label' => 'Daylight contribution', 'value' => ''],
                    ],
                    'highlight_label' => 'Overall conclusion',
                    'highlight_value' => '',
                    'include_checklist' => true,
                    'include_measurements' => true,
                    'checklist_heading' => 'Lighting inspection items',
                    'checklist' => [self::blankChecklistRow()],
                    'measurements_heading' => 'Illuminance readings',
                    'measurements' => [self::blankMeasurementRow()],
                    'sections' => [
                        ['heading' => 'Methodology', 'body' => ''],
                        ['heading' => 'Findings', 'body' => ''],
                        ['heading' => 'Recommendations', 'body' => ''],
                    ],
                    'legal_footer' => 'Prepared for the exclusive use of the client. Readings reflect conditions at the time of survey only.',
                ],
            ],
        ];
    }

    /**
     * @param  mixed  $raw
     * @return array<string, mixed>
     */
    public static function normalize(mixed $raw): array
    {
        $base = self::emptyPayload();
        if (! is_array($raw)) {
            return $base;
        }

        $base['subject_heading'] = trim((string) ($raw['subject_heading'] ?? $base['subject_heading']));
        $base['highlight_label'] = trim((string) ($raw['highlight_label'] ?? 'Overall conclusion')) ?: 'Overall conclusion';
        $base['highlight_value'] = trim((string) ($raw['highlight_value'] ?? ''));
        $base['include_checklist'] = filter_var($raw['include_checklist'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $base['include_measurements'] = filter_var($raw['include_measurements'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $base['checklist_heading'] = trim((string) ($raw['checklist_heading'] ?? $base['checklist_heading']));
        $base['measurements_heading'] = trim((string) ($raw['measurements_heading'] ?? $base['measurements_heading']));
        $base['legal_footer'] = trim((string) ($raw['legal_footer'] ?? ''));

        $attributes = [];
        foreach (($raw['attributes'] ?? []) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $label = trim((string) ($row['label'] ?? ''));
            $value = trim((string) ($row['value'] ?? ''));
            if ($label === '' && $value === '') {
                continue;
            }
            $attributes[] = ['label' => $label, 'value' => $value];
        }
        $base['attributes'] = $attributes !== [] ? $attributes : $base['attributes'];

        $checklist = [];
        foreach (($raw['checklist'] ?? []) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $item = trim((string) ($row['item'] ?? ''));
            $outcome = trim((string) ($row['outcome'] ?? ''));
            $comments = trim((string) ($row['comments'] ?? ''));
            $id = trim((string) ($row['id'] ?? ''));
            if ($id === '' || ! preg_match('/^[A-Za-z0-9_\-]{2,32}$/', $id)) {
                $id = PracticeDocumentContext::newRowId('c');
            }
            $checklist[] = ['id' => $id, 'item' => $item, 'outcome' => $outcome, 'comments' => $comments];
        }
        $base['checklist'] = $checklist !== [] ? $checklist : [self::blankChecklistRow()];
        if (! $base['include_checklist']) {
            $base['checklist'] = [];
        }

        $measurements = [];
        foreach (($raw['measurements'] ?? []) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $location = trim((string) ($row['location'] ?? ''));
            $parameter = trim((string) ($row['parameter'] ?? ''));
            $reading = trim((string) ($row['reading'] ?? ''));
            $unit = trim((string) ($row['unit'] ?? ''));
            $limit = trim((string) ($row['limit'] ?? ''));
            $result = trim((string) ($row['result'] ?? ''));
            $id = trim((string) ($row['id'] ?? ''));
            if ($id === '' || ! preg_match('/^[A-Za-z0-9_\-]{2,32}$/', $id)) {
                $id = PracticeDocumentContext::newRowId('m');
            }
            $measurements[] = [
                'id' => $id,
                'location' => $location,
                'parameter' => $parameter,
                'reading' => $reading,
                'unit' => $unit,
                'limit' => $limit,
                'result' => $result,
            ];
        }
        $base['measurements'] = $measurements !== [] ? $measurements : [self::blankMeasurementRow()];
        if (! $base['include_measurements']) {
            $base['measurements'] = [];
        }

        $sections = [];
        foreach (($raw['sections'] ?? []) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $heading = trim((string) ($row['heading'] ?? ''));
            $body = trim((string) ($row['body'] ?? ''));
            if ($heading === '' && $body === '') {
                continue;
            }
            $sections[] = ['heading' => $heading, 'body' => $body];
        }
        $base['sections'] = $sections;

        return $base;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{id: string, label: string}>
     */
    public static function photoLinkOptions(array $payload): array
    {
        $options = [];
        foreach (($payload['checklist'] ?? []) as $i => $row) {
            if (! is_array($row)) {
                continue;
            }
            $id = trim((string) ($row['id'] ?? ''));
            if ($id === '') {
                continue;
            }
            $item = trim((string) ($row['item'] ?? ''));
            $options[] = [
                'id' => $id,
                'label' => 'Checklist '.($i + 1).($item !== '' ? ' — '.$item : ''),
            ];
        }
        foreach (($payload['measurements'] ?? []) as $i => $row) {
            if (! is_array($row)) {
                continue;
            }
            $id = trim((string) ($row['id'] ?? ''));
            if ($id === '') {
                continue;
            }
            $location = trim((string) ($row['location'] ?? ''));
            $parameter = trim((string) ($row['parameter'] ?? ''));
            $parts = array_filter(['Measurement '.($i + 1), $location, $parameter]);
            $options[] = ['id' => $id, 'label' => implode(' — ', $parts)];
        }

        return $options;
    }
}
