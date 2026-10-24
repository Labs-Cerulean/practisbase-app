<?php

namespace App\Support;

/**
 * Bank lines and ledger lines match when their signed totals are the same.
 * One receipt can cover several invoices. Several bank lines can cover one invoice.
 */
class BankMatch
{
    public static function totalsMatch(float $statementAmount, float $ledgerTotal): bool
    {
        return abs(round($statementAmount, 2) - round($ledgerTotal, 2)) <= 0.009;
    }

    /**
     * Statements in a match all point at the same first ledger line.
     * Ledger lines all point at the statement the match was saved from.
     *
     * @param  list<array{id: int, matched_journal_line_id: ?int}>  $statements
     * @param  list<array{id: int, bank_statement_line_id: ?int}>  $journals
     * @return array{statements: list<int>, journals: list<int>}
     */
    public static function matchGroup(int $startStatementId, array $statements, array $journals): array
    {
        $statementById = [];
        foreach ($statements as $row) {
            $statementById[(int) $row['id']] = $row;
        }

        $journalById = [];
        foreach ($journals as $row) {
            $journalById[(int) $row['id']] = $row;
        }

        $statementIds = [$startStatementId => true];
        $journalIds = [];
        $changed = true;

        while ($changed) {
            $changed = false;

            foreach ($journalById as $id => $row) {
                $statementId = (int) ($row['bank_statement_line_id'] ?? 0);
                if ($statementId && isset($statementIds[$statementId]) && ! isset($journalIds[$id])) {
                    $journalIds[$id] = true;
                    $changed = true;
                }
            }

            foreach (array_keys($statementIds) as $statementId) {
                $row = $statementById[$statementId] ?? null;
                if (! $row) {
                    continue;
                }
                $journalId = (int) ($row['matched_journal_line_id'] ?? 0);
                if ($journalId && isset($journalById[$journalId]) && ! isset($journalIds[$journalId])) {
                    $journalIds[$journalId] = true;
                    $changed = true;
                }
            }

            foreach ($statementById as $id => $row) {
                $journalId = (int) ($row['matched_journal_line_id'] ?? 0);
                if ($journalId && isset($journalIds[$journalId]) && ! isset($statementIds[$id])) {
                    $statementIds[$id] = true;
                    $changed = true;
                }
            }
        }

        return [
            'statements' => array_map('intval', array_keys($statementIds)),
            'journals' => array_map('intval', array_keys($journalIds)),
        ];
    }

    /**
     * @param  iterable<int, float>  $signedAmounts
     */
    public static function signedTotal(iterable $signedAmounts): float
    {
        $total = 0.0;
        foreach ($signedAmounts as $amount) {
            $total += (float) $amount;
        }

        return round($total, 2);
    }
}
