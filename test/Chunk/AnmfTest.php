<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Woltlab\WebpExif\Chunk\Anmf;
use Woltlab\WebpExif\ChunkType;

final class AnmfTest extends TestCase
{
    public function testReportsCorrectFourCC(): void
    {
        $chunk = Anmf::forBytes(0, "");
        $this->assertSame(
            ChunkType::ANMF,
            ChunkType::fromFourCC($chunk->getFourCC()),
        );
    }

    public function testReportsCorrectOffset(): void
    {
        // This is a bogus offset that cannot naturally occur because all chunks
        // in a RIFF contain must be of even length. We do not validate the
        // offset so this ensures we're not dealing with hardcoded values.
        $offset = 7;

        $chunk = Anmf::forBytes($offset, "");
        $this->assertSame(
            $offset,
            $chunk->getOffset(),
        );
    }
}
