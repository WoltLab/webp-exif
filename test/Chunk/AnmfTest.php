<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Woltlab\WebpExif\Chunk\Anmf;
use Woltlab\WebpExif\ChunkType;

final class AnmfTest extends TestCase
{
    public function testReportsCorrectFourCC(): void
    {
        $chunk = Anmf::forBytes("");
        $this->assertSame(
            ChunkType::ANMF,
            ChunkType::fromFourCC($chunk->getFourCC()),
        );
    }
}
