<?php

namespace App\Support;

/**
 * Commercial desk figures. Open proformas are included on purpose.
 * A converted proforma is omitted once its tax invoice exists, so the pair is counted once.
 * Official fiscal cards keep treating open proformas as €0.
 */
class CompanyDeskReport
{
    private const MONTHS = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
        5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
        9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
    ];

    /**
     * @param  list<array<string, mixed>>  $documents
     * @param  list<array<string, mixed>>  $payments
     * @param  list<array<string, mixed>>  $expenses
     * @return array{
     *     today: string,
     *     owes: list<array{id: ?int, name: string, amount: float}>,
     *     ranges: array<string, array<string, mixed>>
     * }
     */
    public static function build(array $documents, array $payments, array $expenses, string $today): array
    {
        $today = self::normaliseDate($today) ?? '1970-01-01';

        $byId = [];
        $superseded = [];
        foreach ($documents as $doc) {
            $id = (int) ($doc['id'] ?? 0);
            if ($id > 0) {
                $byId[$id] = $doc;
            }
            if (($doc['type'] ?? '') === 'invoice' && ! empty($doc['linked_document_id'])) {
                $superseded[(int) $doc['linked_document_id']] = true;
            }
        }

        $billing = [];
        foreach ($documents as $doc) {
            $event = self::billingEvent($doc, $byId, $superseded, $today);
            if ($event) {
                $billing[] = $event;
            }
        }

        $cash = [];
        foreach ($payments as $payment) {
            $event = self::cashEvent($payment, $byId, $today);
            if ($event) {
                $cash[] = $event;
            }
        }

        $costs = [];
        foreach ($expenses as $expense) {
            $event = self::costEvent($expense, $today);
            if ($event) {
                $costs[] = $event;
            }
        }

        $ranges = [];
        foreach (['all', 'ytd', '6m', '1m'] as $key) {
            $ranges[$key] = self::slice($key, self::rangeStart($key, $today), $today, $billing, $cash, $costs);
        }

        return [
            'today' => $today,
            'owes' => self::owes($documents, $superseded, $today),
            'ranges' => $ranges,
        ];
    }

    public static function rangeStart(string $range, string $today): ?string
    {
        $today = self::normaliseDate($today);
        if (! $today) {
            return null;
        }

        return match ($range) {
            'ytd' => substr($today, 0, 4).'-01-01',
            '6m' => self::shiftMonths($today, -6),
            '1m' => self::shiftMonths($today, -1),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $doc
     * @param  array<int, array<string, mixed>>  $byId
     * @param  array<int, true>  $superseded
     * @return ?array{date: string, amount: float, kind: string, client_id: ?int, client_name: string}
     */
    private static function billingEvent(array $doc, array $byId, array $superseded, string $today): ?array
    {
        $date = self::normaliseDate($doc['issue_date'] ?? null);
        if (! $date || $date > $today) {
            return null;
        }

        $type = (string) ($doc['type'] ?? '');
        $amount = round((float) ($doc['total'] ?? 0), 2);
        if ($type === 'invoice') {
            $kind = 'invoice';
        } elseif ($type === 'credit_note') {
            $kind = 'credit';
            $amount = round(-$amount, 2);
        } elseif ($type === 'rfp') {
            if (($doc['status'] ?? '') === 'converted' || isset($superseded[(int) ($doc['id'] ?? 0)]) || ! empty($doc['parent_document_id'])) {
                return null;
            }
            $kind = 'rfp';
        } else {
            return null;
        }

        $clientDoc = $doc;
        if ($kind === 'credit' && ! empty($doc['parent_document_id']) && isset($byId[(int) $doc['parent_document_id']])) {
            $clientDoc = $byId[(int) $doc['parent_document_id']];
        }

        return [
            'date' => $date,
            'amount' => $amount,
            'kind' => $kind,
            'client_id' => self::clientId($clientDoc),
            'client_name' => self::clientName($clientDoc),
        ];
    }

    /**
     * @param  array<string, mixed>  $payment
     * @param  array<int, array<string, mixed>>  $byId
     * @return ?array{date: string, amount: float, days: int, client_id: ?int, client_name: string, ranked: bool}
     */
    private static function cashEvent(array $payment, array $byId, string $today): ?array
    {
        $transfer = $payment['is_transfer'] ?? false;
        if ($transfer === true || $transfer === 1 || $transfer === '1') {
            return null;
        }

        $date = self::normaliseDate($payment['payment_date'] ?? null);
        if (! $date || $date > $today) {
            return null;
        }

        $amount = round((float) ($payment['amount'] ?? 0), 2);
        $doc = $byId[(int) ($payment['invoice_id'] ?? 0)] ?? null;
        $ranked = false;
        $days = 0;
        $clientId = null;
        $clientName = 'No client';
        if ($doc) {
            $start = self::normaliseDate($doc['issue_date'] ?? null) ?? $date;
            if (($doc['type'] ?? '') === 'invoice' && ! empty($doc['linked_document_id'])) {
                $rfp = $byId[(int) $doc['linked_document_id']] ?? null;
                $rfpDate = $rfp ? self::normaliseDate($rfp['issue_date'] ?? null) : null;
                if ($rfpDate) {
                    $start = $rfpDate;
                }
            }
            $days = self::daysBetween($start, $date);
            $clientId = self::clientId($doc);
            $clientName = self::clientName($doc);
            $ranked = true;
        }

        return [
            'date' => $date,
            'amount' => $amount,
            'days' => $days,
            'client_id' => $clientId,
            'client_name' => $clientName,
            'ranked' => $ranked,
        ];
    }

    /**
     * @param  array<string, mixed>  $expense
     * @return ?array{date: string, amount: float}
     */
    private static function costEvent(array $expense, string $today): ?array
    {
        if (! empty($expense['reversed']) || ($expense['category'] ?? '') === 'computer') {
            return null;
        }

        $date = self::normaliseDate($expense['expense_date'] ?? null);
        if (! $date || $date > $today) {
            return null;
        }

        return [
            'date' => $date,
            'amount' => round((float) ($expense['amount'] ?? 0), 2),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $documents
     * @param  array<int, true>  $superseded
     * @return list<array{id: ?int, name: string, amount: float}>
     */
    private static function owes(array $documents, array $superseded, string $today): array
    {
        $credits = [];
        foreach ($documents as $doc) {
            if (($doc['type'] ?? '') !== 'credit_note' || empty($doc['parent_document_id'])) {
                continue;
            }
            $issued = self::normaliseDate($doc['issue_date'] ?? null);
            if ($issued && $issued > $today) {
                continue;
            }
            $parentId = (int) $doc['parent_document_id'];
            $credits[$parentId] = round(($credits[$parentId] ?? 0) + (float) ($doc['total'] ?? 0), 2);
        }

        $byClient = [];
        foreach ($documents as $doc) {
            $issued = self::normaliseDate($doc['issue_date'] ?? null);
            if ($issued && $issued > $today) {
                continue;
            }
            $type = (string) ($doc['type'] ?? '');
            if ($type === 'invoice') {
                $balance = round((float) ($doc['total'] ?? 0) - ($credits[(int) ($doc['id'] ?? 0)] ?? 0) - (float) ($doc['amount_paid'] ?? 0), 2);
            } elseif ($type === 'rfp' && ($doc['status'] ?? '') !== 'converted' && ! isset($superseded[(int) ($doc['id'] ?? 0)]) && empty($doc['parent_document_id'])) {
                $balance = round((float) ($doc['total'] ?? 0) - (float) ($doc['amount_paid'] ?? 0), 2);
            } else {
                continue;
            }

            if ($balance <= 0.009) {
                continue;
            }

            $key = self::rememberClient($byClient, self::clientId($doc), self::clientName($doc));
            $byClient[$key]['amount'] = round($byClient[$key]['amount'] + $balance, 2);
        }

        return self::topAmounts($byClient);
    }

    /**
     * @param  list<array{date: string, amount: float, kind: string, client_id: ?int, client_name: string}>  $billing
     * @param  list<array{date: string, amount: float, days: int, client_id: ?int, client_name: string, ranked: bool}>  $cash
     * @param  list<array{date: string, amount: float}>  $costs
     * @return array<string, mixed>
     */
    private static function slice(string $key, ?string $from, string $today, array $billing, array $cash, array $costs): array
    {
        $inside = static function (string $date) use ($from, $today): bool {
            if ($date > $today) {
                return false;
            }

            return $from === null || $date >= $from;
        };

        $buckets = [];
        $touch = static function (string $date, string $field, float $amount) use (&$buckets): void {
            $month = substr($date, 0, 7);
            if (! isset($buckets[$month])) {
                $buckets[$month] = ['invoiced' => 0.0, 'paid' => 0.0, 'costs' => 0.0];
            }
            $buckets[$month][$field] = round($buckets[$month][$field] + $amount, 2);
        };

        $invoiced = 0.0;
        $openRfps = 0.0;
        $revenue = [];
        foreach ($billing as $event) {
            if (! $inside($event['date'])) {
                continue;
            }
            $touch($event['date'], 'invoiced', $event['amount']);
            $invoiced = round($invoiced + $event['amount'], 2);
            if ($event['kind'] === 'rfp') {
                $openRfps = round($openRfps + $event['amount'], 2);
            }
            $clientKey = self::rememberClient($revenue, $event['client_id'], $event['client_name']);
            $revenue[$clientKey]['amount'] = round($revenue[$clientKey]['amount'] + $event['amount'], 2);
        }

        $paid = 0.0;
        $speed = [];
        foreach ($cash as $event) {
            if (! $inside($event['date'])) {
                continue;
            }
            $touch($event['date'], 'paid', $event['amount']);
            $paid = round($paid + $event['amount'], 2);
            if (! $event['ranked']) {
                continue;
            }
            $clientKey = self::rememberClient($speed, $event['client_id'], $event['client_name']);
            $speed[$clientKey]['weighted'] = ($speed[$clientKey]['weighted'] ?? 0) + ($event['days'] * $event['amount']);
            $speed[$clientKey]['paid'] = round(($speed[$clientKey]['paid'] ?? 0) + $event['amount'], 2);
            $speed[$clientKey]['payments'] = ($speed[$clientKey]['payments'] ?? 0) + 1;
        }

        $costSum = 0.0;
        foreach ($costs as $event) {
            if (! $inside($event['date'])) {
                continue;
            }
            $touch($event['date'], 'costs', $event['amount']);
            $costSum = round($costSum + $event['amount'], 2);
        }

        $endKey = substr($today, 0, 7);
        $startKey = $from ? substr($from, 0, 7) : null;
        if ($startKey === null) {
            $keys = array_keys($buckets);
            sort($keys);
            $startKey = $keys[0] ?? null;
        }

        $months = [];
        if ($startKey !== null && $startKey <= $endKey) {
            foreach (self::monthKeys($startKey, $endKey) as $monthKey) {
                $bucket = $buckets[$monthKey] ?? ['invoiced' => 0.0, 'paid' => 0.0, 'costs' => 0.0];
                $months[] = [
                    'key' => $monthKey,
                    'label' => self::MONTHS[(int) substr($monthKey, 5, 2)].' '.substr($monthKey, 0, 4),
                    'short' => self::MONTHS[(int) substr($monthKey, 5, 2)].' '.substr($monthKey, 2, 2),
                    'invoiced' => $bucket['invoiced'],
                    'paid' => $bucket['paid'],
                    'costs' => $bucket['costs'],
                ];
            }
        }

        $revenue = array_values(array_filter(
            $revenue,
            static fn (array $row): bool => $row['amount'] > 0.009
        ));

        return [
            'key' => $key,
            'label' => match ($key) {
                'ytd' => 'Year to date',
                '6m' => 'Last 6 months',
                '1m' => 'Last month',
                default => 'From the beginning',
            },
            'from' => $from,
            'to' => $today,
            'months' => $months,
            'totals' => [
                'invoiced' => $invoiced,
                'open_rfps' => $openRfps,
                'paid' => $paid,
                'costs' => $costSum,
            ],
            'revenue' => self::topAmounts($revenue),
            'fastest' => self::speeds($speed, 'asc'),
            'slowest' => count($speed) < 2 ? [] : self::speeds($speed, 'desc'),
        ];
    }

    /**
     * @param  array<string, array{id: ?int, name: string, amount: float}>  $map
     */
    private static function rememberClient(array &$map, ?int $id, string $name): string
    {
        $key = $id ? (string) $id : 'none';
        if (! isset($map[$key])) {
            $map[$key] = [
                'id' => $id,
                'name' => $name,
                'amount' => 0.0,
            ];
        }

        return $key;
    }

    /**
     * @param  array<string, array{id: ?int, name: string, amount: float}>  $byClient
     * @return list<array{id: ?int, name: string, amount: float}>
     */
    private static function topAmounts(array $byClient): array
    {
        $rows = array_values($byClient);
        usort($rows, static function (array $a, array $b): int {
            if ($a['amount'] !== $b['amount']) {
                return $a['amount'] < $b['amount'] ? 1 : -1;
            }
            $name = strcasecmp($a['name'], $b['name']);
            if ($name !== 0) {
                return $name;
            }

            return ($a['id'] ?? 0) <=> ($b['id'] ?? 0);
        });

        $rows = array_slice($rows, 0, 6);
        foreach ($rows as &$row) {
            $row['amount'] = round((float) $row['amount'], 2);
            $row['id'] = $row['id'] ?: null;
        }
        unset($row);

        return $rows;
    }

    /**
     * @param  array<string, array<string, mixed>>  $speed
     * @return list<array{id: ?int, name: string, days: int, paid: float, payments: int}>
     */
    private static function speeds(array $speed, string $direction): array
    {
        $rows = [];
        foreach ($speed as $bucket) {
            $paid = round((float) ($bucket['paid'] ?? 0), 2);
            if ($paid <= 0.009) {
                continue;
            }
            $rows[] = [
                'id' => $bucket['id'] ?: null,
                'name' => $bucket['name'],
                'days' => (int) round(((float) $bucket['weighted']) / $paid),
                'paid' => $paid,
                'payments' => (int) $bucket['payments'],
            ];
        }

        usort($rows, static function (array $a, array $b) use ($direction): int {
            if ($a['days'] !== $b['days']) {
                return $direction === 'asc' ? $a['days'] <=> $b['days'] : $b['days'] <=> $a['days'];
            }

            return strcasecmp($a['name'], $b['name']);
        });

        return array_slice($rows, 0, 6);
    }

    /**
     * @return list<string>
     */
    private static function monthKeys(string $start, string $end): array
    {
        $year = (int) substr($start, 0, 4);
        $month = (int) substr($start, 5, 2);
        $endYear = (int) substr($end, 0, 4);
        $endMonth = (int) substr($end, 5, 2);
        $keys = [];
        $guard = 0;
        while (($year < $endYear || ($year === $endYear && $month <= $endMonth)) && $guard < 600) {
            $keys[] = sprintf('%04d-%02d', $year, $month);
            $month++;
            if ($month > 12) {
                $month = 1;
                $year++;
            }
            $guard++;
        }

        return $keys;
    }

    private static function shiftMonths(string $today, int $delta): string
    {
        $year = (int) substr($today, 0, 4);
        $month = (int) substr($today, 5, 2) + $delta;
        $day = (int) substr($today, 8, 2);
        while ($month < 1) {
            $month += 12;
            $year--;
        }
        while ($month > 12) {
            $month -= 12;
            $year++;
        }
        $last = (int) gmdate('t', gmmktime(0, 0, 0, $month, 1, $year));

        return sprintf('%04d-%02d-%02d', $year, $month, min($day, $last));
    }

    private static function daysBetween(string $start, string $end): int
    {
        $from = strtotime($start.' UTC');
        $to = strtotime($end.' UTC');
        if ($from === false || $to === false) {
            return 0;
        }

        return (int) max(0, round(($to - $from) / 86400));
    }

    private static function normaliseDate(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            $value = $value->format('Y-m-d');
        }
        $value = trim((string) $value);
        if (! preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
            return null;
        }
        $date = substr($value, 0, 10);
        $year = (int) substr($date, 0, 4);
        $month = (int) substr($date, 5, 2);
        $day = (int) substr($date, 8, 2);
        if (! checkdate($month, $day, $year)) {
            return null;
        }

        return $date;
    }

    /**
     * @param  array<string, mixed>  $doc
     */
    private static function clientId(array $doc): ?int
    {
        $id = (int) ($doc['client_id'] ?? 0);

        return $id > 0 ? $id : null;
    }

    /**
     * @param  array<string, mixed>  $doc
     */
    private static function clientName(array $doc): string
    {
        $name = trim((string) ($doc['client_name'] ?? ''));

        return $name !== '' ? $name : 'No client';
    }
}
