<?php

namespace App\Support\Architect;

use App\Models\ArchitectPaApplication;
use App\Models\ArchitectProject;
use App\Models\ArchitectSiteParty;
use App\Models\Client;
use App\Models\User;

/**
 * Registry of BCA / architect templates with preferred fill data and original blanks.
 */
class BcaTemplateCatalog
{
    public const REGISTER_URLS = [
        'contractor' => 'https://bca.gov.mt/register-of-contractors/',
        'sto' => 'https://bca.gov.mt/register-of-site-technical-officers/',
        'mason' => 'https://bca.gov.mt/register-of-licensed-masons/',
    ];

    /**
     * Shared field definitions used by fill forms.
     *
     * @return array<string, array{label: string, type: string, help?: string}>
     */
    public static function fieldLibrary(): array
    {
        return [
            'client' => [
                'label' => 'Client',
                'type' => 'client',
                'help' => 'Developer / applicant / owner on your architect client list.',
            ],
            'project' => [
                'label' => 'Project',
                'type' => 'project',
                'help' => 'Filtered to the selected client.',
            ],
            'pa' => [
                'label' => 'PA number',
                'type' => 'pa',
                'help' => 'Filtered to the selected project (and client).',
            ],
            'extra_text' => [
                'label' => 'Additional wording',
                'type' => 'textarea',
                'help' => 'Any extra text to include in the declaration body.',
            ],
            'reasons' => [
                'label' => 'Reasons',
                'type' => 'textarea',
                'help' => 'Reasons supporting the declaration or exemption.',
            ],
            'works_description' => [
                'label' => 'Description of works',
                'type' => 'textarea',
            ],
            'mitigation' => [
                'label' => 'Mitigation measures',
                'type' => 'textarea',
            ],
            'ds_number' => [
                'label' => 'DS number',
                'type' => 'text',
                'help' => 'e.g. DS 12345/24',
            ],
            'start_date' => [
                'label' => 'Start date',
                'type' => 'date',
            ],
            'end_date' => [
                'label' => 'End date',
                'type' => 'date',
            ],
            'third_party_count' => [
                'label' => 'Third party properties eligible for condition report',
                'type' => 'text',
            ],
            'complex_count' => [
                'label' => 'Complexes affected by excavation',
                'type' => 'text',
            ],
            'commencement_override' => [
                'label' => 'Works commencement date (override)',
                'type' => 'date',
                'help' => 'Leave blank to use the project / PA commencement date.',
            ],
        ];
    }

