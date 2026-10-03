<?php

namespace App\Support;

/**
 * Turns supplier-invoice text into a draft the user confirms before save.
 * Amounts are suggestions. Foreign currency is not converted into euro.
 */
class SupplierInvoiceReader
{
    /** @var array<string, array{name: string, category: string}> */
    private const VENDORS = [
        'google' => ['name' => 'Google', 'category' => 'software'],
        'railway' => ['name' => 'Railway', 'category' => 'software'],
        'cursor' => ['name' => 'Cursor', 'category' => 'software'],
        'openai' => ['name' => 'OpenAI', 'category' => 'software'],
        'anthropic' => ['name' => 'Anthropic', 'category' => 'software'],
        'amazon web services' => ['name' => 'Amazon Web Services', 'category' => 'software'],
        'amazonaws' => ['name' => 'Amazon Web Services', 'category' => 'software'],
        'microsoft' => ['name' => 'Microsoft', 'category' => 'software'],
        'github' => ['name' => 'GitHub', 'category' => 'software'],
        'digitalocean' => ['name' => 'DigitalOcean', 'category' => 'software'],
        'hetzner' => ['name' => 'Hetzner', 'category' => 'software'],
        'cloudflare' => ['name' => 'Cloudflare', 'category' => 'software'],
        'namecheap' => ['name' => 'Namecheap', 'category' => 'software'],
        'godaddy' => ['name' => 'GoDaddy', 'category' => 'marketing'],
        'stripe' => ['name' => 'Stripe', 'category' => 'professional'],
        'bank of valletta' => ['name' => 'Bank of Valletta', 'category' => 'bank'],
    ];

    /** @var list<string> */
    private const NAME_SUFFIXES = [
        'limited', 'ltd', 'inc', 'incorporated', 'llc', 'corp', 'corporation',
        'gmbh', 'plc', 'sa', 'bv', 'pty', 'company', 'co', 'emea', 'europe',
        'ireland', 'international', 'the',
    ];

    /** @var list<string> */
    private const GENERIC_TOKENS = [
        'bank', 'cloud', 'software', 'services', 'invoice', 'payment', 'online', 'digital',
    ];

    /** @var array<string, string> */
    private const VAT_COUNTRIES = [
        'MT' => 'Malta',
        'IE' => 'Ireland',
        'DE' => 'Germany',
        'GB' => 'United Kingdom',
        'UK' => 'United Kingdom',
        'US' => 'United States',
        'NL' => 'Netherlands',
        'FR' => 'France',
        'IT' => 'Italy',
        'ES' => 'Spain',
        'LU' => 'Luxembourg',
        'BE' => 'Belgium',
        'AT' => 'Austria',
        'CY' => 'Cyprus',
        'PL' => 'Poland',
        'SE' => 'Sweden',
    ];

    /**
     * @param  list<array{id: int, name: string, vat_number?: ?string}>  $suppliers
     * @param  list<string>  $ignoreNames
     * @return array{
     *     supplier_name: ?string,
     *     vat_number: ?string,
     *     email: ?string,
     *     country: ?string,
     *     address: ?string,
     *     invoice_number: ?string,
     *     invoice_date: ?string,
     *     description: ?string,
     *     net: ?float,
     *     vat: ?float,
     *     total: ?float,
     *     currency: string,
     *     category: string,
     *     reverse_charge: bool,
     *     matched_supplier_ids: list<int>,
     *     notes: list<string>
     * }
     */
    public static function read(string $text, array $suppliers = [], array $ignoreNames = []): array
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = trim($text);

        $empty = self::blankDraft();
        if ($text === '') {
            $empty['notes'][] = 'No text could be read from this PDF. It may be a scan. The file stays attached — fill the supplier and amounts yourself.';

            return $empty;
        }

        $lines = self::lines($text);
        $customerLines = self::customerLines($lines);
        $supplierLines = self::withoutLines($lines, $customerLines);

        $legalName = self::legalName($supplierLines, $ignoreNames);
        $vendor = self::knownVendor($supplierLines);
        $supplierName = $legalName ?: ($vendor['name'] ?? null);
        $vat = self::vatNumber($text, $customerLines);
        $email = self::email($supplierLines);
        $country = self::countryFromVat($vat);
        $address = self::addressAfter($supplierLines, $supplierName);
        $invoiceNumber = self::invoiceNumber($text);
        $invoiceDate = self::invoiceDate($text);
        $currency = self::currency($text);
        $amounts = self::amounts($lines);
        $reverseCharge = preg_match('/reverse\s+charge/i', $text) === 1;
        $category = $vendor['category'] ?? 'general';

