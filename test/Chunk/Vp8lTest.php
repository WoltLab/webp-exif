<?php

declare(strict_types=1);

use Nelexa\Buffer\Buffer;
use Nelexa\Buffer\StringBuffer;
use PHPUnit\Framework\TestCase;
use Woltlab\WebpExif\Chunk\Vp8;
use Woltlab\WebpExif\Chunk\Vp8l;
use Woltlab\WebpExif\ChunkType;

final class Vp8lTest extends TestCase
{
    /**
     * Provides a buffer of a VP8L chunk that is only valid as far as the basic
     * validation goes.
     */
    private function getPseudoValidVp8l(): Buffer
    {
        $buffer = new StringBuffer("\x03\x00\x00\x00\x2F\xFF\xFF\xFF\x0F");
        $buffer->setReadOnly(true);
        $buffer->setOrder(Buffer::LITTLE_ENDIAN);

        return $buffer;
    }

    public function testReportsCorrectFourCC(): void
    {
        $chunk = Vp8l::fromBuffer($this->getPseudoValidVp8l());
        $this->assertSame(
            ChunkType::VP8L,
            ChunkType::fromFourCC($chunk->getFourCC()),
        );
    }
}