    /**
     * @return list<array{
     *   key: string,
     *   title: string,
     *   group: string,
     *   description: string,
     *   blank_file: ?string,
     *   fillable: bool,
     *   preferred_scope: string,
     *   fields: list<array{name: string, required: bool}>
     * }>
     */
    public static function all(): array
    {
        return [
            [
                'key' => 'declaration_ln136_out_of_scope',
                'title' => 'Declaration: works not under LN 136 of 2019',
                'group' => 'Declarations',
                'description' => 'Perit declaration that works do not fall under Regulation 4 criteria.',
                'blank_file' => 'declaration-not-under-ln136.docx',
                'fillable' => true,
                'preferred_scope' => 'pa',
                'fields' => [
                    ['name' => 'client', 'required' => true],
                    ['name' => 'project', 'required' => true],
                    ['name' => 'pa', 'required' => true],
                    ['name' => 'extra_text', 'required' => false],
                ],
            ],
            [
                'key' => 'declaration_reg26_not_affecting',
                'title' => 'Declaration: Regulation 26 (not affecting third party property)',
                'group' => 'Declarations',
                'description' => 'Certification that structural interventions will not affect third party property.',
                'blank_file' => 'declaration-reg26-not-affecting-third-party.docx',
                'fillable' => true,
                'preferred_scope' => 'pa',
                'fields' => [
                    ['name' => 'client', 'required' => true],
                    ['name' => 'project', 'required' => true],
                    ['name' => 'pa', 'required' => true],
                    ['name' => 'reasons', 'required' => true],
                    ['name' => 'extra_text', 'required' => false],
                ],
            ],
            [
                'key' => 'declaration_condition_reports_complexes',
                'title' => 'Declaration: condition reports and excavation affected complexes',
                'group' => 'Declarations',
                'description' => 'Architect and developer declarations for SL 623.06 condition reports.',
                'blank_file' => 'declaration-condition-reports-complexes.docx',
                'fillable' => true,
                'preferred_scope' => 'pa',
                'fields' => [
                    ['name' => 'client', 'required' => true],
                    ['name' => 'project', 'required' => true],
                    ['name' => 'pa', 'required' => true],
                    ['name' => 'third_party_count', 'required' => true],
                    ['name' => 'complex_count', 'required' => false],
                    ['name' => 'extra_text', 'required' => false],
                ],
            ],
            [
                'key' => 'site_management_summary',
                'title' => 'Site management responsibility summary',
                'group' => 'Site team',
                'description' => 'Summary of on-site roles, names and mobiles for the PA. Uses the project site team.',
                'blank_file' => 'site-management-summary.docx',
                'fillable' => true,
                'preferred_scope' => 'pa',
                'fields' => [
                    ['name' => 'client', 'required' => true],
                    ['name' => 'project', 'required' => true],
                    ['name' => 'pa', 'required' => true],
                    ['name' => 'extra_text', 'required' => false],
                ],
            ],
            [
                'key' => 'change_of_responsibility',
                'title' => 'Change of responsibility form',
                'group' => 'Site team',
                'description' => 'Developer declaration transferring a site role. Prefills developer and site team.',
                'blank_file' => 'change-of-responsibility.docx',
                'fillable' => true,
                'preferred_scope' => 'pa',
                'fields' => [
                    ['name' => 'client', 'required' => true],
                    ['name' => 'project', 'required' => true],
                    ['name' => 'pa', 'required' => true],
                    ['name' => 'extra_text', 'required' => false],
                ],
            ],
            [
                'key' => 'site_notice',
                'title' => 'Construction site notice board',
                'group' => 'Site notices',
                'description' => 'First Schedule notice board template with project and contact details.',
                'blank_file' => 'site-notice-board.docx',
                'fillable' => true,
                'preferred_scope' => 'pa',
                'fields' => [
                    ['name' => 'client', 'required' => true],
                    ['name' => 'project', 'required' => true],
                    ['name' => 'pa', 'required' => true],
                    ['name' => 'commencement_override', 'required' => false],
                ],
            ],
            [
                'key' => 'method_statement_demolition',
                'title' => 'Method statement: demolition works',
                'group' => 'Method statements',
                'description' => 'Use the Method statement builder (DMS starter). Official blank still available for e-Apps upload.',
                'blank_file' => 'method-statement-demolition.docx',
                'fillable' => false,
                'builder_path' => '/pro/architect/method-statements/create?starter=demolition',
                'preferred_scope' => 'pa',
                'fields' => [],
            ],
            [
                'key' => 'method_statement_excavation',
                'title' => 'Method statement: excavation works',
                'group' => 'Method statements',
                'description' => 'Fifth Schedule EMS — use the Method statement builder. Official blank still available.',
                'blank_file' => 'method-statement-excavation.docx',
                'fillable' => false,
                'builder_path' => '/pro/architect/method-statements/create?starter=excavation',
                'preferred_scope' => 'pa',
                'fields' => [],
            ],
            [
                'key' => 'method_statement_building',
                'title' => 'Method statement: building works',
                'group' => 'Method statements',
                'description' => 'Sixth Schedule CMS — use the Method statement builder. Official blank still available.',
                'blank_file' => 'method-statement-building.docx',
                'fillable' => false,
                'builder_path' => '/pro/architect/method-statements/create?starter=building',
                'preferred_scope' => 'pa',
                'fields' => [],
            ],
            [
                'key' => 'condition_report',
                'title' => 'Condition report (Seventh Schedule)',
                'group' => 'Condition reports',
                'description' => 'Use the Condition report builder. Official blank still available for e-Apps upload.',
                'blank_file' => 'condition-report.docx',
                'fillable' => false,
                'builder_path' => '/pro/architect/condition-reports/create?starter=seventh_schedule',
                'preferred_scope' => 'project',
                'fields' => [],
            ],
            [
                'key' => 'work_outside_hours_exemption',
                'title' => 'Request: work outside permissible hours',
                'group' => 'Exemptions',
                'description' => 'SL 623.08 exemption for works outside permitted hours.',
                'blank_file' => 'work-outside-hours-exemption.docx',
                'fillable' => true,
                'preferred_scope' => 'pa',
                'fields' => [
                    ['name' => 'client', 'required' => true],
                    ['name' => 'project', 'required' => true],
                    ['name' => 'pa', 'required' => true],
                    ['name' => 'start_date', 'required' => true],
                    ['name' => 'end_date', 'required' => true],
                    ['name' => 'works_description', 'required' => true],
                    ['name' => 'reasons', 'required' => true],
                    ['name' => 'mitigation', 'required' => false],
                ],
            ],
            [
                'key' => 'summer_break_exemption',
                'title' => 'Request: summer break exemption',
                'group' => 'Exemptions',
                'description' => 'SL 623.08 Schedule 3 summer break exemption request.',
                'blank_file' => 'summer-break-exemption.docx',
                'fillable' => true,
                'preferred_scope' => 'pa',
                'fields' => [
                    ['name' => 'client', 'required' => true],
                    ['name' => 'project', 'required' => true],
                    ['name' => 'pa', 'required' => true],
                    ['name' => 'start_date', 'required' => true],
                    ['name' => 'end_date', 'required' => true],
                    ['name' => 'works_description', 'required' => true],
                    ['name' => 'reasons', 'required' => false],
                    ['name' => 'mitigation', 'required' => false],
                ],
            ],
            [
                'key' => 'ds_clearance_application',
                'title' => 'Dangerous Structures: BCA clearance letter',
                'group' => 'Dangerous structures',
                'description' => 'Notification letter template for DS remedial works.',
                'blank_file' => 'ds-bca-clearance-application.docx',
                'fillable' => true,
                'preferred_scope' => 'project',
                'fields' => [
                    ['name' => 'client', 'required' => true],
                    ['name' => 'project', 'required' => true],
                    ['name' => 'pa', 'required' => false],
                    ['name' => 'ds_number', 'required' => true],
                    ['name' => 'extra_text', 'required' => false],
                ],
            ],
            [
                'key' => 'insurance_certificate',
                'title' => 'BCA certificate of insurance (blank workbook)',
                'group' => 'Insurance',
                'description' => 'Official BCA insurance certificate spreadsheet. Download blank and complete externally for now.',
                'blank_file' => 'bca-certificate-of-insurance.xlsx',
                'fillable' => false,
                'preferred_scope' => 'pa',
                'fields' => [],
            ],
            [
                'key' => 'architect_progress_declaration',
                'title' => 'Architect declaration: works in accordance with permit',
                'group' => 'Architect declarations',
                'description' => 'Ready made perit declaration commonly requested during projects.',
                'blank_file' => null,
                'fillable' => true,
                'preferred_scope' => 'pa',
                'fields' => [
                    ['name' => 'client', 'required' => true],
                    ['name' => 'project', 'required' => true],
                    ['name' => 'pa', 'required' => true],
                    ['name' => 'extra_text', 'required' => false],
                ],
            ],
            [
                'key' => 'architect_supervision_declaration',
                'title' => 'Architect declaration: site supervision',
                'group' => 'Architect declarations',
                'description' => 'Declaration that the undersigned perit is supervising the works.',
                'blank_file' => null,
                'fillable' => true,
                'preferred_scope' => 'pa',
                'fields' => [
                    ['name' => 'client', 'required' => true],
                    ['name' => 'project', 'required' => true],
                    ['name' => 'pa', 'required' => true],
                    ['name' => 'extra_text', 'required' => false],
                ],
            ],
            [
                'key' => 'architect_completion_declaration',
                'title' => 'Architect declaration: substantial completion',
                'group' => 'Architect declarations',
                'description' => 'Declaration that works have reached substantial completion as permitted.',
                'blank_file' => null,
                'fillable' => true,
                'preferred_scope' => 'pa',
                'fields' => [
                    ['name' => 'client', 'required' => true],
                    ['name' => 'project', 'required' => true],
                    ['name' => 'pa', 'required' => true],
                    ['name' => 'extra_text', 'required' => false],
                ],
            ],
        ];
    }

