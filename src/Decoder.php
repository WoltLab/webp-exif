<?php

declare(strict_types=1);

namespace Woltlab\WebpExif;

use RuntimeException;
use TypeError;

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
        $offset += 8;

        switch ($topChunk->type) {
            case ChunkType::VP8:
                return $this->decodeLossy($binary, $offset, $topChunk);

            case ChunkType::VP8L:
                return $this->decodeLossless($binary, $offset, $topChunk);

            case ChunkType::VP8X:
                // We're implicitly discarding the top chunk here because it
                // contains no relevant information. The data is a subsection of
                // `$binary` and using the `$topChunk` means we have to reset
                // the offset. Preserving the existing offset means we can
                // output meaningful offsets in error messages if we need to.
                return $this->decodeExtendedHeader($binary, $offset);

            default: {
                    $originalOffset = $offset - 8;
                    throw new RuntimeException("TODO: unexpected chunk {$topChunk->fourCC} at offset {$originalOffset}");
                }
        }
    }

    /**
     * @see https://datatracker.ietf.org/doc/html/rfc6386
     */
    private function decodeLossy(string $binary, int $offset, Chunk $topChunk): WebP
    {
        $totalSize = \strlen($binary);

        $header = \unpack('ctag', $binary, $offset);
        \assert($header !== false);

        $offset += 1;

        // We expect the first frame to be a keyframe.
        $frameType = $header['tag'] & 1;
        if ($frameType !== 0) {
            throw new RuntimeException("Expected the first frame to be a keyframe");
        }

        // Skip the next two bytes, they are part of the header but do not
        // contain any information that is relevant to us.
        $offset += 2;

        // Keyframes must start with 3 magic bytes.
        if ($binary[$offset] !== "\x9D" || $binary[$offset + 1] !== "\x01" || $binary[$offset + 2] !== "\x2A") {
            throw new RuntimeException("Expected the magic bytes 0x9D 0x01 0x2A at the start of the keyframe");
        }

        $offset += 3;

        // The width and height are encoded using 2 bytes each. However, the
        // first two bits are the scale followed by 14 bits for the dimension.
        $dimensions = \unpack('vwidth/vheight', $binary, $offset);
        \assert($dimensions !== false);

        $width = $dimensions['width'] & 0x3FFF;
        $height = $dimensions['height'] & 0x3FFF;

        return WebP::fromChunks($width, $height, [$topChunk]);
    }

    private function decodeLossless(string $binary, int $offset, Chunk $topChunk): WebP
    {
        if ($binary[$offset] !== "\x2F") {
            throw new RuntimeException("TODO: invalid signature for lossless");
        }

        $offset += 1;

        $header = \unpack("Vheader", $binary, $offset);
        \assert($header !== false);

        // The header contains the following data:
        // 0-13: width - 1
        // 14-27: height - 1
        // 28: alpha_is_used
        // 29-31: version (must be 0)
        $header = $header['header'];
        $version = $header >> 29;
        if ($version !== 0) {
            throw new RuntimeException("Expected the version to be 0, found {$version} instead");
        }

        $width = (1 + $header) & 0x3FFF;
        $height = (1 + ($header >> 14)) & 0x3FFF;

        return WebP::fromChunks($width, $height, [$topChunk]);
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

        return WebP::fromChunks($width, $height, $chunks);
    }

    private function decodeChunk(string $binary, int $offset): Chunk
    {
        $totalSize = \strlen($binary);
        if ($offset + 8 > $totalSize) {
            $remainingBytes = $totalSize - $offset;
            throw new RuntimeException("Unexpected EOF, expected a chunk at offset {$offset} but there are only {$remainingBytes} more bytes");
        }

        $header = \unpack('A4fourCC/Vlength', $binary, $offset);
        \assert($header !== false);

        $offset += 8;

        $fourCC = $header['fourCC'];
        $length = $header['length'];
        if ($offset + $length > $totalSize) {
            throw new RuntimeException("TODO: length {$length} for chunk {$fourCC} at offset {$offset} is out of bounds");
        }

        return new Chunk($fourCC, \substr($binary, $offset, $length));
    }

    private function decodeDimension(string $binary, int $offset): int
    {
        $bytes = \unpack('ca/cb/cc', $binary, $offset);
        \assert($bytes !== false);

        return $bytes['a'] + ($bytes['b'] << 8) + ($bytes['c'] << 16) + 1;
    }
}
