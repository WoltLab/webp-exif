<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use WoltLab\WebpExif\Chunk\Anim;
use WoltLab\WebpExif\ChunkType;

final class AnimTest extends TestCase
{
    public function testReportsCorrectFourCC(): void
    {
        $chunk = Anim::forBytes(0, "");
        self::assertSame(
            ChunkType::ANIM,
            ChunkType::fromFourCC($chunk->getFourCC()),
        );
    }

    public function testReportsCorrectOffset(): void
    {
        // This is a bogus offset that cannot naturally occur because all chunks
        // in a RIFF contain must be of even length. We do not validate the
        // offset so this ensures we're not dealing with hardcoded values.
        $offset = 7;

        $chunk = Anim::forBytes($offset, "");
        self::assertSame(
            $offset,
            $chunk->getOffset(),
        );
    }
}
