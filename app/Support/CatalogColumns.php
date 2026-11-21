<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reads table columns in one catalog query.
 * Schema::hasTable and Schema::hasColumn each round-trip to PostgreSQL, and
 * those trips were the wait before a click did anything.
 */
class CatalogColumns
{
    /** @var array<string, list<string>> */
    private static array $columns = [];

    /** @var array<string, true> */
    private static array $loaded = [];

    /**
     * @param  list<string>  $tables
     */
    public static function load(array $tables): void
    {
        $missing = [];
        foreach ($tables as $table) {
            if (! is_string($table) || $table === '' || isset(self::$loaded[$table])) {
                continue;
            }
            $missing[] = $table;
            self::$columns[$table] = [];
            self::$loaded[$table] = true;
        }

        if ($missing === []) {
            return;
        }

        try {
            $found = self::fetch($missing);
        } catch (\Throwable) {
            return;
        }

        foreach ($found as $table => $columns) {
            self::$columns[$table] = $columns;
        }
    }

    public static function hasTable(string $table): bool
    {
        self::load([$table]);

        return (self::$columns[$table] ?? []) !== [];
    }

    public static function hasColumn(string $table, string $column): bool
    {
        self::load([$table]);

        return in_array($column, self::$columns[$table] ?? [], true);
    }

    /**
     * @param  list<string>  $tables  Empty forgets every table loaded this process.
     */
    public static function forget(array $tables = []): void
    {
        if ($tables === []) {
            self::$columns = [];
            self::$loaded = [];

            return;
        }

        foreach ($tables as $table) {
            unset(self::$columns[$table], self::$loaded[$table]);
        }
    }

    /**
     * @param  list<object|array<string, mixed>>  $rows
     * @return array<string, list<string>>
     */
    public static function fromRows(array $rows): array
    {
        $found = [];
        foreach ($rows as $row) {
            $table = (string) (is_array($row) ? ($row['table_name'] ?? '') : ($row->table_name ?? ''));
            $column = (string) (is_array($row) ? ($row['column_name'] ?? '') : ($row->column_name ?? ''));
            if ($table === '' || $column === '') {
                continue;
            }
            $found[$table][] = $column;
        }

        return $found;
    }

    /**
     * @param  list<string>  $tables
     * @return array<string, list<string>>
     */
    private static function fetch(array $tables): array
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            return self::fromRows(self::pgsqlRows($tables));
        }

        $found = [];
        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            $found[$table] = Schema::getColumnListing($table);
        }

        return $found;
    }

    /**
     * @param  list<string>  $tables
     * @return list<object>
     */
    private static function pgsqlRows(array $tables): array
    {
        $placeholders = implode(', ', array_fill(0, count($tables), '?'));

        return DB::select(
            "select c.relname as table_name, a.attname as column_name
             from pg_class c
             inner join pg_namespace n on n.oid = c.relnamespace
             inner join pg_attribute a on a.attrelid = c.oid
             where n.nspname = any (current_schemas(false))
               and c.relkind in ('r', 'p')
               and a.attnum > 0
               and not a.attisdropped
               and c.relname in ({$placeholders})",
            $tables
        );
    }
}
