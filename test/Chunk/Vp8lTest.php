<?php

declare(strict_types=1);

use Nelexa\Buffer\Buffer;
use Nelexa\Buffer\StringBuffer;
use PHPUnit\Framework\TestCase;
use Woltlab\WebpExif\Chunk\Exception\MissingMagicByte;
use Woltlab\WebpExif\Chunk\Exception\UnsupportedVersion;
use Woltlab\WebpExif\Chunk\Vp8l;
use Woltlab\WebpExif\ChunkType;

final class Vp8lTest extends TestCase
{
    public function testReportsCorrectFourCC(): void
    {
        $pseudoValidVp8l = $this->getBufferFor("\x03\x00\x00\x00\x2F\xFF\xFF\xFF\x0F");
        $chunk = Vp8l::fromBuffer($pseudoValidVp8l);
        $this->assertSame(
            ChunkType::VP8L,
            ChunkType::fromFourCC($chunk->getFourCC()),
        );
    }

    public function testMissingMagicByte(): void
    {
        $this->expectException(MissingMagicByte::class);

        $missingMagicByte = $this->getBufferFor("\x03\x00\x00\x00\x00\xFF\xFF\xFF\x0F");
        Vp8l::fromBuffer($missingMagicByte);
    }

    public function testUnsupportedVersion(): void
    {
        $this->expectException(UnsupportedVersion::class);

        $missingMagicByte = $this->getBufferFor("\x03\x00\x00\x00\x2F\xFF\xFF\xFF\xFF");
        Vp8l::fromBuffer($missingMagicByte);
    }

    private function getBufferFor(string $bytes): Buffer
    {
        $buffer = new StringBuffer($bytes);
        $buffer->setReadOnly(true);
        $buffer->setOrder(Buffer::LITTLE_ENDIAN);

        return $buffer;
    }
}
