<?php

namespace Tests\Unit;

use App\Support\DocxDocument;
use App\Support\PatientReport;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class PatientReportTest extends TestCase
{
    public function test_word_report_is_an_editable_draft_with_the_history(): void
    {
        $blocks = PatientReport::blocks(
            [
                'name' => 'Dr Sara',
                'profession' => 'Medical Professional',
                'registration' => '1234',
            ],
            [
                'display_name' => 'Maria Borg',
                'date_of_birth' => '2 Jan 1990',
                'id_card' => '1234567M',
            ],
            [
                [
                    'date' => '3 Mar 2026',
                    'type_label' => 'Patient note',
                    'body' => "Cycle:\nRegular",
                    'archived' => false,
                ],
                [
                    'date' => '4 Mar 2026',
                    'type_label' => 'Prescription',
                    'body' => '',
                    'medicines' => [[
                        'name' => 'Folic acid',
                        'strength' => '5 mg',
                        'dose' => 'once daily',
                        'quantity' => '28',
                        'instructions' => 'With food',
                    ]],
                    'issue_code' => 'RX-100',
                    'archived' => false,
                ],
                [
                    'date' => '1 Jan 2020',
                    'type_label' => 'Patient note',
                    'body' => 'Hidden because it was archived',
                    'archived' => true,
                ],
            ]
        );

        $text = implode("\n", array_column($blocks, 'text'));
        $this->assertStringContainsString('Draft. Edit this in Word', $text);
        $this->assertStringContainsString('Maria Borg', $text);
        $this->assertStringContainsString('Medical Reg Nº: 1234', $text);
        $this->assertStringContainsString('3 Mar 2026 — Patient note', $text);
        $this->assertStringContainsString('Folic acid 5 mg', $text);
        $this->assertStringContainsString('Issued RX-100', $text);
        $this->assertStringNotContainsString('Hidden because it was archived', $text);

        $bytes = DocxDocument::render($blocks);
        $path = tempnam(sys_get_temp_dir(), 'report');
        file_put_contents($path, $bytes);
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($path);

        $this->assertIsString($xml);
        $this->assertStringContainsString('Maria Borg', $xml);
        $this->assertStringContainsString('Folic acid 5 mg', $xml);
        $this->assertStringContainsString('Regular', $xml);
    }
}
