<?php

declare(strict_types=1);

use Nelexa\Buffer\Buffer;
use Nelexa\Buffer\StringBuffer;
use PHPUnit\Framework\TestCase;
use Woltlab\WebpExif\Chunk\Exception\MissingMagicByte;
use Woltlab\WebpExif\Chunk\Exception\UnsupportedVersion;
use Woltlab\WebpExif\Chunk\Vp8l;
use Woltlab\WebpExif\ChunkType;
use Woltlab\WebpExif\Exception\LengthOutOfBounds;

final class Vp8lTest extends TestCase
{
    public function testReportsCorrectFourCC(): void
    {
        $pseudoValidVp8l = $this->getBufferFor("\x00\x00\x00\x00\x2F\xFF\xFF\xFF\x0F");
        $chunk = Vp8l::fromBuffer($pseudoValidVp8l);
        self::assertSame(
            ChunkType::VP8L,
            ChunkType::fromFourCC($chunk->getFourCC()),
        );
    }

    public function testLengthOfPayloadExceedsEof(): void
    {
        $this->expectExceptionObject(new LengthOutOfBounds(6, 4, 5));

        $lengthExceedsEof = $this->getBufferFor("\x06\x00\x00\x00\x2F\x00\x00\x00\x00");
        Vp8l::fromBuffer($lengthExceedsEof);
    }

    public function testMissingMagicByte(): void
    {
        $this->expectException(MissingMagicByte::class);

        $missingMagicByte = $this->getBufferFor("\x00\x00\x00\x00\x00\xFF\xFF\xFF\x0F");
        Vp8l::fromBuffer($missingMagicByte);
    }

    public function testUnsupportedVersion(): void
    {
        $this->expectException(UnsupportedVersion::class);

        $missingMagicByte = $this->getBufferFor("\x00\x00\x00\x00\x2F\xFF\xFF\xFF\xFF");
        Vp8l::fromBuffer($missingMagicByte);
    }

    public function testReportCorrectDimensions(): void
    {
        $width = 38;
        $height = 10_000;

        $buffer = $this->getBufferFor(
            "\x00\x00\x00\x00\x2F" . $this->encodeDimensions($width, $height)
        );
        $vp8l = Vp8l::fromBuffer($buffer);

        self::assertEquals($width, $vp8l->width);
        self::assertEquals($height, $vp8l->height);
    }

    private function encodeDimensions(int $width, int $height): string
    {
        $uint32 = 0;

        // The first 14 bits are the width - 1.
        $uint32 |= (($width - 1) & 0x3FFF);
        // The next 14 bits are the height - 1.
        $uint32 |= (($height - 1) & 0x3FFF) << 14;

        return \pack('V', $uint32);
    }

    private function getBufferFor(string $bytes): Buffer
    {
        $buffer = new StringBuffer($bytes);
        $buffer->setReadOnly(true);
        $buffer->setOrder(Buffer::LITTLE_ENDIAN);

        return $buffer;
    }
}
