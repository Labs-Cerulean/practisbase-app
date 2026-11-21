<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Support\CatalogColumns;
use App\Support\MedicalVaultCrypto;
use App\Support\PatientIdentitySeal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PatientListSealTest extends TestCase
{
    protected function tearDown(): void
    {
        config([
            'app.env' => 'testing',
            'app.url' => 'http://localhost',
            'deployment.railway_environment' => '',
        ]);
        CatalogColumns::forget();
        parent::tearDown();
    }

    public function test_a_sealed_patient_list_does_not_read_the_chart(): void
    {
        Schema::dropIfExists('patients');
        Schema::create('patients', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('vault_id')->default(1);
            $table->string('public_ref');
            $table->unsignedBigInteger('billing_client_id')->nullable();
            $table->text('payload_ciphertext');
            $table->string('payload_nonce');
            $table->text('identity_ciphertext')->nullable();
            $table->string('identity_nonce')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });
        CatalogColumns::forget();

        $key = random_bytes(32);
        $seal = PatientIdentitySeal::encrypt([
            'display_name' => 'Ana Vella',
            'notes' => 'This clinical note must not be selected',
        ], $key);

        DB::table('patients')->insert([
            'user_id' => 5,
            'vault_id' => 1,
            'public_ref' => 'PAT-SEAL',
            'payload_ciphertext' => 'FULL-CHART-SECRET-NOTE',
            'payload_nonce' => 'full-nonce',
            'identity_ciphertext' => $seal['ciphertext'],
            'identity_nonce' => $seal['nonce'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sql = [];
        DB::listen(function ($query) use (&$sql) {
            $sql[] = $query->sql;
        });

        $patients = Patient::query()->where('user_id', 5)->get(PatientIdentitySeal::listColumns());
        PatientIdentitySeal::attachPayloads($patients, 5);

        $this->assertStringNotContainsString('payload_ciphertext', implode("\n", $sql));
        $this->assertArrayNotHasKey('payload_ciphertext', $patients->first()->getAttributes());

        [$opened, $pending] = PatientIdentitySeal::open($patients->first(), $key);
        $this->assertNull($pending);
        $this->assertSame('Ana Vella', $opened['display_name']);
        $this->assertArrayNotHasKey('notes', $opened);
    }

    public function test_staging_adds_the_seal_columns_and_live_does_not(): void
    {
        $this->createPatientsWithoutSeal();
        config([
            'app.env' => 'production',
            'deployment.railway_environment' => 'production',
            'app.url' => 'https://practisbase.com',
        ]);
        CatalogColumns::forget();

        $this->assertFalse(PatientIdentitySeal::columnsReady());
        $this->assertFalse(Schema::hasColumn('patients', 'identity_ciphertext'));

        config([
            'app.env' => 'staging',
            'deployment.railway_environment' => 'staging',
            'app.url' => 'https://practisbase-staging.up.railway.app',
        ]);
        CatalogColumns::forget();

        $this->assertTrue(PatientIdentitySeal::columnsReady());
        $this->assertTrue(Schema::hasColumn('patients', 'identity_ciphertext'));
        $this->assertTrue(Schema::hasColumn('patients', 'identity_nonce'));
    }

    public function test_staging_pointed_at_the_live_address_does_not_alter_patients(): void
    {
        $this->createPatientsWithoutSeal();
        config([
            'app.env' => 'staging',
            'deployment.railway_environment' => 'staging',
            'app.url' => 'https://practisbase.com',
        ]);
        CatalogColumns::forget();

        $this->assertFalse(PatientIdentitySeal::columnsReady());
        $this->assertFalse(Schema::hasColumn('patients', 'identity_ciphertext'));
    }

    private function createPatientsWithoutSeal(): void
    {
        Schema::dropIfExists('patients');
        Schema::create('patients', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->text('payload_ciphertext');
            $table->string('payload_nonce');
            $table->timestamps();
        });
    }
}
