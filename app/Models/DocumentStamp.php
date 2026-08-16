<?php

namespace App\Models;

use App\Support\TenantStorage;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'label',
    'preset',
    'first_name',
    'last_name',
    'postnominals',
    'role_title',
    'warrant_number',
    'signature_path',
    'composed_path',
    'is_default',
])]
class DocumentStamp extends Model
{
    public const PRESETS = [
        'classic_border' => 'Classic border',
        'circular_seal' => 'Circular seal',
        'minimal_line' => 'Minimal line',
        'warrant_block' => 'Warrant block',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function presetLabel(): string
    {
        return self::PRESETS[$this->preset] ?? $this->preset;
    }

    public function displayName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function signatureDataUri(): ?string
    {
        return $this->pathToDataUri($this->signature_path);
    }

    /** Full stamp image (preset + text + signature) for clinical / professional PDFs. */
    public function composedDataUri(): ?string
    {
        return $this->pathToDataUri($this->composed_path);
    }

    public function hasComposedImage(): bool
    {
        if (! filled($this->composed_path)) {
            return false;
        }

        return TenantStorage::disk()->exists($this->composed_path);
    }

    private function pathToDataUri(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        $disk = TenantStorage::disk();
        if (! $disk->exists($path)) {
            return null;
        }

        $binary = $disk->get($path);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => 'image/png',
        };

        return 'data:'.$mime.';base64,'.base64_encode($binary);
    }

    /** Payload for the browser stamper (compose + place on PDF). */
    public function toStamperPayload(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'preset' => $this->preset,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'postnominals' => $this->postnominals,
            'role_title' => $this->role_title,
            'warrant_number' => $this->warrant_number,
            'signature_data_uri' => $this->signatureDataUri(),
            'needs_composed' => ! $this->hasComposedImage(),
            'is_default' => (bool) $this->is_default,
        ];
    }
}
