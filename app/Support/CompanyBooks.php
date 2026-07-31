<?php

namespace App\Support;

use App\Models\CompanyInvoice;
use App\Models\CompanyProfile;
use App\Models\User;

class CompanyBooks
{
    public const DEFAULT_LEGAL_NAME = 'Cerulean Labs Limited';

    public const DEFAULT_REGISTRATION_NUMBER = 'C 116764';

    public const DEFAULT_REGISTERED_OFFICE = 'Flat 6B, Marigold Court, Triq Carmelo Schembri, Mosta MST 2480, Malta';

    public const INCORPORATION_DATE = '2026-07-31';

    public const FIRST_PERIOD_END = '2026-12-31';

    public const SHARE_CAPITAL_EUR = 1200.00;

    public static function ensureProfile(User $user): CompanyProfile
    {
        return CompanyProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'legal_name' => self::DEFAULT_LEGAL_NAME,
                'registration_number' => self::DEFAULT_REGISTRATION_NUMBER,
                'registered_office' => self::DEFAULT_REGISTERED_OFFICE,
                'financial_year_end_month' => 12,
                'financial_year_end_day' => 31,
                'first_period_start' => self::INCORPORATION_DATE,
                'first_period_end' => self::FIRST_PERIOD_END,
                'vat_status' => 'article_10',
                'vat_filing_frequency' => 'quarterly',
                'bank_name' => 'Bank of Valletta',
                'share_capital_eur' => self::SHARE_CAPITAL_EUR,
                'payment_instructions' => "Please pay by bank transfer to Cerulean Labs Limited.\nBank: Bank of Valletta\nQuote the document number as reference.",
            ]
        );
    }

    public static function nextDocumentNumber(int $userId, string $type, ?int $year = null): string
    {
        $year = $year ?: (int) date('Y');
        $prefix = match ($type) {
            'rfp' => 'CL-RFP',
            'invoice' => 'CL-INV',
            'credit_note' => 'CL-CN',
            default => 'CL-'.strtoupper($type),
        };

        $patternPrefix = $prefix.'-'.$year.'-';

        $latest = CompanyInvoice::where('user_id', $userId)
            ->where('type', $type)
            ->where('document_number', 'like', $patternPrefix.'%')
            ->orderByDesc('document_number')
            ->value('document_number');

        $nextSeq = 1;
        if ($latest && preg_match('/-(\d+)$/', $latest, $matches)) {
            $nextSeq = ((int) $matches[1]) + 1;
        }

        return $patternPrefix.str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
    }

    public static function periodLabel(CompanyProfile $profile): string
    {
        return $profile->first_period_start->format('d M Y').' – '.$profile->first_period_end->format('d M Y');
    }
}
