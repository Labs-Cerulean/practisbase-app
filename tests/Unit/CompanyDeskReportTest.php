<?php

namespace Tests\Unit;

use App\Support\CompanyDeskReport;
use PHPUnit\Framework\TestCase;

class CompanyDeskReportTest extends TestCase
{
    public function test_period_bounds_include_the_whole_history_ytd_and_rolling_windows(): void
    {
        $this->assertNull(CompanyDeskReport::rangeStart('all', '2026-11-02'));
        $this->assertSame('2026-01-01', CompanyDeskReport::rangeStart('ytd', '2026-11-02'));
        $this->assertSame('2026-05-02', CompanyDeskReport::rangeStart('6m', '2026-11-02'));
        $this->assertSame('2026-10-02', CompanyDeskReport::rangeStart('1m', '2026-11-02'));
        $this->assertSame('2024-02-29', CompanyDeskReport::rangeStart('1m', '2024-03-31'));
        $this->assertSame('2026-02-28', CompanyDeskReport::rangeStart('1m', '2026-03-31'));
    }

    public function test_commercial_totals_skip_converted_proformas_transfers_computers_and_reversals(): void
    {
        $report = CompanyDeskReport::build($this->documents(), $this->payments(), $this->expenses(), '2026-11-02');

        $all = $report['ranges']['all']['totals'];
        $this->assertSame(594.0, $all['invoiced']);
        $this->assertSame(100.0, $all['open_rfps']);
        $this->assertSame(394.0, $all['paid']);
        $this->assertSame(45.0, $all['costs']);

        $this->assertSame($all, $report['ranges']['ytd']['totals']);

        $six = $report['ranges']['6m']['totals'];
        $this->assertSame(514.0, $six['invoiced']);
        $this->assertSame(100.0, $six['open_rfps']);
        $this->assertSame(314.0, $six['paid']);
        $this->assertSame(40.0, $six['costs']);

        $month = $report['ranges']['1m']['totals'];
        $this->assertSame(50.0, $month['invoiced']);
        $this->assertSame(0.0, $month['open_rfps']);
        $this->assertSame(50.0, $month['paid']);
        $this->assertSame(40.0, $month['costs']);

        $keys = array_column($report['ranges']['all']['months'], 'key');
        $this->assertSame('2026-01', $keys[0]);
        $this->assertSame('2026-11', $keys[array_key_last($keys)]);
        $this->assertCount(11, $keys);
        $february = $report['ranges']['all']['months'][1];
        $this->assertSame('2026-02', $february['key']);
        $this->assertSame(0.0, $february['invoiced']);
        $this->assertSame(80.0, $february['paid']);

        $oneMonthKeys = array_column($report['ranges']['1m']['months'], 'key');
        $this->assertSame(['2026-10', '2026-11'], $oneMonthKeys);
    }

    public function test_client_ranks_use_open_balance_revenue_and_days_from_the_original_ask(): void
    {
        $report = CompanyDeskReport::build($this->documents(), $this->payments(), $this->expenses(), '2026-11-02');

        $this->assertSame(['Grepor Invest Ltd', 'Slow Co'], array_column($report['owes'], 'name'));
        $this->assertSame([100.0, 100.0], array_column($report['owes'], 'amount'));

        $revenue = $report['ranges']['all']['revenue'];
        $this->assertSame('Grepor Invest Ltd', $revenue[0]['name']);
        $this->assertSame(346.0, $revenue[0]['amount']);
        $this->assertSame(['Grepor Invest Ltd', 'Slow Co', 'Old Co', 'Quick Co'], array_column($revenue, 'name'));

        $fastest = $report['ranges']['all']['fastest'];
        $this->assertSame('Quick Co', $fastest[0]['name']);
        $this->assertSame(0, $fastest[0]['days']);
        $grepor = $fastest[1];
        $this->assertSame('Grepor Invest Ltd', $grepor['name']);
        $this->assertSame(18, $grepor['days']);
        $this->assertSame(246.0, $grepor['paid']);
        $this->assertSame(2, $grepor['payments']);
        $this->assertSame('Old Co', $report['ranges']['all']['slowest'][0]['name']);
        $this->assertSame(31, $report['ranges']['all']['slowest'][0]['days']);

        $this->assertSame(['Quick Co'], array_column($report['ranges']['1m']['fastest'], 'name'));
        $this->assertSame([], $report['ranges']['1m']['slowest']);
        $this->assertNotContains('Old Co', array_column($report['ranges']['6m']['revenue'], 'name'));
    }

