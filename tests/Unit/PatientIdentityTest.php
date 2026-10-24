<?php

namespace Tests\Unit;

use App\Support\PatientIdentity;
use PHPUnit\Framework\TestCase;

class PatientIdentityTest extends TestCase
{
    public function test_same_id_card_matches_even_when_the_letter_is_missing(): void
    {
        $this->assertSame('id', PatientIdentity::reason('Anna Vella', '1234567M', 'A. Vella', '1234567'));
    }

    public function test_blank_id_cards_do_not_match_each_other(): void
    {
        $this->assertSame('name', PatientIdentity::reason('Anna Vella', '', 'Anna Vella', ''));
    }

    public function test_exact_name_is_flagged_and_a_short_different_name_is_not(): void
    {
        $this->assertSame('name', PatientIdentity::reason('Maria Borg', '', 'maria   borg', null));
        $this->assertNull(PatientIdentity::reason('Ann', '1', 'Anna', '2'));
    }

    public function test_find_matches_skips_the_chart_being_edited_and_prefers_the_id(): void
    {
        $matches = PatientIdentity::findMatches('Maria Borg', '1234567M', [
            ['id' => 4, 'name' => 'Maria Borg', 'id_card' => '9999999M', 'date_of_birth' => '1980-01-02', 'archived' => false],
            ['id' => 9, 'name' => 'Someone Else', 'id_card' => '1234567M', 'date_of_birth' => '1975-04-04', 'archived' => true],
        ], 4);

        $this->assertCount(1, $matches);
        $this->assertSame(9, $matches[0]['id']);
        $this->assertSame('id', $matches[0]['reason']);
        $this->assertTrue($matches[0]['archived']);
    }
}
