<?php

namespace App\Support;

/**
 * Reads text from a digital invoice PDF.
 * Prefers Poppler when the server has it, then a literal-string reader for
 * simple text PDFs. Scanned images stay empty so the user fills the form.
 */
class PdfPlainText
{
    public static function fromBinary(string $binary): string
    {
        $binary = ltrim($binary, "\xEF\xBB\xBF");
        if ($binary === '' || ! str_starts_with($binary, '%PDF')) {
            return '';
        }

        $parsed = self::viaParser($binary);
        if ($parsed !== '') {
            return $parsed;
        }

        $poppler = self::viaPoppler($binary);
        if ($poppler !== '') {
            return $poppler;
        }

        return self::fromContentStreams($binary);
    }

    public static function fromContentStreams(string $binary): string
    {
        if ($binary === '') {
            return '';
        }

        return self::viaLiteralStreams($binary);
    }

    private static function viaParser(string $binary): string
    {
        if (! class_exists(\Smalot\PdfParser\Parser::class)) {
            return '';
        }

        try {
            $pdf = (new \Smalot\PdfParser\Parser())->parseContent($binary);
            $text = trim($pdf->getText());
        } catch (\Throwable) {
            return '';
        }

        return $text;
    }

    private static function viaPoppler(string $binary): string
    {
        if (! self::canExec()) {
            return '';
        }

        $binaryPath = self::popplerBinary();
        if ($binaryPath === null) {
            return '';
        }

        $tmp = tempnam(sys_get_temp_dir(), 'pb-inv-');
        if ($tmp === false) {
            return '';
        }

        $pdf = $tmp.'.pdf';
        if (! @rename($tmp, $pdf)) {
            $pdf = $tmp;
        }

        try {
            if (file_put_contents($pdf, $binary) === false) {
                return '';
            }

            $command = escapeshellarg($binaryPath).' -layout -enc UTF-8 '.escapeshellarg($pdf).' -';
            $output = [];
            $exit = 1;
            @exec($command, $output, $exit);
            if ($exit !== 0) {
                return '';
            }

            return trim(implode("\n", $output));
        } finally {
            @unlink($pdf);
        }
    }

