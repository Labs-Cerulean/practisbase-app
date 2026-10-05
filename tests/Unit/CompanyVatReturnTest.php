<?php

namespace Tests\Unit;

use App\Support\CompanyVatReturn;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class CompanyVatReturnTest extends TestCase
{
    public function test_reverse_charge_is_output_and_input_and_proformas_are_not_in_the_net(): void
    {
        $math = CompanyVatReturn::fromTotals(
            salesNet: 1000,
            salesVat: 180,
            creditNet: 100,
            creditVat: 18,
            localInputVat: 50,
            reverseChargeNet: 200,
            reverseChargeVat: 36,
            openRfpCount: 2,
            openRfpTotal: 590
        );

        $this->assertSame(900.0, $math['taxable_supplies']);
        $this->assertSame(162.0, $math['sales_output_vat']);
        $this->assertSame(198.0, $math['output_vat']);
        $this->assertSame(86.0, $math['input_vat']);
        $this->assertSame(112.0, $math['net']);
        $this->assertSame('pay', $math['position']);
        $this->assertSame(2, $math['open_rfp_count']);
    }

    public function test_more_input_than_output_is_a_reclaim(): void
    {
        $math = CompanyVatReturn::fromTotals(0, 0, 0, 0, 40, 0, 0);

        $this->assertSame(-40.0, $math['net']);
        $this->assertSame('reclaim', $math['position']);
    }

    public function test_the_next_return_is_the_november_q3_filing_when_today_is_early_october(): void
    {
        $events = [
            [
                'category' => 'vat',
                'severity' => 'filing',
                'due' => '2026-08-15',
                'label' => 'VAT Q2 2026',
                'key' => 'vat_q2_2026',
                'period_from' => '2026-04-01',
                'period_to' => '2026-06-30',
            ],
            [
                'category' => 'vat',
                'severity' => 'filing',
                'due' => '2026-11-15',
                'label' => 'VAT Q3 2026',
                'key' => 'vat_q3_2026',
                'period_from' => '2026-07-01',
                'period_to' => '2026-09-30',
            ],
            [
                'category' => 'tax',
                'severity' => 'filing',
                'due' => '2026-10-30',
                'label' => 'Not VAT',
                'key' => 'pt',
            ],
        ];

        $next = CompanyVatReturn::pickNext($events, Carbon::parse('2026-10-05'));

        $this->assertSame('vat_q3_2026', $next['key']);
        $this->assertSame('2026-11-15', $next['due']);
        $this->assertSame('2026-07-01', $next['period_from']);
        $this->assertSame('2026-09-30', $next['period_to']);
    }
}
