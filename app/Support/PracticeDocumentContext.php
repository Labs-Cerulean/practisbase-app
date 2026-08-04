<?php

namespace App\Support;

use App\Models\ArchitectConditionReport;
use App\Models\ArchitectMethodStatement;
use App\Models\ArchitectPaApplication;
use App\Models\ArchitectProject;
use App\Models\EngineerCertificate;
use App\Models\EngineerPaApplication;
use App\Models\EngineerProject;
use App\Models\EngineerReport;

/**
 * Project-first helpers for Arch/Eng stampable documents:
 * filing refs, party/site auto-fill, stable row ids.
 */
class PracticeDocumentContext
{
    public static function newRowId(string $prefix = 'r'): string
    {
        return $prefix.substr(str_replace('.', '', uniqid('', true)), -8);
    }

    public static function projectCode(?string $referenceCode, int $projectId): string
    {
        $raw = strtoupper(trim((string) $referenceCode));
        $code = preg_replace('/[^A-Z0-9\-_\/.]+/', '-', $raw) ?: '';
        $code = trim((string) $code, '-_/.');

        return $code !== '' ? $code : 'P'.$projectId;
    }

    public static function nextRef(string $projectCode, string $docType, int $existingCount): string
    {
        $seq = max(1, $existingCount + 1);

        return $projectCode.'-'.strtoupper($docType).'-'.str_pad((string) $seq, 2, '0', STR_PAD_LEFT);
    }

    public static function nextArchitectConditionRef(int $userId, ArchitectProject $project): string
    {
        $count = ArchitectConditionReport::where('user_id', $userId)
            ->where('architect_project_id', $project->id)
            ->count();

        return self::nextRef(self::projectCode($project->reference_code, $project->id), 'CR', $count);
    }

    public static function nextArchitectMethodRef(int $userId, ArchitectProject $project, ?string $statementType = null): string
    {
        $type = match ($statementType) {
            'demolition' => 'DMS',
            'excavation' => 'EMS',
            'building' => 'CMS',
            default => 'MS',
        };

        $count = ArchitectMethodStatement::where('user_id', $userId)
            ->where('architect_project_id', $project->id)
            ->count();

        return self::nextRef(self::projectCode($project->reference_code, $project->id), $type, $count);
    }

    public static function nextEngineerCertificateRef(int $userId, EngineerProject $project): string
    {
        $count = EngineerCertificate::where('user_id', $userId)
            ->where('engineer_project_id', $project->id)
            ->count();

        return self::nextRef(self::projectCode($project->reference_code, $project->id), 'EC', $count);
    }

    public static function nextEngineerReportRef(int $userId, EngineerProject $project): string
    {
        $count = EngineerReport::where('user_id', $userId)
            ->where('engineer_project_id', $project->id)
            ->count();

        return self::nextRef(self::projectCode($project->reference_code, $project->id), 'ER', $count);
    }

    /**
     * @return array<string, mixed>
     */
    public static function architectPrefill(?ArchitectProject $project, ?ArchitectPaApplication $pa = null, string $docType = 'CR', ?string $statementType = null): array
    {
        if (! $project) {
            return [
                'client_name' => '',
                'client_address' => '',
                'project_description' => '',
                'site_address' => '',
                'development_address' => '',
                'inspected_address' => '',
                'commencement_note' => '',
                'suggested_ref' => '',
                'pa_number' => '',
            ];
        }

        $client = $project->relationLoaded('client') ? $project->client : $project->client()->first();
        $site = $project->siteAddressLine();
        $userId = (int) $project->user_id;

        $suggested = match ($docType) {
            'MS' => self::nextArchitectMethodRef($userId, $project, $statementType),
            default => self::nextArchitectConditionRef($userId, $project),
        };

        $commencement = '';
        if ($project->commencement_date) {
            $commencement = 'Commencement '.$project->commencement_date->format('d/m/Y');
        } elseif ($pa?->works_commencement_date) {
            $commencement = 'Commencement '.$pa->works_commencement_date->format('d/m/Y');
        }

        return [
            'client_name' => $client?->name ?? '',
            'client_address' => $client?->displayAddress() ?? '',
            'project_description' => trim((string) ($project->name.($project->notes ? "\n".$project->notes : ''))),
            'site_address' => $site,
            'development_address' => $site,
            'inspected_address' => '',
            'commencement_note' => $commencement,
            'suggested_ref' => $suggested,
            'pa_number' => $pa?->pa_number ?? '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function engineerPrefill(?EngineerProject $project, ?EngineerPaApplication $pa = null, string $docType = 'EC'): array
    {
        if (! $project) {
            return [
                'holder_name' => '',
                'client_name' => '',
                'holder_address' => '',
                'client_address' => '',
                'contact_person' => '',
                'contact_phone' => '',
                'site_address' => '',
                'suggested_ref' => '',
                'pa_number' => '',
            ];
        }

        $client = $project->relationLoaded('client') ? $project->client : $project->client()->first();
        $site = $project->siteAddressLine();
        $userId = (int) $project->user_id;

        $suggested = $docType === 'ER'
            ? self::nextEngineerReportRef($userId, $project)
            : self::nextEngineerCertificateRef($userId, $project);

        return [
            'holder_name' => $client?->name ?? '',
            'client_name' => $client?->name ?? '',
            'holder_address' => $client?->displayAddress() ?? '',
            'client_address' => $client?->displayAddress() ?? '',
            'contact_person' => '',
            'contact_phone' => $client?->phone ?? '',
            'site_address' => $site,
            'suggested_ref' => $suggested,
            'pa_number' => $pa?->pa_number ?? '',
        ];
    }

    /**
     * @param  iterable<int, ArchitectProject|EngineerProject>  $projects
     * @return array<int, array<string, string>>
     */
    public static function projectOptionsPayload(iterable $projects, string $package, string $docType = 'CR', ?string $statementType = null): array
    {
        $out = [];
        foreach ($projects as $project) {
            $client = $project->client;
            if ($package === 'arch') {
                $prefill = self::architectPrefill($project, null, $docType, $statementType);
            } else {
                $prefill = self::engineerPrefill($project, null, $docType);
            }
            $out[(int) $project->id] = [
                'name' => (string) $project->name,
                'client_name' => (string) ($prefill['client_name'] ?? $prefill['holder_name'] ?? ''),
                'client_address' => (string) ($prefill['client_address'] ?? $prefill['holder_address'] ?? ''),
                'site_address' => (string) ($prefill['site_address'] ?? $prefill['development_address'] ?? ''),
                'project_description' => (string) ($prefill['project_description'] ?? $project->name),
                'contact_person' => (string) ($prefill['contact_person'] ?? ''),
                'contact_phone' => (string) ($prefill['contact_phone'] ?? ''),
                'commencement_note' => (string) ($prefill['commencement_note'] ?? ''),
                'suggested_ref' => (string) ($prefill['suggested_ref'] ?? ''),
                'pa_number' => (string) ($prefill['pa_number'] ?? ''),
            ];
        }

        return $out;
    }
}