    private static function canExec(): bool
    {
        if (! function_exists('exec')) {
            return false;
        }

        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));

        return ! in_array('exec', $disabled, true);
    }

    private static function popplerBinary(): ?string
    {
        $candidates = ['pdftotext', '/usr/bin/pdftotext', '/usr/local/bin/pdftotext'];
        foreach ($candidates as $candidate) {
            if ($candidate !== 'pdftotext' && is_executable($candidate)) {
                return $candidate;
            }
            if ($candidate === 'pdftotext') {
                $which = [];
                $exit = 1;
                @exec('command -v pdftotext', $which, $exit);
                if ($exit === 0 && isset($which[0]) && is_executable($which[0])) {
                    return $which[0];
                }
            }
        }

        return null;
    }

    private static function viaLiteralStreams(string $binary): string
    {
        $chunks = [];
        $offset = 0;
        $length = strlen($binary);

        while ($offset < $length && ($pos = strpos($binary, 'stream', $offset)) !== false) {
            $dictStart = strrpos(substr($binary, 0, $pos), '<<');
            $dict = $dictStart !== false ? substr($binary, $dictStart, $pos - $dictStart) : '';
            $start = $pos + 6;
            if (isset($binary[$start]) && $binary[$start] === "\r") {
                $start++;
            }
            if (isset($binary[$start]) && $binary[$start] === "\n") {
                $start++;
            }
            $end = strpos($binary, 'endstream', $start);
            if ($end === false) {
                break;
            }
            $data = substr($binary, $start, $end - $start);
            if (str_ends_with($data, "\r\n")) {
                $data = substr($data, 0, -2);
            } elseif (str_ends_with($data, "\n") || str_ends_with($data, "\r")) {
                $data = substr($data, 0, -1);
            }

            $decoded = self::decodeStream($dict, $data);
            if ($decoded !== null) {
                $text = self::operatorsToText($decoded);
                if ($text !== '') {
                    $chunks[] = $text;
                }
            }
            $offset = $end + 9;
        }

        return trim(implode("\n", $chunks));
    }

    private static function decodeStream(string $dict, string $data): ?string
    {
        if (stripos($dict, '/FlateDecode') !== false) {
            $inflated = @gzuncompress($data);
            if ($inflated === false) {
                $inflated = @gzinflate($data);
            }
            if ($inflated === false) {
                return null;
            }

            return $inflated;
        }

        if (preg_match('/\/Filter\b/', $dict)) {
            return null;
        }

        return $data;
    }

    private static function operatorsToText(string $stream): string
    {
        $lines = [];
        $current = '';
        $length = strlen($stream);
        $i = 0;

        while ($i < $length) {
            $ch = $stream[$i];
            if ($ch === '(') {
                [$literal, $i] = self::readLiteral($stream, $i);
                $current .= self::pdfString($literal);
                continue;
            }
            if ($ch === '<' && isset($stream[$i + 1]) && $stream[$i + 1] !== '<') {
                $close = strpos($stream, '>', $i + 1);
                if ($close !== false) {
                    $hex = substr($stream, $i + 1, $close - $i - 1);
                    if (preg_match('/^[0-9A-Fa-f\s]+$/', $hex)) {
                        $current .= self::hexToText($hex);
                    }
                    $i = $close + 1;
                    continue;
                }
            }
            if ($ch === "\n" || $ch === "\r") {
                $i++;
                continue;
            }

            if (preg_match('/\G(Tj|TJ|T\*|\'|")/', $stream, $op, 0, $i)) {
                if (in_array($op[1], ['T*', "'", '"'], true) && trim($current) !== '') {
                    $lines[] = trim($current);
                    $current = '';
                }
                $i += strlen($op[1]);
                continue;
            }

            if (preg_match('/\G(-?\d+(?:\.\d+)?)\s+(-?\d+(?:\.\d+)?)\s+Td\b/', $stream, $td, 0, $i)) {
                if ((float) $td[2] < 0 && trim($current) !== '') {
                    $lines[] = trim($current);
                    $current = '';
                } elseif ((float) $td[1] > 20 && trim($current) !== '') {
                    $current .= ' ';
                }
                $i += strlen($td[0]);
                continue;
            }

            if (preg_match('/\G(-?\d+(?:\.\d+)?)\s+TJ\b/', $stream, $kern, 0, $i)) {
                if ((float) $kern[1] < -80) {
                    $current .= ' ';
                }
                $i += strlen($kern[0]);
                continue;
            }

            $i++;
        }

        if (trim($current) !== '') {
            $lines[] = trim($current);
        }

        return trim(implode("\n", array_filter($lines, fn (string $line) => $line !== '')));
    }

    /**
     * @return array{0: string, 1: int}
     */
    private static function readLiteral(string $stream, int $openParen): array
    {
        $depth = 1;
        $i = $openParen + 1;
        $raw = '';
        $length = strlen($stream);
        while ($i < $length && $depth > 0) {
            $ch = $stream[$i];
            if ($ch === '\\') {
                $raw .= $ch;
                $i++;
                if ($i < $length) {
                    $raw .= $stream[$i];
                    $i++;
                }
                continue;
            }
            if ($ch === '(') {
                $depth++;
                $raw .= $ch;
                $i++;
                continue;
            }
            if ($ch === ')') {
                $depth--;
                if ($depth === 0) {
                    $i++;
                    break;
                }
                $raw .= $ch;
                $i++;
                continue;
            }
            $raw .= $ch;
            $i++;
        }

        return [$raw, $i];
    }

    private static function pdfString(string $raw): string
    {
        $out = '';
        $length = strlen($raw);
        for ($i = 0; $i < $length; $i++) {
            $ch = $raw[$i];
            if ($ch !== '\\') {
                $out .= $ch;
                continue;
            }
            if ($i + 1 >= $length) {
                break;
            }
            $next = $raw[++$i];
            $map = [
                'n' => "\n",
                'r' => "\r",
                't' => "\t",
                'b' => "\x08",
                'f' => "\f",
                '(' => '(',
                ')' => ')',
                '\\' => '\\',
            ];
            if (isset($map[$next])) {
                $out .= $map[$next];
                continue;
            }
            if ($next >= '0' && $next <= '7') {
                $oct = $next;
                for ($k = 0; $k < 2 && ($i + 1) < $length && $raw[$i + 1] >= '0' && $raw[$i + 1] <= '7'; $k++) {
                    $oct .= $raw[++$i];
                }
                $out .= chr(octdec($oct));
                continue;
            }
            if ($next === "\n" || $next === "\r") {
                if ($next === "\r" && isset($raw[$i + 1]) && $raw[$i + 1] === "\n") {
                    $i++;
                }
                continue;
            }
            $out .= $next;
        }

        if (str_starts_with($out, "\xFE\xFF") && function_exists('mb_convert_encoding')) {
            $converted = mb_convert_encoding(substr($out, 2), 'UTF-8', 'UTF-16BE');
            if (is_string($converted)) {
                return $converted;
            }
        }

        return $out;
    }

    private static function hexToText(string $hex): string
    {
        $hex = preg_replace('/\s+/', '', $hex) ?? '';
        if ($hex === '' || strlen($hex) % 2 === 1) {
            if (strlen($hex) % 2 === 1) {
                $hex .= '0';
            }
        }
        $bin = hex2bin($hex);
        if ($bin === false) {
            return '';
        }
        if (str_starts_with($bin, "\xFE\xFF") && function_exists('mb_convert_encoding')) {
            $converted = mb_convert_encoding(substr($bin, 2), 'UTF-8', 'UTF-16BE');

            return is_string($converted) ? $converted : '';
        }

        return $bin;
    }
}
