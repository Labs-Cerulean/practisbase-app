<?php

namespace App\Support;

/**
 * Generic architect method statement body (DMS / EMS / CMS).
 * One builder; schedule starters seed the same flexible sections form.
 */
class ArchitectMethodStatementBlueprint
{
    /**
     * @return array<string, mixed>
     */
    public static function emptyPayload(): array
    {
        return [
            'sections' => [
                ['heading' => 'Scope', 'body' => ''],
                ['heading' => 'Proposed methodology', 'body' => ''],
            ],
            'appendix_ref' => '',
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
                'title' => 'Method statement',
                'payload' => self::emptyPayload(),
            ],
            'demolition' => [
                'label' => 'Demolition (DMS)',
                'title' => 'Method Statement – Demolition',
                'payload' => [
                    'sections' => [
                        ['heading' => 'Scope', 'body' => "This Method Statement describes a proper work procedure which shall be carried out during the demolition stage as per specification and demolition drawings (if required). The method statement is subject to changes during the course of works. Any changes will be included in a revised method statement uploaded onto the e-Apps system."],
                        ['heading' => 'Security and site establishment', 'body' => ''],
                        ['heading' => 'Personnel', 'body' => "All personnel will be inducted prior to gaining access to the site and will be informed of the updated legal notice requirements (LN 136 of 2019) by the appointed Site Technical Officer."],
                        ['heading' => 'Signage', 'body' => ''],
                        ['heading' => 'Storage and handling', 'body' => ''],
                        ['heading' => 'Dust and debris', 'body' => "The site shall be kept clean and tidy at all times and will accord with any statutory requirements. Particular attention is to be given to preventing the contamination of adjoining roadways and existing water courses."],
                        ['heading' => '1. General', 'body' => ''],
                        ['heading' => '2. Demolition – supporting neighbouring structures', 'body' => ''],
                        ['heading' => '2. Demolition – methodology and precautions', 'body' => ''],
                        ['heading' => 'Waste disposal / carting away', 'body' => ''],
                    ],
                    'appendix_ref' => '',
                    'legal_footer' => 'Prepared for the exclusive use of the client. Subject to revision during the course of works; revised versions will be uploaded to e-Apps as required.',
                ],
            ],
            'excavation' => [
                'label' => 'Excavation (EMS / Fifth Schedule)',
                'title' => 'Method Statement for Excavation Works',
                'payload' => [
                    'sections' => [
                        ['heading' => '1. Commencement date of works', 'body' => ''],
                        ['heading' => '2a. Limits of excavation', 'body' => ''],
                        ['heading' => '2b. Depth of each part of the excavation', 'body' => ''],
                        ['heading' => '2c. Affected zone of the excavation (shaded / dimensioned)', 'body' => ''],
                        ['heading' => '2d. Third-party properties within the affected zone', 'body' => ''],
                        ['heading' => '3. Loads acting on the ground within the affected zone', 'body' => ''],
                        ['heading' => '4a. Ground materials (geological map)', 'body' => ''],
                        ['heading' => '4b. Ground investigation reports within the affected zone', 'body' => ''],
                        ['heading' => '4c. Information from other periti (with consent)', 'body' => ''],
                        ['heading' => '4d. Commissioned ground investigation (if 4b/4c unavailable)', 'body' => ''],
                        ['heading' => '5. Identification of the risks involved', 'body' => ''],
                        ['heading' => '6. Declaration – excavation possible and safely executable', 'body' => 'I, the undersigned Perit responsible for this method statement, hereby declare that, after having had regard for the requirements of items 2, 3, 4 and 5 of the Fifth Schedule, the excavation works are possible and can be safely executed.'],
                        ['heading' => '7. Declaration – no ground improvement / underpinning necessary (if applicable)', 'body' => 'I, the undersigned Perit responsible for this method statement, hereby declare that no ground improvement intervention or underpinning intervention is necessary.'],
                        ['heading' => '8a. Where excavation is to be started from', 'body' => ''],
                        ['heading' => '8b. Phasing / rock buttressing', 'body' => ''],
                        ['heading' => '8c. Levels to be attained in each stage', 'body' => ''],
                        ['heading' => '8d. Machinery / equipment allowed', 'body' => ''],
                        ['heading' => '8e. Temporary support / monitoring', 'body' => ''],
                    ],
                    'appendix_ref' => '',
                    'legal_footer' => "Method Statement for Excavation Works as per Fifth Schedule (regulations 7 & 8) of the Avoidance of Damage to Third Party Properties Regulations.\n\nAdditional drawings and photographs are to be referred to as numbered appendices.",
                ],
            ],
            'building' => [
                'label' => 'Building works (CMS / Sixth Schedule)',
                'title' => 'Method Statement – Building Works',
                'payload' => [
                    'sections' => [
                        ['heading' => 'Dust and debris', 'body' => "The site shall be kept clean and tidy at all times and will accord with any statutory requirements. Particular attention is to be given to preventing the contamination of adjoining roadways and existing water courses."],
                        ['heading' => '1. Commencement date of works', 'body' => ''],
                        ['heading' => '2. Checks – existing floors capable of sustaining additional load', 'body' => ''],
                        ['heading' => '3. Checks – foundations capable of sustaining additional loads', 'body' => ''],
                        ['heading' => '4. Declaration where information in items 2 and 3 is not available', 'body' => 'I, the undersigned Perit responsible for the project, am assuming full responsibility for the structural capacity of the building, the safety of its occupants, and the safety of the operatives working on its construction, notwithstanding the missing information that precludes the necessary checks from being made.'],
                        ['heading' => '5a. Risks involved', 'body' => ""],
                        ['heading' => 'Scope', 'body' => 'This Method Statement describes a proper work procedure which shall be carried out during the construction stage as per specification and construction drawings.'],
                        ['heading' => 'Security and site establishment', 'body' => ''],
                        ['heading' => 'Personnel', 'body' => ''],
                        ['heading' => 'Signage', 'body' => ''],
                        ['heading' => 'Storage and handling', 'body' => ''],
                        ['heading' => 'Loads acting on the structure / neighbouring properties', 'body' => ''],
                        ['heading' => '5b. Methodology', 'body' => ''],
                        ['heading' => '5c. Safeguarding contiguous structures', 'body' => ''],
                        ['heading' => '5d / 5e. Equipment to be used', 'body' => ''],
                        ['heading' => '5f. Specific interventions to safeguard contiguous structure or terrain', 'body' => ''],
                    ],
                    'appendix_ref' => '',
                    'legal_footer' => 'Method Statement for Building Works with regard to the Sixth Schedule requirements. Subject to revision during the course of works; revised versions will be uploaded to e-Apps as required.',
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

        $base['appendix_ref'] = trim((string) ($raw['appendix_ref'] ?? ''));
        $base['legal_footer'] = trim((string) ($raw['legal_footer'] ?? ''));

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

        return $base;
    }
}
