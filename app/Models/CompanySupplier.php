<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;

class CompanySupplier extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'vat_number',
        'email',
        'country',
        'address',
        'default_category',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(CompanyExpense::class);
    }

    /**
     * Reuse a supplier with the same name for this user, filling only blank details.
     *
     * @param  array{vat_number?: ?string, email?: ?string, country?: ?string, address?: ?string, default_category?: ?string}  $details
     */
    public static function resolveForUser(int $userId, string $name, array $details = []): self
    {
        $name = trim(preg_replace('/\s+/', ' ', $name) ?? '');
        $existing = self::where('user_id', $userId)
            ->whereRaw('lower(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing) {
            $fill = [];
            foreach (['vat_number', 'email', 'country', 'address', 'default_category'] as $field) {
                if (blank($existing->{$field}) && filled($details[$field] ?? null)) {
                    $fill[$field] = $details[$field];
                }
            }
            if ($fill !== []) {
                $existing->update($fill);
            }

            return $existing;
        }

        try {
            return self::create([
                'user_id' => $userId,
                'name' => $name,
                'vat_number' => $details['vat_number'] ?? null,
                'email' => $details['email'] ?? null,
                'country' => $details['country'] ?? null,
                'address' => $details['address'] ?? null,
                'default_category' => $details['default_category'] ?? null,
            ]);
        } catch (QueryException $e) {
            $again = self::where('user_id', $userId)
                ->whereRaw('lower(name) = ?', [mb_strtolower($name)])
                ->first();
            if ($again) {
                return $again;
            }
            throw $e;
        }
    }
}
