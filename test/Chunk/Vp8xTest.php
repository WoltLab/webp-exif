<?php

declare(strict_types=1);

use Nelexa\Buffer\Buffer;
use Nelexa\Buffer\StringBuffer;
use PHPUnit\Framework\TestCase;
use Woltlab\WebpExif\Chunk\Exception\DimensionsExceedInt32;
use Woltlab\WebpExif\Chunk\Vp8x;
use Woltlab\WebpExif\ChunkType;
use Woltlab\WebpExif\Exception\Vp8xHeaderLengthMismatch;

final class Vp8xTest extends TestCase
{
    public function testReportsCorrectFourCC(): void
    {
        $pseudoValidVp8x = $this->getBufferFor("\x0A\x00\x00\x00" . str_repeat("\x00", 10));
        $chunk = Vp8x::fromBuffer($pseudoValidVp8x);
        $this->assertSame(
            ChunkType::VP8X,
            ChunkType::fromFourCC($chunk->getFourCC()),
        );
    }

    public function testLengthMismatch(): void
    {
        $this->expectExceptionObject(new Vp8xHeaderLengthMismatch(10, 12));

        $pseudoValidVp8x = $this->getBufferFor("\x0C\x00\x00\x00" . str_repeat("\x00", 12));
        Vp8x::fromBuffer($pseudoValidVp8x);
    }

    public function testExcessiveWidth(): void
    {
        $width = 16_777_215; // max uint24
        $height = 1200;

        $this->expectExceptionObject(new DimensionsExceedInt32($width, $height));

        Vp8x::fromBuffer($this->generateVp8x(width: $width, height: $height));
    }

    public function testExcessiveHeight(): void
    {
        $width = 1200;
        $height = 16_777_215; // max uint24

        $this->expectExceptionObject(new DimensionsExceedInt32($width, $height));

        Vp8x::fromBuffer($this->generateVp8x(width: $width, height: $height));
    }

    public function testDecodeDimensionsMatchRawValues(): void
    {
        $width = 33;
        $height = 66;
        $vp8x = Vp8x::fromBuffer($this->generateVp8x(width: $width, height: $height));

        $this->assertEquals($vp8x->width, $width);
        $this->assertEquals($vp8x->height, $height);
    }

    private function generateVp8x(
        int $headerLength = 10,
        int $width = 1_234,
        int $height = 2_345,
    ): Buffer {
        $buffer = new StringBuffer();
        $buffer->setOrder(Buffer::LITTLE_ENDIAN);

        $buffer->insertInt($headerLength);

        // We don't care for the flags.
        $buffer->insertInt(0);

        // Encode the width and height as a 3 byte value each.
        $width = ($width - 1) & 0x00FFFFFF;
        $buffer->insertByte(($width >>  0) & 0xFF);
        $buffer->insertByte(($width >>  8) & 0xFF);
        $buffer->insertByte(($width >> 16) & 0xFF);

        $height = ($height - 1) & 0x00FFFFFF;
        $buffer->insertByte(($height >>  0) & 0xFF);
        $buffer->insertByte(($height >>  8) & 0xFF);
        $buffer->insertByte(($height >> 16) & 0xFF);

        return $this->getBufferFor(
            $buffer->toString()
        );
    }

    private function getBufferFor(string $bytes): Buffer
    {
        $buffer = new StringBuffer($bytes);
        $buffer->setReadOnly(true);
        $buffer->setOrder(Buffer::LITTLE_ENDIAN);

        return $buffer;
    }
}
