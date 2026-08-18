<?php

namespace Tests\Unit;

use App\Support\Architect\ProjectReference;
use PHPUnit\Framework\TestCase;

class ArchitectProjectReferenceTest extends TestCase
{
    public function test_slug_strips_noise_for_practice_name(): void
    {
        $this->assertSame(
            'CAMILLERI',
            ProjectReference::slugPart('Camilleri Architecture Studio', 16, preferMeaningful: true)
        );
        $this->assertSame(
            'CERULEANLABS',
            ProjectReference::slugPart('Cerulean Labs Limited', 16, preferMeaningful: true)
        );
    }

    public function test_slug_client_and_locality(): void
    {
        $this->assertSame('MARIABORG', ProjectReference::slugPart('Maria Borg', 16, preferMeaningful: false));
        $this->assertSame('SLIEMA', ProjectReference::slugPart('Sliema', 12, preferMeaningful: false));
        $this->assertSame('STJULIANS', ProjectReference::slugPart("St. Julian's", 12, preferMeaningful: false));
        // Multi-word over max → last word when it fits
        $this->assertSame(
            'WORDS',
            ProjectReference::slugPart('Very Long Name Here Extra Words', 16, preferMeaningful: false)
        );
        $this->assertSame(
            'SUPERCALIFRAGILI',
            ProjectReference::slugPart('Supercalifragilistic', 16, preferMeaningful: false)
        );
    }

    public function test_prefix_joins_practice_client_locality(): void
    {
        $this->assertSame(
            'CAMILLERI-MARIABORG-SLIEMA',
            ProjectReference::prefix('Camilleri Architecture', 'Maria Borg', 'Sliema')
        );
    }

    public function test_prefix_omits_empty_locality(): void
    {
        $this->assertSame(
            'CAMILLERI-BORG',
            ProjectReference::prefix('Camilleri Architecture', 'Borg', '')
        );
    }

    public function test_next_sequence_from_codes(): void
    {
        $codes = [
            'CAMILLERI-BORG-SLIEMA-001',
            'CAMILLERI-BORG-SLIEMA-003',
            'OTHER-001',
            'CAMILLERI-BORG-SLIEMA-002',
        ];

        $this->assertSame(4, ProjectReference::nextSequenceFromCodes($codes, 'CAMILLERI-BORG-SLIEMA'));
        $this->assertSame(1, ProjectReference::nextSequenceFromCodes([], 'CAMILLERI-BORG-SLIEMA'));
        $this->assertSame(2, ProjectReference::nextSequenceFromCodes(['CAMILLERI-BORG-SLIEMA-001'], 'CAMILLERI-BORG-SLIEMA'));
    }

    public function test_format_sequence_pads_three_digits(): void
    {
        $this->assertSame('001', ProjectReference::formatSequence(1));
        $this->assertSame('012', ProjectReference::formatSequence(12));
        $this->assertSame('100', ProjectReference::formatSequence(100));
    }
}
