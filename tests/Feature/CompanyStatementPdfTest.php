<?php

namespace Tests\Feature;

use App\Models\CompanyClient;
use App\Models\CompanyProfile;
use App\Support\CompanyVatReturn;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Tests\TestCase;

class CompanyStatementPdfTest extends TestCase
{
    public function test_statement_history_and_vat_worksheets_render_as_pdfs(): void
    {
        $profile = new CompanyProfile(['legal_name' => 'Cerulean Labs Ltd']);
        $client = new CompanyClient(['name' => 'Grepor Invest Ltd']);
        $date = Carbon::parse('2026-10-02');

        $historyHtml = view('company.pdf.client-history', [
            'profile' => $profile,
            'client' => $client,
            'history' => [
                'rows' => collect([[
                    'date' => $date,
                    'label' => 'Invoice CL-INV-2026-0001',
                    'debit' => 590,
                    'credit' => 0,
                    'official_balance' => 0,
                    'rfp_balance' => 0,
                    'note' => 'Converted from CL-RFP-2026-0016',
                ]]),
                'official_owed' => 0,
                'rfp_owed' => 0,
            ],
        ])->render();
        $this->assertStringContainsString('CL-INV-2026-0001', $historyHtml);
        $this->assertStringStartsWith('%PDF', Pdf::loadHTML($historyHtml)->output());

        $statementHtml = view('company.pdf.client-statement', [
            'profile' => $profile,
            'client' => $client,
            'statement' => [
                'rows' => collect([[
                    'date' => $date,
                    'label' => 'CL-INV-2026-0001',
                    'kind' => 'invoice',
                    'billed' => 590,
                    'paid' => 590,
                    'credits' => 0,
                    'due' => 0,
                ]]),
                'official_owed' => 0,
                'rfp_owed' => 0,
                'total_owed' => 0,
            ],
        ])->render();
        $this->assertStringContainsString('Account statement', $statementHtml);

        $arHtml = view('company.pdf.ar-statement', [
            'profile' => $profile,
            'client' => $client,
            'from' => '2026-01-01',
            'to' => '2026-10-05',
            'closing' => 0,
            'rows' => collect([[
                'date' => $date,
                'reference' => 'Tax invoice CL-INV-2026-0001',
                'debit' => 590,
                'credit' => 590,
                'balance' => 0,
            ]]),
        ])->render();
        $this->assertStringContainsString('Closing balance', $arHtml);

        $vat = CompanyVatReturn::fromTotals(1000, 180, 0, 0, 50, 0, 0);
        $vat = array_merge($vat, [
            'label' => 'VAT Q3 2026',
            'from' => '2026-07-01',
            'to' => '2026-09-30',
            'due' => '2026-11-15',
            'first_return' => true,
            'period_open' => false,
            'open_rfp_count' => 0,
            'open_rfp_total' => 0,
        ]);
        $vatHtml = view('company.pdf.vat-return', [
            'profile' => $profile,
            'vatReturn' => $vat,
        ])->render();
        $this->assertStringContainsString('VAT to pay MTCA', $vatHtml);
        $this->assertStringContainsString('15 Nov 2026', $vatHtml);
        $this->assertStringStartsWith('%PDF', Pdf::loadHTML($vatHtml)->output());
    }
}
