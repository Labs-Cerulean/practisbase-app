<?php

namespace Tests\Unit;

use App\Support\StagingSchemaPlan;
use PHPUnit\Framework\TestCase;

class StagingSchemaPlanTest extends TestCase
{
    public function test_company_tables_exist_before_later_company_files_change_them(): void
    {
        $files = StagingSchemaPlan::files();

        $books = array_search('company_books_postgresql.sql', $files, true);
        $logo = array_search('company_books_logo_postgresql.sql', $files, true);
        $phase4 = array_search('phase4_postgresql.sql', $files, true);
        $dms = array_search('architect_dms_postgresql.sql', $files, true);
        $unify = array_search('unify_practice_clients_to_clients_postgresql.sql', $files, true);

        $this->assertIsInt($books);
        $this->assertIsInt($logo);
        $this->assertIsInt($phase4);
        $this->assertIsInt($dms);
        $this->assertIsInt($unify);
        $this->assertLessThan($logo, $books);
        $this->assertLessThan($dms, $phase4);
        $this->assertLessThan($unify, $dms);
        $this->assertCount(count(array_unique($files)), $files);
    }
}
