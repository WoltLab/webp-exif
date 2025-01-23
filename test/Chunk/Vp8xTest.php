<?php

declare(strict_types=1);

use Nelexa\Buffer\Buffer;
use Nelexa\Buffer\StringBuffer;
use PHPUnit\Framework\TestCase;
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