        $description = null;
        if ($supplierName) {
            $description = $invoiceNumber
                ? $supplierName.' invoice '.$invoiceNumber
                : $supplierName.' invoice';
        }

        $matched = self::matchSuppliers($supplierName ?? '', $vat, $suppliers);

        $notes = [];
        if ($matched !== []) {
            $notes[] = 'An existing supplier looks like this invoice. Confirm the link, or create a new supplier if it is a different entity.';
        } elseif ($supplierName) {
            $notes[] = 'No saved supplier matched. Creating one will keep the next invoice from this supplier on the same record.';
        } else {
            $notes[] = 'A supplier name was not found. Pick a saved supplier or type a new one.';
        }

        $net = $amounts['net'];
        $vatAmount = $amounts['vat'];
        $total = $amounts['total'];
        if ($currency !== 'EUR') {
            $foreign = $total ?? $net;
            $shown = $foreign !== null ? $currency.' '.number_format($foreign, 2) : $currency;
            $notes[] = 'Invoice currency looks like '.$shown.'. Euro amounts were left blank so the books are not posted in the wrong currency.';
            $net = null;
            $vatAmount = null;
            $total = null;
        }

        if ($reverseCharge) {
            $notes[] = 'The invoice mentions reverse charge. Check the box if you must self-assess 18% Maltese VAT.';
        }

