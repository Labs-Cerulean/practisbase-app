<?php

namespace Tests\Feature;

use App\Models\CompanyBooksLock;
use App\Models\CompanyExpense;
use App\Models\CompanyExpensePayment;
use App\Models\CompanyProfile;
use App\Models\CompanySupplier;
use App\Models\User;
use App\Support\CompanyExpenseFiles;
use App\Support\FiscalYearGuard;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use ReflectionClass;
use Tests\TestCase;

class CompanyExpenseReceiptViewTest extends TestCase
{
    private User $owner;

    private User $other;

    protected function setUp(): void
    {
        parent::setUp();
        $this->flushSession();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
        $this->app->forgetInstance('encrypter');
        $closedYears = (new ReflectionClass(FiscalYearGuard::class))->getProperty('closedYears');
        $closedYears->setAccessible(true);
        $closedYears->setValue(null, []);
        Carbon::setTestNow('2026-11-21');
        config(['filesystems.tenant_disk' => 'local']);
        Storage::fake('local');
        $this->createTables();
        $this->owner = $this->companyUser(41, 'Nicholas');
        $this->other = $this->companyUser(42, 'Someone else');
        $this->profileFor(41);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_the_open_line_shows_the_receipt_and_the_payment_proof_beside_the_posting(): void
    {
        $supplier = $this->supplier(41, 'Cyberport SE');
        $hosting = $this->expense($supplier, [
            'description' => 'Hosting invoice',
            'amount' => 100,
            'vat_amount' => 18,
            'funded_by' => 'payable',
            'receipt_path' => $this->store('tenants/41/company/receipts/hosting.png', $this->pngBytes()),
        ]);
        $hostingPayment = $this->payment(41, 'tenants/41/company/payments/hosting.pdf', $this->pdfBytes('AUDIT-PDF-HOSTING'), 'supplier_payment', 118);
        $hosting->update(['company_expense_payment_id' => $hostingPayment->id]);

        $photo = $this->expense(null, [
            'description' => 'Cursor photo',
            'amount' => 10,
            'vat_amount' => 0,
            'funded_by' => 'company',
            'receipt_path' => $this->store('tenants/41/company/receipts/cursor.jpg', 'AUDIT-JPEG-CURSOR'),
        ]);

        $lunch = $this->expense($supplier, [
            'description' => 'Director lunch',
            'amount' => 20,
            'vat_amount' => 0,
            'funded_by' => 'director',
            'director_refunded_at' => '2026-11-04',
        ]);
        $lunchPayment = $this->payment(41, 'tenants/41/company/payments/lunch.jpg', 'AUDIT-JPEG-LUNCH', 'director_refund', 20);
        $lunch->update(['company_expense_payment_id' => $lunchPayment->id]);

        $reversed = $this->expense($supplier, [
            'description' => 'Wrong subscription',
            'amount' => 5,
            'vat_amount' => 0,
            'funded_by' => 'payable',
            'reversed_at' => '2026-11-05',
            'receipt_path' => $this->store('tenants/41/company/receipts/wrong.png', $this->pngBytes()),
        ]);
        $reversedPayment = $this->payment(41, 'tenants/41/company/payments/wrong.pdf', 'SHOULD-NOT-EMBED', 'supplier_payment', 5);
        $reversed->update(['company_expense_payment_id' => $reversedPayment->id]);

        $foreign = $this->expense(null, [
            'user_id' => 42,
            'description' => 'Foreign filing',
            'amount' => 50,
            'receipt_path' => $this->store('tenants/42/company/receipts/foreign.pdf', 'OTHER-USER-SECRET'),
        ]);

        $sql = [];
        DB::listen(function ($query) use (&$sql) {
            $sql[] = $query->sql;
        });

        $response = $this->actingAs($this->owner)->get('/company/expenses');
        $html = $response->getContent();

        $response->assertOk();
        $response->assertSee('Hosting invoice');
        $response->assertSee('Cursor photo');
        $response->assertSee('Director lunch');
        $response->assertSee('€148.00');
        $response->assertSee('€118.00');
        $response->assertSee('ex-VAT €100.00');
        $response->assertSee('VAT €18.00');
        $response->assertSee('Paid 03 Nov 2026');
        $response->assertSee('Refunded 04 Nov 2026');
        $response->assertDontSee('Foreign filing');
        $response->assertDontSee('OTHER-USER-SECRET');
        $response->assertDontSee('tenants/41/company/receipts');
        $response->assertDontSee('tenants/42/');

        $this->assertStringContainsString('data-expense-line', $html);
        $this->assertStringContainsString('class="expense-audit has-file"', $html);
        $this->assertStringNotContainsString('<iframe', $html);
        $this->assertStringContainsString('/js/pdfjs/pdf.min.js', $html);
        $this->assertStringContainsString('disableWorker: true', $html);
        $this->assertMatchesRegularExpression(
            '/<img\b[^>]*alt="Receipt for Hosting invoice"[^>]*src="\/company\/expenses\/'.$hosting->id.'\/receipt"/',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/<div\b[^>]*data-pdf[^>]*data-file="\/company\/expenses\/payments\/'.$hostingPayment->id.'\/proof"/',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/<img\b[^>]*src="\/company\/expenses\/'.$photo->id.'\/receipt"/',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/<img\b[^>]*alt="Payment proof for Director lunch"[^>]*src="\/company\/expenses\/payments\/'.$lunchPayment->id.'\/proof"/',
            $html
        );
        $this->assertStringContainsString('/company/expenses/'.$reversed->id.'/receipt', $html);
        $this->assertStringNotContainsString('/company/expenses/payments/'.$reversedPayment->id.'/proof', $html);
        $this->assertStringNotContainsString('/company/expenses/'.$foreign->id.'/', $html);
        $this->assertStringContainsString('/company/expenses/'.$hosting->id.'/receipt?download=1', $html);
        $this->assertDoesNotMatchRegularExpression('/<a\b[^>]*href="\/company\/expenses\/\d+\/receipt"/', $html);
        $this->assertDoesNotMatchRegularExpression('/<a\b[^>]*href="\/company\/expenses\/payments\/\d+\/proof"/', $html);

        $joined = implode("\n", $sql);
        $this->assertStringNotContainsString('company_journal', $joined);
        $this->assertStringNotContainsString('company_gl_accounts', $joined);
        foreach ($sql as $statement) {
            if (str_contains($statement, 'company_expenses') || str_contains($statement, 'company_expense_payments')) {
                $this->assertStringContainsString('user_id', $statement);
            }
        }
    }

    public function test_the_owner_views_the_receipt_on_the_page_and_can_still_download_it(): void
    {
        $png = $this->pngBytes();
        $expense = $this->expense(null, [
            'description' => 'Hosting invoice',
            'receipt_path' => $this->store('tenants/41/company/receipts/hosting.png', $png),
        ]);
        $jpeg = $this->expense(null, [
            'description' => 'Cursor photo',
            'receipt_path' => $this->store('tenants/41/company/receipts/cursor.jpg', 'AUDIT-JPEG-CURSOR'),
        ]);

        $inline = $this->actingAs($this->owner)->get('/company/expenses/'.$expense->id.'/receipt');
        $inline->assertOk();
        $inline->assertHeader('content-type', 'image/png');
        $inline->assertHeader('x-content-type-options', 'nosniff');
        $inline->assertHeader('x-frame-options', 'SAMEORIGIN');
        $disposition = (string) $inline->headers->get('content-disposition');
        $this->assertStringContainsString('inline', $disposition);
        $this->assertStringContainsString('receipt.png', $disposition);
        $this->assertStringNotContainsString('attachment', $disposition);
        $this->assertSame($png, $inline->streamedContent());

        $photo = $this->actingAs($this->owner)->get('/company/expenses/'.$jpeg->id.'/receipt');
        $photo->assertOk();
        $photo->assertHeader('content-type', 'image/jpeg');
        $this->assertSame('AUDIT-JPEG-CURSOR', $photo->streamedContent());

        $download = $this->actingAs($this->owner)->get('/company/expenses/'.$expense->id.'/receipt?download=1');
        $download->assertOk();
        $this->assertStringContainsString('attachment', (string) $download->headers->get('content-disposition'));
        $this->assertSame($png, $download->streamedContent());
    }

    public function test_another_users_receipt_and_proof_are_not_served(): void
    {
        $secret = 'OTHER-USER-SECRET';
        $foreignExpense = $this->expense(null, [
            'user_id' => 42,
            'description' => 'Foreign filing',
            'receipt_path' => $this->store('tenants/42/company/receipts/foreign.pdf', $secret),
        ]);
        $foreignPayment = $this->payment(42, 'tenants/42/company/payments/foreign.pdf', $secret, 'supplier_payment', 50);
        $pointing = $this->expense(null, [
            'description' => 'Posted here',
            'funded_by' => 'payable',
            'company_expense_payment_id' => $foreignPayment->id,
        ]);

        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query;
        });

