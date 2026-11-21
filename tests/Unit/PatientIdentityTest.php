<?php

namespace Tests\Unit;

use App\Support\PatientIdentity;
use PHPUnit\Framework\TestCase;

class PatientIdentityTest extends TestCase
{
    public function test_the_same_id_card_matches_after_spaces_are_ignored(): void
    {
        $this->assertSame('id', PatientIdentity::reason('Anna Vella', '1234567 M', 'A. Vella', '1234567M'));
    }

    public function test_a_partial_id_or_a_near_name_is_not_a_duplicate(): void
    {
        $this->assertNull(PatientIdentity::reason('Anna Vella', '1234567M', 'A. Vella', '1234567'));
        $this->assertNull(PatientIdentity::reason('Anna Vella', '123456', 'Someone Else', '1234567M'));
        $this->assertNull(PatientIdentity::reason('Anna Vel', '', 'Anna Vella', ''));
        $this->assertNull(PatientIdentity::reason('Maria Bor', '', 'Maria Borg', ''));
        $this->assertNull(PatientIdentity::reason('Maria', '', 'Maria Borg', ''));
        $this->assertNull(PatientIdentity::reason('Marie Borg', '', 'Maria Borg', ''));
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

    public function test_the_live_check_waits_for_a_surname_or_a_finished_id(): void
    {
        $js = (string) file_get_contents(dirname(__DIR__, 2).'/resources/views/pro/medical/_patient-match.blade.php');

        $this->assertStringContainsString("trimmed.indexOf(' ')", $js);
        $this->assertStringContainsString('/^[0-9]{5,8}[A-Z]$/', $js);
        $this->assertStringNotContainsString('idNorm.length >= 5', $js);
        $this->assertStringNotContainsString('name="tel"', $js);
        $this->assertStringNotContainsString('name="email"', $js);
        $this->assertStringNotContainsString('name="date_of_birth"', $js);
    }
}