    public function test_credits_follow_the_parent_client_and_partial_proformas_stay_open(): void
    {
        $report = CompanyDeskReport::build([
            [
                'id' => 1, 'client_id' => 1, 'client_name' => 'Parent Co', 'type' => 'invoice', 'status' => 'partial',
                'issue_date' => '2026-04-01', 'total' => 100, 'amount_paid' => 10,
                'linked_document_id' => null, 'parent_document_id' => null,
            ],
            [
                'id' => 2, 'client_id' => 9, 'client_name' => 'Wrong Co', 'type' => 'credit_note', 'status' => 'issued',
                'issue_date' => '2026-04-10', 'total' => 40, 'amount_paid' => 0,
                'linked_document_id' => null, 'parent_document_id' => 1,
            ],
            [
                'id' => 3, 'client_id' => 2, 'client_name' => 'Partial Co', 'type' => 'rfp', 'status' => 'partial',
                'issue_date' => '2026-05-01', 'total' => 100, 'amount_paid' => 30,
                'linked_document_id' => null, 'parent_document_id' => null,
            ],
            [
                'id' => 4, 'client_id' => 3, 'client_name' => 'Future Co', 'type' => 'invoice', 'status' => 'unpaid',
                'issue_date' => '2026-12-01', 'total' => 500, 'amount_paid' => 0,
                'linked_document_id' => null, 'parent_document_id' => null,
            ],
            [
                'id' => 5, 'client_id' => 2, 'client_name' => 'Partial Co', 'type' => 'rfp', 'status' => 'unpaid',
                'issue_date' => null, 'total' => 80, 'amount_paid' => 0,
                'linked_document_id' => null, 'parent_document_id' => 3,
            ],
        ], [], [], '2026-06-01');

        $this->assertSame(160.0, $report['ranges']['all']['totals']['invoiced']);
        $this->assertSame(['Partial Co', 'Parent Co'], array_column($report['ranges']['all']['revenue'], 'name'));
        $this->assertSame([100.0, 60.0], array_column($report['ranges']['all']['revenue'], 'amount'));

        $owed = [];
        foreach ($report['owes'] as $row) {
            $owed[$row['name']] = $row['amount'];
        }
        $this->assertSame(50.0, $owed['Parent Co']);
        $this->assertSame(70.0, $owed['Partial Co']);
        $this->assertArrayNotHasKey('Future Co', $owed);
        $this->assertArrayNotHasKey('Wrong Co', $owed);
    }

