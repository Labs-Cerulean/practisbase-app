<?php

namespace App\Support;

use App\Models\CompanyInvoice;
use App\Models\CompanyProfile;
use App\Models\User;
use Illuminate\Mail\Message;

class CompanyBooks
{
    public const DEFAULT_LEGAL_NAME = 'Cerulean Labs Limited';

    public const DEFAULT_REGISTRATION_NUMBER = 'C 116764';

    public const DEFAULT_REGISTERED_OFFICE = 'Flat 6B, Marigold Court, Triq Carmelo Schembri, Mosta MST 2480, Malta';

    public const INCORPORATION_DATE = '2026-07-29';

    public const FIRST_PERIOD_END = '2026-12-31';

    public const SHARE_CAPITAL_EUR = 1200.00;

    public const ACCOUNTS_COPY_EMAIL = 'accounts@labscerulean.com';

    public static function ensureProfile(User $user): CompanyProfile
    {
        $profile = CompanyProfile::firstOrCreate(
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

        // Correct earlier placeholder incorporation date (31 Jul → 29 Jul 2026).
        if ($profile->first_period_start
            && $profile->first_period_start->toDateString() === '2026-07-31') {
            $profile->first_period_start = self::INCORPORATION_DATE;
            $profile->save();
        }

        return $profile;
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

    /**
     * Monthly bills download as "CL-RFP-2026-0004 (Oct-26 Grepor).pdf".
     * The brackets hold the month and a short developer name. Other documents keep the reference only.
     */
    public static function documentPdfFilename(string $documentNumber, \DateTimeInterface $supplyMonth, bool $monthlyBill, ?string $developer = null): string
    {
        $ref = trim(str_replace(['/', '\\', "\0"], '-', $documentNumber));
        if ($ref === '') {
            $ref = 'document';
        }

        if (! $monthlyBill) {
            return $ref.'.pdf';
        }

        $inside = $supplyMonth->format('M-y');
        $tag = self::developerTag($developer);
        if ($tag !== '') {
            $inside .= ' '.$tag;
        }

        return $ref.' ('.$inside.').pdf';
    }

    /**
     * Short label for a developer, for a filename. "J Portelli Projects" becomes Portelli.
     */
    public static function developerTag(?string $name): string
    {
        $skip = ['ltd', 'limited', 'plc', 'inc', 'projects', 'project', 'holdings', 'group', 'company', 'co', 'the', 'and'];
        $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
        foreach ($parts as $part) {
            $clean = trim($part, ".,'\"&");
            if ($clean === '' || strlen($clean) === 1) {
                continue;
            }
            if (in_array(strtolower($clean), $skip, true)) {
                continue;
            }
            $tag = preg_replace('/[^A-Za-z0-9-]/', '', $clean) ?? '';
            if ($tag !== '') {
                return $tag;
            }
        }

        return '';
    }

    /**
     * Copy the company accounts inbox when a monthly bill goes out.
     * Skipped when that inbox is already the recipient.
     */
    public static function copyAccountsOnMonthlyBill(Message $message, string $kind, string $recipient): void
    {
        if ($kind !== 'proforma') {
            return;
        }

        $copy = self::ACCOUNTS_COPY_EMAIL;
        if (strcasecmp(trim($recipient), $copy) === 0) {
            return;
        }

        $message->cc($copy);
    }

    public static function periodLabel(CompanyProfile $profile): string
    {
        return $profile->first_period_start->format('d M Y').' – '.$profile->first_period_end->format('d M Y');
    }
}
