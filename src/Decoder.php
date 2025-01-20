<?php

declare(strict_types=1);

namespace Woltlab\WebpExif;

use Nelexa\Buffer\Buffer;
use Nelexa\Buffer\StringBuffer;
use RuntimeException;
use Woltlab\WebpExif\Chunk\Alph;
use Woltlab\WebpExif\Chunk\Anim;
use Woltlab\WebpExif\Chunk\Exif;
use Woltlab\WebpExif\Chunk\Iccp;
use Woltlab\WebpExif\Chunk\UnknownChunk;
use Woltlab\WebpExif\Chunk\Vp8;
use Woltlab\WebpExif\Chunk\Vp8l;
use Woltlab\WebpExif\Chunk\Xmp;

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
        $vp8 = Vp8::fromBuffer($buffer);

        return WebP::fromChunks($vp8->width, $vp8->height, [$vp8]);
    }

    private function decodeLossless(Buffer $buffer): WebP
    {
        $vp8l = Vp8l::fromBuffer($buffer);

        return WebP::fromChunks($vp8l->width, $vp8l->height, [$vp8l]);
    }

    private function decodeExtendedHeader(Buffer $buffer): WebP
    {
        // The next 4 bytes represent the length of the VP8X header which must
        // be 10 bytes long.
        $length = $buffer->getUnsignedInt();
        if ($length !== 10) {
            throw new RuntimeException("TODO: length of the VP8X header must be 10");
        }

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
            if ($chunk->getLength() % 2 === 1) {
                $buffer->skip(1);
            }
        }

        if ($chunks === []) {
            throw new RuntimeException("TODO: VP8X contains no data");
        }

        return WebP::fromChunks($width, $height, $chunks);
    }

    private function decodeChunk(Buffer $buffer): \Woltlab\WebpExif\Chunk\Chunk
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

        switch (ChunkType::fromFourCC($fourCC)) {
            case ChunkType::ALPH:
                return Alph::forBytes($buffer->getString($length));

            case ChunkType::ANIM:
                return Anim::forBytes($buffer->getString($length));

            case ChunkType::EXIF:
                return Exif::forBytes($buffer->getString($length));

            case ChunkType::ICCP:
                return Iccp::forBytes($buffer->getString($length));

            case ChunkType::VP8:
                return Vp8::fromBuffer($buffer);

            case ChunkType::VP8L:
                return Vp8l::fromBuffer($buffer);

            case ChunkType::VP8X:
                throw new RuntimeException("TODO: unexpected VP8X chunk inside of a VP8X chunk");

            case ChunkType::XMP:
                return Xmp::forBytes($buffer->getString($length));

            default:
                return UnknownChunk::forBytes($fourCC, $buffer->getString($length));
        }
    }

    private function decodeDimension(Buffer $buffer): int
    {
        $a = $buffer->getUnsignedByte();
        $b = $buffer->getUnsignedByte();
        $c = $buffer->getUnsignedByte();

        return $a + ($b << 8) + ($c << 16) + 1;
    }
}
