<?php

declare(strict_types=1);

namespace Woltlab\WebpExif;

use Nelexa\Buffer\Buffer;
use Nelexa\Buffer\StringBuffer;
use RuntimeException;
use TypeError;
use Woltlab\WebpExif\Chunk\Vp8l;

final class Decoder
{
    public function fromBinary(string $binary): WebP
    {
        $buffer = new StringBuffer($binary);
        $buffer->setOrder(Buffer::LITTLE_ENDIAN);
        $buffer->setReadOnly(true);

        // A RIFF container at its minimum contains the "RIFF" header, a
        // uint32LE representing the chunk size, the "WEBP" type and the data
        // section. The data section of a WebP at minimum contains one chunk
        // (header + uint32LE + data).
        //
        // The shortest possible WebP image is a simple VP8L container that
        // contains only the magic byte, a uint32 for the flags and dimensions,
        // and at last a single byte of data. This takes up 26 bytes in total.
        if ($buffer->size() < 26) {
            throw new RuntimeException("TODO: too small");
        }

        $riff = $buffer->getString(4);
        $length = $buffer->getUnsignedInt();
        $format = $buffer->getString(4);
        if ($riff !== 'RIFF' || $format !== 'WEBP') {
            throw new RuntimeException("TODO: not riff/webp");
        }

        // The length in the header is does not include "RIFF" and the length
        // itself. It must therefore be exactly 8 bytes shorter than the total
        // size.
        if ($length !== $buffer->size() - 8) {
            throw new RuntimeException("TODO: length mismatch");
        }

        $fourCC = $buffer->getString(4);
        switch (ChunkType::fromFourCC($fourCC)) {
            case ChunkType::VP8:
                return $this->decodeLossy($buffer);

            case ChunkType::VP8L:
                return $this->decodeLossless($buffer);

            case ChunkType::VP8X:
                // We're implicitly discarding the top chunk here because it
                // contains no relevant information. The data is a subsection of
                // `$binary` and using the `$topChunk` means we have to reset
                // the offset. Preserving the existing offset means we can
                // output meaningful offsets in error messages if we need to.
                return $this->decodeExtendedHeader($buffer);

            default: {
                    $originalOffset = $buffer->position() - 4;
                    throw new RuntimeException("TODO: unexpected chunk {$fourCC} at offset {$originalOffset}");
                }
        }
    }

    /**
     * @see https://datatracker.ietf.org/doc/html/rfc6386
     */
    private function decodeLossy(Buffer $buffer): WebP
    {
        $length = $buffer->getUnsignedInt();
        $startOfData = $buffer->position();
        //$totalSize = \strlen($binary);

        $tag = $buffer->getUnsignedByte();

        // We expect the first frame to be a keyframe.
        $frameType = $tag & 1;
        if ($frameType !== 0) {
            throw new RuntimeException("Expected the first frame to be a keyframe");
        }

        // Skip the next two bytes, they are part of the header but do not
        // contain any information that is relevant to us.
        $buffer->skip(2);

        // Keyframes must start with 3 magic bytes.
        $marker = $buffer->getString(3);
        if ($marker !== "\x9D\x01\x2A") {
            throw new RuntimeException("Expected the magic bytes 0x9D 0x01 0x2A at the start of the keyframe");
        }

        // The width and height are encoded using 2 bytes each. However, the
        // first two bits are the scale followed by 14 bits for the dimension.
        $width = $buffer->getUnsignedShort() & 0x3FFF;
        $height = $buffer->getUnsignedShort() & 0x3FFF;

        return WebP::fromChunks(
            $width,
            $height,
            [
                new Chunk("VP8 ", $buffer->setPosition($startOfData)->getString($length))
            ]
        );
    }

    private function decodeLossless(Buffer $buffer): WebP
    {
        $vp8l = Vp8l::fromBuffer($buffer);

        return WebP::fromChunks($vp8l->width, $vp8l->height, [$vp8l]);
    }

    private function decodeExtendedHeader(Buffer $buffer): WebP
    {
        // After the `VP8X` header there are 4 more bytes that appear to have no
        // meaning but the first byte is always set to 0x0A.
        $buffer->skip(4);

        // The following 4 bytes contain a single byte containing a bitmask for
        // the contained features (which we can safely ignore at this point),
        // followed by 24 reserved bits.
        $buffer->skip(4);

        // The width of the canvas is represented as a uint24LE but minus one,
        // therefore we have to add 1 when decoding the value.
        $width = $this->decodeDimension($buffer);

        // The height follows the same rules as the width.
        $height = $this->decodeDimension($buffer);

        // Decode the remaining chunks.
        $chunks = [];
        while ($buffer->hasRemaining()) {
            $chunk = $this->decodeChunk($buffer);
            $chunks[] = $chunk;

            // RIFF requires all chunks to be of even length, chunks with an
            // uneven length must be padded by a single 0x00 at the end.
            if ($chunk->length % 2 === 1) {
                $buffer->skip(1);
            }
        }

        if ($chunks === []) {
            throw new RuntimeException("TODO: VP8X contains no data");
        }

        return WebP::fromChunks($width, $height, $chunks);
    }

    private function decodeChunk(Buffer $buffer): Chunk
    {
        $remainingBytes = $buffer->remaining();
        if ($remainingBytes < 8) {
            $offset = $buffer->position();
            throw new RuntimeException("Unexpected EOF, expected a chunk at offset {$offset} but there are only {$remainingBytes} more bytes");
        }

        $fourCC = $buffer->getString(4);
        $length = $buffer->getUnsignedInt();
        if ($buffer->remaining() < $length) {
            $offset = $buffer->position();
            throw new RuntimeException("TODO: length {$length} for chunk {$fourCC} at offset {$offset} is out of bounds");
        }

        return new Chunk($fourCC, $buffer->getString($length));
    }

    private function decodeDimension(Buffer $buffer): int
    {
        $a = $buffer->getUnsignedByte();
        $b = $buffer->getUnsignedByte();
        $c = $buffer->getUnsignedByte();

        return $a + ($b << 8) + ($c << 16) + 1;
    }
}
