<?php

namespace App\Support;

/**
 * Generic engineer certificate body.
 * One builder covers equipment, scaffold, PA declarations, etc. via flexible blocks.
 */
class EngineerCertificateBlueprint
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
     * Optional helper items — not seeded into new certificates.
     *
     * @return list<string>
     */
    public static function commonChecklistItems(string $starterKey): array
    {
        return match ($starterKey) {
            'equipment' => [
                'Structural integrity / welds / lifting points',
                'Labels and markings',
            ],
            'scaffold' => [
                'Handrails / midrails',
                'Toe-boards',
                'Ladders / access',
                'Anchoring',
                'Decks / fittings',
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
            'subject_heading' => 'Subject / equipment',
            'attributes' => [
                ['label' => 'Description', 'value' => ''],
                ['label' => 'Manufacturer / model', 'value' => ''],
                ['label' => 'Serial / inventory no.', 'value' => ''],
            ],
            'highlight_label' => '',
            'highlight_value' => '',
            'checklist_heading' => 'Inspection checklist',
            'checklist' => [self::blankChecklistRow()],
            'sections' => [
                ['heading' => 'Findings / remarks', 'body' => ''],
                ['heading' => 'Conditions of validity', 'body' => ''],
            ],
            'legal_footer' => '',
        ];
    }

    /**
     * Optional starters — seed the same generic form, not separate certificate products.
     *
     * @return array<string, array{label: string, title: string, payload: array<string, mixed>}>
     */
    public static function starters(): array
    {
        return [
            'blank' => [
                'label' => 'Blank',
                'title' => 'Certificate',
                'payload' => self::emptyPayload(),
            ],
            'equipment' => [
                'label' => 'Equipment / safe operation',
                'title' => 'Certificate of Safe Operation',
                'payload' => [
                    'subject_heading' => 'Equipment details',
                    'attributes' => [
                        ['label' => 'Type', 'value' => ''],
                        ['label' => 'Manufacturer / model', 'value' => ''],
                        ['label' => 'Inventory / serial no.', 'value' => ''],
                        ['label' => 'Capacity / rating', 'value' => ''],
                        ['label' => 'Year of manufacture', 'value' => ''],
                        ['label' => 'NDT / inspection method', 'value' => 'Visual inspection'],
                    ],
                    'highlight_label' => 'Maximum certified safe working load',
                    'highlight_value' => '',
                    'checklist_heading' => 'Inspection items',
                    'checklist' => [self::blankChecklistRow()],
                    'sections' => [
                        ['heading' => 'Results', 'body' => ''],
                        ['heading' => 'Conditions of validity', 'body' => "This certificate is valid only if the equipment is operated by authorised competent persons, maintained in accordance with applicable regulations, and is not used if damaged or modified.\n\nIssued in accordance with Occupational Health & Safety Authority requirements and S.L.424.35 / L.N. 293 of 2016 as applicable."],
                    ],
                    'legal_footer' => 'Prepared for the exclusive use of the client. Third parties shall not rely upon this document. Formulated on information available at the time of inspection.',
                ],
            ],
            'scaffold' => [
                'label' => 'Scaffold / temporary works',
                'title' => 'Scaffolding Inspection Certificate',
                'payload' => [
                    'subject_heading' => 'Scaffold / project details',
                    'attributes' => [
                        ['label' => 'Scaffold type', 'value' => ''],
                        ['label' => 'Size (W×L×H)', 'value' => ''],
                        ['label' => 'Duty classification', 'value' => ''],
                        ['label' => 'Load rating', 'value' => ''],
                        ['label' => 'Permissible weather', 'value' => ''],
                        ['label' => 'Intended use', 'value' => ''],
                    ],
                    'highlight_label' => 'Appraisal',
                    'highlight_value' => 'Safe for use',
                    'checklist_heading' => 'Inspection checklist',
                    'checklist' => [self::blankChecklistRow()],
                    'sections' => [
                        ['heading' => 'Additional details', 'body' => ''],
                        ['heading' => 'Other comments', 'body' => ''],
                        ['heading' => 'Appraisal', 'body' => 'Issued in accordance with applicable workplace health and safety regulations for temporary works / scaffolding. Valid only if no unauthorised modifications are made and the structure is maintained and used as intended.'],
                    ],
                    'legal_footer' => 'Inspection certifies the erected structure at the time of survey only.',
                ],
            ],
            'pa_compliance' => [
                'label' => 'PA / permission compliance',
                'title' => 'Certification by Engineer in relation to development permission',
                'payload' => [
                    'subject_heading' => 'Permission particulars',
                    'attributes' => [
                        ['label' => 'Full address of premises', 'value' => ''],
                        ['label' => 'Condition number', 'value' => ''],
                        ['label' => 'Approved document number(s)', 'value' => ''],
                        ['label' => 'PA / permission reference', 'value' => ''],
                    ],
                    'highlight_label' => 'Declaration',
                    'highlight_value' => 'Requirements implemented / satisfied / operational',
                    'checklist_heading' => '',
                    'checklist' => [self::blankChecklistRow()],
                    'sections' => [
                        ['heading' => 'Declaration', 'body' => "I, the undersigned engineer as prescribed by the relevant legislation, hereby declare, in terms of the requirements of the condition of the permission and in relation to the premises cited above, that the requirements of the approved document(s) cited above have been implemented on site, are found to be fully satisfied, and are fully operational as verified through an inspection.\n\n(Alternatively state: not applicable to the premises cited above.)"],
                    ],
                    'legal_footer' => '',
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

        return $options;
    }
}
