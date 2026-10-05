<?php

namespace Tests\Feature;

use App\Models\CompanyClient;
use App\Models\CompanyInvoice;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class CompanyInvoiceListTest extends TestCase
{
    public function test_a_paid_invoice_shows_the_proforma_as_superseded_with_no_second_balance(): void
    {
        $client = new CompanyClient(['name' => 'Grepor Invest Ltd']);

        $rfp = $this->document([
            'type' => 'rfp',
            'status' => 'converted',
            'document_number' => 'CL-RFP-2026-0016',
            'issue_date' => '2026-10-01',
            'total' => 590,
            'amount_paid' => 0,
        ], $client, 16);

        $invoice = $this->document([
            'type' => 'invoice',
            'status' => 'paid',
            'document_number' => 'CL-INV-2026-0001',
            'issue_date' => '2026-10-02',
            'total' => 590,
            'amount_paid' => 590,
            'linked_document_id' => 16,
        ], $client, 1);
        $invoice->setRelation('linkedDocument', $rfp);

        $open = $this->document([
            'type' => 'rfp',
            'status' => 'unpaid',
            'document_number' => 'CL-RFP-2026-0017',
            'issue_date' => '2026-10-03',
            'total' => 100,
            'amount_paid' => 0,
        ], $client, 17);

        $listed = CompanyInvoice::withoutSupersededProformas(collect([$invoice, $rfp, $open]));

        $this->assertSame([1, 17], $listed->pluck('id')->all());

        $html = $this->renderList($listed);

        $this->assertStringContainsString('CL-INV-2026-0001 · Invoice', $html);
        $this->assertStringContainsString('Superseded by this invoice:', $html);
        $this->assertStringContainsString('CL-RFP-2026-0016', $html);
        $this->assertStringContainsString('proforma 01 Oct 2026', $html);
        $this->assertStringContainsString('Balance €0.00', $html);
        $this->assertStringNotContainsString('Balance €590.00', $html);
        $this->assertSame(1, substr_count($html, 'CL-RFP-2026-0016'));
        $this->assertStringContainsString('CL-RFP-2026-0017 · RFP', $html);
        $this->assertStringContainsString('Balance €100.00', $html);
        $this->assertStringContainsString('Log payment', $html);
    }

    public function test_an_unpaid_tax_invoice_keeps_the_amount_due_on_the_invoice_only(): void
    {
        $client = new CompanyClient(['name' => 'J Portelli Projects']);

        $rfp = $this->document([
            'type' => 'rfp',
            'status' => 'converted',
            'document_number' => 'CL-RFP-2026-0015',
            'issue_date' => '2026-10-01',
            'total' => 1180,
            'amount_paid' => 0,
        ], $client, 15);

        $invoice = $this->document([
            'type' => 'invoice',
            'status' => 'unpaid',
            'document_number' => 'CL-INV-2026-0002',
            'issue_date' => '2026-10-05',
            'total' => 1180,
            'amount_paid' => 0,
            'linked_document_id' => 15,
        ], $client, 2);
        $invoice->setRelation('linkedDocument', $rfp);

        $listed = CompanyInvoice::withoutSupersededProformas(collect([$invoice, $rfp]));
        $html = $this->renderList($listed);

        $this->assertSame([2], $listed->pluck('id')->all());
        $this->assertStringContainsString('Balance €1,180.00', $html);
        $this->assertSame(1, substr_count($html, 'Balance €1,180.00'));
        $this->assertStringContainsString('Superseded by this invoice:', $html);
        $this->assertStringContainsString('CL-RFP-2026-0015', $html);
        $this->assertStringNotContainsString('CL-RFP-2026-0015 · RFP', $html);
    }

    private function renderList($documents): string
    {
        view()->share('errors', new ViewErrorBag);

        return view('company.invoices-index', ['documents' => $documents])->render();
    }

    private function document(array $attrs, CompanyClient $client, int $id): CompanyInvoice
    {
        $doc = new CompanyInvoice($attrs);
        $doc->id = $id;
        $doc->setRelation('client', $client);
        $doc->setRelation('childDocuments', collect());
        $doc->setRelation('payments', collect());
        if (! $doc->relationLoaded('linkedDocument')) {
            $doc->setRelation('linkedDocument', null);
        }

        return $doc;
    }
}