        $receipt = $this->actingAs($this->owner)->get('/company/expenses/'.$foreignExpense->id.'/receipt');
        $receipt->assertNotFound();
        $this->assertStringNotContainsString($secret, $receipt->getContent());

        $proof = $this->actingAs($this->owner)->get('/company/expenses/payments/'.$foreignPayment->id.'/proof');
        $proof->assertNotFound();
        $this->assertStringNotContainsString($secret, $proof->getContent());

        $page = $this->actingAs($this->owner)->get('/company/expenses');
        $page->assertOk();
        $page->assertSee('Posted here');
        $page->assertDontSee('Foreign filing');
        $page->assertDontSee($secret);
        $page->assertDontSee('/company/expenses/payments/'.$foreignPayment->id.'/proof');
        $this->assertStringNotContainsString('data-file="/company/expenses/'.$pointing->id.'/receipt"', $page->getContent());
        $page->assertSee('data-drop-url="/company/expenses/'.$pointing->id.'/receipt"', false);
        $page->assertSee('class="expense-missing-file"', false);

        foreach ($queries as $query) {
            if (! str_contains($query->sql, 'company_expenses') && ! str_contains($query->sql, 'company_expense_payments')) {
                continue;
            }
            $this->assertStringContainsString('user_id', $query->sql);
            $ids = array_map(fn ($value) => is_numeric($value) ? (int) $value : $value, $query->bindings);
            $this->assertNotContains(42, $ids);
        }

