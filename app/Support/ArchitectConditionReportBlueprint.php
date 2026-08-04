<?php

namespace App\Support;

/**
 * Generic architect condition / neighbour report body.
 * One builder; Seventh Schedule starter seeds the same flexible form.
 */
class ArchitectConditionReportBlueprint
{
    /**
     * @return array<string, mixed>
     */
    public static function emptyPayload(): array
    {
        return [
            'sections' => [
                ['heading' => '1. Details of property', 'body' => ''],
                ['heading' => '2. Description of the structure system used for floors', 'body' => ''],
                ['heading' => '3. Description of the structure system used for transmitting vertical load', 'body' => ''],
                ['heading' => '4. Brief description of finishes and their general condition', 'body' => ''],
                ['heading' => '5. Brief description of the condition of existing services', 'body' => ''],
                ['heading' => '8. Additional requirements for properties falling within the affected zone of excavations', 'body' => ''],
                ['heading' => '9. Additional requirements for buildings over which additional floors are to be constructed', 'body' => ''],
                ['heading' => '10. Declaration regarding foundations / bearing pressure', 'body' => ''],
            ],
            'defects_heading' => '7. List of observed defects (cross-referenced to photos)',
            'defects' => [
                ['location' => '', 'defect' => '', 'photo_ref' => '', 'notes' => ''],
            ],
            'sketch_ref' => '',
            'legal_footer' => '',
        ];
    }

    /**
     * @return array<string, array{label: string, title: string, payload: array<string, mixed>}>
     */
    public static function starters(): array
    {
        return [
            'blank' => [
                'label' => 'Blank',
                'title' => 'Condition report',
                'payload' => self::emptyPayload(),
            ],
            'seventh_schedule' => [
                'label' => 'Seventh Schedule (neighbour)',
                'title' => 'Condition Report',
                'payload' => [
                    'sections' => [
                        ['heading' => '1. Details of property', 'body' => ''],
                        ['heading' => '2. Description of the structure system used for floors', 'body' => ''],
                        ['heading' => '3. Description of the structure system used for transmitting vertical load', 'body' => ''],
                        ['heading' => '4. Brief description of finishes and their general condition', 'body' => ''],
                        ['heading' => '5. Brief description of the condition of existing services', 'body' => ''],
                        ['heading' => '8. Additional requirements for properties falling within the affected zone of excavations', 'body' => ''],
                        ['heading' => '9. Additional requirements for buildings over which additional floors are to be constructed', 'body' => ''],
                        ['heading' => '10. Declaration that information about the foundations of the building is not readily available and including assumptions made in calculating bearing pressure', 'body' => ''],
                    ],
                    'defects_heading' => '7. List of observed defects, by room, cross-referenced to photos',
                    'defects' => [
                        ['location' => '', 'defect' => 'Flaking / peeling of paint', 'photo_ref' => '', 'notes' => ''],
                        ['location' => '', 'defect' => 'Rising damp / humidity / water stains', 'photo_ref' => '', 'notes' => ''],
                        ['location' => '', 'defect' => 'Settlement cracks', 'photo_ref' => '', 'notes' => ''],
                        ['location' => '', 'defect' => 'Other cracks', 'photo_ref' => '', 'notes' => ''],
                        ['location' => '', 'defect' => 'Corrosion of steel / spalling', 'photo_ref' => '', 'notes' => ''],
                        ['location' => '', 'defect' => 'Poor tile / skirting bonding', 'photo_ref' => '', 'notes' => ''],
                        ['location' => '', 'defect' => 'Bulging / peeling of membrane', 'photo_ref' => '', 'notes' => ''],
                    ],
                    'sketch_ref' => 'Refer to Appendix A / sketch plan attached.',
                    'legal_footer' => "Condition Report as per Seventh Schedule (regulation 7) of the Avoidance of Damage to Third Party Properties Regulations, 2019.\n\nPrepared for the exclusive use of the client in relation to the cited development. Third parties shall not rely upon this document without the author's written consent. Formulated on information available at the time of inspection.",
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

        $base['sketch_ref'] = trim((string) ($raw['sketch_ref'] ?? ''));
        $base['legal_footer'] = trim((string) ($raw['legal_footer'] ?? ''));
        $base['defects_heading'] = trim((string) ($raw['defects_heading'] ?? $base['defects_heading']));

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
        $base['sections'] = $sections !== [] ? $sections : $base['sections'];

        $defects = [];
        foreach (($raw['defects'] ?? []) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $location = trim((string) ($row['location'] ?? ''));
            $defect = trim((string) ($row['defect'] ?? ''));
            $photoRef = trim((string) ($row['photo_ref'] ?? ''));
            $notes = trim((string) ($row['notes'] ?? ''));
            if ($location === '' && $defect === '' && $photoRef === '' && $notes === '') {
                continue;
            }
            $defects[] = [
                'location' => $location,
                'defect' => $defect,
                'photo_ref' => $photoRef,
                'notes' => $notes,
            ];
        }
        $base['defects'] = $defects;

        return $base;
    }
}
