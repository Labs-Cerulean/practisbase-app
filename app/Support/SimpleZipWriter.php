<?php

namespace App\Support;

/**
 * Minimal ZIP writer (store / no compression) that does not need ext-zip.
 * Suitable for CSV/text accountant packs on hosts without php-zip.
 */
class SimpleZipWriter
{
    /** @var array<int, array{name: string, data: string, crc: int, size: int, offset: int}> */
    private array $entries = [];

    private string $data = '';

    public function addFile(string $name, string $contents): void
    {
        $name = str_replace('\\', '/', $name);
        $size = strlen($contents);
        $crc = crc32($contents);
        $offset = strlen($this->data);

        $this->data .= pack('VvvvvvVVVvv',
            0x04034b50, // local file header signature
            20,         // version needed
            0,          // general purpose bit flag
            0,          // compression method: store
            0,          // last mod file time
            0,          // last mod file date
            $crc,
            $size,
            $size,
            strlen($name),
            0           // extra field length
        );
        $this->data .= $name;
        $this->data .= $contents;

        $this->entries[] = [
            'name' => $name,
            'data' => $contents,
            'crc' => $crc,
            'size' => $size,
            'offset' => $offset,
        ];
    }

    public function finish(): string
    {
        $central = '';
        foreach ($this->entries as $entry) {
            $central .= pack('VvvvvvvVVVvvvvvVV',
                0x02014b50, // central file header signature
                20,         // version made by
                20,         // version needed
                0,          // general purpose bit flag
                0,          // compression method
                0,          // last mod file time
                0,          // last mod file date
                $entry['crc'],
                $entry['size'],
                $entry['size'],
                strlen($entry['name']),
                0,          // extra field length
                0,          // file comment length
                0,          // disk number start
                0,          // internal file attributes
                0,          // external file attributes
                $entry['offset']
            );
            $central .= $entry['name'];
        }

        $centralOffset = strlen($this->data);
        $centralSize = strlen($central);
        $count = count($this->entries);

        $end = pack('VvvvvVVv',
            0x06054b50, // end of central directory
            0,          // number of this disk
            0,          // disk with central directory
            $count,
            $count,
            $centralSize,
            $centralOffset,
            0           // zip file comment length
        );

        return $this->data . $central . $end;
    }

    public function writeTo(string $path): void
    {
        if (file_put_contents($path, $this->finish()) === false) {
            throw new \RuntimeException('Could not write ZIP archive to disk.');
        }
    }
}
