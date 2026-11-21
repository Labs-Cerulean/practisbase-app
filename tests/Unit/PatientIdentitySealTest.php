<?php

namespace Tests\Unit;

use App\Support\MedicalVaultCrypto;
use App\Support\PatientIdentitySeal;
use PHPUnit\Framework\TestCase;

class PatientIdentitySealTest extends TestCase
{
    public function test_the_seal_keeps_the_name_and_leaves_clinical_notes_out(): void
    {
        $key = random_bytes(32);
        $seal = PatientIdentitySeal::encrypt([
            'display_name' => 'Maria Borg',
            'date_of_birth' => '1980-01-02',
            'id_card' => '123456M',
            'tel' => '79000000',
            'email' => 'maria@example.com',
            'address' => 'A private address',
            'notes' => 'Clinical note that must stay in the chart only',
        ], $key);

        $opened = MedicalVaultCrypto::decrypt($seal['ciphertext'], $seal['nonce'], $key);

        $this->assertSame('Maria Borg', $opened['display_name']);
        $this->assertSame('123456M', $opened['id_card']);
        $this->assertArrayNotHasKey('notes', $opened);
        $this->assertArrayNotHasKey('address', $opened);
        $this->assertStringNotContainsString('Clinical note', $seal['ciphertext']);
        $this->assertStringNotContainsString('private address', $seal['ciphertext']);
    }
}
