<?php

namespace Tests\Unit;

use App\Support\CompanyBooks;
use Illuminate\Mail\Message;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\Email;

class CompanyBooksTest extends TestCase
{
    public function test_default_registry_constants(): void
    {
        $this->assertSame('Cerulean Labs Limited', CompanyBooks::DEFAULT_LEGAL_NAME);
        $this->assertSame('C 116764', CompanyBooks::DEFAULT_REGISTRATION_NUMBER);
        $this->assertSame('2026-07-29', CompanyBooks::INCORPORATION_DATE);
        $this->assertSame('2026-12-31', CompanyBooks::FIRST_PERIOD_END);
        $this->assertSame(1200.0, CompanyBooks::SHARE_CAPITAL_EUR);
        $this->assertSame('accounts@labscerulean.com', CompanyBooks::ACCOUNTS_COPY_EMAIL);
    }

    public function test_monthly_bill_copies_the_accounts_inbox(): void
    {
        $bill = new Message(new Email());
        CompanyBooks::copyAccountsOnMonthlyBill($bill, 'proforma', 'client@grepor.test');
        $this->assertSame(['accounts@labscerulean.com'], array_map(
            fn ($address) => $address->getAddress(),
            $bill->getSymfonyMessage()->getCc()
        ));

        $already = new Message(new Email());
        CompanyBooks::copyAccountsOnMonthlyBill($already, 'proforma', 'Accounts@LabsCerulean.com');
        $this->assertSame([], $already->getSymfonyMessage()->getCc());

        $reminder = new Message(new Email());
        CompanyBooks::copyAccountsOnMonthlyBill($reminder, 'reminder', 'client@grepor.test');
        $this->assertSame([], $reminder->getSymfonyMessage()->getCc());
    }

    public function test_monthly_bill_pdf_name_uses_reference_and_supply_month(): void
    {
        $october = new \DateTimeImmutable('2026-10-01');

        $this->assertSame(
            'CL-RFP-2026-0016 (Oct-26 Grepor).pdf',
            CompanyBooks::documentPdfFilename('CL-RFP-2026-0016', $october, true, 'Grepor Invest Ltd')
        );
        $this->assertSame(
            'CL-INV-2026-0002 (Oct-26 Portelli).pdf',
            CompanyBooks::documentPdfFilename('CL-INV-2026-0002', $october, true, 'J Portelli Projects')
        );
        $this->assertSame(
            'CL-RFP-2026-0004 (Oct-26).pdf',
            CompanyBooks::documentPdfFilename('CL-RFP-2026-0004', $october, true)
        );
        $this->assertSame(
            'CL-RFP-2026-0004.pdf',
            CompanyBooks::documentPdfFilename('CL-RFP-2026-0004', $october, false, 'Grepor Invest Ltd')
        );
    }
}
