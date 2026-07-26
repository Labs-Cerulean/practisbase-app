<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EngineerProject extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'reference_code',
        'discipline',
        'phase',
        'status',
        'notes',
    ];

    public const DISCIPLINES = [
        'general' => 'General',
        'electrical' => 'Electrical',
        'mechanical' => 'Mechanical',
        'civil' => 'Civil / structural',
        'ems' => 'EMS',
        'bms' => 'BMS',
    ];

    public const PHASES = [
        'design' => 'Design',
        'tender' => 'Tender',
        'installation' => 'Installation',
        'commissioning' => 'Commissioning',
        'handover' => 'Handover',
        'maintenance' => 'Maintenance',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
