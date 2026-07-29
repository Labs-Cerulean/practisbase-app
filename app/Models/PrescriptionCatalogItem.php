<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-doctor plaintext medicine catalogue for prescription autocomplete.
 * Intentionally unencrypted — no patient data; scoped by user_id.
 */
class PrescriptionCatalogItem extends Model
{
    protected $table = 'prescription_catalog';

    protected $fillable = [
        'user_id',
        'medicine_name',
        'strength',
        'dose',
        'quantity',
        'instructions',
        'use_count',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'use_count' => 'integer',
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Upsert medicines from a prescription payload (store/update/issue).
     * Soft-fails if the catalog table is missing so clinical saves stay available.
     *
     * @param  list<array{name: string, strength?: string, dose?: string, quantity?: string, instructions?: string}>  $medicines
     */
    public static function rememberForUser(int $userId, array $medicines): void
    {
        try {
            foreach ($medicines as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $name = trim((string) ($row['name'] ?? ''));
                if ($name === '') {
                    continue;
                }

                $strength = trim((string) ($row['strength'] ?? ''));
                $dose = trim((string) ($row['dose'] ?? ''));
                $quantity = trim((string) ($row['quantity'] ?? ''));
                $instructions = trim((string) ($row['instructions'] ?? ''));

                $existing = static::query()
                    ->where('user_id', $userId)
                    ->whereRaw('LOWER(medicine_name) = ?', [mb_strtolower($name)])
                    ->whereRaw('LOWER(COALESCE(strength, \'\')) = ?', [mb_strtolower($strength)])
                    ->first();

                if ($existing) {
                    $existing->update([
                        'medicine_name' => $name,
                        'strength' => $strength !== '' ? $strength : $existing->strength,
                        'dose' => $dose !== '' ? $dose : $existing->dose,
                        'quantity' => $quantity !== '' ? $quantity : $existing->quantity,
                        'instructions' => $instructions !== '' ? $instructions : $existing->instructions,
                        'use_count' => (int) $existing->use_count + 1,
                        'last_used_at' => now(),
                    ]);
                } else {
                    static::create([
                        'user_id' => $userId,
                        'medicine_name' => $name,
                        'strength' => $strength,
                        'dose' => $dose,
                        'quantity' => $quantity,
                        'instructions' => $instructions,
                        'use_count' => 1,
                        'last_used_at' => now(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * @return list<array{id: int, medicine_name: string, strength: string, dose: string, quantity: string, instructions: string, use_count: int}>
     */
    public static function suggestForUser(int $userId, string $query, int $limit = 8): array
    {
        $query = trim($query);
        $limit = max(1, min(20, $limit));

        try {
            $builder = static::query()
                ->where('user_id', $userId)
                ->orderByDesc('use_count')
                ->orderByDesc('last_used_at')
                ->limit($limit);

            if ($query !== '') {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], mb_strtolower($query)).'%';
                $builder->where(function ($q) use ($like) {
                    $q->whereRaw('LOWER(medicine_name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(COALESCE(strength, \'\')) LIKE ?', [$like]);
                });
            }

            return $builder->get()->map(fn (self $item) => [
                'id' => $item->id,
                'medicine_name' => $item->medicine_name,
                'strength' => (string) ($item->strength ?? ''),
                'dose' => (string) ($item->dose ?? ''),
                'quantity' => (string) ($item->quantity ?? ''),
                'instructions' => (string) ($item->instructions ?? ''),
                'use_count' => (int) $item->use_count,
            ])->all();
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
    }
}