    public static function find(string $key): ?array
    {
        foreach (self::all() as $tpl) {
            if ($tpl['key'] === $key) {
                return $tpl;
            }
        }

        return null;
    }

    public static function blankPath(string $blankFile): string
    {
        return resource_path('bca-templates/originals/'.$blankFile);
    }

    /**
     * @param  array{fields?: list<array{name: string, required: bool}>}  $template
     * @return list<array{name: string, required: bool, label: string, type: string, help: ?string}>
     */
    public static function resolvedFields(array $template): array
    {
        $library = self::fieldLibrary();
        $resolved = [];

        foreach ($template['fields'] ?? [] as $field) {
            $name = $field['name'];
            if (! isset($library[$name])) {
                continue;
            }
            $resolved[] = [
                'name' => $name,
                'required' => (bool) ($field['required'] ?? false),
                'label' => $library[$name]['label'],
                'type' => $library[$name]['type'],
                'help' => $library[$name]['help'] ?? null,
            ];
        }

        return $resolved;
    }

    /**
     * Prefill map used by fillable PDF views.
     *
     * @return array<string, mixed>
     */
    public static function prefillContext(
        User $user,
        ?Client $client = null,
        ?ArchitectProject $project = null,
        ?ArchitectPaApplication $pa = null
    ): array {
        $parties = collect();
        if ($project) {
            $parties = ArchitectSiteParty::query()
                ->where('user_id', $user->id)
                ->where('architect_project_id', $project->id)
                ->when($pa, fn ($q) => $q->where(function ($inner) use ($pa) {
                    $inner->whereNull('architect_pa_application_id')
                        ->orWhere('architect_pa_application_id', $pa->id);
                }))
                ->orderBy('role_key')
                ->get()
                ->keyBy('role_key');
        }

        return [
            'user' => $user,
            'perit_name' => $user->name,
            'perit_postnominals' => $user->postnominalsLine(),
            'perit_warrant' => $user->warrant_number,
            'perit_email' => $user->email,
            'perit_phone' => $user->clinic_phone,
            'perit_address' => $user->clinic_address,
            'client' => $client,
            'client_name' => $client?->name,
            'client_id_card' => $client?->idCardNumber(),
            'client_email' => $client?->email,
            'client_phone' => $client?->phone,
            'client_address' => $client?->displayAddress(),
            'project' => $project,
            'project_name' => $project?->name,
            'project_reference' => $project?->reference_code,
            'site_address' => $project?->siteAddressLine(),
            'site_premises' => $project?->site_premises,
            'site_street' => $project?->site_street,
            'site_locality' => $project?->site_locality,
            'commencement_date' => $project?->commencement_date?->format('d/m/Y')
                ?? $pa?->works_commencement_date?->format('d/m/Y'),
            'pa' => $pa,
            'pa_number' => $pa?->pa_number,
            'pa_title' => $pa?->title,
            'parties' => $parties,
            'today' => now()->format('d/m/Y'),
            'register_urls' => self::REGISTER_URLS,
        ];
    }
}
