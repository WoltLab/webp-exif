<?php

declare(strict_types=1);

use Nelexa\Buffer\Buffer;
use Nelexa\Buffer\StringBuffer;
use PHPUnit\Framework\TestCase;
use Woltlab\WebpExif\Chunk\Exception\ExpectedKeyFrame;
use Woltlab\WebpExif\Chunk\Exception\MissingMagicByte;
use Woltlab\WebpExif\Chunk\Vp8;
use Woltlab\WebpExif\ChunkType;

final class Vp8Test extends TestCase
{
    public function testReportsCorrectFourCC(): void
    {
        $pseudoValidVp8 = $this->getBufferFor("\x0A\x00\x00\x00\x00\x00\x00\x9D\x01\x2A\xFF\xFF\xFF\xFF");
        $chunk = Vp8::fromBuffer($pseudoValidVp8);
        $this->assertSame(
            ChunkType::VP8,
            ChunkType::fromFourCC($chunk->getFourCC()),
        );
    }

    public function testUnexpectedFrame(): void
    {
        $this->expectException(ExpectedKeyFrame::class);

        $interframeFirst = $this->getBufferFor("\x0A\x00\x00\x00\x01\x00\x00\x9D\x01\x2A\xFF\xFF\xFF\xFF");
        Vp8::fromBuffer($interframeFirst);
    }

    public function testMissingMagicByte(): void
    {
        $this->expectException(MissingMagicByte::class);

        $missingMagicByte = $this->getBufferFor("\x0A\x00\x00\x00\x00\x00\x00\x9D\xFF\x2A\xFF\xFF\xFF\xFF");
        Vp8::fromBuffer($missingMagicByte);
    }

    private function getBufferFor(string $bytes): Buffer
    {
        $buffer = new StringBuffer($bytes);
        $buffer->setReadOnly(true);
        $buffer->setOrder(Buffer::LITTLE_ENDIAN);

        return $buffer;
    }
}
