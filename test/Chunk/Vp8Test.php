<?php

declare(strict_types=1);

use Nelexa\Buffer\Buffer;
use Nelexa\Buffer\StringBuffer;
use PHPUnit\Framework\TestCase;
use Woltlab\WebpExif\Chunk\Vp8;
use Woltlab\WebpExif\ChunkType;

final class Vp8Test extends TestCase
{
    /**
     * Provides a buffer of a VP8 chunk that is only valid as far as the basic
     * validation goes.
     */
    private function getPseudoValidVp8(): Buffer
    {
        $buffer = new StringBuffer("\x0A\x00\x00\x00\x00\x00\x00\x9D\x01\x2A\xFF\xFF\xFF\xFF");
        $buffer->setReadOnly(true);
        $buffer->setOrder(Buffer::LITTLE_ENDIAN);

        return $buffer;
    }

    public function testReportsCorrectFourCC(): void
    {
        $chunk = Vp8::fromBuffer($this->getPseudoValidVp8());
        $this->assertSame(
            ChunkType::VP8,
            ChunkType::fromFourCC($chunk->getFourCC()),
        );
    }
}
