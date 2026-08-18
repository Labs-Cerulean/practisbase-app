<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchitectLicenceContact extends Model
{
    protected $fillable = [
        'user_id',
        'licence_type',
        'licence_number',
        'full_name',
        'company_name',
        'mobile',
        'email',
        'id_card',
        'preferred_role_key',
        'locality',
        'source',
        'notes',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Remember / refresh a reusable team contact for this practice.
     *
     * @param  array{
     *   full_name: string,
     *   company_name?: ?string,
     *   mobile?: ?string,
     *   email?: ?string,
     *   id_card?: ?string,
     *   licence_type?: ?string,
     *   licence_number?: ?string,
     *   preferred_role_key?: ?string,
     *   source?: string
     * }  $data
     */
    public static function rememberForUser(int $userId, array $data): self
    {
        $fullName = trim((string) ($data['full_name'] ?? ''));
        $licenceNumber = filled($data['licence_number'] ?? null)
            ? trim((string) $data['licence_number'])
            : null;

        $query = static::query()
            ->where('user_id', $userId)
            ->whereRaw('LOWER(full_name) = ?', [mb_strtolower($fullName)]);

        if ($licenceNumber !== null) {
            $query->whereRaw('LOWER(COALESCE(licence_number, \'\')) = ?', [mb_strtolower($licenceNumber)]);
        } else {
            $query->where(function ($inner) {
                $inner->whereNull('licence_number')->orWhere('licence_number', '');
            });
        }

        $contact = $query->orderByDesc('last_used_at')->first() ?? new static([
            'user_id' => $userId,
            'full_name' => $fullName,
        ]);

        $contact->full_name = $fullName;
        $contact->company_name = filled($data['company_name'] ?? null) ? trim((string) $data['company_name']) : $contact->company_name;
        $contact->mobile = filled($data['mobile'] ?? null) ? trim((string) $data['mobile']) : $contact->mobile;
        $contact->email = filled($data['email'] ?? null) ? trim((string) $data['email']) : $contact->email;
        $contact->id_card = filled($data['id_card'] ?? null) ? trim((string) $data['id_card']) : $contact->id_card;
        $contact->licence_type = filled($data['licence_type'] ?? null) ? (string) $data['licence_type'] : $contact->licence_type;
        $contact->licence_number = $licenceNumber ?? $contact->licence_number;
        $contact->preferred_role_key = filled($data['preferred_role_key'] ?? null)
            ? (string) $data['preferred_role_key']
            : $contact->preferred_role_key;
        $contact->source = (string) ($data['source'] ?? $contact->source ?? 'site_team');
        $contact->last_used_at = now();
        $contact->save();

        return $contact;
    }

    /**
     * @return array{id: int|string, full_name: string, company_name: ?string, mobile: ?string, email: ?string, id_card: ?string, licence_type: ?string, licence_number: ?string, preferred_role_key: ?string, locality: ?string, source: string}
     */
    public function toSuggestPayload(): array
    {
        return [
            'id' => 'contact-'.$this->id,
            'full_name' => (string) $this->full_name,
            'company_name' => $this->company_name,
            'mobile' => $this->mobile,
            'email' => $this->email,
            'id_card' => $this->id_card,
            'licence_type' => $this->licence_type,
            'licence_number' => $this->licence_number,
            'preferred_role_key' => $this->preferred_role_key,
            'locality' => $this->locality,
            'source' => (string) ($this->source ?: 'saved'),
        ];
    }
}
