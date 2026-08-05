<?php

namespace App\Support;

use App\Models\ArchitectClient;
use App\Models\Client;
use App\Models\EngineerClient;
use App\Models\User;

/**
 * Keep practice contacts (architect/engineer) linked to the unified Clients list.
 *
 * Direction A — billing → practice: new/updated /clients rows get a practice mirror
 * so project forms can select them.
 *
 * Direction B — practice → billing: older practice-only rows (no billing_client_id)
 * are imported into /clients so they remain visible after nav unification.
 *
 * Do not drop architect_clients / engineer_clients yet: projects and documents still
 * foreign-key those tables. They act as practice mirrors, not a second directory.
 */
class PracticeClientBridge
{
    public static function syncFromBillingClient(User $user, Client $client): void
    {
        if ($user->canAccessProPackage('arch')) {
            self::upsertArchitect($user->id, $client);
        }
        if ($user->canAccessProPackage('eng')) {
            self::upsertEngineer($user->id, $client);
        }
    }

    /**
     * Import practice-only contacts into the unified Clients list.
     * Does not consume Free-tier lifetime slots (pre-existing practice contacts).
     *
     * @return int Number of practice rows newly linked (or created) to a billing client
     */
    public static function importOrphanPracticeClients(User $user): int
    {
        $linked = 0;

        if ($user->canAccessProPackage('arch')) {
            $orphans = ArchitectClient::where('user_id', $user->id)
                ->whereNull('billing_client_id')
                ->orderBy('id')
                ->get();
            foreach ($orphans as $practice) {
                self::ensureBillingLinkForArchitect($user, $practice);
                $linked++;
            }
        }

        if ($user->canAccessProPackage('eng')) {
            $orphans = EngineerClient::where('user_id', $user->id)
                ->whereNull('billing_client_id')
                ->orderBy('id')
                ->get();
            foreach ($orphans as $practice) {
                self::ensureBillingLinkForEngineer($user, $practice);
                $linked++;
            }
        }

        return $linked;
    }

    public static function upsertArchitect(int $userId, Client $client): ArchitectClient
    {
        $existing = ArchitectClient::where('user_id', $userId)
            ->where('billing_client_id', $client->id)
            ->first();

        $payload = [
            'name' => $client->name,
            'email' => $client->email,
            'phone' => $client->phone,
            'address' => $client->billing_address,
            'billing_client_id' => $client->id,
        ];

        if ($existing) {
            $existing->fill($payload);
            $existing->save();

            return $existing;
        }

        return ArchitectClient::create(array_merge($payload, [
            'user_id' => $userId,
        ]));
    }

    public static function upsertEngineer(int $userId, Client $client): EngineerClient
    {
        $existing = EngineerClient::where('user_id', $userId)
            ->where('billing_client_id', $client->id)
            ->first();

        $payload = [
            'name' => $client->name,
            'email' => $client->email,
            'phone' => $client->phone,
            'address' => $client->billing_address,
            'billing_client_id' => $client->id,
        ];

        if ($existing) {
            $existing->fill($payload);
            $existing->save();

            return $existing;
        }

        return EngineerClient::create(array_merge($payload, [
            'user_id' => $userId,
        ]));
    }

    private static function ensureBillingLinkForArchitect(User $user, ArchitectClient $practice): Client
    {
        $billing = self::findOrCreateBillingFromPractice($user, $practice->name, $practice->email, $practice->phone, $practice->address, $practice->id_card);
        $practice->billing_client_id = $billing->id;
        $practice->save();

        return $billing;
    }

    private static function ensureBillingLinkForEngineer(User $user, EngineerClient $practice): Client
    {
        $billing = self::findOrCreateBillingFromPractice($user, $practice->name, $practice->email, $practice->phone, $practice->address, $practice->id_card);
        $practice->billing_client_id = $billing->id;
        $practice->save();

        return $billing;
    }

    private static function findOrCreateBillingFromPractice(
        User $user,
        ?string $name,
        ?string $email,
        ?string $phone,
        ?string $address,
        ?string $idCard
    ): Client {
        $name = trim((string) $name);
        if ($name === '') {
            $name = 'Practice contact';
        }

        $email = trim((string) $email) ?: null;
        $phone = trim((string) $phone) ?: null;
        $address = trim((string) $address) ?: null;

        $query = Client::withTrashed()->where('user_id', $user->id);
        if ($email) {
            $match = (clone $query)->whereRaw('LOWER(email) = ?', [strtolower($email)])->first();
            if ($match) {
                if ($match->trashed()) {
                    $match->restore();
                }

                return $match;
            }
        }

        $match = $query->whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
        if ($match) {
            if ($match->trashed()) {
                $match->restore();
            }

            return $match;
        }

        $profile = Client::billingProfileOnly([
            'id_card_number' => $idCard,
        ]);

        // Pre-existing practice contact — do not burn Free lifetime slots.
        return Client::create([
            'user_id' => $user->id,
            'type' => 'individual',
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'billing_address' => $address,
            'profile_data' => $profile,
        ]);
    }
}
