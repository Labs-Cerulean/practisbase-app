<?php

namespace App\Support;

use App\Models\Patient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * A second secretbox for the patient list and the duplicate check.
 * Same vault key as the chart. It holds the name, ID, phone, email, and date of birth.
 * Clinical notes and the address stay in the full chart ciphertext only.
 */
class PatientIdentitySeal
{
    /**
     * @return list<string>
     */
    public static function listColumns(): array
    {
        $columns = [
            'id',
            'user_id',
            'vault_id',
            'public_ref',
            'billing_client_id',
            'archived_at',
            'created_at',
        ];

        if (self::columnsReady()) {
            $columns[] = 'identity_ciphertext';
            $columns[] = 'identity_nonce';

            return $columns;
        }

        $columns[] = 'payload_ciphertext';
        $columns[] = 'payload_nonce';

        return $columns;
    }

    public static function columnsReady(): bool
    {
        self::ensure();

        return self::present();
    }

    /**
     * Staging creates the seal columns on first use. Live waits for the SQL file.
     */
    public static function ensure(): void
    {
        if (self::present() || ! Deployment::isStaging()) {
            return;
        }

        try {
            Deployment::guardLiveAddress();
            foreach (self::statements() as $statement) {
                DB::unprepared($statement);
            }
        } catch (\Throwable $e) {
            report($e);

            return;
        }

        CatalogColumns::forget(['patients']);
    }

    private static function present(): bool
    {
        return CatalogColumns::hasColumn('patients', 'identity_ciphertext')
            && CatalogColumns::hasColumn('patients', 'identity_nonce');
    }

    /**
     * @return list<string>
     */
    private static function statements(): array
    {
        $path = database_path('manual/patient_identity_seal_postgresql.sql');
        $sql = is_file($path) ? (string) file_get_contents($path) : '';
        if ($sql === '') {
            return [];
        }

        if (DB::connection()->getDriverName() !== 'pgsql') {
            $sql = str_replace(' ADD COLUMN IF NOT EXISTS ', ' ADD COLUMN ', $sql);
        }

        $parts = preg_split('/;\s*/', trim($sql)) ?: [];

        return array_values(array_filter(array_map('trim', $parts), fn (string $statement) => $statement !== ''));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function fields(array $payload): array
    {
        return [
            'display_name' => $payload['display_name'] ?? null,
            'date_of_birth' => $payload['date_of_birth'] ?? null,
            'age' => $payload['age'] ?? null,
            'id_card' => $payload['id_card'] ?? null,
            'tel' => $payload['tel'] ?? null,
            'email' => $payload['email'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{ciphertext: string, nonce: string}
     */
    public static function encrypt(array $payload, string $key): array
    {
        return MedicalVaultCrypto::encrypt(self::fields($payload), $key);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function attributes(array $payload, string $key): array
    {
        if (! self::columnsReady()) {
            return [];
        }

        $seal = self::encrypt($payload, $key);

        return [
            'identity_ciphertext' => $seal['ciphertext'],
            'identity_nonce' => $seal['nonce'],
        ];
    }

    /**
     * Load full charts only for rows that do not yet have a seal.
     *
     * @param  Collection<int, Patient>  $patients
     */
    public static function attachPayloads(Collection $patients, int $userId): void
    {
        if (! self::columnsReady() || $patients->isEmpty()) {
            return;
        }

        $missingIds = [];
        foreach ($patients as $patient) {
            if (! filled($patient->identity_ciphertext) || ! filled($patient->identity_nonce)) {
                $missingIds[] = $patient->id;
            }
        }

        if ($missingIds === []) {
            return;
        }

        $payloads = Patient::query()
            ->where('user_id', $userId)
            ->whereIn('id', $missingIds)
            ->get(['id', 'payload_ciphertext', 'payload_nonce'])
            ->keyBy('id');

        foreach ($patients as $patient) {
            $row = $payloads->get($patient->id);
            if ($row === null) {
                continue;
            }
            $patient->payload_ciphertext = $row->payload_ciphertext;
            $patient->payload_nonce = $row->payload_nonce;
        }
    }

    /**
     * @return array{0: array<string, mixed>, 1: ?array{ciphertext: string, nonce: string}}
     */
    public static function open(Patient $patient, string $key): array
    {
        if (self::columnsReady() && filled($patient->identity_ciphertext) && filled($patient->identity_nonce)) {
            try {
                return [MedicalVaultCrypto::decrypt($patient->identity_ciphertext, $patient->identity_nonce, $key), null];
            } catch (\Throwable) {
                // A damaged seal falls through to the chart. The name must still open.
            }
        }

        $full = MedicalVaultCrypto::decrypt($patient->payload_ciphertext, $patient->payload_nonce, $key);
        $seal = self::columnsReady() ? self::encrypt($full, $key) : null;

        return [self::fields($full), $seal];
    }

    /**
     * @param  array{ciphertext: string, nonce: string}  $seal
     */
    public static function persist(int $userId, int $patientId, array $seal): void
    {
        Patient::query()
            ->where('user_id', $userId)
            ->where('id', $patientId)
            ->whereNull('identity_ciphertext')
            ->update([
                'identity_ciphertext' => $seal['ciphertext'],
                'identity_nonce' => $seal['nonce'],
            ]);
    }
}
