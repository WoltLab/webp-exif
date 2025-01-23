<?php

declare(strict_types=1);

namespace Woltlab\WebpExif;

use Nelexa\Buffer\Buffer;
use Nelexa\Buffer\StringBuffer;
use Woltlab\WebpExif\Chunk\Alph;
use Woltlab\WebpExif\Chunk\Anim;
use Woltlab\WebpExif\Chunk\Anmf;
use Woltlab\WebpExif\Chunk\Chunk;
use Woltlab\WebpExif\Chunk\Exif;
use Woltlab\WebpExif\Chunk\Iccp;
use Woltlab\WebpExif\Chunk\UnknownChunk;
use Woltlab\WebpExif\Chunk\Vp8;
use Woltlab\WebpExif\Chunk\Vp8l;
use Woltlab\WebpExif\Chunk\Vp8x;
use Woltlab\WebpExif\Chunk\Xmp;
use Woltlab\WebpExif\Exception\DataAfterLastChunk;
use Woltlab\WebpExif\Exception\FileSizeMismatch;
use Woltlab\WebpExif\Exception\LengthOutOfBounds;
use Woltlab\WebpExif\Exception\NotEnoughData;
use Woltlab\WebpExif\Exception\UnexpectedChunk;
use Woltlab\WebpExif\Exception\UnexpectedEndOfFile;
use Woltlab\WebpExif\Exception\UnrecognizedFileFormat;
use Woltlab\WebpExif\Exception\Vp8xHeaderLengthMismatch;
use Woltlab\WebpExif\Exception\Vp8xMissingImageData;

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
        $expectedMinimumFileSize = 26;
        if ($buffer->size() < $expectedMinimumFileSize) {
            throw new NotEnoughData($expectedMinimumFileSize, $buffer->size());
        }

        $riff = $buffer->getString(4);
        $length = $buffer->getUnsignedInt();
        $format = $buffer->getString(4);
        if ($riff !== 'RIFF' || $format !== 'WEBP') {
            throw new UnrecognizedFileFormat();
        }

        // The length in the header is does not include "RIFF" and the length
        // itself. It must therefore be exactly 8 bytes shorter than the total
        // size.
        $actualLength = $buffer->size() - 8;
        if ($length !== $actualLength) {
            throw new FileSizeMismatch($length, $actualLength);
        }

        $fourCC = $buffer->getString(4);
        $chunkType = ChunkType::fromFourCC($fourCC);
        if ($chunkType === ChunkType::VP8) {
            $vp8 = Vp8::fromBuffer($buffer);
            if ($buffer->hasRemaining()) {
                throw new DataAfterLastChunk($buffer->remaining());
            }

            return WebP::fromChunks($vp8->width, $vp8->height, [$vp8]);
        } else if ($chunkType === ChunkType::VP8L) {
            $vp8l = Vp8l::fromBuffer($buffer);
            if ($buffer->hasRemaining()) {
                throw new DataAfterLastChunk($buffer->remaining());
            }

            return WebP::fromChunks($vp8l->width, $vp8l->height, [$vp8l]);
        } else if ($chunkType === ChunkType::VP8X) {
            return $this->decodeExtendedHeader($buffer);
        } else {
            $originalOffset = $buffer->position() - 4;
            throw new UnexpectedChunk($fourCC, $originalOffset);
        }
    }

    private function decodeExtendedHeader(Buffer $buffer): WebP
    {
        $vp8x = Vp8x::fromBuffer($buffer);

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
            throw new UnexpectedEndOfFile($buffer->position(), $buffer->remaining());
        }

        // The VP8X chunk most contain image data that can come in two flavors:
        //  1. Still images must contain either a VP8 or VP8L chunk.
        //  2. Animated images must contain multiple frames.
        $hasAnimation = \array_find($chunks, static fn(Chunk $chunk) => $chunk instanceof Anim);
        if ($hasAnimation) {
            $frames = \array_filter(
                $chunks,
                static fn(Chunk $chunk) => $chunk instanceof Anmf
            );

            if (\count($frames) < 2) {
                throw new Vp8xMissingImageData(stillImage: false);
            }
        } else {
            $hasBitstreamChunk = \array_find(
                $chunks,
                static fn(Chunk $chunk) => ($chunk instanceof Vp8) || ($chunk instanceof Vp8l)
            );

            if (!$hasBitstreamChunk) {
                throw new Vp8xMissingImageData(stillImage: true);
            }
        }

        return WebP::fromChunks($vp8x->width, $vp8x->height, $chunks);
    }

    private function decodeChunk(Buffer $buffer): Chunk
    {
        $remainingBytes = $buffer->remaining();
        if ($remainingBytes < 8) {
            throw new UnexpectedEndOfFile($buffer->position(), $buffer->remaining());
        }

        $fourCC = $buffer->getString(4);
        $length = $buffer->getUnsignedInt();
        if ($buffer->remaining() < $length) {
            $originalOffset = $buffer->position() - 4;

            throw new LengthOutOfBounds($length, $originalOffset, $buffer->remaining());
        }

        return match (ChunkType::fromFourCC($fourCC)) {
            ChunkType::ALPH => Alph::forBytes($buffer->getString($length)),
            ChunkType::ANIM => Anim::forBytes($buffer->getString($length)),
            ChunkType::ANMF => Anmf::forBytes($buffer->getString($length)),
            ChunkType::EXIF => Exif::forBytes($buffer->getString($length)),
            ChunkType::ICCP => Iccp::forBytes($buffer->getString($length)),
            ChunkType::VP8  => Vp8::fromBuffer($buffer),
            ChunkType::VP8L => Vp8l::fromBuffer($buffer),
            ChunkType::VP8X => throw new UnexpectedChunk("VP8X", $buffer->position() - 4),
            ChunkType::XMP  => Xmp::forBytes($buffer->getString($length)),
            default         => UnknownChunk::forBytes($fourCC, $buffer->getString($length)),
        };
    }
}
