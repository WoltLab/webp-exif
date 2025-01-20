<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Woltlab\WebpExif\Chunk\Xmp;
use Woltlab\WebpExif\ChunkType;

final class XmpTest extends TestCase
{
    public function testReportsCorrectFourCC(): void
    {
        $chunk = Xmp::forBytes("");
        $this->assertSame(
            ChunkType::XMP,
            ChunkType::fromFourCC($chunk->getFourCC()),
        );
    }
}
