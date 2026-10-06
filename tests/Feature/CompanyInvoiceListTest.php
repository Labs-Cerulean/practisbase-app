<?php

namespace Tests\Feature;

use App\Models\CompanyClient;
use App\Models\CompanyInvoice;
use App\Models\CompanyRecurringInvoice;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class CompanyInvoiceListTest extends TestCase
{
    public function test_converted_proformas_fold_under_the_tax_invoice(): void
    {
        $grepor = new CompanyClient(['name' => 'Grepor Invest Ltd']);
        $portelli = new CompanyClient(['name' => 'J Portelli Projects']);

        $paidRfp = $this->document($grepor, [
            'id' => 16,
            'document_number' => 'CL-RFP-2026-0016',
            'type' => 'rfp',
            'status' => 'converted',
            'issue_date' => '2026-10-01',
            'total' => 590,
            'amount_paid' => 0,
        ]);
        $paidInvoice = $this->document($grepor, [
            'id' => 1,
            'document_number' => 'CL-INV-2026-0001',
            'type' => 'invoice',
            'status' => 'paid',
            'issue_date' => '2026-10-02',
            'coverage_start' => '2026-10-01',
            'coverage_end' => '2026-10-31',
            'total' => 590,
            'amount_paid' => 590,
            'linked_document_id' => 16,
            'notes' => 'Recurring proforma: Estate hub',
        ], $paidRfp);

        $openRfp = $this->document($portelli, [
            'id' => 15,
            'document_number' => 'CL-RFP-2026-0015',
            'type' => 'rfp',
            'status' => 'converted',
            'issue_date' => '2026-10-01',
            'total' => 1180,
            'amount_paid' => 0,
        ]);
        $openInvoice = $this->document($portelli, [
            'id' => 2,
            'document_number' => 'CL-INV-2026-0002',
            'type' => 'invoice',
            'status' => 'unpaid',
            'issue_date' => '2026-11-06',
            'coverage_start' => '2026-10-01',
            'coverage_end' => '2026-10-31',
            'total' => 1180,
            'amount_paid' => 0,
            'linked_document_id' => 15,
            'notes' => 'Recurring proforma: Estate hub',
        ], $openRfp);

        $orphan = $this->document($grepor, [
            'id' => 9,
            'document_number' => 'CL-RFP-2026-0009',
            'type' => 'rfp',
            'status' => 'converted',
            'issue_date' => '2026-09-01',
            'total' => 200,
            'amount_paid' => 0,
        ]);

        $visible = CompanyInvoice::withoutSupersededProformas(collect([
            $openInvoice, $openRfp, $paidInvoice, $paidRfp, $orphan,
        ]));

        $this->assertSame(
            ['CL-INV-2026-0002', 'CL-INV-2026-0001', 'CL-RFP-2026-0009'],
            $visible->pluck('document_number')->all()
        );

        $html = view('company.invoices-index', [
            'documents' => $visible,
            'errors' => new ViewErrorBag,
        ])->render();

        $this->assertSame(2, substr_count($html, 'Superseded by this invoice:'));
        $this->assertStringContainsString('CL-RFP-2026-0016', $html);
        $this->assertStringContainsString('CL-RFP-2026-0015', $html);
        $this->assertStringNotContainsString('CL-RFP-2026-0016 · RFP', $html);
        $this->assertStringNotContainsString('CL-RFP-2026-0015 · RFP', $html);
        $this->assertStringContainsString('Balance €0.00', $html);
        $this->assertSame(1, substr_count($html, 'Balance €1,180.00'));
        $this->assertStringNotContainsString('Balance €590.00', $html);
        $this->assertStringNotContainsString('Balance €200.00', $html);
        $this->assertStringContainsString('CL-RFP-2026-0009 · RFP', $html);
        $this->assertStringContainsString('Superseded', $html);

        $this->assertSame('CL-INV-2026-0001 (Oct-26 Grepor).pdf', $paidInvoice->pdfDownloadName());
        $this->assertSame('CL-INV-2026-0002 (Oct-26 Portelli).pdf', $openInvoice->pdfDownloadName());
        $this->assertSame('CL-RFP-2026-0016 (Oct-26 Grepor).pdf', $paidRfp->pdfDownloadName());
    }

    public function test_monthly_billing_hides_the_converted_proforma_row(): void
    {
        $client = new CompanyClient(['name' => 'Grepor Invest Ltd']);
        $rfp = $this->document($client, [
            'id' => 16,
            'document_number' => 'CL-RFP-2026-0016',
            'type' => 'rfp',
            'status' => 'converted',
            'issue_date' => '2026-10-01',
            'coverage_start' => '2026-10-01',
            'coverage_end' => '2026-10-31',
            'total' => 590,
            'amount_paid' => 0,
            'notes' => 'Recurring proforma: Estate hub',
        ]);
        $invoice = $this->document($client, [
            'id' => 1,
            'document_number' => 'CL-INV-2026-0001',
            'type' => 'invoice',
            'status' => 'paid',
            'issue_date' => '2026-10-02',
            'coverage_start' => '2026-10-01',
            'coverage_end' => '2026-10-31',
            'total' => 590,
            'amount_paid' => 590,
            'linked_document_id' => 16,
            'notes' => 'Recurring proforma: Estate hub',
        ], $rfp);

        $schedule = new CompanyRecurringInvoice([
            'title' => 'Estate hub: OS + Sales hub',
            'company_client_id' => 4,
            'day_of_month' => 1,
            'next_issue_on' => '2026-11-01',
            'is_active' => true,
            'package_sections' => ['os', 'sales'],
            'agreed_rate_os' => 0,
            'agreed_rate_sales' => 500,
            'start_date' => '2026-10-01',
            'items' => [],
        ]);
        $schedule->id = 7;
        $schedule->setRelation('client', $client);

        $issued = CompanyInvoice::withoutSupersededProformas(collect([$invoice, $rfp]));

        $html = view('company.accounts.recurring', [
            'schedules' => collect([$schedule]),
            'clients' => collect([$client]),
            'issuedBySchedule' => [7 => $issued],
            'openCreate' => false,
            'dueCatchUpCount' => 0,
            'mailStatus' => ['delivers' => true, 'mailer' => 'array', 'transport' => 'array', 'from' => 'books@example.test'],
            'errors' => new ViewErrorBag,
        ])->render();

        $this->assertStringContainsString('Issued documents (1)', $html);
        $this->assertStringContainsString('CL-INV-2026-0001', $html);
        $this->assertStringContainsString('Superseded by this invoice:', $html);
        $this->assertStringContainsString('CL-RFP-2026-0016', $html);
        $this->assertStringNotContainsString('>Proforma</td>', $html);
        $this->assertStringNotContainsString('Pay / convert', $html);
        $this->assertStringContainsString('€0.00', $html);
    }

    private function document(CompanyClient $client, array $attrs, ?CompanyInvoice $proforma = null): CompanyInvoice
    {
        $id = $attrs['id'];
        unset($attrs['id']);

        $doc = new CompanyInvoice(array_merge([
            'coverage_start' => '2026-10-01',
            'coverage_end' => '2026-10-31',
            'notes' => 'Recurring proforma: Estate hub',
        ], $attrs));
        $doc->id = $id;
        $doc->setRelation('client', $client);
        $doc->setRelation('payments', collect());
        $doc->setRelation('childDocuments', collect());
        $doc->setRelation('linkedDocument', $proforma);

        return $doc;
    }
}
