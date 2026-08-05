<?php

namespace App\Support;

use App\Models\ArchitectClient;
use App\Models\Client;
use App\Models\EngineerClient;
use App\Models\User;

/**
 * Keep a practice contact (architect/engineer) mirrored from the unified Clients list
 * so projects can reference practice clients while nav only shows one Clients area.
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
}
