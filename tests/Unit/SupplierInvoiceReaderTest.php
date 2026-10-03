<?php

namespace Tests\Unit;

use App\Support\PdfPlainText;
use App\Support\SupplierInvoiceReader;
use PHPUnit\Framework\TestCase;

class SupplierInvoiceReaderTest extends TestCase
{
    public function test_google_invoice_links_existing_supplier_and_fills_euro_amounts(): void
    {
        $text = <<<'TXT'
Google Ireland Limited
Gordon House, Barrow Street, Dublin 4, Ireland
VAT IE6388047V
billing@google.com
Invoice number: 482193
Invoice date: 01 Sep 2026
Bill to
Cerulean Labs Limited
Subtotal €10.00
VAT €0.00
Total €10.00
TXT;

        $read = SupplierInvoiceReader::read($text, [
            ['id' => 7, 'name' => 'Google', 'vat_number' => null],
            ['id' => 8, 'name' => 'Bank of Valletta', 'vat_number' => null],
        ], ['Cerulean Labs Limited']);

        $this->assertSame('Google Ireland Limited', $read['supplier_name']);
        $this->assertSame('IE6388047V', $read['vat_number']);
        $this->assertSame('Ireland', $read['country']);
        $this->assertSame('billing@google.com', $read['email']);
        $this->assertSame('482193', $read['invoice_number']);
        $this->assertSame('2026-09-01', $read['invoice_date']);
        $this->assertSame('Google Ireland Limited invoice 482193', $read['description']);
        $this->assertSame(10.0, $read['net']);
        $this->assertSame(0.0, $read['vat']);
        $this->assertSame('software', $read['category']);
        $this->assertFalse($read['reverse_charge']);
        $this->assertSame([7], $read['matched_supplier_ids']);
        $this->assertStringContainsString('Gordon House', (string) $read['address']);
        $this->assertStringNotContainsString('Cerulean', (string) $read['supplier_name']);
    }

    public function test_vat_match_wins_over_a_different_name(): void
    {
        $ids = SupplierInvoiceReader::matchSuppliers('Google Ireland Limited', 'IE6388047V', [
            ['id' => 1, 'name' => 'Google', 'vat_number' => null],
            ['id' => 2, 'name' => 'Google Cloud EMEA Limited', 'vat_number' => 'IE 6388047V'],
        ]);

        $this->assertSame([2], $ids);
    }

    public function test_railway_invoice_without_a_legal_suffix(): void
    {
        $text = <<<'TXT'
Railway
Invoice number R-100
Invoice date: 15 Aug 2026
Total €20.00
TXT;

        $read = SupplierInvoiceReader::read($text);

        $this->assertSame('Railway', $read['supplier_name']);
        $this->assertSame('software', $read['category']);
        $this->assertSame('R-100', $read['invoice_number']);
        $this->assertSame('2026-08-15', $read['invoice_date']);
        $this->assertSame(20.0, $read['net']);
        $this->assertSame(0.0, $read['vat']);
        $this->assertSame([], $read['matched_supplier_ids']);
    }

    public function test_reverse_charge_is_flagged_and_foreign_currency_amounts_stay_blank(): void
    {
        $text = <<<'TXT'
Amazon Web Services Inc
Invoice number: 991122
Invoice date: 02 Oct 2026
Reverse charge
Total USD 20.00
TXT;

        $read = SupplierInvoiceReader::read($text);

        $this->assertSame('Amazon Web Services Inc', $read['supplier_name']);
        $this->assertSame('software', $read['category']);
        $this->assertTrue($read['reverse_charge']);
        $this->assertSame('USD', $read['currency']);
        $this->assertNull($read['net']);
        $this->assertNull($read['vat']);
        $this->assertNotEmpty($read['notes']);
    }

    public function test_future_invoice_date_is_left_blank(): void
    {
        $text = "Railway\nInvoice date: 01 Dec 2026\nTotal €5.00";
        $read = SupplierInvoiceReader::read($text);

        $this->assertNull($read['invoice_date']);
        $this->assertSame(5.0, $read['net']);
    }

    public function test_european_money_and_empty_scan(): void
    {
        $this->assertSame(1234.56, SupplierInvoiceReader::parseMoney('1.234,56'));
        $this->assertSame(1234.56, SupplierInvoiceReader::parseMoney('1,234.56'));

        $read = SupplierInvoiceReader::read('   ');
        $this->assertNull($read['supplier_name']);
        $this->assertSame([], $read['matched_supplier_ids']);
        $this->assertNotEmpty($read['notes']);
    }

    public function test_existing_google_expense_description_suggests_google(): void
    {
        $suppliers = [
            ['id' => 4, 'name' => 'Google'],
            ['id' => 5, 'name' => 'Bank of Valletta'],
        ];

        $this->assertSame(4, SupplierInvoiceReader::suggestSupplierId('Google Workspace Jul 2026', $suppliers));
        $this->assertNull(SupplierInvoiceReader::suggestSupplierId('Monthly bank charge', $suppliers));
    }

    public function test_literal_pdf_stream_reads_supplier_name(): void
    {
        $stream = "BT\n(Google Ireland Limited) Tj\nT*\n(Invoice number: 482193) Tj\nT*\n(Total EUR 12.34) Tj\nET\n";
        $pdf = $this->pdfWithStream($stream, false);

        $text = PdfPlainText::fromContentStreams($pdf);

        $this->assertStringContainsString('Google Ireland Limited', $text);
        $this->assertStringContainsString('482193', $text);

        $read = SupplierInvoiceReader::read($text);
        $this->assertSame('Google Ireland Limited', $read['supplier_name']);
        $this->assertSame('482193', $read['invoice_number']);
        $this->assertSame(12.34, $read['net']);
    }

    public function test_flate_pdf_stream_reads_supplier_name(): void
    {
        $stream = "BT\n(Railway) Tj\nT*\n(Invoice number: R-100) Tj\nT*\n(Total EUR 20.00) Tj\nET\n";
        $pdf = $this->pdfWithStream($stream, true);

        $text = PdfPlainText::fromContentStreams($pdf);
        $read = SupplierInvoiceReader::read($text);

        $this->assertSame('Railway', $read['supplier_name']);
        $this->assertSame('R-100', $read['invoice_number']);
        $this->assertSame(20.0, $read['net']);
    }

    public function test_poppler_reads_the_same_simple_pdf_when_available(): void
    {
        $stream = "BT\n(Google Ireland Limited) Tj\nT*\n(Total EUR 9.00) Tj\nET\n";
        $text = PdfPlainText::fromBinary($this->pdfWithStream($stream, false));

        if ($text === '') {
            $this->markTestSkipped('PDF text extraction is unavailable in this environment.');
        }

        $this->assertStringContainsString('Google Ireland Limited', $text);
    }

    private function pdfWithStream(string $stream, bool $flate): string
    {
        $data = $flate ? gzcompress($stream) : $stream;
        $filter = $flate ? ' /Filter /FlateDecode' : '';
        $objects = [];
        $objects[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $objects[] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $objects[] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 300 200] /Contents 4 0 R >>\nendobj\n";
        $objects[] = "4 0 obj\n<< /Length ".strlen($data).$filter." >>\nstream\n".$data."\nendstream\nendobj\n";

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }
        $xref = strlen($pdf);
        $count = count($objects) + 1;
        $pdf .= "xref\n0 {$count}\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i < $count; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size {$count} /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";

        return $pdf;
    }
}
