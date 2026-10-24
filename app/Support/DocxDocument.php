<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

/**
 * Minimal Word document so a doctor can open a patient report and edit it.
 *
 * @phpstan-type Block array{kind: string, text: string}
 */
class DocxDocument
{
    /**
     * @param  list<Block>  $blocks
     */
    public static function render(array $blocks): string
    {
        $paragraphs = '';
        foreach ($blocks as $block) {
            $kind = (string) ($block['kind'] ?? 'p');
            $style = match ($kind) {
                'h1' => 'Heading1',
                'h2' => 'Heading2',
                default => 'Normal',
            };
            $text = (string) ($block['text'] ?? '');
            $lines = preg_split("/\r\n|\n|\r/", $text) ?: [''];
            if ($lines === []) {
                $lines = [''];
            }
            foreach ($lines as $line) {
                $paragraphs .= self::paragraph($style, (string) $line);
            }
        }

        $document = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:body>
    {$paragraphs}
    <w:sectPr>
      <w:pgSz w:w="11906" w:h="16838"/>
      <w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1134"/>
    </w:sectPr>
  </w:body>
</w:document>
XML;

        return self::package($document);
    }

    private static function paragraph(string $style, string $text): string
    {
        $escaped = self::xml($text);

        return '<w:p><w:pPr><w:pStyle w:val="'.$style.'"/></w:pPr><w:r><w:t xml:space="preserve">'.$escaped.'</w:t></w:r></w:p>';
    }

    private static function xml(string $text): string
    {
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $text) ?? $text;

        return htmlspecialchars($clean, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private static function package(string $documentXml): string
    {
        $contentTypes = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
  <Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>
</Types>
XML;

        $rootRels = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>
XML;

        $documentRels = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>
XML;

        $styles = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:style w:type="paragraph" w:default="1" w:styleId="Normal">
    <w:name w:val="Normal"/>
    <w:rPr>
      <w:sz w:val="22"/>
      <w:szCs w:val="22"/>
    </w:rPr>
  </w:style>
  <w:style w:type="paragraph" w:styleId="Heading1">
    <w:name w:val="heading 1"/>
    <w:basedOn w:val="Normal"/>
    <w:rPr>
      <w:b/>
      <w:sz w:val="32"/>
      <w:szCs w:val="32"/>
    </w:rPr>
  </w:style>
  <w:style w:type="paragraph" w:styleId="Heading2">
    <w:name w:val="heading 2"/>
    <w:basedOn w:val="Normal"/>
    <w:rPr>
      <w:b/>
      <w:sz w:val="26"/>
      <w:szCs w:val="26"/>
    </w:rPr>
  </w:style>
</w:styles>
XML;

        $tmp = tempnam(sys_get_temp_dir(), 'pbdocx');
        if ($tmp === false) {
            throw new RuntimeException('Unable to create a Word file.');
        }
        @unlink($tmp);

        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::CREATE) !== true) {
            @unlink($tmp);
            throw new RuntimeException('Unable to open a Word file.');
        }

        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rootRels);
        $zip->addFromString('word/document.xml', $documentXml);
        $zip->addFromString('word/_rels/document.xml.rels', $documentRels);
        $zip->addFromString('word/styles.xml', $styles);
        $zip->close();

        $bytes = file_get_contents($tmp);
        @unlink($tmp);

        if ($bytes === false || $bytes === '') {
            throw new RuntimeException('Unable to read the Word file.');
        }

        return $bytes;
    }
}
