<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Woltlab\WebpExif\Chunk\Alph;
use Woltlab\WebpExif\ChunkType;

final class AlphTest extends TestCase
{
    public function testReportsCorrectFourCC(): void
    {
        $chunk = Alph::forBytes("");
        $this->assertSame(
            ChunkType::ALPH,
            ChunkType::fromFourCC($chunk->getFourCC()),
        );
    }
}
