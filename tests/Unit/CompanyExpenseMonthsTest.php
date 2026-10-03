<?php

namespace Tests\Unit;

use App\Support\CompanyExpenseMonths;
use PHPUnit\Framework\TestCase;

class CompanyExpenseMonthsTest extends TestCase
{
    public function test_current_month_opens_and_reversed_stay_out_of_the_total(): void
    {
        $months = CompanyExpenseMonths::group([
            ['id' => 1, 'date' => '2026-10-03', 'reversed' => false, 'cash' => 21.29, 'owed' => 21.29],
            ['id' => 2, 'date' => '2026-10-01', 'reversed' => true, 'cash' => 10.00, 'owed' => 0.0],
            ['id' => 3, 'date' => '2026-08-30', 'reversed' => false, 'cash' => 17.37, 'owed' => 17.37],
        ], '2026-10-03', false);

        $this->assertSame('2026-10', $months[0]['key']);
        $this->assertSame('October 2026', $months[0]['label']);
        $this->assertTrue($months[0]['open']);
        $this->assertSame([1], $months[0]['active_ids']);
        $this->assertSame([2], $months[0]['reversed_ids']);
        $this->assertSame(21.29, $months[0]['cash']);
        $this->assertSame(21.29, $months[0]['owed']);

        $this->assertSame('August 2026', $months[1]['label']);
        $this->assertFalse($months[1]['open']);
        $this->assertSame(17.37, $months[1]['cash']);
    }

    public function test_reversed_view_lists_only_reversed_cash(): void
    {
        $months = CompanyExpenseMonths::group([
            ['id' => 9, 'date' => '2026-07-31', 'reversed' => true, 'cash' => 8.84, 'owed' => 0.0],
        ], '2026-10-03', true);

        $this->assertSame([], $months[0]['active_ids']);
        $this->assertSame([9], $months[0]['reversed_ids']);
        $this->assertSame(8.84, $months[0]['cash']);
        $this->assertSame(0.0, $months[0]['owed']);
        $this->assertTrue($months[0]['open']);
    }

    public function test_open_month_falls_back_to_the_latest_month_with_rows(): void
    {
        $this->assertSame('2026-08', CompanyExpenseMonths::openKey(['2026-07', '2026-08'], '2026-10-03'));
        $this->assertNull(CompanyExpenseMonths::openKey([], '2026-10-03'));
    }

    public function test_search_term_escapes_like_wildcards(): void
    {
        $this->assertSame('%100\\%\\_off%', CompanyExpenseMonths::likeTerm('100%_off'));
    }
}
