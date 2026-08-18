<?php

namespace Tests\Unit;

use App\Support\Architect\ProjectReference;
use PHPUnit\Framework\TestCase;

class ArchitectProjectReferenceTest extends TestCase
{
    public function test_slug_strips_noise_and_truncates_to_four(): void
    {
        $this->assertSame(
            'CAMI',
            ProjectReference::slugPart('Camilleri Architecture Studio', preferMeaningful: true)
        );
        $this->assertSame(
            'CERU',
            ProjectReference::slugPart('Cerulean Labs Limited', preferMeaningful: true)
        );
        $this->assertSame(4, strlen(ProjectReference::slugPart('Camilleri Architecture Studio', preferMeaningful: true)));
    }

    public function test_slug_client_and_locality_max_four(): void
    {
        $this->assertSame('BORG', ProjectReference::slugPart('Maria Borg'));
        $this->assertSame('SLIE', ProjectReference::slugPart('Sliema'));
        $this->assertSame('JULI', ProjectReference::slugPart("St. Julian's"));
        $this->assertSame('WORD', ProjectReference::slugPart('Very Long Name Here Extra Words'));
        $this->assertSame('SUPE', ProjectReference::slugPart('Supercalifragilistic'));
    }

    public function test_prefix_joins_truncated_parts(): void
    {
        $this->assertSame(
            'CAMI-BORG-SLIE',
            ProjectReference::prefix('Camilleri Architecture', 'Maria Borg', 'Sliema')
        );
    }

    public function test_prefix_omits_empty_locality(): void
    {
        $this->assertSame(
            'CAMI-BORG',
            ProjectReference::prefix('Camilleri Architecture', 'Borg', '')
        );
    }

    public function test_next_sequence_from_codes(): void
    {
        $codes = [
            'CAMI-BORG-SLIE-001',
            'CAMI-BORG-SLIE-003',
            'OTHER-001',
            'CAMI-BORG-SLIE-002',
        ];

        $this->assertSame(4, ProjectReference::nextSequenceFromCodes($codes, 'CAMI-BORG-SLIE'));
        $this->assertSame(1, ProjectReference::nextSequenceFromCodes([], 'CAMI-BORG-SLIE'));
        $this->assertSame(2, ProjectReference::nextSequenceFromCodes(['CAMI-BORG-SLIE-001'], 'CAMI-BORG-SLIE'));
    }

    public function test_format_sequence_pads_three_digits(): void
    {
        $this->assertSame('001', ProjectReference::formatSequence(1));
        $this->assertSame('012', ProjectReference::formatSequence(12));
        $this->assertSame('100', ProjectReference::formatSequence(100));
    }
}