    public function test_rank_lists_keep_the_six_largest(): void
    {
        $documents = [];
        for ($i = 1; $i <= 8; $i++) {
            $documents[] = [
                'id' => $i, 'client_id' => $i, 'client_name' => 'Client '.$i, 'type' => 'invoice', 'status' => 'unpaid',
                'issue_date' => '2026-01-0'.$i, 'total' => $i * 10, 'amount_paid' => 0,
                'linked_document_id' => null, 'parent_document_id' => null,
            ];
        }
        $documents[6]['issue_date'] = '2026-01-07';
        $documents[7]['issue_date'] = '2026-01-08';

        $report = CompanyDeskReport::build($documents, [], [], '2026-02-01');

        $this->assertSame(['Client 8', 'Client 7', 'Client 6', 'Client 5', 'Client 4', 'Client 3'], array_column($report['owes'], 'name'));
        $this->assertCount(6, $report['ranges']['all']['revenue']);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function documents(): array
    {
        return [
            ['id' => 1, 'client_id' => 10, 'client_name' => 'Grepor Invest Ltd', 'type' => 'rfp', 'status' => 'unpaid', 'issue_date' => '2026-10-01', 'total' => 100, 'amount_paid' => 0, 'linked_document_id' => null, 'parent_document_id' => null],
            ['id' => 2, 'client_id' => 10, 'client_name' => 'Grepor Invest Ltd', 'type' => 'rfp', 'status' => 'converted', 'issue_date' => '2026-08-01', 'total' => 200, 'amount_paid' => 0, 'linked_document_id' => null, 'parent_document_id' => null],
            ['id' => 3, 'client_id' => 10, 'client_name' => 'Grepor Invest Ltd', 'type' => 'invoice', 'status' => 'paid', 'issue_date' => '2026-08-20', 'total' => 236, 'amount_paid' => 236, 'linked_document_id' => 2, 'parent_document_id' => null],
            ['id' => 4, 'client_id' => 11, 'client_name' => 'Slow Co', 'type' => 'invoice', 'status' => 'partial', 'issue_date' => '2026-09-01', 'total' => 118, 'amount_paid' => 18, 'linked_document_id' => null, 'parent_document_id' => null],
            ['id' => 5, 'client_id' => 12, 'client_name' => 'Quick Co', 'type' => 'invoice', 'status' => 'paid', 'issue_date' => '2026-11-01', 'total' => 50, 'amount_paid' => 50, 'linked_document_id' => null, 'parent_document_id' => null],
            ['id' => 6, 'client_id' => 13, 'client_name' => 'Old Co', 'type' => 'invoice', 'status' => 'paid', 'issue_date' => '2026-01-15', 'total' => 80, 'amount_paid' => 80, 'linked_document_id' => null, 'parent_document_id' => null],
            ['id' => 7, 'client_id' => 10, 'client_name' => 'Grepor Invest Ltd', 'type' => 'rfp', 'status' => 'paid', 'issue_date' => '2026-07-01', 'total' => 999, 'amount_paid' => 999, 'linked_document_id' => null, 'parent_document_id' => null],
            ['id' => 8, 'client_id' => 10, 'client_name' => 'Grepor Invest Ltd', 'type' => 'invoice', 'status' => 'paid', 'issue_date' => '2026-07-02', 'total' => 10, 'amount_paid' => 10, 'linked_document_id' => 7, 'parent_document_id' => null],
            ['id' => 9, 'client_id' => 10, 'client_name' => 'Grepor Invest Ltd', 'type' => 'rfp', 'status' => 'unpaid', 'issue_date' => '2026-10-15', 'total' => 777, 'amount_paid' => 0, 'linked_document_id' => null, 'parent_document_id' => 1],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function payments(): array
    {
        return [
            ['invoice_id' => 3, 'amount' => 236, 'payment_date' => '2026-08-20', 'is_transfer' => false],
            ['invoice_id' => 4, 'amount' => 18, 'payment_date' => '2026-10-01', 'is_transfer' => false],
            ['invoice_id' => 5, 'amount' => 50, 'payment_date' => '2026-11-01', 'is_transfer' => false],
            ['invoice_id' => 6, 'amount' => 80, 'payment_date' => '2026-02-15', 'is_transfer' => false],
            ['invoice_id' => 8, 'amount' => 10, 'payment_date' => '2026-07-02', 'is_transfer' => false],
            ['invoice_id' => 3, 'amount' => 500, 'payment_date' => '2026-08-20', 'is_transfer' => true],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function expenses(): array
    {
        return [
            ['expense_date' => '2026-10-05', 'amount' => 40, 'category' => 'software', 'reversed' => false],
            ['expense_date' => '2026-10-06', 'amount' => 999, 'category' => 'computer', 'reversed' => false],
            ['expense_date' => '2026-09-01', 'amount' => 15, 'category' => 'office', 'reversed' => true],
            ['expense_date' => '2026-01-20', 'amount' => 5, 'category' => 'bank', 'reversed' => false],
        ];
    }
}
