<?php

namespace Tests\Unit;

use App\Support\InvoiceCoverage;
use PHPUnit\Framework\TestCase;

class InvoiceCoverageTest extends TestCase
{
    public function test_a_month_starting_on_the_first_ends_on_the_last_day(): void
    {
        $this->assertSame('2026-10-31', InvoiceCoverage::monthEnd('2026-10-01'));
        $this->assertSame(
            '(From: 01 Oct 2026, To: 31 Oct 2026)',
            InvoiceCoverage::label('2026-10-01', '2026-10-31')
        );
    }

    public function test_a_month_starting_mid_month_ends_the_day_before_the_next_same_day(): void
    {
        $this->assertSame('2026-11-14', InvoiceCoverage::monthEnd('2026-10-15'));
        $this->assertSame(
            '(From: 15 Oct 2026, To: 14 Nov 2026)',
            InvoiceCoverage::label('2026-10-15', '2026-11-14')
        );
    }
}
