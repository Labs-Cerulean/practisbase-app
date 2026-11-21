<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\User;
use App\Support\CatalogColumns;
use App\Support\FiscalYearGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Tests\TestCase;

class ClickSpeedTest extends TestCase
{
    protected function tearDown(): void
    {
        CatalogColumns::forget();
        $this->flushClosedYears();
        parent::tearDown();
    }

    public function test_verified_gate_does_not_query_when_the_flag_is_already_loaded(): void
    {
        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });

        $user = new User;
        $user->setRawAttributes([
            'must_verify_email' => true,
            'email_verified_at' => null,
        ], true);

        $this->assertTrue($user->mustVerifyEmailNow());
        $this->assertSame([], $queries);

        $user->setRawAttributes([
            'must_verify_email' => false,
            'email_verified_at' => null,
        ], true);
        $this->assertFalse($user->mustVerifyEmailNow());
        $this->assertSame([], $queries);
    }

    public function test_ledger_year_lock_is_one_query_for_every_document_on_the_page(): void
    {
        Schema::dropIfExists('fiscal_years');
        Schema::create('fiscal_years', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->integer('year');
        });
        DB::table('fiscal_years')->insert([
            ['user_id' => 5, 'year' => 2024],
            ['user_id' => 5, 'year' => 2025],
            ['user_id' => 9, 'year' => 2023],
        ]);

        $this->flushClosedYears();

        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        $this->assertTrue(FiscalYearGuard::isClosed(5, 2024));
        $this->assertTrue(FiscalYearGuard::isClosed(5, 2025));
        $this->assertFalse(FiscalYearGuard::isClosed(5, 2026));
        $this->assertSame(1, $queries);

        $this->assertFalse(FiscalYearGuard::isClosed(9, 2026));
        $this->assertTrue(FiscalYearGuard::isClosed(9, 2023));
        $this->assertSame(2, $queries);
    }

    public function test_invoice_action_view_asks_fiscal_years_once_for_the_whole_list(): void
    {
        Schema::dropIfExists('fiscal_years');
        Schema::create('fiscal_years', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->integer('year');
        });
        DB::table('fiscal_years')->insert([
            ['user_id' => 5, 'year' => 2024],
        ]);
        $this->flushClosedYears();

        $user = new User;
        $user->id = 5;
        $user->exists = true;
        Auth::login($user);

        $fiscalQueries = 0;
        DB::listen(function ($query) use (&$fiscalQueries) {
            if (str_contains($query->sql, 'fiscal_years')) {
                $fiscalQueries++;
            }
        });

        for ($i = 1; $i <= 8; $i++) {
            $document = new Invoice;
            $document->forceFill([
                'id' => $i,
                'type' => 'invoice',
                'status' => 'paid',
                'issue_date' => '2024-06-0'.min($i, 9),
                'total' => 10,
                'amount_paid' => 10,
                'parent_document_id' => null,
            ]);
            $document->setRelation('payments', collect());
            $document->setRelation('childDocuments', collect());

            $html = view('invoices.partials.actions', [
                'document' => $document,
                'maxPayable' => 0,
                'familyBalance' => 0,
            ])->render();

            $this->assertStringContainsString('Download PDF', $html);
        }

        $this->assertSame(1, $fiscalQueries);
    }

    public function test_a_loaded_catalog_does_not_query_again_for_the_same_tables(): void
    {
        Schema::dropIfExists('speed_alpha');
        Schema::dropIfExists('speed_beta');
        Schema::create('speed_alpha', function ($table) {
            $table->id();
            $table->string('name');
        });
        Schema::create('speed_beta', function ($table) {
            $table->id();
            $table->timestamp('deleted_at')->nullable();
        });

        CatalogColumns::forget();

        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        CatalogColumns::load(['speed_alpha', 'speed_beta']);
        $afterLoad = $queries;

        $this->assertTrue(CatalogColumns::hasTable('speed_alpha'));
        $this->assertTrue(CatalogColumns::hasColumn('speed_alpha', 'name'));
        $this->assertTrue(CatalogColumns::hasColumn('speed_beta', 'deleted_at'));
        $this->assertFalse(CatalogColumns::hasColumn('speed_beta', 'missing'));
        $this->assertSame($afterLoad, $queries);
        $this->assertGreaterThan(0, $afterLoad);
    }

    private function flushClosedYears(): void
    {
        $property = (new ReflectionClass(FiscalYearGuard::class))->getProperty('closedYears');
        $property->setValue(null, []);
    }
}
