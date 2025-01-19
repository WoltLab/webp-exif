<?php

declare(strict_types=1);

namespace Woltlab\WebpExif;

use RuntimeException;

final class Decoder
{
    public function fromBinary(string $binary): WebP
    {
        $totalSize = \strlen($binary);

        // A RIFF container at its minimum contains the "RIFF" header, a
        // uint32LE representing the chunk size, the "WEBP" type and the data
        // section. The data section of a WebP at minimum contains one chunk
        // (header + uint32LE + data). We assume the data to be non empty which
        // is at least 1 byte, also RIFF requires chunks to have an even length
        // which forces a 0x00 padding byte, upping this to 2. The smallest
        // possible length of a WebP, regardless of the data, thus is 2x 12 + 2
        // bytes = 26 bytes.
        if ($totalSize < 26) {
            throw new RuntimeException("TODO: too small");
        }

        $header = \unpack('A4riff/Vlength/A4format', $binary);
        \assert($header !== false);
        if ($header['riff'] !== 'RIFF' || $header['format'] !== 'WEBP') {
            throw new RuntimeException("TODO: not riff/webp");
        }

        // The length in the header is does not include "RIFF" and the length
        // itself. It must therefore be exactly 8 bytes shorter than the total
        // size.
        if ($header['length'] !== $totalSize - 8) {
            throw new RuntimeException("TODO: length mismatch");
        }

        $offset = 12;
        $topChunk = $this->decodeChunk($binary, $offset);
        switch ($topChunk->type) {
            case ChunkType::VP8:
            case ChunkType::VP8L:
                return WebP::fromChunks([$topChunk]);

            case ChunkType::VP8X:
                // We're implicitly discarding the top chunk here because it
                // contains no relevant information. The data is a subsection of
                // `$binary` and using the `$topChunk` means we have to reset
                // the offset. Preserving the existing offset means we can
                // output meaningful offsets in error messages if we need to.
                return $this->decodeExtendedHeader($binary, $offset + 8);

            default:
                throw new RuntimeException("TODO: unexpected chunk {$topChunk->fourCC} at offset {$offset}");
        }
    }

    private function decodeExtendedHeader(string $binary, int $offset): WebP
    {
        // After the `VP8X` header there are 4 more bytes that appear to have no
        // meaning but the first byte is always set to 0x0A.
        $offset += 4;

        // The following 4 bytes contain a single byte containing a bitmask for
        // the contained features (which we can safely ignore at this point),
        // followed by 24 reserved bits.
        $offset += 4;

        // The width of the canvas is represented as a uint24LE but minus one,
        // therefore we have to add 1 when decoding the value.
        $width = $this->decodeDimension($binary, $offset);
        $offset += 3;

        // The height follows the same rules as the width.
        $height = $this->decodeDimension($binary, $offset);
        $offset += 3;

        // Decode the remaining chunks.
        $chunks = [];
        $totalSize = \strlen($binary);
        while ($offset < $totalSize) {
            $chunk = $this->decodeChunk($binary, $offset);
            $chunks[] = $chunk;

            // RIFF requires all chunks to be of even length, chunks with an
            // uneven length must be padded by a single 0x00 at the end.
            $paddingByte = $chunk->length % 2;
            $offset += $chunk->length + $paddingByte;
        }

        \assert($offset === $totalSize);

        if ($chunks === []) {
            throw new RuntimeException("TODO: VP8X contains no data");
        }

        return WebP::fromChunks($chunks);
    }

    private function decodeChunk(string $binary, int $offset): Chunk
    {
        $header = \unpack('A4fourCC/Vlength', $binary, $offset);
        \assert($header !== false);

        $fourCC = $header['fourCC'];
        $length = $header['length'];
        if ($length + 8 > \strlen($binary)) {
            throw new RuntimeException("TODO: length {$length} for chunk {$fourCC} at offset {$offset} is out of bounds");
        }

        return new Chunk($fourCC, \substr($binary, $offset + 8, $length));
    }

    private function decodeDimension(string $binary, int $offset): int
    {
        $bytes = \unpack('ca/cb/cc', $binary, $offset);
        \assert($bytes !== false);

        return $bytes['a'] + ($bytes['b'] << 8) + ($bytes['c'] << 16) + 1;
    }
}
