<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Woltlab\WebpExif\Chunk\Exif;
use Woltlab\WebpExif\ChunkType;

final class ExifTest extends TestCase
{
    public function testReportsCorrectFourCC(): void
    {
        $chunk = Exif::forBytes("");
        $this->assertSame(
            ChunkType::EXIF,
            ChunkType::fromFourCC($chunk->getFourCC()),
        );
    }
}
