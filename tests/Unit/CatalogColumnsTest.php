<?php

namespace Tests\Unit;

use App\Support\CatalogColumns;
use PHPUnit\Framework\TestCase;

class CatalogColumnsTest extends TestCase
{
    public function test_rows_group_columns_under_their_table(): void
    {
        $found = CatalogColumns::fromRows([
            (object) ['table_name' => 'users', 'column_name' => 'id'],
            (object) ['table_name' => 'users', 'column_name' => 'must_verify_email'],
            ['table_name' => 'sessions', 'column_name' => 'user_id'],
            (object) ['table_name' => '', 'column_name' => 'skip'],
        ]);

        $this->assertSame(['id', 'must_verify_email'], $found['users']);
        $this->assertSame(['user_id'], $found['sessions']);
        $this->assertArrayNotHasKey('', $found);
    }
}
