<?php

namespace Tests\Unit;

use App\Support\Architect\EappsCaseUrl;
use PHPUnit\Framework\TestCase;

class EappsCaseUrlTest extends TestCase
{
    public function test_pads_case_number_to_five_digits(): void
    {
        $this->assertSame('00525', EappsCaseUrl::padCaseNumber('525'));
        $this->assertSame('00525', EappsCaseUrl::padCaseNumber('0525'));
        $this->assertSame('00525', EappsCaseUrl::padCaseNumber('00525'));
        $this->assertSame('00525', EappsCaseUrl::padCaseNumber(525));
    }

    public function test_builds_eapps_url_with_padded_number(): void
    {
        $url = EappsCaseUrl::build([
            'case_type' => 'PA',
            'case_number' => '525',
            'case_year' => '22',
        ]);

        $this->assertSame(
            'https://eapps.pa.org.mt/Case/CaseDetails?casenumber=00525&caseType=PA&caseYear=22',
            $url
        );
    }

    public function test_rejects_unpadded_style_when_building_from_display(): void
    {
        $parsed = EappsCaseUrl::parse('PA/0525/22');
        $this->assertNotNull($parsed);
        $this->assertSame('00525', $parsed['case_number']);

        $url = EappsCaseUrl::build(['pa_number' => 'pa/525/22']);
        $this->assertStringContainsString('casenumber=00525', (string) $url);
        $this->assertStringNotContainsString('casenumber=0525', (string) $url);
        $this->assertStringNotContainsString('casenumber=525&', (string) $url);
    }

    public function test_format_display_canonical(): void
    {
        $this->assertSame('PA/00525/22', EappsCaseUrl::formatDisplay('pa', '525', '22'));
        $this->assertSame('PA/00525/22', EappsCaseUrl::formatDisplay('PA', '0525', '2022'));
    }
}