        $own = $this->actingAs($this->other)->get('/company/expenses/'.$foreignExpense->id.'/receipt');
        $own->assertOk();
        $this->assertSame($secret, $own->streamedContent());
    }

    public function test_a_guest_is_sent_to_login(): void
    {
        $expense = $this->expense(null, [
            'description' => 'Hosting invoice',
            'receipt_path' => $this->store('tenants/41/company/receipts/hosting.png', $this->pngBytes()),
        ]);

        $this->get('/company/expenses/'.$expense->id.'/receipt')->assertRedirect('/login');
        $this->get('/company/expenses')->assertRedirect('/login');
    }

    public function test_a_path_outside_this_users_folder_is_refused(): void
    {
        $secret = 'PATH-ESCAPE-SECRET';
        $this->store('tenants/42/company/receipts/foreign.pdf', $secret);
        $this->store('tenants/42/company/payments/foreign.pdf', $secret);
        $this->store('tenants/41/company/receipts/note.html', '<script>alert(1)</script>');

        $expense = $this->expense(null, [
            'description' => 'Hosting invoice',
            'receipt_path' => 'tenants/42/company/receipts/foreign.pdf',
        ]);
        $payment = $this->payment(41, 'tenants/42/company/payments/foreign.pdf', $secret, 'supplier_payment', 10);
        $expense->update([
            'funded_by' => 'payable',
            'company_expense_payment_id' => $payment->id,
        ]);

        $stolen = $this->actingAs($this->owner)->get('/company/expenses/'.$expense->id.'/receipt');
        $stolen->assertNotFound();
        $this->assertStringNotContainsString($secret, $stolen->getContent());

        $stolenProof = $this->actingAs($this->owner)->get('/company/expenses/payments/'.$payment->id.'/proof');
        $stolenProof->assertNotFound();
        $this->assertStringNotContainsString($secret, $stolenProof->getContent());

        $page = $this->actingAs($this->owner)->get('/company/expenses');
        $page->assertOk();
        $page->assertSee('Hosting invoice');
        $page->assertDontSee('data-file="/company/expenses/'.$expense->id.'/receipt"', false);
        $page->assertDontSee('/company/expenses/payments/'.$payment->id.'/proof');
        $page->assertDontSee($secret);

        $expense->update(['receipt_path' => 'tenants/41/company/receipts/../../42/company/receipts/foreign.pdf']);
        $climbed = $this->actingAs($this->owner)->get('/company/expenses/'.$expense->id.'/receipt');
        $climbed->assertNotFound();
        $this->assertStringNotContainsString($secret, $climbed->getContent());

        $expense->update(['receipt_path' => 'tenants/41/company/receipts/note.html']);
        $html = $this->actingAs($this->owner)->get('/company/expenses/'.$expense->id.'/receipt');
        $html->assertNotFound();
        $this->assertStringNotContainsString('alert(1)', $html->getContent());

        $list = $this->actingAs($this->owner)->get('/company/expenses');
        $list->assertDontSee('data-file="/company/expenses/'.$expense->id.'/receipt"', false);
        $list->assertDontSee('alert(1)');

        $expense->update(['receipt_path' => 'tenants/41/company/receipts/missing.png']);
        $this->actingAs($this->owner)->get('/company/expenses/'.$expense->id.'/receipt')->assertNotFound();

        $png = $this->pngBytes();
        $this->store('tenants/41/company/receipts/slash.png', $png);
        $expense->update(['receipt_path' => '/tenants/41/company/receipts/slash.png']);
        $slash = $this->actingAs($this->owner)->get('/company/expenses/'.$expense->id.'/receipt');
        $slash->assertOk();
        $this->assertSame($png, $slash->streamedContent());

        $expense->update(['receipt_path' => 'storage/app/private/tenants/41/company/receipts/slash.png']);
        $rooted = $this->actingAs($this->owner)->get('/company/expenses/'.$expense->id.'/receipt');
        $rooted->assertOk();
        $this->assertSame($png, $rooted->streamedContent());

        $this->store('tenants/41/company/receipts/photo.jfif', $png);
        $expense->update(['receipt_path' => 'tenants/41/company/receipts/photo.jfif']);
        $jfif = $this->actingAs($this->owner)->get('/company/expenses/'.$expense->id.'/receipt');
        $jfif->assertOk();
        $jfif->assertHeader('content-type', 'image/jpeg');

        $expense->update(['receipt_path' => 'storage/app/private/tenants/42/company/receipts/foreign.pdf']);
        $foreign = $this->actingAs($this->owner)->get('/company/expenses/'.$expense->id.'/receipt');
        $foreign->assertNotFound();
        $this->assertStringNotContainsString($secret, $foreign->getContent());

        $this->store('var/www/html/storage/app/private/tenants/41/company/receipts/buried.png', $png);
        $expense->update(['receipt_path' => '/var/www/html/storage/app/private/tenants/41/company/receipts/buried.png']);
        $buried = $this->actingAs($this->owner)->get('/company/expenses/'.$expense->id.'/receipt');
        $buried->assertOk();
        $this->assertSame($png, $buried->streamedContent());

        $expense->update(['receipt_path' => 'https://files.example.test/bucket/tenants/41/company/receipts/slash.png?sig=abc']);
        $linked = $this->actingAs($this->owner)->get('/company/expenses/'.$expense->id.'/receipt');
        $linked->assertOk();
        $this->assertSame($png, $linked->streamedContent());
        $page = $this->actingAs($this->owner)->get('/company/expenses');
        $page->assertOk();
        $page->assertSee('src="/company/expenses/'.$expense->id.'/receipt"', false);

        $expense->update(['receipt_path' => 'https://evil.example/tenants/42/company/receipts/foreign.pdf']);
        $remote = $this->actingAs($this->owner)->get('/company/expenses/'.$expense->id.'/receipt');
        $remote->assertNotFound();
        $this->assertStringNotContainsString($secret, $remote->getContent());

        $expense->update(['receipt_path' => 'https://evil.example/tenants/41/company/receipts/../../42/company/receipts/foreign.pdf']);
        $climbedUrl = $this->actingAs($this->owner)->get('/company/expenses/'.$expense->id.'/receipt');
        $climbedUrl->assertNotFound();
        $this->assertStringNotContainsString($secret, $climbedUrl->getContent());

        $this->store('evil-tenants/41/company/receipts/slash.png', 'NOT-THE-FILE');
        $expense->update(['receipt_path' => 'evil-tenants/41/company/receipts/slash.png']);
        $this->actingAs($this->owner)->get('/company/expenses/'.$expense->id.'/receipt')->assertNotFound();
    }

    public function test_a_read_invoice_left_in_drafts_is_shown_only_for_this_user(): void
    {
        $supplier = $this->supplier(41, 'Arkadia Development Ltd');
        $drink = $this->expense($supplier, [
            'expense_date' => '2026-11-13',
            'description' => 'Drink',
            'supplier_invoice_number' => 'T000902-28933',
            'amount' => 6.36,
            'vat_amount' => 1.14,
            'funded_by' => 'company',
            'receipt_path' => null,
        ]);
        $lunch = $this->expense($supplier, [
            'expense_date' => '2026-11-20',
            'description' => 'Bought client a quick lunch',
            'supplier_invoice_number' => '05/74833',
            'amount' => 4,
            'vat_amount' => 0,
            'category' => 'general',
            'funded_by' => 'company',
            'receipt_path' => null,
        ]);

        $drinkPdf = $this->invoicePdf('T000902-28933');
        $lunchPdf = $this->invoicePdf('05/74833');
        $this->store('tenants/41/company/receipts/drafts/'.str_repeat('a', 40).'.pdf', $drinkPdf);
        $this->store('tenants/41/company/receipts/drafts/'.str_repeat('b', 40).'.pdf', $lunchPdf);
        $this->store('tenants/42/company/receipts/drafts/'.str_repeat('c', 40).'.pdf', $this->invoicePdf('OTHER-USER-SECRET T000902-28933'));
        $this->store('tenants/41/receipts/older.pdf', $this->invoicePdf('05/74833'));
        $this->store('tenants/41/company/payments/proof.pdf', $this->invoicePdf('05/74833'));

        $page = $this->actingAs($this->owner)->get('/company/expenses');
        $page->assertOk();
        $page->assertSee('data-file="/company/expenses/'.$drink->id.'/receipt"', false);
        $page->assertSee('data-file="/company/expenses/'.$lunch->id.'/receipt"', false);
        $page->assertDontSee('OTHER-USER-SECRET');
        $page->assertDontSee('tenants/41/company/receipts/drafts');

        $served = $this->actingAs($this->owner)->get('/company/expenses/'.$drink->id.'/receipt');
        $served->assertOk();
        $served->assertHeader('content-type', 'application/pdf');
        $this->assertSame($drinkPdf, $served->streamedContent());
        $this->assertStringNotContainsString('OTHER-USER-SECRET', $served->streamedContent());

        $lunchServed = $this->actingAs($this->owner)->get('/company/expenses/'.$lunch->id.'/receipt');
        $lunchServed->assertOk();
        $this->assertSame($lunchPdf, $lunchServed->streamedContent());

        $this->store(
            'tenants/41/company/receipts/drafts/'.str_repeat('d', 40).'.pdf',
            $this->invoicePdf('T000902-28933 SECOND-COPY')
        );
        $again = $this->actingAs($this->owner)->get('/company/expenses');
        $again->assertOk();
        $again->assertDontSee('data-file="/company/expenses/'.$drink->id.'/receipt"', false);
        $again->assertSee('data-file="/company/expenses/'.$lunch->id.'/receipt"', false);
        $this->actingAs($this->owner)->get('/company/expenses/'.$drink->id.'/receipt')->assertNotFound();

        $this->assertNull(CompanyExpenseFiles::listedObject(
            'tenants/41/company/receipts/drafts',
            'tenants/42/company/receipts/drafts/abc.pdf'
        ));
        $this->assertNull(CompanyExpenseFiles::listedObject(
            'tenants/41/company/receipts/drafts',
            '../abc.pdf'
        ));
        $this->assertSame(
            'tenants/41/company/receipts/drafts/abc.pdf',
            CompanyExpenseFiles::listedObject('tenants/41/company/receipts/drafts', 'abc.pdf')
        );
    }

    public function test_a_scan_with_no_text_is_not_guessed_onto_a_line(): void
    {
        $supplier = $this->supplier(41, 'Mr. Fitra');
        $lunch = $this->expense($supplier, [
            'expense_date' => '2026-11-20',
            'description' => 'Bought client a quick lunch',
            'supplier_invoice_number' => '05/74833',
            'amount' => 4,
            'vat_amount' => 0,
            'category' => 'general',
            'funded_by' => 'company',
            'receipt_path' => null,
        ]);

        $this->store(
            'tenants/41/company/receipts/drafts/'.str_repeat('e', 40).'.pdf',
            "%PDF-1.4\n%FITRA-SCAN\ntrailer\n%%EOF\n"
        );

        $page = $this->actingAs($this->owner)->get('/company/expenses');
        $page->assertOk();
        $page->assertSee('class="expense-missing-file"', false);
        $page->assertSee('No file');
        $page->assertSee('data-drop-url="/company/expenses/'.$lunch->id.'/receipt"', false);
        $page->assertDontSee('data-file="/company/expenses/'.$lunch->id.'/receipt"', false);
        $this->actingAs($this->owner)->get('/company/expenses/'.$lunch->id.'/receipt')->assertNotFound();
    }

    public function test_dropping_a_pdf_attaches_it_to_that_expense_only(): void
    {
        $supplier = $this->supplier(41, 'Mr. Fitra');
        $lunch = $this->expense($supplier, [
            'expense_date' => '2026-11-20',
            'description' => 'Bought client a quick lunch',
            'supplier_invoice_number' => '05/74833',
            'amount' => 4,
            'vat_amount' => 0,
            'category' => 'general',
            'funded_by' => 'company',
            'receipt_path' => null,
        ]);
        $previous = $this->store('tenants/41/company/receipts/old-lunch.pdf', 'OLD-LUNCH-PDF');
        $lunch->update(['receipt_path' => $previous]);

        $create = $this->actingAs($this->owner)->get('/company/expenses/create');
        $create->assertOk();
        $this->assertDoesNotMatchRegularExpression('/<input[^>]*name="receipt"[^>]*required/', $create->getContent());

        $this->actingAs($this->other)->post('/company/expenses/'.$lunch->id.'/receipt', [
            'receipt' => UploadedFile::fake()->createWithContent('lunch.pdf', $this->invoicePdf('05/74833')),
        ])->assertNotFound();
        $this->assertSame($previous, $lunch->fresh()->receipt_path);

        $pdf = $this->invoicePdf('05/74833 DROPPED');
        $this->actingAs($this->owner)->post('/company/expenses/'.$lunch->id.'/receipt', [
            'receipt' => UploadedFile::fake()->createWithContent('lunch.pdf', $pdf),
        ])->assertRedirect();

        $lunch->refresh();
        $this->assertSame(4.0, (float) $lunch->amount);
        $this->assertSame(0.0, (float) $lunch->vat_amount);
        $this->assertStringStartsWith('tenants/41/company/receipts/', (string) $lunch->receipt_path);
        $this->assertStringNotContainsString('..', (string) $lunch->receipt_path);
        $this->assertNotSame($previous, $lunch->receipt_path);
        Storage::disk('local')->assertMissing($previous);

        $page = $this->actingAs($this->owner)->get('/company/expenses');
        $page->assertOk();
        $page->assertSee('data-file="/company/expenses/'.$lunch->id.'/receipt"', false);
        $page->assertDontSee('class="expense-missing-file"', false);
        $served = $this->actingAs($this->owner)->get('/company/expenses/'.$lunch->id.'/receipt');
        $served->assertOk();
        $this->assertSame($pdf, $served->streamedContent());

        $this->actingAs($this->owner)->post('/company/expenses/'.$lunch->id.'/receipt', [
            'receipt' => UploadedFile::fake()->create('photo.jpg', 20, 'image/jpeg'),
        ])->assertSessionHasErrors('receipt');
        $this->assertSame($lunch->receipt_path, $lunch->fresh()->receipt_path);
    }

    public function test_a_locked_year_or_a_future_date_blocks_the_dropped_pdf(): void
    {
        $supplier = $this->supplier(41, 'Arkadia Development Ltd');
        $drink = $this->expense($supplier, [
            'expense_date' => '2026-11-13',
            'description' => 'Business Development Opportunity - Drink',
            'supplier_invoice_number' => 'T000902-28933',
            'amount' => 6.36,
            'vat_amount' => 1.14,
            'funded_by' => 'company',
            'receipt_path' => null,
        ]);
        $upload = function () {
            return UploadedFile::fake()->createWithContent('drink.pdf', $this->invoicePdf('T000902-28933'));
        };

        $this->withoutExceptionHandling();

        $drink->update(['expense_date' => '2026-11-22']);
        try {
            $this->actingAs($this->owner)->post('/company/expenses/'.$drink->id.'/receipt', [
                'receipt' => $upload(),
            ]);
            $this->fail('A future expense date should refuse the PDF.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('receipt', $e->errors());
        }
        $this->assertNull($drink->fresh()->receipt_path);

        $drink->update(['expense_date' => '2026-11-13']);
        DB::table('fiscal_years')->insert([
            'user_id' => 41,
            'year' => 2026,
            'closed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        try {
            $this->actingAs($this->owner)->post('/company/expenses/'.$drink->id.'/receipt', [
                'receipt' => $upload(),
            ]);
            $this->fail('A closed year should refuse the PDF.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('books', $e->errors());
        }
        $this->assertNull($drink->fresh()->receipt_path);

        DB::table('fiscal_years')->delete();
        CompanyBooksLock::create([
            'user_id' => 41,
            'locked_through' => '2026-11-30',
        ]);
        try {
            $this->actingAs($this->owner)->post('/company/expenses/'.$drink->id.'/receipt', [
                'receipt' => $upload(),
            ]);
            $this->fail('Locked company books should refuse the PDF.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('books', $e->errors());
        }
        $this->assertNull($drink->fresh()->receipt_path);
    }

    public function test_an_older_receipts_path_and_a_webp_photo_stay_on_this_users_row(): void
    {
        $jpg = 'OLDER-FOLDER-JPEG';
        $expense = $this->expense(null, [
            'description' => 'Older folder',
            'receipt_path' => 'tenants/41/receipts/lunch.jpg',
        ]);
        $this->store('tenants/41/receipts/lunch.jpg', $jpg);

        $page = $this->actingAs($this->owner)->get('/company/expenses');
        $page->assertOk();
        $page->assertSee('src="/company/expenses/'.$expense->id.'/receipt"', false);

        $served = $this->actingAs($this->owner)->get('/company/expenses/'.$expense->id.'/receipt');
        $served->assertOk();
        $this->assertSame($jpg, $served->streamedContent());

        $expense->update(['receipt_path' => 'tenants/42/receipts/lunch.jpg']);
        $this->store('tenants/42/receipts/lunch.jpg', 'OTHER-USER-SECRET');
        $foreign = $this->actingAs($this->owner)->get('/company/expenses/'.$expense->id.'/receipt');
        $foreign->assertNotFound();
        $this->assertStringNotContainsString('OTHER-USER-SECRET', $foreign->getContent());

        $webp = 'RIFF-WEBP';
        $expense->update(['receipt_path' => 'tenants/41/company/receipts/camera.webp']);
        $this->store('tenants/41/company/receipts/camera.webp', $webp);
        $photo = $this->actingAs($this->owner)->get('/company/expenses/'.$expense->id.'/receipt');
        $photo->assertOk();
        $photo->assertHeader('content-type', 'image/webp');
        $this->assertSame($webp, $photo->streamedContent());
    }

    private function store(string $path, string $bytes): string
    {
        Storage::disk('local')->put($path, $bytes);

        return $path;
    }

    private function pngBytes(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
    }

    private function pdfBytes(string $marker): string
    {
        return "%PDF-1.4\n%".$marker."\ntrailer<<>>\n%%EOF\n";
    }

    private function invoicePdf(string $number): string
    {
        $literal = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $number);

        return "%PDF-1.4\n1 0 obj\n<< >>\nstream\n(".$literal.") Tj\nendstream\nendobj\n%%EOF\n";
    }

    private function companyUser(int $id, string $name): User
    {
        DB::table('users')->insert([
            'id' => $id,
            'name' => $name,
            'email' => 'user'.$id.'@example.test',
            'password' => 'secret',
            'profession' => 'Company',
            'tier' => 'pro',
            'company_books_enabled' => 1,
            'must_verify_email' => 0,
            'terms_accepted_at' => '2026-07-29 00:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::findOrFail($id);
    }

    private function profileFor(int $userId): void
    {
        CompanyProfile::create([
            'user_id' => $userId,
            'legal_name' => 'Cerulean Labs Limited',
            'registration_number' => 'C 116764',
            'registered_office' => 'Flat 6B, Marigold Court, Mosta',
            'financial_year_end_month' => 12,
            'financial_year_end_day' => 31,
            'first_period_start' => '2026-07-29',
            'first_period_end' => '2026-12-31',
            'vat_status' => 'article_10',
            'vat_filing_frequency' => 'quarterly',
            'share_capital_eur' => 1200,
        ]);
    }

    private function supplier(int $userId, string $name): CompanySupplier
    {
        return CompanySupplier::create([
            'user_id' => $userId,
            'name' => $name,
        ]);
    }

    private function expense(?CompanySupplier $supplier, array $overrides): CompanyExpense
    {
        return CompanyExpense::create(array_merge([
            'user_id' => 41,
            'company_supplier_id' => $supplier?->id,
            'expense_date' => '2026-11-02',
            'category' => 'software',
            'description' => 'Expense',
            'amount' => 10,
            'vat_amount' => 0,
            'funded_by' => 'company',
        ], $overrides));
    }

    private function payment(int $userId, string $path, string $bytes, string $kind, float $amount): CompanyExpensePayment
    {
        return CompanyExpensePayment::create([
            'user_id' => $userId,
            'paid_on' => '2026-11-03',
            'reference' => 'BOV',
            'kind' => $kind,
            'amount' => $amount,
            'proof_path' => $this->store($path, $bytes),
        ]);
    }

    private function createTables(): void
    {
        Schema::dropIfExists('company_expenses');
        Schema::dropIfExists('company_suppliers');
        Schema::dropIfExists('company_expense_payments');
        Schema::dropIfExists('company_books_locks');
        Schema::dropIfExists('company_profiles');
        Schema::dropIfExists('fiscal_years');
        Schema::dropIfExists('users');

        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('profession')->nullable();
            $table->string('tier')->nullable();
            $table->boolean('company_books_enabled')->default(false);
            $table->boolean('must_verify_email')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('terms_accepted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('fiscal_years', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->integer('year');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('company_books_locks', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->date('locked_through');
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('company_profiles', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('legal_name');
            $table->string('registration_number');
            $table->text('registered_office');
            $table->unsignedSmallInteger('financial_year_end_month')->default(12);
            $table->unsignedSmallInteger('financial_year_end_day')->default(31);
            $table->date('first_period_start');
            $table->date('first_period_end');
            $table->string('vat_status')->default('article_10');
            $table->string('vat_number')->nullable();
            $table->string('vat_filing_frequency')->default('quarterly');
            $table->string('bank_name')->nullable();
            $table->string('bank_iban')->nullable();
            $table->decimal('share_capital_eur', 12, 2)->default(1200);
            $table->date('share_capital_received_at')->nullable();
            $table->text('payment_instructions')->nullable();
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });

        Schema::create('company_suppliers', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->string('vat_number')->nullable();
            $table->string('email')->nullable();
            $table->string('country')->nullable();
            $table->text('address')->nullable();
            $table->string('default_category')->nullable();
            $table->timestamps();
        });

        Schema::create('company_expense_payments', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('paid_on');
            $table->string('reference')->nullable();
            $table->string('kind')->default('director_refund');
            $table->decimal('amount', 12, 2);
            $table->string('proof_path')->nullable();
            $table->timestamps();
        });

        Schema::create('company_expenses', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('company_supplier_id')->nullable();
            $table->unsignedBigInteger('company_expense_payment_id')->nullable();
            $table->date('expense_date');
            $table->string('category')->default('software');
            $table->text('description');
            $table->string('supplier_invoice_number')->nullable();
            $table->decimal('amount', 12, 2);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->boolean('is_reverse_charge')->default(false);
            $table->string('funded_by')->default('company');
            $table->date('director_refunded_at')->nullable();
            $table->string('refund_reference')->nullable();
            $table->string('receipt_path')->nullable();
            $table->boolean('is_pre_incorporation')->default(false);
            $table->date('reversed_at')->nullable();
            $table->string('reversal_note')->nullable();
            $table->timestamps();
        });
    }
}
