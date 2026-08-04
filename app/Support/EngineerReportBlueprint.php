<?php

namespace App\Support;

/**
 * Generic engineer specialised report body.
 * One builder covers fire, noise, ventilation, lighting, etc. via flexible blocks.
 */
class EngineerReportBlueprint
{
    /**
     * @return array<string, mixed>
     */
    public static function emptyPayload(): array
    {
        return [
            'subject_heading' => 'Premises / scope',
            'attributes' => [
                ['label' => 'Premises use', 'value' => ''],
                ['label' => 'Standards / references', 'value' => ''],
            ],
            'highlight_label' => 'Overall conclusion',
            'highlight_value' => '',
            'checklist_heading' => 'Inspection checklist',
            'checklist' => [
                ['item' => '', 'outcome' => '', 'comments' => ''],
            ],
            'measurements_heading' => 'Measurements / readings',
            'measurements' => [
                ['location' => '', 'parameter' => '', 'reading' => '', 'unit' => '', 'limit' => '', 'result' => ''],
            ],
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
                    'subject_heading' => 'Premises / scope',
                    'attributes' => [
                        ['label' => 'Premises use', 'value' => ''],
                        ['label' => 'Occupancy / capacity', 'value' => ''],
                        ['label' => 'Number of storeys', 'value' => ''],
                        ['label' => 'Standards referenced', 'value' => ''],
                        ['label' => 'PA / permission reference', 'value' => ''],
                    ],
                    'highlight_label' => 'Overall risk',
                    'highlight_value' => '',
                    'checklist_heading' => 'Fire safety inspection items',
                    'checklist' => [
                        ['item' => 'Means of escape / travel distances', 'outcome' => '', 'comments' => ''],
                        ['item' => 'Fire detection / alarm provision', 'outcome' => '', 'comments' => ''],
                        ['item' => 'Emergency lighting', 'outcome' => '', 'comments' => ''],
                        ['item' => 'Fire-fighting equipment / access', 'outcome' => '', 'comments' => ''],
                        ['item' => 'Compartmentation / fire doors', 'outcome' => '', 'comments' => ''],
                        ['item' => 'Signage', 'outcome' => '', 'comments' => ''],
                    ],
                    'measurements_heading' => '',
                    'measurements' => [],
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
                    'highlight_label' => 'Overall assessment',
                    'highlight_value' => '',
                    'checklist_heading' => 'Assessment checks',
                    'checklist' => [
                        ['item' => 'Meter calibration verified', 'outcome' => '', 'comments' => ''],
                        ['item' => 'Background / ambient measured', 'outcome' => '', 'comments' => ''],
                        ['item' => 'Source operating conditions noted', 'outcome' => '', 'comments' => ''],
                    ],
                    'measurements_heading' => 'Noise measurements',
                    'measurements' => [
                        ['location' => '', 'parameter' => 'LAeq', 'reading' => '', 'unit' => 'dB(A)', 'limit' => '', 'result' => ''],
                        ['location' => '', 'parameter' => 'LAFmax', 'reading' => '', 'unit' => 'dB(A)', 'limit' => '', 'result' => ''],
                    ],
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
                    'subject_heading' => 'System / premises',
                    'attributes' => [
                        ['label' => 'System type', 'value' => ''],
                        ['label' => 'Areas served', 'value' => ''],
                        ['label' => 'Design occupancy', 'value' => ''],
                        ['label' => 'Standards referenced', 'value' => ''],
                        ['label' => 'Instrument / method', 'value' => ''],
                    ],
                    'highlight_label' => 'Overall conclusion',
                    'highlight_value' => '',
                    'checklist_heading' => 'Ventilation inspection items',
                    'checklist' => [
                        ['item' => 'Supply / extract operation', 'outcome' => '', 'comments' => ''],
                        ['item' => 'Filters / coils condition', 'outcome' => '', 'comments' => ''],
                        ['item' => 'Controls / interlocks', 'outcome' => '', 'comments' => ''],
                        ['item' => 'Fresh air provision vs design', 'outcome' => '', 'comments' => ''],
                        ['item' => 'Transfer / make-up paths', 'outcome' => '', 'comments' => ''],
                    ],
                    'measurements_heading' => 'Airflow / ventilation readings',
                    'measurements' => [
                        ['location' => '', 'parameter' => 'Supply airflow', 'reading' => '', 'unit' => 'l/s', 'limit' => '', 'result' => ''],
                        ['location' => '', 'parameter' => 'Extract airflow', 'reading' => '', 'unit' => 'l/s', 'limit' => '', 'result' => ''],
                        ['location' => '', 'parameter' => 'ACH (est.)', 'reading' => '', 'unit' => 'h⁻¹', 'limit' => '', 'result' => ''],
                    ],
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
                    'checklist_heading' => 'Lighting inspection items',
                    'checklist' => [
                        ['item' => 'General illuminance adequacy', 'outcome' => '', 'comments' => ''],
                        ['item' => 'Uniformity', 'outcome' => '', 'comments' => ''],
                        ['item' => 'Glare / veiling reflections', 'outcome' => '', 'comments' => ''],
                        ['item' => 'Emergency lighting (if applicable)', 'outcome' => '', 'comments' => ''],
                        ['item' => 'Controls / switching', 'outcome' => '', 'comments' => ''],
                    ],
                    'measurements_heading' => 'Illuminance readings',
                    'measurements' => [
                        ['location' => '', 'parameter' => 'Illuminance', 'reading' => '', 'unit' => 'lux', 'limit' => '', 'result' => ''],
                        ['location' => '', 'parameter' => 'Illuminance', 'reading' => '', 'unit' => 'lux', 'limit' => '', 'result' => ''],
                    ],
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
        $base['highlight_label'] = trim((string) ($raw['highlight_label'] ?? ''));
        $base['highlight_value'] = trim((string) ($raw['highlight_value'] ?? ''));
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
            if ($item === '' && $outcome === '' && $comments === '') {
                continue;
            }
            $checklist[] = ['item' => $item, 'outcome' => $outcome, 'comments' => $comments];
        }
        $base['checklist'] = $checklist;

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
            if ($location === '' && $parameter === '' && $reading === '' && $unit === '' && $limit === '' && $result === '') {
                continue;
            }
            $measurements[] = [
                'location' => $location,
                'parameter' => $parameter,
                'reading' => $reading,
                'unit' => $unit,
                'limit' => $limit,
                'result' => $result,
            ];
        }
        $base['measurements'] = $measurements;

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
}
