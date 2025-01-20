<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Woltlab\WebpExif\Chunk\Iccp;
use Woltlab\WebpExif\ChunkType;

final class IccpTest extends TestCase
{
    public function testReportsCorrectFourCC(): void
    {
        $chunk = Iccp::forBytes("");
        $this->assertSame(
            ChunkType::ICCP,
            ChunkType::fromFourCC($chunk->getFourCC()),
        );
    }
}
