<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Woltlab\WebpExif\Chunk\Anim;
use Woltlab\WebpExif\ChunkType;

final class AnimTest extends TestCase
{
    public function testReportsCorrectFourCC(): void
    {
        $chunk = Anim::forBytes("");
        $this->assertSame(
            ChunkType::ANIM,
            ChunkType::fromFourCC($chunk->getFourCC()),
        );
    }
}