        return [
            'supplier_name' => $supplierName,
            'vat_number' => $vat,
            'email' => $email,
            'country' => $country,
            'address' => $address,
            'invoice_number' => $invoiceNumber,
            'invoice_date' => $invoiceDate,
            'description' => $description,
            'net' => $net,
            'vat' => $vatAmount,
            'total' => $total,
            'currency' => $currency,
            'category' => $category,
            'reverse_charge' => $reverseCharge,
            'matched_supplier_ids' => $matched,
            'notes' => $notes,
        ];
    }

    /**
     * @param  list<array{id: int, name: string, vat_number?: ?string}>  $suppliers
     * @return list<int>
     */
    public static function matchSuppliers(string $name, ?string $vat, array $suppliers): array
    {
        $wantedVat = self::vatKey($vat);
        $wantedName = self::normalizeName($name);
        $wantedToken = self::firstToken($wantedName);

        $vatHits = [];
        $scored = [];

        foreach ($suppliers as $supplier) {
            $id = (int) $supplier['id'];
            $theirVat = self::vatKey($supplier['vat_number'] ?? null);
            if ($wantedVat !== '' && $theirVat !== '' && $wantedVat === $theirVat) {
                $vatHits[] = $id;
                continue;
            }

            $theirName = self::normalizeName((string) ($supplier['name'] ?? ''));
            if ($theirName === '' || $wantedName === '') {
                continue;
            }
            if ($theirName === $wantedName) {
                $scored[$id] = 80;
                continue;
            }

            $theirToken = self::firstToken($theirName);
            if (
                $wantedToken !== ''
                && $theirToken === $wantedToken
                && strlen($wantedToken) >= 4
                && ! in_array($wantedToken, self::GENERIC_TOKENS, true)
            ) {
                $scored[$id] = max($scored[$id] ?? 0, 60);
            }
        }

        if ($vatHits !== []) {
            return array_values(array_unique($vatHits));
        }

        arsort($scored);

        return array_map('intval', array_keys(array_filter($scored, fn (int $score) => $score >= 60)));
    }

    /**
     * Best supplier id for an existing expense description, such as "Google Workspace".
     *
     * @param  list<array{id: int, name: string, vat_number?: ?string}>  $suppliers
     */
    public static function suggestSupplierId(string $description, array $suppliers): ?int
    {
        $desc = ' '.self::normalizeName($description).' ';
        $bestId = null;
        $bestLen = 0;

        foreach ($suppliers as $supplier) {
            $name = self::normalizeName((string) ($supplier['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $token = self::firstToken($name);
            $hit = str_contains($desc, ' '.$name.' ');
            if (! $hit && $token !== '' && strlen($token) >= 4 && ! in_array($token, self::GENERIC_TOKENS, true)) {
                $hit = str_contains($desc, ' '.$token.' ');
            }
            if ($hit && strlen($name) >= $bestLen) {
                $bestLen = strlen($name);
                $bestId = (int) $supplier['id'];
            }
        }

        return $bestId;
    }

    public static function normalizeName(string $name): string
    {
        $name = function_exists('mb_strtolower') ? mb_strtolower($name) : strtolower($name);
        $name = preg_replace('/[^a-z0-9]+/', ' ', $name) ?? '';
        $parts = array_values(array_filter(explode(' ', trim($name)), fn (string $part) => $part !== ''));
        while ($parts !== [] && in_array($parts[count($parts) - 1], self::NAME_SUFFIXES, true)) {
            array_pop($parts);
        }
        while ($parts !== [] && $parts[0] === 'the') {
            array_shift($parts);
        }

        return implode(' ', $parts);
    }

    /**
     * @return array{
     *     supplier_name: null, vat_number: null, email: null, country: null, address: null,
     *     invoice_number: null, invoice_date: null, description: null, net: null, vat: null,
     *     total: null, currency: string, category: string, reverse_charge: bool,
     *     matched_supplier_ids: list<int>, notes: list<string>
     * }
     */
    private static function blankDraft(): array
    {
        return [
            'supplier_name' => null,
            'vat_number' => null,
            'email' => null,
            'country' => null,
            'address' => null,
            'invoice_number' => null,
            'invoice_date' => null,
            'description' => null,
            'net' => null,
            'vat' => null,
            'total' => null,
            'currency' => 'EUR',
            'category' => 'general',
            'reverse_charge' => false,
            'matched_supplier_ids' => [],
            'notes' => [],
        ];
    }

    /**
     * @return list<string>
     */
    private static function lines(string $text): array
    {
        $raw = preg_split('/\n+/', $text) ?: [];
        $lines = [];
        foreach ($raw as $line) {
            $line = trim(preg_replace('/[ \t]+/', ' ', $line) ?? '');
            if ($line !== '') {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    /**
     * @param  list<string>  $lines
     * @return list<string>
     */
    private static function customerLines(array $lines): array
    {
        $block = [];
        $capture = false;
        foreach ($lines as $line) {
            if (preg_match('/^(bill to|billed to|sold to|invoice to|customer)\b/i', $line)) {
                $capture = true;
                $block[] = $line;
                continue;
            }
            if (! $capture) {
                continue;
            }
            if (preg_match('/^(description|subtotal|sub-total|invoice number|invoice date|date of issue|qty|quantity|amount)\b/i', $line)) {
                break;
            }
            $block[] = $line;
            if (count($block) > 8) {
                break;
            }
        }

        return $block;
    }

    /**
     * @param  list<string>  $lines
     * @param  list<string>  $drop
     * @return list<string>
     */
    private static function withoutLines(array $lines, array $drop): array
    {
        if ($drop === []) {
            return $lines;
        }
        $dropMap = array_fill_keys($drop, true);

        return array_values(array_filter($lines, fn (string $line) => ! isset($dropMap[$line])));
    }

    /**
     * @param  list<string>  $lines
     * @param  list<string>  $ignoreNames
     */
    private static function legalName(array $lines, array $ignoreNames): ?string
    {
        $ignored = [];
        foreach ($ignoreNames as $name) {
            $normalized = self::normalizeName($name);
            if ($normalized !== '') {
                $ignored[$normalized] = true;
            }
        }

        foreach (array_slice($lines, 0, 25) as $line) {
            if (! preg_match('/\b(limited|ltd|inc|llc|gmbh|plc|corp|corporation)\b/i', $line)) {
                continue;
            }
            if (preg_match('/^(invoice|bill|total|subtotal|vat|date)\b/i', $line)) {
                continue;
            }
            if (strlen($line) > 120) {
                continue;
            }
            $normalized = self::normalizeName($line);
            if ($normalized === '' || isset($ignored[$normalized])) {
                continue;
            }

            return self::cleanLabel($line);
        }

        return null;
    }

    /**
     * @param  list<string>  $lines
     * @return array{name: string, category: string}|null
     */
    private static function knownVendor(array $lines): ?array
    {
        $haystack = ' '.self::normalizeName(implode(' ', $lines)).' ';
        foreach (self::VENDORS as $needle => $vendor) {
            if (str_contains($haystack, ' '.$needle.' ')) {
                return $vendor;
            }
        }
        if (preg_match('/\bbov\b/i', implode("\n", $lines))) {
            return self::VENDORS['bank of valletta'];
        }

        return null;
    }

    /**
     * @param  list<string>  $customerLines
     */
    private static function vatNumber(string $text, array $customerLines): ?string
    {
        $customer = implode("\n", $customerLines);
        $head = $text;
        if ($customer !== '' && str_contains($text, $customer)) {
            $head = strstr($text, $customer, true) ?: $text;
        }

        $pattern = '/\b(?:VAT|V\.A\.T\.|Tax\s*ID|VAT\s*(?:No|Number|Reg(?:istration)?)?)\b[:\s#.]*([A-Z]{2}[A-Z0-9]{6,14})\b/i';
        if (preg_match($pattern, $head, $match)) {
            return strtoupper($match[1]);
        }
        if (preg_match('/\b((?:MT|IE|DE|GB|FR|IT|ES|NL|LU|BE|AT|CY|PL|SE)[A-Z0-9]{6,14})\b/', $head, $match)) {
            return strtoupper($match[1]);
        }

        return null;
    }

    /**
     * @param  list<string>  $lines
     */
    private static function email(array $lines): ?string
    {
        foreach ($lines as $line) {
            if (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $line, $match)) {
                $email = strtolower($match[0]);
                if (str_contains($email, 'cerulean') || str_contains($email, 'practisbase')) {
                    continue;
                }

                return $email;
            }
        }

        return null;
    }

    private static function countryFromVat(?string $vat): ?string
    {
        if ($vat === null || strlen($vat) < 2) {
            return null;
        }
        $prefix = strtoupper(substr($vat, 0, 2));

        return self::VAT_COUNTRIES[$prefix] ?? null;
    }

    /**
     * @param  list<string>  $lines
     */
    private static function addressAfter(array $lines, ?string $supplierName): ?string
    {
        if ($supplierName === null) {
            return null;
        }
        $index = null;
        foreach ($lines as $i => $line) {
            if (strcasecmp($line, $supplierName) === 0 || str_contains($line, $supplierName)) {
                $index = $i;
                break;
            }
        }
        if ($index === null) {
            return null;
        }

        $parts = [];
        for ($i = $index + 1; $i < count($lines) && count($parts) < 3; $i++) {
            $line = $lines[$i];
            if (preg_match('/^(invoice|bill to|description|subtotal|vat|date)\b/i', $line)) {
                break;
            }
            if (! preg_match('/\d|,/', $line)) {
                break;
            }
            $parts[] = $line;
        }

        return $parts === [] ? null : implode(', ', $parts);
    }

    private static function invoiceNumber(string $text): ?string
    {
        if (preg_match('/invoice\s*(?:number|no\.?|#|id)\s*[:#]?\s*([A-Z0-9][A-Z0-9\/\-]{2,40})/i', $text, $match)) {
            return strtoupper($match[1]);
        }

        return null;
    }

    private static function invoiceDate(string $text): ?string
    {
        $pattern = '/(?:invoice\s*date|date\s*of\s*issue|issue\s*date|dated)\s*[:\-]?\s*([0-9]{1,2}[\/.\-][0-9]{1,2}[\/.\-][0-9]{2,4}|[0-9]{1,2}\s+[A-Za-z]{3,9}\s+[0-9]{4}|[A-Za-z]{3,9}\s+[0-9]{1,2},?\s+[0-9]{4})/i';
        if (! preg_match($pattern, $text, $match)) {
            return null;
        }

        $parsed = self::parseDate($match[1]);
        if ($parsed === null) {
            return null;
        }
        if ($parsed > date('Y-m-d')) {
            return null;
        }

        return $parsed;
    }

    private static function parseDate(string $raw): ?string
    {
        $raw = trim($raw);
        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'd/m/y', 'd-m-y', 'j M Y', 'd M Y', 'M j, Y', 'M j Y', 'F j, Y', 'F j Y'];
        foreach ($formats as $format) {
            $dt = \DateTimeImmutable::createFromFormat('!'.$format, $raw);
            if ($dt instanceof \DateTimeImmutable) {
                $errors = \DateTimeImmutable::getLastErrors();
                if ($errors !== false && (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0)) {
                    continue;
                }

                return $dt->format('Y-m-d');
            }
        }

        $stamp = strtotime($raw);
        if ($stamp === false) {
            return null;
        }

        return date('Y-m-d', $stamp);
    }

    private static function currency(string $text): string
    {
        if (preg_match('/\b(?:total|amount due|grand total|balance due)\b[^\n]{0,40}\b(USD|EUR|GBP)\b/i', $text, $match)) {
            return strtoupper($match[1]);
        }
        if (preg_match('/(?:USD|US\$|\$)\s*\d/', $text) && ! preg_match('/€|\bEUR\b/', $text)) {
            return 'USD';
        }
        if (preg_match('/\bGBP\b|£\s*\d/', $text) && ! preg_match('/€|\bEUR\b/', $text)) {
            return 'GBP';
        }

        return 'EUR';
    }

    /**
     * @param  list<string>  $lines
     * @return array{net: ?float, vat: ?float, total: ?float}
     */
    private static function amounts(array $lines): array
    {
        $subtotal = self::amountAfterLabel($lines, ['subtotal', 'sub-total', 'total excl. vat', 'total excluding vat', 'net amount', 'amount excl. vat']);
        $vat = self::vatAmount($lines);
        $total = self::amountAfterLabel($lines, ['amount due', 'grand total', 'balance due', 'total due', 'total']);

        if ($subtotal !== null) {
            $net = $subtotal;
        } elseif ($total !== null && $vat !== null && $total + 0.001 >= $vat) {
            $net = round($total - $vat, 2);
        } else {
            $net = $total;
        }

        if ($vat === null && $net !== null && $total !== null && $total + 0.001 >= $net) {
            $vat = round($total - $net, 2);
        }
        if ($vat === null) {
            $vat = 0.0;
        }
        if ($net !== null && $net <= 0) {
            $net = null;
        }

        return [
            'net' => $net,
            'vat' => $net === null ? null : $vat,
            'total' => $total,
        ];
    }

    /**
     * @param  list<string>  $lines
     * @param  list<string>  $labels
     */
    private static function amountAfterLabel(array $lines, array $labels): ?float
    {
        foreach ($lines as $i => $line) {
            foreach ($labels as $label) {
                $quoted = preg_quote($label, '/');
                if (! preg_match('/^'.$quoted.'\b/i', $line)) {
                    continue;
                }
                if ($label === 'total' && preg_match('/\b(excl|excluding|subtotal|sub-total)\b/i', $line)) {
                    continue;
                }
                $money = self::moneyOnLine($line);
                if ($money !== null) {
                    return $money;
                }
                if (isset($lines[$i + 1])) {
                    $next = self::moneyOnLine($lines[$i + 1]);
                    if ($next !== null && preg_match('/^\s*(?:€|EUR|USD|GBP|£|\$)?\s*\d/', $lines[$i + 1])) {
                        return $next;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $lines
     */
    private static function vatAmount(array $lines): ?float
    {
        foreach ($lines as $i => $line) {
            if (! preg_match('/^(?:vat|tax|gst)\b/i', $line)) {
                continue;
            }
            if (preg_match('/\b(?:number|no\.?|id|reg|registration)\b/i', $line) && ! preg_match('/\d+[.,]\d{2}\s*$/', $line)) {
                continue;
            }
            $money = self::moneyOnLine($line);
            if ($money !== null) {
                return $money;
            }
            if (isset($lines[$i + 1])) {
                $next = self::moneyOnLine($lines[$i + 1]);
                if ($next !== null) {
                    return $next;
                }
            }
        }

        return null;
    }

    private static function moneyOnLine(string $line): ?float
    {
        if (! preg_match('/(-?\d[\d.,]*)\s*(?:€|EUR|USD|GBP)?\s*$/i', $line, $match)) {
            return null;
        }

        return self::parseMoney($match[1]);
    }

    public static function parseMoney(string $raw): ?float
    {
        $raw = trim($raw);
        $raw = str_replace(['€', '$', '£', ' '], '', $raw);
        $raw = preg_replace('/(?:EUR|USD|GBP)/i', '', $raw) ?? $raw;
        $raw = trim($raw);
        if ($raw === '' || $raw === '-') {
            return null;
        }

        if (preg_match('/^\d{1,3}(\.\d{3})+,\d{2}$/', $raw)) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        } elseif (preg_match('/^\d{1,3}(,\d{3})+\.\d{2}$/', $raw)) {
            $raw = str_replace(',', '', $raw);
        } elseif (preg_match('/^\d+,\d{2}$/', $raw)) {
            $raw = str_replace(',', '.', $raw);
        } else {
            $raw = str_replace(',', '', $raw);
        }

        if (! is_numeric($raw)) {
            return null;
        }

        return round((float) $raw, 2);
    }

    private static function vatKey(?string $vat): string
    {
        if ($vat === null) {
            return '';
        }

        return strtoupper(preg_replace('/[^A-Z0-9]/i', '', $vat) ?? '');
    }

    private static function firstToken(string $normalized): string
    {
        $parts = explode(' ', $normalized);

        return $parts[0] ?? '';
    }

    private static function cleanLabel(string $line): string
    {
        $line = trim($line, " \t-:");

        return preg_replace('/\s+/', ' ', $line) ?? $line;
    }
}
