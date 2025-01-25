<?php

declare(strict_types=1);

namespace Woltlab\WebpExif;

use Nelexa\Buffer\Buffer;
use Nelexa\Buffer\BufferException;
use Nelexa\Buffer\StringBuffer;
use Woltlab\WebpExif\Chunk\Alph;
use Woltlab\WebpExif\Chunk\Anim;
use Woltlab\WebpExif\Chunk\Anmf;
use Woltlab\WebpExif\Chunk\Chunk;
use Woltlab\WebpExif\Chunk\Exception\UnknownChunkWithKnownFourCC;
use Woltlab\WebpExif\Chunk\Exception\ExpectedKeyFrame;
use Woltlab\WebpExif\Chunk\Exception\MissingMagicByte;
use Woltlab\WebpExif\Chunk\Exception\UnsupportedVersion;
use Woltlab\WebpExif\Chunk\Exception\DimensionsExceedInt32;
use Woltlab\WebpExif\Chunk\Exif;
use Woltlab\WebpExif\Chunk\Iccp;
use Woltlab\WebpExif\Chunk\UnknownChunk;
use Woltlab\WebpExif\Chunk\Vp8;
use Woltlab\WebpExif\Chunk\Vp8l;
use Woltlab\WebpExif\Chunk\Vp8x;
use Woltlab\WebpExif\Chunk\Xmp;
use Woltlab\WebpExif\Exception\FileSizeMismatch;
use Woltlab\WebpExif\Exception\LengthOutOfBounds;
use Woltlab\WebpExif\Exception\NotEnoughData;
use Woltlab\WebpExif\Exception\UnexpectedEndOfFile;
use Woltlab\WebpExif\Exception\UnrecognizedFileFormat;
use Woltlab\WebpExif\Exception\Vp8xHeaderLengthMismatch;

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

        // The length in the header does not include "RIFF" and the length
        // itself. It must therefore be exactly 8 bytes shorter than the total
        // size.
        $actualLength = $buffer->size() - 8;
        if ($length !== $actualLength) {
            throw new FileSizeMismatch($length, $actualLength);
        }

        /** @var list<Chunk> */
        $chunks = [];
        do {
            $chunks[] = $this->decodeChunk($buffer);
        } while ($buffer->hasRemaining());

        return WebP::fromChunks($chunks);
    }

    /**
     * @internal
     */
    public function decodeChunk(Buffer $buffer): Chunk
    {
        $remainingBytes = $buffer->remaining();
        if ($remainingBytes < 8) {
            throw new UnexpectedEndOfFile($buffer->position(), $buffer->remaining());
        }

        $chunkPosition = $buffer->position();
        $fourCC = $buffer->getString(4);
        $originalOffset = $buffer->position();
        $length = $buffer->getUnsignedInt();
        if ($buffer->remaining() < $length) {
            throw new LengthOutOfBounds($length, $originalOffset, $buffer->remaining());
        }

        $chunk = match (ChunkType::fromFourCC($fourCC)) {
            ChunkType::ALPH => Alph::forBytes($chunkPosition, $buffer->getString($length)),
            ChunkType::ANIM => Anim::forBytes($chunkPosition, $buffer->getString($length)),
            ChunkType::ANMF => Anmf::fromBuffer($buffer->setPosition($originalOffset)),
            ChunkType::EXIF => Exif::forBytes($chunkPosition, $buffer->getString($length)),
            ChunkType::ICCP => Iccp::forBytes($chunkPosition, $buffer->getString($length)),
            ChunkType::XMP  => Xmp::forBytes($chunkPosition, $buffer->getString($length)),
            default         => UnknownChunk::forBytes($fourCC, $chunkPosition, $buffer->getString($length)),

            // VP8, VP8L and VP8X are a bit different because these need to be
            // able to evaluate the length of the chunk themselves for various
            // reasons.
            ChunkType::VP8  => Vp8::fromBuffer($buffer->setPosition($originalOffset)),
            ChunkType::VP8L => Vp8l::fromBuffer($buffer->setPosition($originalOffset)),
            ChunkType::VP8X => Vp8x::fromBuffer($buffer->setPosition($originalOffset)),
        };

        // The length of every chunk in a RIFF container must be of an even
        // length. Uneven chunks must be padded by a single 0x00 at the end.
        if ($length % 2 === 1) {
            $buffer->setPosition($buffer->position() + 1);
        }

        return $chunk;
    }
}
