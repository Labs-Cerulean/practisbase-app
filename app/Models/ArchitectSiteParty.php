<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchitectSiteParty extends Model
{
    protected $fillable = [
        'user_id',
        'architect_project_id',
        'architect_pa_application_id',
        'role_key',
        'full_name',
        'id_card',
        'mobile',
        'email',
        'address',
        'company_name',
        'licence_type',
        'licence_number',
        'notes',
    ];

    public const ROLES = [
        'perit_in_charge' => 'Perit in charge of the project',
        'developer' => 'Client / Applicant / Developer / Owner',
        'licensed_mason' => 'Licensed Mason',
        'site_manager' => 'Site Manager (CAP 623)',
        'perit_demolition_ms' => 'Perit responsible for demolition method statement',
        'sto_demolition' => 'Site Technical Officer during demolition',
        'contractor_demolition' => 'Contractor responsible for demolition',
        'perit_excavation_ms' => 'Perit responsible for excavation method statement',
        'sto_excavation' => 'Site Technical Officer during excavation',
        'contractor_excavation' => 'Contractor responsible for excavation',
        'perit_building_ms' => 'Perit responsible for construction method statement',
        'sto_building' => 'Site Technical Officer during building works',
        'contractor_building' => 'Contractor responsible for building works',
        'project_supervisor' => 'Project Supervisor (LN 88 of 2018)',
        'ohsa_officer' => 'OHSA Health & Safety Officer',
    ];

    public const LICENCE_TYPES = [
        'contractor' => 'Contractor licence',
        'sto' => 'Site Technical Officer',
        'mason' => 'Licensed mason',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(ArchitectProject::class, 'architect_project_id');
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role_key] ?? $this->role_key;
    }
}
